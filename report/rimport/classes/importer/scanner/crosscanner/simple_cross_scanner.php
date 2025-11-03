<?php
// This file is part of offlinequiz_rimport - http://moodle.org/
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

namespace offlinequiz_rimport\importer\scanner\crosscanner;
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/mod/offlinequiz/report/rimport/point.php');
define('CROSSSIZEBOUNDRY', 15);
define('SIMPLE_CROSSFINDER_MARGIN', 200);
define('CROSS_SEARCH_MARGIN', 5);
define('ALIGNMENTCROSSBOUNDRY', 3);
define('CROSSTHICKNESS', 2);
/**
 * Class simple_cross_scanner
 *
 * @package    offlinequiz_rimport
 * @copyright  2025 Thomas Wedekind <Thomas.wedekind@univie.ac.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class simple_cross_scanner {
    /**
     * constructor
     * @param offlinequiz_result_page $page
     * @return void
     */
    public function __construct($page) {
        return;
    }
    /**
     * find cross
     * @param \Imagick $image
     * @param \offlinequiz_result_import\offlinequiz_point $upperleft
     * @param \offlinequiz_result_import\offlinequiz_point $lowerright
     * @return offlinequiz_point
     */
    public function findcross(\Imagick $image, offlinequiz_point $upperleft, offlinequiz_point $lowerright) {
        $geometry = $image->getimagegeometry();
        $downwards = ($geometry["height"]) / 2 > $upperleft->y;
        $margin = $geometry["height"] / A4_HEIGHT * SIMPLE_CROSSFINDER_MARGIN;
        $boundry = round($geometry["height"] / A4_HEIGHT * CROSSSIZEBOUNDRY);
        $realcrossthickness = CROSSTHICKNESS * $geometry["height"] / A4_HEIGHT;
        $right = ($geometry["width"]) / 2 > $upperleft->x;
        $lastfoundx = 0;
        $lastfoundy = 0;
        $pointx = 0;
        $pointy = 0;
        if ($right) {
            for ($i = $upperleft->getx(); $i < $upperleft->getx() + $margin; $i++) {
                $count = 0;
                if ($downwards) {
                    for ($j = $upperleft->gety(); $j < $upperleft->gety() + $margin; $j++) {
                        if (pixelisblack($image, $i, $j)) {
                            $count++;
                            if ($count == $boundry) {
                                $lastfoundx = $i;
                                break;
                            }
                        }
                    }
                } else {
                    for ($j = $upperleft->gety(); $j > $upperleft->gety() - $margin; $j--) {
                        if (pixelisblack($image, $i, $j)) {
                            $count++;
                            if ($count == $boundry) {
                                $lastfoundx = $i;
                                break;
                            }
                        }
                    }
                }
                if ($lastfoundx) {
                    if (!$pointx) {
                        $pointx = $lastfoundx;
                    } else if ($lastfoundx != $i) {
                        if (abs($pointx - $lastfoundx) > ALIGNMENTCROSSBOUNDRY * $geometry["height"] / A4_HEIGHT) {
                            $pointx += $realcrossthickness / 2;
                            break;
                        } else {
                            $pointx = ($pointx + $lastfoundx) / 2;
                            break;
                        }
                    }
                }
            }
        }

        if (!$right) {
            for ($i = $upperleft->getx(); $i > $upperleft->getx() - $margin; $i--) {
                $count = 0;
                if ($downwards) {
                    for ($j = $upperleft->gety(); $j < $upperleft->gety() + $margin; $j++) {
                        if (pixelisblack($image, $i, $j)) {
                            $count++;
                            if ($count == $boundry) {
                                $lastfoundx = $i;
                                break;
                            }
                        }
                    }
                } else {
                    for ($j = $upperleft->gety(); $j > $upperleft->gety() - $margin; $j--) {
                        if (pixelisblack($image, $i, $j)) {
                            $count++;
                            if ($count == $boundry) {
                                $lastfoundx = $i;
                                break;
                            }
                        }
                    }
                }
                if ($lastfoundx) {
                    if (!$pointx) {
                        $pointx = $lastfoundx;
                    } else if ($lastfoundx != $i) {
                        if (abs($pointx - $lastfoundx) > ALIGNMENTCROSSBOUNDRY * $geometry["height"] / A4_HEIGHT) {
                            $pointx -= $realcrossthickness / 2;
                            break;
                        } else {
                            $pointx = ($pointx + $lastfoundx) / 2;
                            break;
                        }
                    }
                }
            }
        }

        if ($downwards) {
            for ($i = $upperleft->gety(); $i < $upperleft->gety() + $margin; $i++) {
                $count = 0;
                if ($right) {
                    for ($j = $upperleft->getx(); $j < $upperleft->getx() + $margin; $j++) {
                        if (pixelisblack($image, $j, $i)) {
                            $count++;
                            if ($count == $boundry) {
                                $lastfoundy = $i;
                                break;
                            }
                        }
                    }
                } else {
                    for ($j = $upperleft->getx(); $j > $upperleft->getx() - $margin; $j--) {
                        if (pixelisblack($image, $j, $i)) {
                            $count++;
                            if ($count == $boundry) {
                                $lastfoundy = $i;
                                break;
                            }
                        }
                    }
                }
                if ($lastfoundy) {
                    if (!$pointy) {
                        $pointy = $lastfoundy;
                    } else if ($lastfoundy != $i) {
                        if (abs($pointy - $lastfoundy) > ALIGNMENTCROSSBOUNDRY * $geometry["height"] / A4_HEIGHT) {
                            $pointy += $realcrossthickness / 2;
                            break;
                        } else {
                            $pointy = ($pointy + $lastfoundy) / 2;
                            break;
                        }
                    }
                }
            }
        }
        if (!$downwards) {
            for ($i = $upperleft->gety(); $i > $upperleft->gety() - $margin; $i--) {
                $count = 0;
                if ($right) {
                    for ($j = $upperleft->getx(); $j < $upperleft->getx() + $margin; $j++) {
                        if (pixelisblack($image, $j, $i)) {
                            $count++;
                            if ($count == $boundry) {
                                $lastfoundy = $i;
                                break;
                            }
                        }
                    }
                } else {
                    for ($j = $upperleft->getx(); $j > $upperleft->getx() - $margin; $j--) {
                        if (pixelisblack($image, $j, $i)) {
                            $count++;
                            if ($count == $boundry) {
                                $lastfoundy = $i;
                                break;
                            }
                        }
                    }
                }
                if ($lastfoundy) {
                    if (!$pointy) {
                        $pointy = $lastfoundy;
                    } else if ($lastfoundy != $i) {
                        if (abs($pointy - $lastfoundy) > ALIGNMENTCROSSBOUNDRY * $geometry["height"] / A4_HEIGHT) {
                            $pointy -= $realcrossthickness / 2;
                            break;
                        } else {
                            $pointy = ($pointy + $lastfoundy) / 2;
                            break;
                        }
                    }
                }
            }
        }
        // To do: Fehlerbehandlung.
        return new offlinequiz_point($pointx, $pointy, 1);
    }
}
