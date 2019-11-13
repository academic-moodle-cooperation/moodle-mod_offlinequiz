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

define("BOX_MARGIN", 10);
define("BLACK_DOTS_CHANGE_LIMIT", 0.85);
define("CROSS_FOUND_LOWER_LIMIT", 0.04);
define("CROSS_FOUND_REASK_MARGIN", 0.03);
define("CROSS_FOUND_UPPER_LIMIT", 0.50);
define("WEIGHTEDVALUE_LOWER_LIMIT", 0.8);
define("WEIGHTEDVALUE_UPPER_LIMIT", 0.9);
define("NORMAL_DISTRIBUTION_VARIANCE", 0.1);
class pixelcountboxscanner {

    private static $count = 0;

    /**
     * General Idea of this function: It scans a box, and returns, if it is crossed out or not, or if it's uncertain.
     * This boxscanner does that by just calculating the black/white ratio.
     * In the future we will provide more sophisticated ways to find out if a box is crossed out or not.
     * @param offlinequiz_result_page $page
     * @param offlinequiz_point $boxmiddlepoint the guessed middle of the box.
     * @param integer $marginedboxsize the size of the box in pixels from left to right and from top to bottom
     * @return number 0, if not crossed out, 1 if crossed out. -1 if uncertain
     */
    public function scan_box(offlinequiz_result_page $page, offlinequiz_point $boxmiddlepoint, $boxsize) {
        // First we find out, where the upper left of the box SHOULD be (plus some margin to be sure we hit the whole box).
        $marginedboxsize = $boxsize + BOX_MARGIN;
        $middletoupperleft = new offlinequiz_point(round(-$marginedboxsize / 2), round(-$marginedboxsize / 2), 2);
        $boxupperleft = add_with_adjustment($page, $boxmiddlepoint, $middletoupperleft);
        // Get the cropped Image of the box.
        $zoomfactorx = $page->scanproperties->zoomfactorx;
        $zoomfactory = $page->scanproperties->zoomfactory;
        $boximage = clone $page->image;
        $boximage->cropimage(round($marginedboxsize * $zoomfactorx), round($marginedboxsize * $zoomfactory),
            $boxupperleft->getx(), $boxupperleft->gety());

        // Find out how many black points we have in the image.
        $blackpoints = $this->get_image_black_value($boximage);

        self::$count++;
        $maxpoints = ($marginedboxsize * $zoomfactory) * ($marginedboxsize * $zoomfactorx);

        // Depending on how many black pixels we have in comparison to all pixels, decide if it is crossed out or not.
        if ($blackpoints < $maxpoints * (CROSS_FOUND_LOWER_LIMIT - CROSS_FOUND_REASK_MARGIN)) {
            print("box empty \n");
            return 0;
        } else if ($blackpoints < $maxpoints * (CROSS_FOUND_LOWER_LIMIT + CROSS_FOUND_REASK_MARGIN)) {
            print("box empty or crossed \n");
            return -1;
        } else if ($blackpoints < $maxpoints * (CROSS_FOUND_UPPER_LIMIT - CROSS_FOUND_REASK_MARGIN)) {
            print("box crossed \n");
            return 1;
        } else if ($blackpoints < $maxpoints * (CROSS_FOUND_UPPER_LIMIT + CROSS_FOUND_REASK_MARGIN)) {
            print("box crossed or filled \n");
            return -1;
        } else {
            print("box filled \n");
            return 0;
        }
    }

    private function get_image_black_value(\Imagick $image) {
        $histo = $image->getimagehistogram();
        foreach ($histo as $h) {
            $color = $h->getColor();
            if ($color['r'] == 0 && $color['g'] == 0 && $color['b'] == 0) {
                return $h->getColorCount();
            }
        }
        return 0;
    }
}

class weighted_diagonal_box_scanner{
    private static $count = 0;

    /**
     * General Idea of this function: It scans a box, and returns, if it is crossed out or not, or if it's uncertain.
     * This boxscanner does that by just calculating the black/white ratio.
     * In the future we will provide more sophisticated ways to find out if a box is crossed out or not.
     * @param offlinequiz_result_page $page
     * @param offlinequiz_point $boxmiddlepoint the guessed middle of the box.
     * @param integer $marginedboxsize the size of the box in pixels from left to right and from top to bottom
     * @return number 0, if not crossed out, 1 if crossed out. -1 if uncertain
     */
    public function scan_box(offlinequiz_result_page $page, offlinequiz_point $boxmiddlepoint, $boxsize) {
        self::$count++;
        // First we find out, where the upper left of the box SHOULD be (plus some margin to be sure we hit the whole box).
        $marginedboxsize = $boxsize + BOX_MARGIN;
        $middletoupperleft = new offlinequiz_point(-$marginedboxsize / 2, -$marginedboxsize / 2, 2);
        $boxupperleft = add_with_adjustment($page, $boxmiddlepoint, $middletoupperleft);

        // Get the cropped Image of the box.
        $zoomfactorx = $page->scanproperties->zoomfactorx;
        $zoomfactory = $page->scanproperties->zoomfactory;
        $boximage = clone $page->image;
        $boximage->cropimage(round($marginedboxsize * $zoomfactorx), round($marginedboxsize * $zoomfactory),
            $boxupperleft->getx(), $boxupperleft->gety());
        $blackdotsbefore = $this->get_image_black_value($boximage);

        $this->remove_edges($boximage);
        // Find out how many black dots we have in the image.
        $blackdots = $this->get_image_black_value($boximage);
        $maxdots = pow($marginedboxsize * $zoomfactory, 2);
        // Depending on how many black pixels we have in comparison to all pixels, decide if it is crossed out or not.
        if ($blackdotsbefore > $maxdots * CROSS_FOUND_UPPER_LIMIT) {
            return 0;
        }
        if ($blackdotsbefore * (1 - BLACK_DOTS_CHANGE_LIMIT) > $blackdots) {
            return 0;
        }
        if ($blackdots < $maxdots * CROSS_FOUND_LOWER_LIMIT) {
            return 0;
        }

        $boxdiagupvalue = $this->get_box_diag_up_black_value($boximage) * $maxdots / $blackdots;
        $boxdiagdownvalue = $this->get_box_diag_down_black_value($boximage) * $maxdots / $blackdots;

        $boxdiagvalue = $boxdiagupvalue + $boxdiagdownvalue;
        if ($boxdiagvalue < WEIGHTEDVALUE_LOWER_LIMIT) {
            return 0;
        } else if ($boxdiagvalue > WEIGHTEDVALUE_UPPER_LIMIT) {
            return 1;
        } else {
            return -1;
        }

    }

    private function remove_edges( \Imagick $image) {
        $geometry = $image->getimagegeometry();
        $maxx = 0;
        $maxy = 0;
        $countx = array();
        $county = array();

        for ($i = 0; $i < $geometry["width"]; $i++) {
            $countx[$i] = 0;
            for ($j = 0; $j < $geometry["height"]; $j++) {
                if (pixelisblack($image, $i, $j)) {
                    $countx[$i]++;
                }
            }
            if ($countx[$i] > $maxx) {
                $maxx = $countx[$i];
            }
        }
        for ($i = 0; $i < $geometry["height"]; $i++) {
            $county[$i] = 0;
            for ($j = 0; $j < $geometry["width"]; $j++) {
                if (pixelisblack($image, $j, $i)) {
                    $county[$i]++;
                }
            }
            if ($county[$i] > $maxy) {
                $maxy = $county[$i];
            }
        }
        $draw  = new \ImagickDraw();
        $draw->setFillColor(new \ImagickPixel('#FFFFFF'));

        for ($i = 0; $i < $geometry["width"]; $i++) {
            if ($maxx <= $countx[$i] + 5) {
                for ($j = 0; $j < $geometry["height"]; $j++) {
                    $draw->point($i, $j);
                }
            }
        }

        for ($i = 0; $i < $geometry["height"]; $i++) {
            if ($maxy <= $county[$i] + 5) {
                for ($j = 0; $j < $geometry["width"]; $j++) {
                    $draw->point($j, $i);
                }
            }
        }
        $image->drawImage($draw);
    }

    private function get_image_black_value(\Imagick $image) {
        $histo = $image->getimagehistogram();
        foreach ($histo as $h) {
            $color = $h->getColor();
            if ($color['r'] == 0 && $color['g'] == 0 && $color['b'] == 0) {
                return $h->getColorCount();
            }
        }
        return 0;
    }

    private function get_box_diag_up_black_value(\Imagick $image) {
        $geometry = $image->getimagegeometry();
        $dots = $geometry["width"] * $geometry["height"];
        $totaldiagblackvalue = 0;
        for ($i = 0; $i < $geometry["width"]; $i++) {
            for ($j = 0; $j < $geometry["height"]; $j++) {
                if (pixelisblack($image, $i, $j)) {
                    $totaldiagblackvalue += $this->get_diag_up_value($i, $j, $geometry["width"], $geometry["height"]);
                }
            }
        }
        return $totaldiagblackvalue / $dots;
    }

    private function get_box_diag_down_black_value(\Imagick $image) {
        $geometry = $image->getimagegeometry();
        $dots = $geometry["width"] * $geometry["height"];
        $totaldiagblackvalue = 0;
        for ($i = 0; $i < $geometry["width"]; $i++) {
            for ($j = 0; $j < $geometry["height"]; $j++) {
                if (pixelisblack($image, $i, $j)) {
                    $totaldiagblackvalue += $this->get_diag_down_value($i, $j, $geometry["width"], $geometry["height"]);
                }
            }
        }
        return $totaldiagblackvalue / $dots;
    }

    private function get_diag_up_value($i, $j, $width, $height) {
        $distance = $this->get_diag_up_distance($i, $j, $width, $height);
        return 1 / (NORMAL_DISTRIBUTION_VARIANCE * 2 * M_PI) * pow(M_E, (-1 / 2) * pow($distance / NORMAL_DISTRIBUTION_VARIANCE, 2));
    }

    private function get_diag_down_value($i, $j, $width, $height) {
        $distance = $this->get_diag_down_distance($i, $j, $width, $height);
        // Normal distribution.
        return 1 / (NORMAL_DISTRIBUTION_VARIANCE * 2 * M_PI) * pow(M_E, (-1 / 2) * pow($distance / NORMAL_DISTRIBUTION_VARIANCE, 2));
    }

    private function get_diag_up_distance($i, $j, $width, $height) {
        // The linear functions for the cross, the shift is 0 for downwards, $height for upwards.
        $gradiantupwardsdiag = -$height / $width;
        // The orthogonal lines of these equations.
        $orthogonalupwards = -1 / $gradiantupwardsdiag;
                $shiftupwards = $j - $i * $orthogonalupwards;
        // Some fancy linear equations here: find out the meeting points from the two lines with their orthogonals.

        $meetingpointupwardsx = ($shiftupwards - $height) / ($gradiantupwardsdiag - $orthogonalupwards);
        $meetingpointupwardsy = $orthogonalupwards * $meetingpointupwardsx + $shiftupwards;

        $distanceupwards = (new offlinequiz_point($meetingpointupwardsx - $i, $meetingpointupwardsy - $j, 0))->getdistance();

        return $distanceupwards / sqrt($width * $height);
    }
    private function get_diag_down_distance($i, $j, $width, $height) {
        // The linear functions for the cross, the shift is 0 for downwards, $height for upwards.
        $gradiantdiag = $height / $width;
        // The orthogonal lines of these equations.
        $orthogonal = -1 / $gradiantdiag;
                $shiftdownwards = $j - $i * $orthogonal;
        // Some fancy linear equations here: find out the meeting points from the two lines with their orthogonals.
        $meetingpointx = $shiftdownwards / ($gradiantdiag - $orthogonal);
        $meetingpointy = $orthogonal * $meetingpointx + $shiftdownwards;

        $distancedownwards = (new offlinequiz_point($meetingpointx - $i, $meetingpointy - $j, 0))->getdistance();

        return $distancedownwards / sqrt($width * $height);
    }
}


