<?php
// This file is part of mod_offlinequiz for Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Creates the PDF forms for offlinequizzes
 *
 * @package       mod
 * @subpackage    offlinequiz
 * @author        Juergen Zimmer <zimmerj7@univie.ac.at>
 * @copyright     2012 University of Vienna
 * @since         Moodle 2.2+
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


class offlinequiz_html_translator
{
    private $tempfiles = array();

    public function __construct() {
        $this->tempfiles = array();
    }

    /**
     * Function to replace @@PLUGINFILE@@ references to image files by local file:// URLs.
     *
     * @param string $input The input string (Moodle HTML)
     * @param int $contextid The context ID.
     * @param string $filearea The filearea used to locate the image files.
     * @param int $itemid The itemid used to locate the image files.
     * @param float $kfactor A magnification factor.
     * @param int $maxwidth The maximum width in pixels for images.
     * @return string The result string
     */
    public function fix_image_paths($input, $contextid, $filearea, $itemid, $kfactor,
            $maxwidth, $disableimgnewlines, $format = 'pdf') {
        global $CFG, $DB;

        require_once($CFG->dirroot.'/filter/tex/lib.php');
        require_once($CFG->dirroot.'/filter/tex/latex.php');
        $file = null;
        $fs = get_file_storage();

        $output = $input;
        $strings = preg_split("/<img/i", $output);
        $output = array_shift($strings);
        foreach ($strings as $string) {
            // Define a unique temporary name for each image file.
            $unique = str_replace('.', '', microtime(true) . '_' . rand(0, 100000));

            $imagetag = substr($string, 0, strpos($string, '>'));
            $attributestrings = explode(' ', $imagetag);
            $attributes = array();
            foreach ($attributestrings as $attributestring) {
                $valuepair = explode('=', $attributestring);
                if (count($valuepair) > 1 && strlen(trim($valuepair[0])) > 0) {
                    $attributes[strtolower(trim($valuepair[0]))] = str_replace('"', '', str_replace("'", '', $valuepair[1]));
                }
            }

            if (array_key_exists('width', $attributes) && $attributes['width'] > 0) {
                $imagewidth = $attributes['width'];
            } else {
                $imagewidth = 0;
            }
            if (array_key_exists('height', $attributes) && $attributes['height'] > 0) {
                $imageheight = $attributes['height'];
            } else {
                $imageheight = 0;
            }

            $imagefilename = '';
            if (array_key_exists('src', $attributes) && strlen($attributes['src']) > 10) {
                $pluginfilename = $attributes['src'];
                $imageurl = false;
                $teximage = false;
                $pluginfile = false;
                $parts = preg_split("!$CFG->wwwroot/filter/tex/pix.php/!", $pluginfilename);

                if (preg_match('!@@PLUGINFILE@@/!', $pluginfilename)) {

                    $pluginfilename = str_replace('@@PLUGINFILE@@/', '', $pluginfilename);
                    $pathparts = pathinfo($pluginfilename);
                    if (!empty($pathparts['dirname']) && $pathparts['dirname'] != '.') {
                        $filepath = '/' . $pathparts['dirname'] . '/';
                    } else {
                        $filepath = '/';
                    }
                    if ($imagefile = $fs->get_file($contextid, 'question', $filearea, $itemid, $filepath,
                            rawurldecode($pathparts['basename']))) {
                        $imagefilename = $imagefile->get_filename();
                        // Copy image content to temporary file.
                        $pathparts = pathinfo($imagefilename);
                        $file = $CFG->dataroot . "/temp/offlinequiz/" . $unique . '.' . strtolower($pathparts["extension"]);
                        clearstatcache();
                        if (!check_dir_exists($CFG->dataroot."/temp/offlinequiz", true, true)) {
                            print_error("Could not create data directory");
                        }
                        $imagefile->copy_content_to($file);
                        $pluginfile = true;
                    } else {
                        $output .= 'Image file not found ' . $pathparts['dirname'] . '/' . $pathparts['basename'];
                    }
                } else if (count($parts) > 1) {
                    $teximagefile = $CFG->dataroot . '/filter/tex/' . $parts[1];
                    if (!file_exists($teximagefile)) {
                        // Create the TeX image if it does not exist yet.
                        $convertformat = $DB->get_field('config_plugins', 'value', array('plugin' => 'filter_tex',
                                'name' => 'convertformat'));
                        $md5 = str_replace(".{$convertformat}", '', $parts[1]);
                        if ($texcache = $DB->get_record('cache_filters', array('filter' => 'tex', 'md5key' => $md5))) {
                            if (!file_exists($CFG->dataroot . '/filter/tex')) {
                                make_upload_directory('filter/tex');
                            }

                            // Try and render with latex first.
                            $latex = new latex();
                            $density = $DB->get_field('config_plugins', 'value', array('plugin' => 'filter_tex',
                                'name' => 'density'));
                            $background = $DB->get_field('config_plugins', 'value', array('plugin' => 'filter_tex',
                                'name' => 'latexbackground'));
                            $texexp = $texcache->rawtext; // The entities are now decoded before inserting to DB.
                            $latexpath = $latex->render($texexp, $md5, 12, $density, $background);
                            if ($latexpath) {
                                copy($latexpath, $teximagefile);
                                $latex->clean_up($md5);
                            } else {
                                // Failing that, use mimetex.
                                $texexp = $texcache->rawtext;
                                $texexp = str_replace('&lt;', '<', $texexp);
                                $texexp = str_replace('&gt;', '>', $texexp);
                                $texexp = preg_replace('!\r\n?!', ' ', $texexp);
                                $texexp = '\Large '.$texexp;
                                $cmd = filter_tex_get_cmd($teximagefile, $texexp);
                                system($cmd, $status);
                            }
                        }
                    }
                    $pathparts = pathinfo($teximagefile);

                    $file = $CFG->dataroot . "/temp/offlinequiz/" . $unique . '.' . strtolower($pathparts["extension"]);
                    clearstatcache();
                    if (!check_dir_exists($CFG->dataroot."/temp/offlinequiz", true, true)) {
                        print_error("Could not create data directory");
                    }
                    copy($teximagefile, $file);
                    $teximage = true;
                } else {
                    // Image file URL.
                    $imageurl = true;
                }

                $factor = 2; // Per default show images half sized.

                if (!$imageurl) {
                    if (!file_exists($file)) {
                        $output .= get_string('imagenotfound', 'offlinequiz', $file);
                    } else {
                        // Use imagemagick to remove alpha channel and reduce resolution of large images.
                        $imageinfo = getimagesize($file);
                        $filewidth  = $imageinfo[0];
                        $fileheight = $imageinfo[1];
                        $pathconvert = $DB->get_field('config_plugins', 'value', array('plugin' => 'filter_tex',
                                          'name' => 'pathconvert'));

                        if (file_exists($pathconvert)) {
                            $newfile = $CFG->dataroot . "/temp/offlinequiz/" . $unique . '_c.png';
                            $resize = '';
                            $percent = round(200000000 / ($filewidth * $fileheight));
                            if ($percent < 100) {
                                $resize = ' -resize '.$percent.'%';
                            }
                            $handle = popen($pathconvert . ' ' . $file . $resize . ' -background white -flatten +matte ' .
                                    $newfile, 'r');
                            pclose($handle);
                            $this->tempfiles[] = $file;
                            $file = $newfile;
                            if ($percent < 100) {
                                $imageinfo = getimagesize($file);
                                $filewidth  = $imageinfo[0];
                                $fileheight = $imageinfo[1];
                            }
                        }

                        if ($imagewidth > 0) {
                            if ($imageheight > 0) {
                                $fileheight = $imageheight;
                            } else {
                                $fileheight = $imagewidth / $filewidth * $fileheight;
                            }
                            $filewidth = $imagewidth;
                        }

                        if ($teximage) {
                            if ($format == 'pdf') {
                                $factor = $factor * 1.2;
                            } else {
                                $factor = $factor * 1.5;
                            }
                        }

                        $width = round($filewidth / ($kfactor * $factor));

                        if ($width > $maxwidth) {
                            $width = $maxwidth;
                        }

                        $height = round($fileheight * $width / $filewidth);

                        // Add filename to list of temporary files.
                        $this->tempfiles[] = $file;

                        // In answer texts we want a line break to avoid the picture going above the line.
                        if ($filearea == 'answer' and $disableimgnewlines == 0) {
                            $output .= '<br/>';
                        }

                        // Finally, add the image tag for tcpdf.
                        $output .= '<img src="file://' . $file . '" align="middle" width="' . $width . '" height="' .
                            $height .'"/>';
                    }
                } else {

                    if (($imagewidth > 0) && ($imageheight > 0)) {
                        $width = $imagewidth / ($kfactor * $factor);
                        if ($width > $maxwidth) {
                            $width = $maxwidth;
                        }
                        $height = $imageheight * $width / $imagewidth;
                        $output .= '<img src="' . $pluginfilename . '" align="middle" width="' . $width . '" height="' .
                            $height .'"/>';
                    } else {
                        $output .= '<img src="' . $pluginfilename . '" align="middle"/>';
                    }
                }
            }
            $output .= substr($string, strpos($string, '>') + 1);
        }
        return $output;
    }

    /**
     * Removes all the temporary image files created for document creation only.
     */
    public function remove_temp_files() {
        foreach ($this->tempfiles as $file) {
            unlink($file);
        }
    }
}
