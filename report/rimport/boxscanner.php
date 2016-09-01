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

define("BOX_MARGIN",5);
define("CROSS_FOUND_LOWER_LIMIT","0.3");
define("CROSS_FOUND_REASK_MARGIN","0.05");
define("CROSS_FOUND_UPPER_LIMIT","0.8");
class pixelcountboxscanner {

    private $count=0;

    public function scan_box(offlinequiz_result_page $page,offlinequiz_point $boxmiddlepoint,$boxsize) {
        $boxsize = $boxsize+BOX_MARGIN;
        $middletoupperleft = new offlinequiz_point(round(-$boxsize/2), round(-$boxsize/2), 2);
        $boxupperleft = add_with_adjustment($page,$boxmiddlepoint,$middletoupperleft);
        $zoomfactorx = $page->scanproperties->zoomfactorx;
        $zoomfactory = $page->scanproperties->zoomfactory;
        $boximage = clone $page->image;
        $boximage->cropimage(round($boxsize*$zoomfactorx),round($boxsize*$zoomfactory),$boxupperleft->getx(),$boxupperleft->gety());
        $blackpoints = $this->get_image_black_value($boximage);
        //TODO rausnehmen
        $boximage->writeImage("/tmp/boxtest" . $this->count);
        $this->count++;

        $maxpoints = pow($boxsize,2);
        if($blackpoints<$maxpoints*(CROSS_FOUND_LOWER_LIMIT-CROSS_FOUND_REASK_MARGIN)) {
            return 0;
        }
        else if($blackpoints<$maxpoints*(CROSS_FOUND_LOWER_LIMIT+CROSS_FOUND_REASK_MARGIN)) {
            return -1;
        }
        else if($blackpoints<$maxpoints*(CROSS_FOUND_UPPER_LIMIT-CROSS_FOUND_REASK_MARGIN)) {
            return 1;
        }
        elseif($blackpoints<$maxpoints*(CROSS_FOUND_UPPER_LIMIT+CROSS_FOUND_REASK_MARGIN)) {
            return -1;
        }
        else {
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