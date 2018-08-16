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
 * classes and functions for interpreting the scanned answer forms (image files)
 *
 * @package       mod
 * @subpackage    offlinequiz
 * @author        Juergen Zimmer <zimmerj7@univie.ac.at>
 * @copyright     2015 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @since         Moodle 2.2
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

defined('MOODLE_INTERNAL') || die();

define("A3_WIDTH", "2100");                    // Paper size.
define("A3_HEIGHT", "2970");
define("LAYER_WIDTH", "1815");                 // Active layer from corner cross to corner cross.
define("LAYER_HEIGHT", "2723");

define("MAX_BORDER", "110");                   // Max. black margin of rotated scan sheet.
define("MIN_BORDER", "20");                    // Width of white margin that will be ignored.
define("CROSS_WIDTH", "34");                   // Width of the little corner cross.
define("CORNER_SPOT_WIDTH_VERTICAL", "300");   // The width of the area where we search for the little corner cross verticaly.
define("CORNER_SPOT_WIDTH_HORIZONTAL", "250"); // The width of the area where we search for the little corner cross horizontaly.

define("BOX_OUTER_WIDTH", "54");               // Outer width of the little boxes.
define("BOX_INNER_WIDTH", "28");               // Inner width of the little boxes.

/**
 * A class for points in cartesian system that can rotate and zoom.
 *
 */
class oq_point {
    public $x;
    public $y;
    public $blank;

    /**
     * Constructor
     *
     * @param unknown_type $x
     * @param unknown_type $y
     * @param unknown_type $blank
     */
    public function __construct($x=0, $y=0, $blank = true) {
        $this->x = round($x);
        $this->y = round($y);
        $this->blank = $blank;
    }


    /**
     * Rotates the point with center 0/0
     *
     * @param unknown_type $alpha
     */
    public function rotate($alpha) {
        if ($alpha == 0) {
            return;
        }
        $sin = sin(deg2rad($alpha));
        $cos = cos(deg2rad($alpha));
        $x = $this->x * $cos - $this->y * $sin;
        $this->y = round($this->y * $cos + $this->x * $sin);
        $this->x = round($x);
    }

    /**
     * Zooms the point's distance from center 0/0
     *
     * @param unknown_type $x
     * @param unknown_type $y
     */
    public function zoom($x, $y) {
        $this->x = round($this->x * $x);
        $this->y = round($this->y * $y);
    }

}

/**
 * Class that contains all the routines and data to interprate scanned answer forms
 *
 * @author        Juergen Zimmer <zimmerj7@univie.ac.at>
 *
 */
class offlinequiz_page_scanner {

    public $calibrated;
    public $contextid;
    public $offlinequizid;
    public $filename;
    public $sourcefile;
    public $pageimage;
    public $path;
    public $id_digits;
    public $maxanswers;
    public $maxquestions;
    public $questionsonpage;
    public $formtype;
    public $numpages;
    public $offset;
    public $image;
    public $zoomx;
    public $zoomy;
    public $alpha;
    public $hotspots;      // We store all the points in this array. This makes it easy to rotate them all together.
    public $pattern;       // contains the hotspot pattern for a cross.
    public $pattern1;      // Contains the hotspot pattern for a cross moved to one of the corners.
    public $pattern2;      // Contains the hotspot pattern for a cross moved to one of the corners.
    public $pattern3;      // Contains the hotspot pattern for a cross moved to one of the corners.
    public $pattern4;      // Contains the hotspot pattern for a cross moved to one of the corners.
    public $papergray;
    public $corners;       // The corners as passed by evallib.php or correct.php.
    public $lowertrigger;
    public $uppertrigger;
    public $lowerwarning;
    public $upperwarning;
    public $upperleft;
    public $lowerleft;
    public $upperright;
    public $lowerright;
    public $insecure;      // This flag is set if one of the boxes is between value and warning level.
    public $blankbox;      // This flag is set if one of the boxes could not be grabbed.
    public $ontop;
    public $page;
    public $cache;

    /**
     * Constructor
     *
     * @param unknown_type $offlinequiz
     * @param unknown_type $contextid
     * @param unknown_type $maxquestions
     * @param unknown_type $maxanswers
     */
    public function __construct($offlinequiz, $contextid, $maxquestions, $maxanswers) {
        if ($maxanswers > 26) {
            $maxanswers = 26; // There won't be more than 26 answers or 96 questions on the sheet.
        }
        $this->maxanswers = $maxanswers;
        $this->maxquestions = $maxquestions;
        $this->formtype = 4;
        $this->ontop = false;
        $this->calibrated = false;
        $this->papergray = $offlinequiz->papergray;
        $this->contextid = $contextid;
        $this->offlinequizid = $offlinequiz->id;

        if ($maxanswers > 5) {
            $this->formtype = 3;
        }
        if ($maxanswers > 7) {
            $this->formtype = 2;
        }
        if ($maxanswers > 12) {
            $this->formtype = 1;
        }
        $this->numpages = ceil($maxquestions / ($this->formtype * 24));

        $this->id_digits = $offlinequiz->id_digits;
    }

    /**
     * Initialises all the hotspots to be checked.
     *
     */
    public function init_hotspots() {
        global $CFG;

        $this->hotspots = array();
        $offlinequizconfig = get_config('offlinequiz');

        // Load hotspots for usernumber.
        for ($x = 0; $x <= ($offlinequizconfig->ID_digits - 1); $x++) {
            for ($y = 0; $y <= 9; $y++) {
                $point = new oq_point(($x * 65) + 1247, ($y * 60) + 306);
                $this->hotspots["u$x$y"] = $point;
            }
        }

        // Load hotspots for group.
        for ($i = 0; $i <= 5; $i++) {
            $point = new oq_point(($i * 95) + 274, 440);
            $this->hotspots["g$i"] = $point;
        }

        $point = new oq_point(436, 804);          // The black box in the middle.
        $this->hotspots['deleted'] = $point;       // To check if we grabbed it right and to get upper trigger and papergray..

        $point = new oq_point(436, 640);          // The box with the cross in the middle.
        $this->hotspots['cross'] = $point;         // To get lower trigger.

        switch ($this->formtype) {                // Load hotspots for answers.
            case 1:
                $colwidth = 26 * 65;
                break;
            case 2:
                $colwidth = 14 * 65;
                break;
            case 3:
                $colwidth = 9 * 65;
                break;
            case 4:
                $colwidth = 7 * 65;
                break;
            default:
                error('Missing type for form');
        }

        $col = 0;
        $y = 926;

        for ($number = 0; $number < $this->formtype * 24; $number++) {

            if ($number % 8 == 0) {
                $y += 44;
            }

            for ($i = 0; $i < $this->maxanswers; $i++) {
                $point = new oq_point(($i * 65) + ($colwidth * $col) + 84, $y);
                $this->hotspots["a-$number-$i"] = $point;
            }
            $y += 65;

            if (($number + 1) % 24 == 0) {
                $y = 926;
                $col++;
            }
        }

        $point = new oq_point(1572, 2639);
        $this->hotspots["page"] = $point;

    }

    /**
     * Creates a check pattern
     *
     */
    public function init_pattern() {
        if ($this->zoomx > $this->zoomy) {
            $a = BOX_INNER_WIDTH * $this->zoomx + 2;
        } else {
            $a = BOX_INNER_WIDTH * $this->zoomy + 2;
        }
        $this->pattern = array();
        $width = $a / 6;                                // Calculate dimensions of the pattern.
        $halfwidth = round($a / 12);                    // Halfwidth for pattern1-4.
        $length = round($width * 2);
        $width = round($width);                                 // XXXX      width=4.
        for ($i = 0; $i <= $a; $i++) {                          // XXXXX.
            $start = $i - $width;                               // XXXXXX    a=dimension of square.
            for ($j = 0; $j <= $length; $j++) {                 // XXXXXXX.
                if ($start + $j >= 0 and $start + $j <= $a) {   // XXXXXXXX  length=8.
                    // This creates the line from upper left to lower right corner.
                    $this->pattern[($start + $j)][$i] = 1;
                    // This creates the line from upper right to lower left corner.
                    $this->pattern[($start + $j)][($a - $i)] = 1;
                }
            }
        }
        $this->pattern1 = array();
        for ($i = 0; $i <= $a; $i++) {
            $start = $i - $width;
            for ($j = 0; $j <= $length; $j++) {
                if ($i + $j + $halfwidth <= $a and $i + $j + $halfwidth >= 0) {
                    $this->pattern1[($i + $j + $halfwidth)][$i] = 1;
                }
                if ($start + $j <= $a and $start + $j >= 0) {
                    $this->pattern1[($start + $j)][($a - $i)] = 1;
                }
            }
        }
        $this->pattern2 = array();
        for ($i = 0; $i <= $a; $i++) {
            $start = $i - $width;
            for ($j = 0; $j <= $length; $j++) {
                if ($j + $start - $width <= $a and $j + $start - $width >= 0) {
                    $this->pattern2[($j + $start - $width)][$i] = 1;
                }
                if ($start + $j <= $a and $start + $j >= 0) {
                    $this->pattern2[($start + $j)][($a - $i)] = 1;
                }
            }
        }
        $this->pattern3 = array();
        for ($i = 0; $i <= $a; $i++) {
            $start = $i - $width;
            for ($j = 0; $j <= $length; $j++) {
                if ($start + $j <= $a and $start + $j >= 0) {
                    $this->pattern3[($start + $j)][$i] = 1;
                }
                if ($i + $j + $halfwidth <= $a and $i + $j + $halfwidth >= 0) {
                    $this->pattern3[($i + $j + $halfwidth)][($a - $i)] = 1;
                }
            }
        }
        $this->pattern4 = array();
        for ($i = 0; $i <= $a; $i++) {
            $start = $i - $width;
            for ($j = 0; $j <= $length; $j++) {
                if ($start + $j <= $a and $start + $j >= 0) {
                    $this->pattern4[($start + $j)][$i] = 1;
                }
                if ($j + $start - $width <= $a and $j + $start - $width >= 0) {
                    $this->pattern4[($j + $start - $width)][($a - $i)] = 1;
                }
            }
        }
    }


    /**
     * Loads a temporary image file and creates and record for the table offlinequiz_scanned_pages if possible.
     *
     * @param string $file The full path to the image file.
     * @return mixed the scanned page object.
     */
    public function load_image($file) {
        global $CFG, $OUTPUT;

        $this->offset = new oq_point();

        $this->insecure = false;
        $this->init_hotspots();
        $this->sourcefile = $file;

        $scannedpage = new stdClass();
        $scannedpage->offlinequizid = $this->offlinequizid;
        $scannedpage->time = time();

        $pathparts = pathinfo($file);
        $this->filename = $pathparts['filename'] . '.' . $pathparts['extension'];
        $scannedpage->origfilename = $this->filename;

        if (!file_exists($file)) {
            $scannedpage->status = 'error';
            $scannedpage->error = 'filenotfound';
            $scannedpage->filename = $this->filename;
            $scannedpage->info = $this->filename;
            return $scannedpage;
        }

        $imageinfo = getimagesize($file);

        // Reduce resolution of large images.
        $percent = round(300000 / $imageinfo['0']);
        if ($percent > 0 && $percent < 100) {
            $handle = popen("convert '" . $file . "' -resize " . $percent . "% '" . $file . "'", 'r');
            pclose($handle);
            $imageinfo = getimagesize($file);
        }

        $this->zoomx = $imageinfo['0'] / A3_WIDTH;  // First estimation of zoom factor, will be adjusted later.
        $this->zoomy = $imageinfo['1'] / A3_HEIGHT;
        $type = $imageinfo['2'];

        switch ($type) {
            case IMAGETYPE_GIF:
                if (function_exists('imagecreatefromgif')) {
                    $this->image = imagecreatefromgif($file);
                } else {
                    $scannedpage->status = 'error';
                    $scannedpage->error = 'gifnotsupported';
                    $scannedpage->info = $this->filename;
                    return $scannedpage;
                }
                break;
            case IMAGETYPE_JPEG:
                if (function_exists('imagecreatefromjpeg')) {
                    $this->image = imagecreatefromjpeg($file);
                } else {
                    $scannedpage->status = 'error';
                    $scannedpage->error = 'jpgnotsupported';
                    $scannedpage->info = $this->filename;
                    return $scannedpage;
                }
                break;
            case IMAGETYPE_PNG:
                if (function_exists('imagecreatefrompng')) {
                    $this->image = imagecreatefrompng($file);
                } else {
                    $scannedpage->status = 'error';
                    $scannedpage->error = 'pngnotsupported';
                    $scannedpage->info = $this->filename;
                    return $scannedpage;
                }
                break;
            case IMAGETYPE_TIFF_II:
                $newfile = $pathparts["dirname"] . '/' . $pathparts["filename"] . ".png";
                // Converting the tiff file to png format via imagemagick.
                // This is much faster then using php's imagick extension.
                $handle = popen("convert '" . $file . "' '" . $newfile . "' ", 'r');
                fread($handle, 1);
                while (!feof($handle)) {
                    fread($handle, 1);
                }
                pclose($handle);
                if (file_exists($newfile)) {
                    $this->filename = $pathparts["filename"] . ".png";
                    $scannedpage->origfilename = $this->filename;
                    $this->sourcefile = $newfile;
                    if (function_exists('imagecreatefrompng')) {
                        $this->image = imagecreatefrompng($newfile);
                    } else {
                        $scannedpage->status = 'error';
                        $scannedpage->error = 'pngnotsupported';
                        $scannedpage->info = $this->filename;
                        return $scannedpage;
                    }
                } else {
                    $scannedpage->status = 'error';
                    $scannedpage->error = 'tiffnotsupported';
                    $scannedpage->info = $this->filename;
                    return $scannedpage;
                }
                break;
            case IMAGETYPE_TIFF_MM:
                $newfile = $pathparts["dirname"] . '/' . $pathparts["filename"] . ".png";
                // Converting the tiff file to png format via imagemagick.
                $handle = popen("convert '" . $file . "' '" . $newfile . "' ", 'r');
                fread($handle, 100);
                while (!feof($handle)) {
                    fread($handle, 100);
                }
                pclose($handle);
                if (file_exists($newfile)) {
                    $this->filename = $pathparts["filename"] . ".png";
                    $scannedpage->origfilename = $this->filename;
                    $this->sourcefile = $newfile;
                    if (function_exists('imagecreatefrompng')) {
                        $this->image = imagecreatefrompng($newfile);
                    } else {
                        $scannedpage->status = 'error';
                        $scannedpage->error = 'pngnotsupported';
                        $scannedpage->info = $this->filename;
                        return $scannedpage;
                    }
                } else {
                    $scannedpage->status = 'error';
                    $scannedpage->error = 'tiffnotsupported';
                    $scannedpage->info = $this->filename;
                    return $scannedpage;
                }
                break;
            default:
                $scannedpage->status = 'error';
                $scannedpage->error = 'imagenotsupported';
                $scannedpage->info = $this->filename . ' has image type ' . $type;
                return $scannedpage;
        }

        $filerecord = array(
                'contextid' => $this->contextid,      // ID of context.
                'component' => 'mod_offlinequiz', // Usually = table name.
                'filearea'  => 'imagefiles',      // Usually = table name.
                'itemid'    => 0,                 // Usually = ID of row in table.
                'filepath'  => '/',               // Any path beginning and ending in.
                'filename'  => $this->filename); // Any filename.

        $storedfile = $this->save_image($filerecord, $this->sourcefile);
        $scannedpage->filename = $storedfile->get_filename();

        // Check if we can adjust the image s.t. we can determine the hotspots.
        if ($this->adjust(true, false, false, false, false, 0)) {
            $scannedpage->status = 'ok';
            $scannedpage->error = '';
        } else {
            $scannedpage->status = 'error';
            $scannedpage->error = 'couldnotgrab';
            $scannedpage->info = $this->filename;
        }

        return $scannedpage;
    }


    /**
     * Loads an image file previously stored in the Moodle filespace by the load_image method.
     *
     * @param int $filename The name of the file in the imagefiles filearea of the scanner's context
     * @param mixed $corners The corner coordinates as retrieved from the offlinequiz_page_corners table
     */
    public function load_stored_image($filename, $corners, $scannedpageid = null) {
        global $CFG, $OUTPUT;

        $this->offset = new oq_point();
        // Remember the corners passed. They are needed by set_maxanswers.
        $this->corners = $corners;
        $this->insecure = false;
        $this->init_hotspots();

        $fs = get_file_storage();

        if (!$file = $fs->get_file($this->contextid, 'mod_offlinequiz', 'imagefiles', 0, '/', $filename)) {
            print_error('Could not load file');
        }

        $this->filename = $file->get_filename();

        $imagesize = $file->get_imageinfo();

        $this->sourcefile = $file;

        $this->zoomx = $imagesize['width'] / A3_WIDTH;  // First estimation of zoom factor, will be adjusted later.
        $this->zoomy = $imagesize['height'] / A3_HEIGHT;

        $this->image = imagecreatefromstring($file->get_content());
        if (count($corners) > 3) {
            $ok = $this->adjust(false, $corners[0], $corners[1], $corners[2], $corners[3], OQ_IMAGE_WIDTH, $scannedpageid);
        } else {
            $ok = $this->adjust(true, false, false, false, false, 0, $scannedpageid);
        }
        return $ok;
    }

    /**
     * Saves an image in the Moodle file space. Extends the filename in case the file already exists.
     *
     * @param unknown_type $filerecord
     * @param unknown_type $sourcefile
     * @param unknown_type $postfix
     * @return stored_file
     */
    public function save_image($filerecord, $sourcefile, $postfix = '') {
        $fs = get_file_storage();
        $filename = $filerecord['filename'] . $postfix;

        $counter = 1;
        while ($file = $fs->get_file($filerecord['contextid'], $filerecord['component'],
                $filerecord['filearea'], $filerecord['itemid'], $filerecord['filepath'],
                $filename)) {

            $filename = $filerecord['filename'] . $postfix . '_' . $counter;
            $counter++;
        }
        $filerecord['filename'] = $filename;
        $storedfile = $fs->create_file_from_pathname($filerecord, $sourcefile);
        return $storedfile;
    }


    /**
     * Returns absolute positions of hotspots
     *
     * @param unknown_type $width
     * @return multitype:oq_point
     */
    public function export_hotspots_userid($width) {
        global $CFG;
        $offlinequizconfig = get_config('offlinequiz');

        $export = array();
        $factor = $width / imagesx($this->image);

        for ($x = 0; $x < $offlinequizconfig->ID_digits; $x++) {
            for ($y = 0; $y <= 9; $y++) {
                $point = new oq_point(($this->hotspots["u$x$y"]->x + $this->offset->x) * $factor - 2 * $this->zoomx,
                        ($this->hotspots["u$x$y"]->y + $this->offset->y) * $factor - 2 * $this->zoomy);
                $export["u$x$y"] = $point;
            }
        }
        return $export;
    }

    /**
     * Returns absolute positions of hotspots for group numbers.
     *
     * @param unknown_type $width
     * @return multitype:oq_point
     */
    public function export_hotspots_group($width) {
        global $CFG;

        $export = array();
        $factor = $width / imagesx($this->image);

        for ($i = 0; $i <= 5; $i++) {
            $point = new oq_point(($this->hotspots["g$i"]->x + $this->offset->x) * $width / imagesx($this->image)
                      - 2 * $this->zoomx, ($this->hotspots["g$i"]->y + $this->offset->y) * $factor - 2 * $this->zoomy);
            $export["g$i"] = $point;
        }
        return $export;
    }

    /**
     * Returns absolute positions for answer hotspots.
     *
     * @param unknown_type $width
     * @return multitype:oq_point
     */
    public function export_hotspots_answer($width) {
        global $CFG;

        $export = array();
        $factor = $width / imagesx($this->image);

        for ($number = 0; $number < $this->questionsonpage; $number++) {
            for ($i = 0; $i < $this->maxanswers; $i++) {
                $point = new oq_point(
                        ($this->hotspots["a-$number-$i"]->x + $this->offset->x) * $factor - 2 * $this->zoomx,
                        ($this->hotspots["a-$number-$i"]->y + $this->offset->y) * $factor - 2 * $this->zoomy
                );
                $export["a-$number-$i"] = $point;
            }
        }
        return $export;
    }


    /**
     * Goes through all the pixels of a hotspot (box) and counts the black pixels
     * This is very inefficient!
     *
     * @param unknown_type $point
     * @return number
     */
    public function get_hotspot_x($point) {

        $positionx = $point->x + $this->offset->x;
        $positiony = $point->y + $this->offset->y;
        $lastx = BOX_OUTER_WIDTH * $this->zoomx / 2 + $positionx;
        $lasty = BOX_OUTER_WIDTH * $this->zoomy + $positiony;
        // Number of black dots that have to be found.
        $numtofind = round(BOX_OUTER_WIDTH * $this->zoomy * 0.5);

        for ($x = $positionx; $x <= $lastx; $x++) {
            $numblacks = 0;
            for ($y = $positiony; $y <= $lasty; $y++) {
                if ($this->pixel_is_black($x, $y)) {
                    $numblacks++;
                    if ($numblacks >= $numtofind) {
                        $this->blankbox = false;
                        return round($x - $this->offset->x + 4 * $this->zoomx);
                    }
                }
            }
        }
        return $positionx + (BOX_OUTER_WIDTH - BOX_INNER_WIDTH) / 4 * $this->zoomx - $this->offset->x;
    }

    /**
     * Goes through all the pixels of a hotspot (box) and counts the black pixels
     * This is very inefficient!
     *
     * @param unknown_type $point
     * @return number
     */
    public function get_hotspot_y($point) {
        $positionx = $point->x + $this->offset->x;
        $positiony = $point->y + $this->offset->y;
        $lastx = BOX_OUTER_WIDTH * $this->zoomx + $positionx;
        $lasty = BOX_OUTER_WIDTH * $this->zoomy + $positiony;
        // Number of black dots that have to be found.
        $numtofind = round(BOX_OUTER_WIDTH * $this->zoomx * 0.5);

        for ($y = $positiony; $y <= $lasty; $y++) {
            $numblacks = 0;
            for ($x = $positionx; $x <= $lastx; $x++) {
                if ($this->pixel_is_black($x, $y)) {
                    $numblacks++;
                    if ($numblacks >= $numtofind) {
                        $this->blankbox = false;
                        return round($y - $this->offset->y + 4 * $this->zoomy);
                    }
                }
            }
        }
        return $positiony + (BOX_OUTER_WIDTH - BOX_INNER_WIDTH) / 2 * $this->zoomx - $this->offset->y;
    }

    /**
     * Returns the page number recognised. Returns 0 if the page number could not be determined.
     *
     * @return number the page number
     */
    public function get_page() {
        $this->page = 0;

        $this->blankbox = true;
        $x = $this->get_hotspot_x($this->hotspots['page']);
        $this->hotspots['page']->y = $this->get_hotspot_y($this->hotspots['page']);
        $this->hotspots['page']->x = $x;
        $this->hotspots['page']->blank = $this->blankbox;

        $this->page = $this->get_barcode($this->hotspots["page"]);

        if (!$this->page) {
            return 0;
        }

        if ($this->page * $this->formtype * 24 < $this->maxquestions) {
            $this->questionsonpage = $this->formtype * 24;
        } else {
            $this->questionsonpage = $this->maxquestions - ($this->formtype * 24 * ($this->page - 1));
        }

        return $this->page;
    }

    /**
     * Sets the page number of this scanner. This has to be used if the user actively chooses a page number.
     *
     * @param unknown_type $page
     * @return number|Ambigous <number, unknown, boolean, string>
     */
    public function set_page($page) {
        $this->page = $page;

        if (!$this->page) {
            return 0;
        }

        if ($this->page * $this->formtype * 24 < $this->maxquestions) {
            $this->questionsonpage = $this->formtype * 24;
        } else {
            $this->questionsonpage = $this->maxquestions - ($this->formtype * 24 * ($this->page - 1));
        }

        return $this->page;
    }

    /**
     * Returns the number of questions on the current page.
     *
     */
    public function get_questions_on_page() {
        return $this->questionsonpage;
    }

    /**
     * Performs fine-adjustments of all hotspots, i.e. tries to determine the exact position of the upper left corner.
     *
     * This can take a long time (e.g. 10 seconds)
     */
    public function adjust_hotspots() {

        foreach ($this->hotspots as $key => $hotspot) {
            if ($key == 'page') {
                continue;
            }
            $this->blankbox = true;

            $x = $this->get_hotspot_x($hotspot);
            $hotspot->y = $this->get_hotspot_y($hotspot);
            $hotspot->x = $x;
            $hotspot->blank = $this->blankbox;
        }
    }

    /**
     * Function to store the hotspots in the DB for retrieval during correction. This is called by cron.php because
     * we only store the hotspots if the scannedpage has an error.
     *
     * @param unknown_type $scannedpageid
     */
    public function store_hotspots($scannedpageid) {
        global $DB;

        $timenow = time();

        foreach ($this->hotspots as $key => $hotspot) {
            if ($key == 'page') {
                continue;
            }

            $entry = new stdClass();
            $entry->scannedpageid = $scannedpageid;
            $entry->name = $key;
            $entry->x = $hotspot->x;
            $entry->y = $hotspot->y;
            $entry->time = $timenow;

            if ($hotspot->blank) {
                $entry->blank = 1;
            } else {
                $entry->blank = 0;
            }
            $DB->insert_record('offlinequiz_hotspots', $entry);
        }
    }

    /**
     * Function to restore the hotspots from DB records in offlinequiz_hotspots.
     *
     * @param unknown_type $hotspots
     */
    private function restore_hotspots($hotspots) {
        foreach ($hotspots as $hotspot) {
            $this->hotspots[$hotspot->name] = new oq_point($hotspot->x, $hotspot->y, $hotspot->blank);
        }
    }

    /**
     * Moves the hotspots according to rotation and zooming.
     *
     */
    public function move_hotspots() {
        foreach ($this->hotspots as $point) {
            $point->zoom($this->zoomx, $this->zoomy);
            $point->rotate($this->alpha);
        }
    }

    /**
     * Determines the black value of a hotspot.
     * Returns
     *    0 if empty,
     *    1 if marked,
     *    2 if deleted,
     *    3 if insecure.
     * If return is true, it returns the percentage of black pixels found
     *
     * @param unknown_type $point
     * @param unknown_type $return
     * @return number
     */
    public function hotspot_value($point, $return = false, $name = '') {
        global $CFG;

        $numpoints = 0;
        $numblacks = 0;
        $patternin = array();
        $patternout = array();

        for ($i = 0; $i <= 4; $i++) {
            $patternin[$i] = 0;
            $patternout[$i] = 0;
        }
        $positionx = $point->x + $this->offset->x;
        $positiony = $point->y + $this->offset->y;
        $lastx = BOX_INNER_WIDTH * $this->zoomx + $positionx;
        $lasty = BOX_INNER_WIDTH * $this->zoomy + $positiony;

        for ($x = $positionx; $x <= $lastx; $x++) {
            for ($y = $positiony; $y <= $lasty; $y++) {
                $numpoints++;
                if ($this->pixel_is_black($x, $y)) {
                    $numblacks++;
                    if (!empty($this->pattern[($x - $positionx)][($y - $positiony)])) {
                        $patternin[0]++;
                    } else {
                        $patternout[0]++;
                    }
                    if (!empty($this->pattern1[($x - $positionx)][($y - $positiony)])) {
                        $patternin[1]++;
                    } else {
                        $patternout[1]++;
                    }
                    if (!empty($this->pattern2[($x - $positionx)][($y - $positiony)])) {
                        $patternin[2]++;
                    } else {
                        $patternout[2]++;
                    }
                    if (!empty($this->pattern3[($x - $positionx)][($y - $positiony)])) {
                        $patternin[3]++;
                    } else {
                        $patternout[3]++;
                    }
                    if (!empty($this->pattern4[($x - $positionx)][($y - $positiony)])) {
                        $patternin[4]++;
                    } else {
                        $patternout[4]++;
                    }
                }
            }
        }

        $percent = round($numblacks / $numpoints * 100);
        if ($return) {
            return $percent;
        }

        if ($percent >= $this->uppertrigger) {
            return 2;
        } else if ($percent >= $this->upperwarning) {
            if ($patternout[0] == 0 or $patternout[1] == 0
               or $patternout[2] == 0 or $patternout[3] == 0
               or $patternout[4] == 0) {
                return 1;
            }
            $patternfactor = $patternin[0] / $patternout[0];
            $patternfactor2 = 0;
            for ($i = 1; $i <= 4; $i++) {
                if ($patternin[$i] / $patternout[$i] > $patternfactor2) {
                    $patternfactor2 = $patternin[$i] / $patternout[$i];
                }
            }
            if ($patternfactor > 2.6) {
                return 1;
            } else if ($patternfactor2 > 2.8) {
                 return 1;
            } else if ($patternfactor < 1.4 or $patternfactor2 < 1.7) {
                return 2;
            } else {
                return 3;
            }
        } else if ($percent >= $this->lowertrigger) {
              return 1;
        } else if ($percent >= $this->lowerwarning) {
            return 3;
        } else {
            return 0;
        }
    }

    /**
     * Marks the hotspot with red square for student warnings.
     *
     * @param unknown_type $point
     */
    public function mark_hotspot($point) {
        global $CFG;

        $positionx = $point->x + $this->offset->x;
        $positiony = $point->y + $this->offset->y;
        $lastx = BOX_INNER_WIDTH * $this->zoomx + $positionx;
        $lasty = BOX_INNER_WIDTH * $this->zoomy + $positiony;
        $color = imagecolorallocate($this->image, 255, 0, 0);
        imagerectangle($this->image, $positionx - 2, $positiony - 2, $lastx + 2, $lasty + 2, $color);
        imagerectangle($this->image, $positionx - 3, $positiony - 3, $lastx + 3, $lasty + 3, $color);
        imagerectangle($this->image, $positionx - 4, $positiony - 4, $lastx + 4, $lasty + 4, $color);
        imagerectangle($this->image, $positionx - 5, $positiony - 4, $lastx + 5, $lasty + 5, $color);
    }

    /**
     * Creates an image with red markings to inform student about problems interpreting his markings.
     *
     * @param unknown_type $wrongusernumber
     * @param unknown_type $rightusernumber
     * @param unknown_type $wronggroup
     * @param unknown_type $rightgroup
     * @param unknown_type $wrongitems
     */
    public function create_warning_image($wrongusernumber, $rightusernumber, $wronggroup, $rightgroup, $wrongitems) {
        // Mark errors in usernumber.
        for ($i = 0; $i < strlen($wrongusernumber); $i++) {
            if (substr($wrongusernumber, $i, 1) != substr($rightusernumber, $i, 1)) {
                $this->mark_hotspot($this->hotspots["u$i".substr($rightusernumber, $i, 1)]);
            }
        }
        // Mark errors in group, not really necessary, because there should always be premarked correctly.
        if ($wronggroup != $rightgroup) {
            $index = $rightgroup - 1;
            $this->mark_hotspot($this->hotspots["g$index"]);
        }
        foreach ($wrongitems as $item) {
            $this->mark_hotspot($this->hotspots['a-' . $item['question'] . '-' . $item['answer']]);
        }
    }

    /**
     * Checks if the field for deleted (completely black) is adjusted properly.
     *
     * @return boolean
     */
    public function check_deleted() {
        return $this->hotspot_value($this->hotspots['deleted'], true) > 70;
    }

    /**
     *  Rotates image by 180 degrees.
     */
    public function rotate_180() {
        global $CFG;

        $uniquename = time();
        $tempsrc = $CFG->dataroot . "/temp/$uniquename" . "_src.png";
        $tempdst = $CFG->dataroot . "/temp/$uniquename" . "_dst.png";
        if (imagepng($this->image, $tempsrc)) {
            $handle = popen("convert '" . $tempsrc . "' -rotate 180 '" . $tempdst . "' ", 'r');
            pclose($handle);
            if ($this->image = imagecreatefrompng($tempdst)) {
                $this->sourcefile = $tempdst;

                $filerecord = array(
                        'contextid' => $this->contextid,  // ID of context.
                        'component' => 'mod_offlinequiz', // Usually = table name.
                        'filearea'  => 'imagefiles',      // Usually = table name.
                        'itemid'    => 0,                 // Usually = ID of row in table.
                        'filepath'  => '/',               // Any path beginning and ending in.
                        'filename'  => $this->filename . '_rotated'); // Any filename.

                $newfile = $this->save_image($filerecord, $this->sourcefile);

                unlink($tempdst);
                unlink($tempsrc);
                return $newfile;
            }
        }

        $srcx = imagesx($this->image);
        $srcy = imagesy($this->image);

        $destx = $srcx - 1;
        $desty = $srcy - 1;

        $rotate = imagecreatetruecolor($destx, $desty);
        imagealphablending($rotate, false);

        for ($y = 0; $y < $srcy; $y++) {
            for ($x = 0; $x < $srcx; $x++) {
                $rgb = imagecolorsforindex($this->image, imagecolorat($this->image, $x, $y));
                $color = imagecolorallocate($rotate, $rgb['red'], $rgb['green'], $rgb['blue']);
                imagesetpixel($rotate, $destx - $x, $desty - $y, $color);
            }
        }
        $this->image = $rotate;
        if ($this->image = imagecreatefrompng($tempdst)) {
            $this->sourcefile = $tempdst;

            $filerecord = array(
                    'contextid' => $this->contextid,  // ID of context.
                    'component' => 'mod_offlinequiz', // Usually = table name.
                    'filearea'  => 'imagefiles',      // Usually = table name.
                    'itemid'    => 0,                 // Usually = ID of row in table.
                    'filepath'  => '/',               // Any path beginning and ending in.
                    'filename'  => $this->filename . 'rotated'); // Any filename.

            $newfile = $this->save_image($filerecord, $this->sourcefile);
            unlink($tempdst);
            return $newfile;
        }
    }

    /**
     *  Rotates image by 90 degrees.
     */
    public function rotate_90() {
        global $CFG;

        $uniquename = time();
        $tempsrc = $CFG->dataroot."/temp/$uniquename"."_src.png";
        $tempdst = $CFG->dataroot."/temp/$uniquename"."_dst.png";
        if (imagepng($this->image, $tempsrc)) {
            $handle = popen("convert '" . $tempsrc . "' -rotate 90 '" . $tempdst . "' ", 'r');
            pclose($handle);
            if ($this->image = imagecreatefrompng($tempdst)) {
                unlink($tempdst);
                unlink($tempsrc);
                return;
            }
        }

        $srcx = imagesx($this->image);
        $srcy = imagesy($this->image);

        $destx = $srcy - 1;
        $desty = $srcx - 1;

        $rotate = imagecreatetruecolor($destx, $desty);
        imagealphablending($rotate, false);

        for ($x = 0; $x < $srcx; $x++) {
            for ($y = 0; $y < $srcy; $y++) {
                $rgb = imagecolorsforindex($this->image, imagecolorat($this->image, $x, $y));
                $color = imagecolorallocate($rotate, $rgb['red'], $rgb['green'], $rgb['blue']);
                imagesetpixel($rotate, $destx - $y, $x, $color);
            }
        }
        $this->image = $rotate;
    }

    /**
     * Returns true if pixel color is lower than paper gray.
     *
     * @param unknown_type $x
     * @param unknown_type $y
     * @return boolean
     */
    public function pixel_is_black($x, $y) {
        global $CFG;

        if ($x >= imagesx($this->image) or $x >= imagesy($this->image)) { // Point is out of range.
            return false;
        }
        $rgb = imagecolorsforindex($this->image, imagecolorat($this->image, $x, $y));
        $gray = $rgb['red'] + $rgb['green'] + $rgb['blue'];

        if ($gray > $this->papergray) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Determines the value of a bar code.
     * Given a starting point, this function returns the number (base converted into decimal) of the bar code.
     *
     * @param unknown_type $point
     * @return string|boolean
     */
    public function get_barcode($point) {
        $positionx = $point->x + $this->offset->x - 4;
        $y = $point->y + $this->offset->y + (BOX_INNER_WIDTH * $this->zoomy / 2);
        $lastx = BOX_INNER_WIDTH * $this->zoomx * 10 + $positionx;
        $isblack = false;
        $numblacks = 0;
        $min = 100;
        $max = 0;

        $values = array();

        // Scan first line.
        $data = array();
        for ($x = $positionx; $x <= $lastx; $x++) {
            if ($this->pixel_is_black($x, $y)) {
                $numblacks++;
                $isblack = true;
            } else {
                if ($isblack) {
                    $data[] = $numblacks;
                    if ($min > $numblacks) {
                        $min = $numblacks;
                    }
                    if ($max < $numblacks) {
                        $max = $numblacks;
                    }
                    $isblack = false;
                    $numblacks = 0;
                }
            }
        }

        $trigger = ($min + $max) / 2;
        $values[0] = '';
        if (count($data) == 27) {
            for ($i = 1; $i <= 25; $i++) {
                if ($data[$i] <= $trigger) {
                    $values[0] .= '0';
                } else {
                    $values[0] .= '1';
                }
            }
        }

        // Scan second line.
        $data = array();
        $y -= BOX_INNER_WIDTH * $this->zoomy / 4;
        for ($x = $positionx; $x <= $lastx; $x++) {
            if ($this->pixel_is_black($x, $y)) {
                $numblacks++;
                $isblack = true;
            } else {
                if ($isblack) {
                    $data[] = $numblacks;
                    if ($min > $numblacks) {
                        $min = $numblacks;
                    }
                    if ($max < $numblacks) {
                        $max = $numblacks;
                    }
                    $isblack = false;
                    $numblacks = 0;
                }
            }
        }

        $trigger = ($min + $max) / 2;
        $values[1] = '';
        if (count($data) == 27) {
            for ($i = 1; $i <= 25; $i++) {
                if ($data[$i] <= $trigger) {
                    $values[1] .= '0';
                } else {
                    $values[1] .= '1';
                }
            }
        }

        // Scan third line.
        $data = array();
        $y += BOX_INNER_WIDTH * $this->zoomy / 2;
        for ($x = $positionx; $x <= $lastx; $x++) {
            if ($this->pixel_is_black($x, $y)) {
                $numblacks++;
                $isblack = true;
            } else {
                if ($isblack) {
                    $data[] = $numblacks;
                    if ($min > $numblacks) {
                        $min = $numblacks;
                    }
                    if ($max < $numblacks) {
                        $max = $numblacks;
                    }
                    $isblack = false;
                    $numblacks = 0;
                }
            }
        }

        $trigger = ($min + $max) / 2;
        $values[2] = '';
        if (count($data) == 27) {
            for ($i = 1; $i <= 25; $i++) {
                if ($data[$i] <= $trigger) {
                    $values[2] .= '0';
                } else {
                    $values[2] .= '1';
                }
            }
        }

        // If two values are equal, return them, else false.
        if ($values[0] == $values[1]) {
            return base_convert($values[0], 2, 10);
        } else if ($values[1] == $values[2]) {
            return base_convert($values[1], 2, 10);
        } else if ($values[0] == $values[2]) {
            return base_convert($values[0], 2, 10);
        } else {
            return false;
        }

    }

    /**
     * Tries to find a fixation cross on the page.
     *
     * @param int $startx The x position to start at (0 for left edge)
     * @param int $starty The y position to start at (0 for top edge)
     * @param int $xfactor The x step factor (1 to go right)
     * @param int $yfactor The y step factor (-1 to go up)
     * @return boolean|oq_point
     */
    private function get_corner($startx, $starty, $xfactor, $yfactor) {
        $xstart = $startx;
        $ystart = $starty;
        $i = 0;
        $numtofind = round(MIN_BORDER * $this->zoomx);
        $found = false;
        while (!$found and $i < MAX_BORDER * $this->zoomx) {   // Searching for first white in upper left corner.
            if (!$this->pixel_is_black($xstart + $xfactor * $i, $ystart + $yfactor * $i)) {
                $numtofind -= 1;
            }
            if ($numtofind <= 0) {
                $found = true;
            }
            $i++;
        }
        $xstart = $xstart + $xfactor * $i;
        $ystart = $ystart + $yfactor * $i;
        $i = 0;
        $found = false;
        // Number of lines that have to be found (otherwise it is just the shit of a fly).
        $numtofind = round(CROSS_WIDTH * $this->zoomy / 2);
        while (!$found and $i < CORNER_SPOT_WIDTH_VERTICAL * $this->zoomy) {  // Scanning for cross verticaly.
            $i++;
            $whiteline = true;
            for ($j = 0; $j < CORNER_SPOT_WIDTH_HORIZONTAL * $this->zoomx; $j++) {
                if ($this->pixel_is_black($xstart + $xfactor * $j, $ystart + $yfactor * $i)) {
                    $whiteline = false;
                }
            }
            if ($whiteline) {
                $numtofind = round(CROSS_WIDTH * $this->zoomy / 2);
            } else {
                $numtofind -= 1;
            }
            if ($numtofind <= 0) {
                $found = true;
                $y = $ystart + $yfactor * $i;
            }
        }
        $i = 0;
        $found = false;
        $numtofind = round(CROSS_WIDTH * $this->zoomx / 2);
        while (!$found and $i < CORNER_SPOT_WIDTH_HORIZONTAL * $this->zoomx) {  // Scanning for cross horizontal.
            $i++;
            $whiteline = true;
            for ($j = 0; $j < CORNER_SPOT_WIDTH_VERTICAL * $this->zoomy; $j++) {
                if ($this->pixel_is_black($xstart + $xfactor * $i, $ystart + $yfactor * $j)) {
                    $whiteline = false;
                }
            }
            if ($whiteline) {
                $numtofind = round(CROSS_WIDTH * $this->zoomx / 2);
            } else {
                $numtofind -= 1;
            }
            if ($numtofind <= 0) {
                $found = true;
                $x = $xstart + $xfactor * $i;
            }
        }
        if (!isset($x) or !isset($y)) {
            return false;
        }
        return new oq_point($x, $y);
    }

    /**
     * Find upper left corner cross.
     *
     * @return Ambigous <boolean, oq_point>
     */
    private function get_upper_left() {
        return $this->get_corner(0, 0, 1, 1);
    }

    /**
     * Find upper right corner cross.
     * @return Ambigous <boolean, oq_point>
     */
    private function get_upper_right() {
        return $this->get_corner(imagesx($this->image) - 1, 0, -1, 1);
    }

    /**
     * Find lower left corner cross.
     * @return Ambigous <boolean, oq_point>
     */
    private function get_lower_left() {
        return $this->get_corner(0, imagesy($this->image) - 1, 1, -1);
    }

    /**
     * Find lower right corner cross.
     * @return Ambigous <boolean, oq_point>
     */
    private function get_lower_right() {
        return $this->get_corner(imagesx($this->image) - 1, imagesy($this->image) - 1, -1, -1);
    }

    /**
     * Returns the corners as an array of oq_points (topleft, topright, bottomleft, bottomright).
     * @param int $width
     * @return multitype:oq_point
     */
    public function get_corners() {
        global $CFG;

        $export = array();
        $factor = OQ_IMAGE_WIDTH / imagesx($this->image);

        $point = new oq_point(($this->upperleft->x) * $factor - 2 * $this->zoomx,
                ($this->upperleft->y) * $factor - 2 * $this->zoomy);
        $export[0] = $point;
        $point = new oq_point(($this->upperright->x) * $factor - 2 * $this->zoomx,
                ($this->upperright->y) * $factor - 2 * $this->zoomy);
        $export[1] = $point;
        $point = new oq_point(($this->lowerleft->x) * $factor - 2 * $this->zoomx,
                ($this->lowerleft->y) * $factor - 2 * $this->zoomy);
        $export[2] = $point;
        $point = new oq_point(($this->lowerright->x) * $factor - 2 * $this->zoomx,
                ($this->lowerright->y) * $factor - 2 * $this->zoomy);
        $export[3] = $point;

        return $export;
    }

    /**
     * Tries to grab the form sheet: corners, zoom factor, angle, orientation
     * This can take a long time (e.g. 12 seconds)
     *
     * @param unknown_type $check
     * @param unknown_type $upperleft
     * @param unknown_type $upperright
     * @param unknown_type $lowerleft
     * @param unknown_type $lowerright
     * @param unknown_type $width
     * @return boolean
     */
    public function adjust($check, $upperleft, $upperright, $lowerleft, $lowerright, $width, $scannedpageid = null) {
        global $DB;

        if (imagesx($this->image) > imagesy($this->image)) {  // Flip image if landscape orientation.
            // Downsize large pictures first if it is not send from correct.php (width != 0).
            if (imagesy($this->image) > 3000 and empty($width)) {
                $dest = imagecreatetruecolor(A3_HEIGHT, A3_WIDTH);
                imagecopyresampled($dest, $this->image, 0, 0, 0, 0, A3_HEIGHT, A3_WIDTH,
                imagesy($this->image), imagesx($this->image));
                $this->image = $dest;
            }
            $this->rotate_90();
            // First estimation of zoom factor, will be adjusted later.
            $this->zoomx = imagesx($this->image) / A3_WIDTH;
            $this->zoomy = imagesy($this->image) / A3_HEIGHT;
        } else {
            // Downsize large pictures if it is not send from correct.php (width != 0).
            if (imagesx($this->image) > 3000 and empty($width)) {
                $dest = imagecreatetruecolor(A3_WIDTH, A3_HEIGHT);
                imagecopyresampled($dest, $this->image, 0, 0, 0, 0, A3_WIDTH, A3_HEIGHT,
                imagesx($this->image), imagesy($this->image));
                $this->image = $dest;
                // First estimation of zoom factor, will be adjusted later.
                $this->zoomx = imagesx($this->image) / A3_WIDTH;
                $this->zoomy = imagesy($this->image) / A3_HEIGHT;
            }
        }

        $return = true;

        if ($width) {
            $factor = $width / imagesx($this->image);
        } else {
            $factor = 1;
        }

        if ($upperleft) {
            $this->upperleft = new oq_point();
            $this->upperleft->x = $upperleft->x / $factor + 2 * $this->zoomx;
            $this->upperleft->y = $upperleft->y / $factor + 2 * $this->zoomy;
        } else {
            $this->upperleft = $this->get_upper_left();
        }

        if (!$this->upperleft) {
            $return = false;
        } else if ($check) {
            // We check if it is on top only if it is not from correct.php or participants_correct.php.
            $blackpix = 0;
            $halfwidth = round(BOX_INNER_WIDTH * $this->zoomx / 2);
            // We use the black box down left to indicate if we should rotate by 180 deg if it is on top.
            for ($i = 0; $i <= $halfwidth; $i++) {
                if ($this->pixel_is_black($this->upperleft->x + $i + 2, $this->upperleft->y + $i + 2)) {
                    $blackpix++;
                }
            }
            if ($blackpix / $halfwidth > 0.5) {
                // Remember that we rotated it. In case of grab error it should be saved
                // in the original orientation.
                $this->ontop = true;
            }
        }

        if ($upperright) {
            $this->upperright = new oq_point();
            $this->upperright->x = $upperright->x / $factor + 2 * $this->zoomx;
            $this->upperright->y = $upperright->y / $factor + 2 * $this->zoomy;
        } else {
            $this->upperright = $this->get_upper_right();
        }
        if (!$this->upperright) {
            $return = false;
        }

        if ($lowerleft) {
            $this->lowerleft = new oq_point();
            $this->lowerleft->x = $lowerleft->x / $factor + 2 * $this->zoomx;
            $this->lowerleft->y = $lowerleft->y / $factor + 2 * $this->zoomy;
        } else {
            $this->lowerleft = $this->get_lower_left();
        }
        if (!$this->lowerleft) {
            $return = false;
        }

        if ($lowerright) {
            $this->lowerright = new oq_point();
            $this->lowerright->x = $lowerright->x / $factor + 2 * $this->zoomx;
            $this->lowerright->y = $lowerright->y / $factor + 2 * $this->zoomy;
        } else {
            $this->lowerright = $this->get_lower_right();
        }
        if (!$this->lowerright) {
            $return = false;
        }

        // Now set angle and exact zoom factors.
        // Avoid division by zero.
        if ($this->upperright->x == $this->upperleft->x or $this->lowerleft->y == $this->upperleft->y) {
            $this->alpha = 0;
        } else {
            $alpha1 = rad2deg(atan(($this->upperright->y - $this->upperleft->y) / ($this->upperright->x - $this->upperleft->x)));
            $alpha2 = rad2deg(atan(($this->upperleft->x - $this->lowerleft->x) / ($this->lowerleft->y - $this->upperleft->y)));
            $this->alpha = ($alpha1 + $alpha2) / 2;
        }

        $a2 = pow($this->upperright->y - $this->upperleft->y, 2);
        $b2 = pow($this->upperright->x - $this->upperleft->x, 2);
        $this->zoomx = sqrt($a2 + $b2) / LAYER_WIDTH;
        $a2 = pow($this->lowerleft->y - $this->upperleft->y, 2);
        $b2 = pow($this->upperleft->x - $this->lowerleft->x, 2);
        $this->zoomy = sqrt($a2 + $b2) / LAYER_HEIGHT;

        $this->offset = $this->upperleft;

        $this->move_hotspots();

        if ($scannedpageid && $hotspots = $DB->get_records('offlinequiz_hotspots', array('scannedpageid' => $scannedpageid))) {
            $this->restore_hotspots($hotspots);
        } else {
            $this->adjust_hotspots();
        }

        $this->init_pattern();

        return $return;
    }


    /**
     * Set the 4 trigger values.
     * @param unknown_type $empty
     * @param unknown_type $cross
     */
    public function calibrate($empty, $cross) {
        $this->lowerwarning = $empty + ($cross - $empty) * 0.2;
        $this->lowertrigger = $this->lowerwarning + (($cross - $this->lowerwarning) * 0.2);
        $this->upperwarning = $cross + ((100 - $cross) * 0.2);
        $this->uppertrigger = $cross + ((100 - $cross) * 0.63);
        $this->calibrated = true;
    }

    /**
     * Determines and returns the usernumber
     *
     * @return string
     */
    public function get_usernumber() {
        global $CFG;
        $offlinequizconfig = get_config('offlinequiz');

        $usernumber = '';
        for ($i = 0; $i < $this->id_digits; $i++) {
            $found = 0;
            $value = 'X';                   // If we cannot read the value, set it to X.
            $insecure = false;
            for ($j = 0; $j <= 9; $j++) {
                $spotvalue = $this->hotspot_value($this->hotspots["u$i$j"]);
                if ($spotvalue == 1) {
                    $value = $j;
                    $found++;
                } else if ($spotvalue == 3) {
                    $insecure = true;
                    $this->insecure = true;
                }
            }
            if ($found > 1 or $insecure) {
                $value = 'X';              // If we get more than one value, set it to X.
                $this->insecure = true;
            }

            $usernumber .= $value;
        }

        /*
         * This is just due to the Austrian universities changing from 7 digits to 8 digits in student IDs.
         * We are handling the cases where answer sheets have been created with 7 digits and the
         * student IDs contain already 8 digits. This has no effect on other systems/instances.
         */
        if ($offlinequizconfig->ID_digits > $this->id_digits) {
            // Fill the usernumber to the new length with 0 on the left!
            $usernumber = str_pad($usernumber, $offlinequizconfig->ID_digits, "0", STR_PAD_LEFT);
        }

        return $usernumber;
    }

    /**
     * Returns the group as a number (1 to 6) and calibrates the form (setting trigger values).
     * @return number
     */
    public function calibrate_and_get_group() {
        global $CFG;

        $group = 0;

        $groupspots = array();
        $value = 0;

        // Get all the group hotspot values and select the biggest value.
        for ($i = 0; $i <= 5; $i++) {
            $groupspots[$i] = $this->hotspot_value($this->hotspots["g$i"], true);
            if ($groupspots[$i] > $value) {
                $group = $i;
                $value = $groupspots[$i];
            }
        }

        // Now we go through all the group hotspots (except the one picked before)
        // and look for another hotspot with the second biggest percentage of black pixels.
        $value = 0;
        for ($i = 0; $i <= 5; $i++) {
            if ($i != $group and $groupspots[$i] > $value) {
                $value = $groupspots[$i];
            }
        }
        // Check for correction and set to insecure.
        if ($value > 15) {
            $insecure = true;
        } else {
            $insecure = false;
        }
        // We calibrate with the second biggest value as 'empty' and the biggest value as 'cross'
        // JZ: This doesn't make any sense!
        if (!$this->calibrated) {
            $this->calibrate($value, $groupspots[$group]);
        }

        if ($insecure) {
            $this->insecure = true;
            return 0;
        }
        $group++;
        return $group;
    }

    public function set_maxanswers($maxanswers, $scannedpage) {
        if ($maxanswers > 26) {
            $maxanswers = 26; // There won't be more than 26 answers or 96 questions on the sheet.
        }
        $this->maxanswers = $maxanswers;
        $this->formtype = 4;

        if ($maxanswers > 5) {
            $this->formtype = 3;
        }
        if ($maxanswers > 7) {
            $this->formtype = 2;
        }
        if ($maxanswers > 12) {
            $this->formtype = 1;
        }

        $this->numpages = ceil($this->maxquestions / ($this->formtype * 24));

        $this->init_hotspots();

        $corners = $this->corners;
        if (!empty($corners)) {
            $ok = $this->adjust(false, $corners[0], $corners[1], $corners[2], $corners[3], OQ_IMAGE_WIDTH, $scannedpage->id);
        } else {
            $ok = $this->adjust(true, false, false, false, false, 0, $scannedpage->id);
        }

        // Check if we can adjust the image s.t. we can determine the hotspots.
        if ($ok) {
            $scannedpage->status = 'ok';
            $scannedpage->error = '';
        } else {
            $scannedpage->status = 'error';
            $scannedpage->error = 'couldnotgrab';
            $scannedpage->info = $this->filename;
        }

        return $scannedpage;
    }


    /**
     * Sets the group number to the one chosen by the user and calibrates correctly.
     *
     * @param unknown_type $group
     */
    public function set_group($group) {

        // We compute min, max and medium values.
        $groupspots = array();

        $maxvalue = 0;
        $minvalue = 100;
        $mediumvalue = 0;

        // Get all the group hotspot values as percents and select the minimum and maximum value.
        for ($i = 0; $i <= 5; $i++) {
            $groupspots[$i] = $this->hotspot_value($this->hotspots["g$i"], true);
            if ($groupspots[$i] > $maxvalue) {
                $maxvalue = $groupspots[$i];
            }
            if ($groupspots[$i] < $minvalue) {
                $minvalue = $groupspots[$i];
            }
        }

        for ($i = 0; $i <= 5; $i++) {
            if ($groupspots[$i] > $minvalue && $groupspots[$i] < $maxvalue) {
                if ($groupspots[$i] > $mediumvalue) {
                    $mediumvalue = $groupspots[$i];
                }
            }
        }
        if ($mediumvalue == 0) {
            $mediumvalue = $maxvalue;
        }
        // We assume that minvalue is empty, and mediumvalue is a cross.
        $this->calibrate($minvalue, $mediumvalue);
    }

    /**
     * Returns the answers as recognised on the answer form.
     * @return multitype:multitype:string
     */
    public function get_answers() {
        $answers = array();

        for ($number = 0; $number < $this->questionsonpage; $number++) {
            $row = array();
            for ($i = 0; $i < $this->maxanswers; $i++) {
                $spotvalue = $this->hotspot_value($this->hotspots["a-$number-$i"], false,  "a-$number-$i");
                if ($spotvalue == 1) {
                    $row[] = 'marked';
                } else if ($spotvalue == 3) {
                    $row[] = 'unknown';
                    $this->insecure = true;
                } else {
                    $row[] = 'empty';
                }
            }
            $answers[] = $row;
        }
        return $answers;
    }

    /**
     * Returns true if this scanner is insecure about some markings.
     * @return boolean
     */
    public function is_insecure() {
        return $this->insecure;
    }

}
