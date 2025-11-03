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

namespace offlinequiz_rimport\importer\scanner\boxscanner;
/**
 * Summary of pixelcountboxscanner
 *
 * @package    offlinequiz_rimport
 * @copyright  2025 Thomas Wedekind <Thomas.wedekind@univie.ac.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pixelcountboxscanner {
    /**
     * number of pixels
     * @var int
     */
    private static $count = 0;

    /**
     * General Idea of this function: It scans a box, and returns, if it is crossed out or not, or if it's uncertain.
     * This boxscanner does that by just calculating the black/white ratio.
     * In the future we will provide more sophisticated ways to find out if a box is crossed out or not.
     * @param offlinequiz_result_page $page
     * @param offlinequiz_point $boxmiddlepoint the guessed middle of the box.
     * @param integer $boxsize the size of the box in pixels from left to right and from top to bottom
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
        $boximage->cropimage(
            round($marginedboxsize * $zoomfactorx),
            round($marginedboxsize * $zoomfactory),
            $boxupperleft->getx(),
            $boxupperleft->gety()
        );

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
    /**
     * return the black/white value of an image
     * @param \Imagick $image
     */
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
