<?php
// This file is for Moodle - http://moodle.org/
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
 * @author        Juergen Zimmer
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
    public function fix_image_paths($input, $contextid, $filearea, $itemid, $kfactor, $maxwidth, $format = 'pdf') {
        global $CFG, $DB;

        require_once($CFG->dirroot.'/filter/tex/lib.php');
        require_once($CFG->dirroot.'/filter/tex/latex.php');
        $fs = get_file_storage();

        $output = $input;
        $strings = preg_split("/<img/i",$output);
        $output = array_shift($strings);
        foreach ($strings as $string) {
            // Define a unique temporary name for each image file.
            srand(microtime() * 1000000);
            $unique = str_replace('.', '', microtime(true) . '_' . rand(0, 100000));

            $imagetag = substr($string, 0, strpos($string, '>'));
            $attributestrings = explode(' ', $imagetag);
            $attributes = array();
            foreach ($attributestrings as $attributestring) {
                $valuepair = explode('=',$attributestring);
                if (sizeof($valuepair) > 1 && strlen(trim($valuepair[0])) > 0) {
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
                    $path_parts = pathinfo($pluginfilename);
                    if (!empty($path_parts['dirname']) && $path_parts['dirname'] != '.') {
                        $filepath = '/' . $path_parts['dirname'] . '/';
                    } else {
                        $filepath = '/';
                    }
                    if ($imagefile = $fs->get_file($contextid, 'question', $filearea, $itemid, $filepath, rawurldecode($path_parts['basename']))) {
                        $imagefilename = $imagefile->get_filename();
                        // copy image content to temporary file
                        $path_parts = pathinfo($imagefilename);
                        $file = $CFG->dataroot . "/temp/offlinequiz/" . $unique . '.' . strtolower($path_parts["extension"]);
                        clearstatcache();
                        if (!check_dir_exists($CFG->dataroot."/temp/offlinequiz", true, true)) {
                            print_error("Could not create data directory");
                        }
                        $imagefile->copy_content_to($file);
                        $pluginfile = true;
                    } else {
                        $output .= 'Image file not found ' . $path_parts['dirname'] . '/' . $path_parts['basename'];
                    }
                } else if (count($parts) > 1) {
                    $teximagefile = $CFG->dataroot . '/filter/tex/' . $parts[1];
                    if (!file_exists($teximagefile)) {
                        // Create the TeX image if it does not exist yet.
                        $md5 = str_replace(".{$CFG->filter_tex_convertformat}", '', $parts[1]);
                        if ($texcache = $DB->get_record('cache_filters', array('filter' => 'tex', 'md5key' => $md5))) {
                            if (!file_exists($CFG->dataroot . '/filter/tex')) {
                                make_upload_directory('filter/tex');
                            }

                            // Try and render with latex first.
                            $latex = new latex();
                            $density = $CFG->filter_tex_density;
                            $background = $CFG->filter_tex_latexbackground;
                            $texexp = $texcache->rawtext; // the entities are now decoded before inserting to DB
                            $latex_path = $latex->render($texexp, $md5, 12, $density, $background);
                            if ($latex_path) {
                                copy($latex_path, $teximagefile);
                                $latex->clean_up($md5);
                            } else {
                                // Failing that, use mimetex
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
                    $path_parts = pathinfo($teximagefile);

                    $file = $CFG->dataroot . "/temp/offlinequiz/" . $unique . '.' . strtolower($path_parts["extension"]);
                    clearstatcache();
                    if (!check_dir_exists($CFG->dataroot."/temp/offlinequiz", true, true)) {
                        print_error("Could not create data directory");
                    }
                    copy($teximagefile, $file);
                    $teximage = true;
                } else {
                    // Image file URL
                    $imageurl = true;
                }

                $factor = 2; // per default show images half sized

                if (!$imageurl) {
                    if (!file_exists($file)) {
                        $output .= get_string('imagenotfound', 'offlinequiz', $file);
                    } else {
                        // use imagemagick to remove alpha channel and reduce resolution of large images
                        $imageinfo = getimagesize($file);
                        $filewidth  = $imageinfo[0];
                        $fileheight = $imageinfo[1];

                        if (file_exists($CFG->filter_tex_pathconvert)) {
                            $newfile = $CFG->dataroot . "/temp/offlinequiz/" . $unique . '_c.png';
                            $resize = '';
                            $percent = round(200000000 / ($filewidth * $fileheight));
                            if ($percent < 100) {
                                $resize = ' -resize '.$percent.'%';
                            }
                            $handle = popen($CFG->filter_tex_pathconvert . ' ' . $file . $resize . ' -background white -flatten +matte ' . $newfile, 'r');
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
                        	
                        // In answer texts we want a line break to avoid the picture going above the line
                        if ($filearea == 'answer') {
                            $output .= '<br/>';
                        }
                        	
                        // Finally, add the image tag for tcpdf
                        $output.= '<img src="file://' . $file . '" align="middle" width="' . $width . '" height="' . $height .'"/>';
                    }
                } else {

                    if (($imagewidth > 0) && ($imageheight > 0)) {
                        $width = $imagewidth / ($kfactor * $factor);
                        if ($width > $maxwidth) {
                            $width = $maxwidth;
                        }
                        $height = $imageheight * $width / $imagewidth;
                        $output.= '<img src="' . $pluginfilename . '" align="middle" width="' . $width . '" height="' . $height .'"/>';
                    } else {
                        $output.= '<img src="' . $pluginfilename . '" align="middle"/>';
                    }
                }
            }
            $output .= substr($string, strpos($string, '>')+1);
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
