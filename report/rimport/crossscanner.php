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
namespace offlinequiz_result_import;
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/offlinequiz/report/rimport/point.php');
define('CROSSSIZEBOUNDRY', 15);
define('SIMPLE_CROSSFINDER_MARGIN', 200);
define('CROSS_SEARCH_MARGIN', 5);
define('ALIGNMENTCROSSBOUNDRY', 3);
define('CROSSTHICKNESS', 2);
class crossfinder {

    /**
     * Finds one cross in a corner to find out the adjustment of a page
     * @param \Imagick $image the image of the page
     * @param offlinequiz_point $upperleft the upperleft corner of where to expect the cross
     * @param offlinequiz_point $lowerright the lowerright corner of where to expect the cross
     * @return \offlinequiz_result_import\offlinequiz_point the point where a cross is found (if any exists)
     */
    public function findcross(\Imagick $image, offlinequiz_point $upperleft, offlinequiz_point $lowerright) {

        $sizex = $lowerright->getx() - $upperleft->getx();
        $sizey = $lowerright->gety() - $upperleft->gety();
        $middle = new offlinequiz_point($upperleft->getx() + $sizex / 2, $upperleft->gety() + $sizey / 2, false);
        $ylower = 0;
        $yupper = 0;
        $xleft = 0;
        $xright = 0;
        $count = 0;

        // Try to go downwards from the middle.
        for ($j = $middle->gety(); $j < $lowerright->gety() + 1; $j++) {
            // Count for every line the amount of black pixels.
            for ($i = $upperleft->getx(); $i < $lowerright->getx(); $i++) {
                if (pixelisblack($image, $i, $j)) {
                    $count++;
                    // If the amount of pixels exceeds a certain amount, save the linenumber and stop.
                    if ($count == CROSSSIZEBOUNDRY) {
                        $ylower = $j;
                        break;
                    }
                }
            }
            $count = 0;
            // If you have found line with a lot of black pixels stop.
            if ($ylower) {
                break;
            }
        }

        // The same as above but going upwards.
        for ($j = $middle->gety(); $j > $upperleft->gety(); $j--) {
            for ($i = $upperleft->getx(); $i < $lowerright->getx(); $i++) {
                if (pixelisblack($image, $i, $j)) {
                    $count++;
                    if ($count == CROSSSIZEBOUNDRY) {
                        $yupper = $j;
                        break;
                    }
                }
            }
            $count = 0;
            if ($yupper) {
                break;
            }
        }
        // We now (hopefully) have found the horizontal line. If we have found two, we choose the one closer to the guessed middle.
        if ($yupper || $ylower) {
            $y = $this->findclosest($middle->gety(), $ylower, $yupper);
        }

        // Do the exact same thing as above for the vertical line.
        for ($j = $middle->getx(); $j < $lowerright->getx(); $j++) {
            for ($i = $upperleft->gety(); $i < $lowerright->gety(); $i++) {
                if (pixelisblack($image, $j, $i)) {
                    $count++;
                    if ($count == CROSSSIZEBOUNDRY) {
                        $xright = $j;
                        break;
                    }
                }
            }
            $count = 0;
            if ($xright) {
                break;
            }
        }

        for ($j = $middle->getx(); $j > $upperleft->getx(); $j--) {
            for ($i = $upperleft->gety(); $i < $lowerright->gety(); $i++) {
                if (pixelisblack($image, $j, $i)) {
                    $count++;
                    if ($count == CROSSSIZEBOUNDRY) {
                        $xleft = $j;
                        break;
                    }
                }
            }
            $count = 0;
            if ($xleft) {
                break;
            }
        }
        $x = $this->findclosest($middle->getx(), $xright, $xleft);

        if ($x && $y) {
            // Point found.
            return new offlinequiz_point($x, $y, true);
        } else {
            // Guess the point.
            return $middle;
        }
    }

    private function findclosest ($middle, $upper, $lower) {
        if ($upper && $lower) {
            if ( abs($upper - $middle) > abs($lower - $middle) ) {
                return $lower;
            } else {
                return $upper;
            }
        } else if ($lower) {
            return $lower;
        } else if ($upper) {
            return $upper;
        }
        return 0;
    }
}

class simple_cross_scanner {

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
        // TODO Fehlerbehandlung.
        return new offlinequiz_point($pointx, $pointy, 1);

    }
}