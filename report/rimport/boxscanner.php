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
define("CROSS_FOUND_LOWER_LIMIT","0.32");
define("CROSS_FOUND_REASK_MARGIN","0.03");
define("CROSS_FOUND_UPPER_LIMIT","0.7");
class pixelcountboxscanner {

    private static $count=0;

	/**
	 * General Idea of this function: It scans a box, and returns, if it is crossed out or not, or if it's uncertain.
	 * This boxscanner does that by just calculating the black/white ratio.
	 * In the future we will provide more sophisticated ways to find out if a box is crossed out or not.
	 * @param offlinequiz_result_page $page
	 * @param offlinequiz_point $boxmiddlepoint the guessed middle of the box.
	 * @param integer $marginedboxsize the size of the box in pixels from left to right and from top to bottom
	 * @return number 0, if not crossed out, 1 if crossed out. -1 if uncertain
	 */
    public function scan_box(offlinequiz_result_page $page,offlinequiz_point $boxmiddlepoint,$boxsize) {
    	//first we find out, where the upper left of the box SHOULD be (plus some margin to be sure we hit the whole box)
        $marginedboxsize = $boxsize+BOX_MARGIN*$page->scanproperties->zoomfactorx;
        $middletoupperleft = new offlinequiz_point(round(-$marginedboxsize/2), round(-$marginedboxsize/2), 2);
        $boxupperleft = add_with_adjustment($page,$boxmiddlepoint,$middletoupperleft);
//         print_object($boxupperleft);

        //get the cropped Image of the box
        $zoomfactorx = $page->scanproperties->zoomfactorx;
        $zoomfactory = $page->scanproperties->zoomfactory;
        $boximage = clone $page->image;
        $boximage->cropimage(round($marginedboxsize*$zoomfactorx),round($marginedboxsize*$zoomfactory),$boxupperleft->getx(),$boxupperleft->gety());
        
        //find out how many black points we have in the image
        $blackpoints = $this->get_image_black_value($boximage);
        //TODO rausnehmen
        print("box " . self::$count . "\n");
//         print_object($boxmiddlepoint);
        $boximage->writeImage("/tmp/boxtest" . self::$count . ".tif");
        self::$count++;
//         print($marginedboxsize);
        $maxpoints = pow($marginedboxsize*$zoomfactory,2);
//         print("schwarze Punkte: ". $blackpoints . "/" . $maxpoints . "\n");
		//Depending on how many black pixels we have in comparison to all pixels, decide if it is crossed out or not
        if($blackpoints<$maxpoints*(CROSS_FOUND_LOWER_LIMIT-CROSS_FOUND_REASK_MARGIN)) {
        	print("box empty \n");
            return 0;
        }
        else if($blackpoints<$maxpoints*(CROSS_FOUND_LOWER_LIMIT+CROSS_FOUND_REASK_MARGIN)) {
        	print("box empty or crossed \n");
            return -1;
        }
        else if($blackpoints<$maxpoints*(CROSS_FOUND_UPPER_LIMIT-CROSS_FOUND_REASK_MARGIN)) {
        	print("box crossed \n");
            return 1;
        }
        elseif($blackpoints<$maxpoints*(CROSS_FOUND_UPPER_LIMIT+CROSS_FOUND_REASK_MARGIN)) {
        	print("box crossed or filled \n");
            return -1;
        }
        else {
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

class weightediagonalboxscanner{
	private static $count=0;
	
	/**
	 * General Idea of this function: It scans a box, and returns, if it is crossed out or not, or if it's uncertain.
	 * This boxscanner does that by just calculating the black/white ratio.
	 * In the future we will provide more sophisticated ways to find out if a box is crossed out or not.
	 * @param offlinequiz_result_page $page
	 * @param offlinequiz_point $boxmiddlepoint the guessed middle of the box.
	 * @param integer $marginedboxsize the size of the box in pixels from left to right and from top to bottom
	 * @return number 0, if not crossed out, 1 if crossed out. -1 if uncertain
	 */
	public function scan_box(offlinequiz_result_page $page,offlinequiz_point $boxmiddlepoint,$boxsize) {
		//first we find out, where the upper left of the box SHOULD be (plus some margin to be sure we hit the whole box)
		$marginedboxsize = $boxsize+BOX_MARGIN*$page->scanproperties->zoomfactorx;
		$middletoupperleft = new offlinequiz_point(round(-$marginedboxsize/2), round(-$marginedboxsize/2), 2);
		$boxupperleft = add_with_adjustment($page,$boxmiddlepoint,$middletoupperleft);
		//         print_object($boxupperleft);
		
		//get the cropped Image of the box
		$zoomfactorx = $page->scanproperties->zoomfactorx;
		$zoomfactory = $page->scanproperties->zoomfactory;
		$boximage = clone $page->image;
		$boximage->cropimage(round($marginedboxsize*$zoomfactorx),round($marginedboxsize*$zoomfactory),$boxupperleft->getx(),$boxupperleft->gety());
		
		//find out how many black points we have in the image
		$blackpoints = $this->get_image_black_value($boximage);
		$boxdiagvalue = getbox_blackdiag_value($boximage);
		//TODO rausnehmen
		print("box " . self::$count . "\n");
		//         print_object($boxmiddlepoint);
		$boximage->writeImage("/tmp/boxtest" . self::$count . ".tif");
		self::$count++;
		//         print($marginedboxsize);
		$maxpoints = pow($marginedboxsize*$zoomfactory,2);
		//         print("schwarze Punkte: ". $blackpoints . "/" . $maxpoints . "\n");
		//Depending on how many black pixels we have in comparison to all pixels, decide if it is crossed out or not
		if($blackpoints<$maxpoints*(CROSS_FOUND_LOWER_LIMIT-CROSS_FOUND_REASK_MARGIN)) {
			print("box empty \n");
			return 0;
		}
		else if($blackpoints<$maxpoints*(CROSS_FOUND_LOWER_LIMIT+CROSS_FOUND_REASK_MARGIN)) {
			print("box empty or crossed \n");
			return -1;
		}
		else if($blackpoints<$maxpoints*(CROSS_FOUND_UPPER_LIMIT-CROSS_FOUND_REASK_MARGIN)) {
			print("box crossed \n");
			return 1;
		}
		elseif($blackpoints<$maxpoints*(CROSS_FOUND_UPPER_LIMIT+CROSS_FOUND_REASK_MARGIN)) {
			print("box crossed or filled \n");
			return -1;
		}
		else {
			print("box filled \n");
			return 0;
		}
	}
	
	private function getbox_blackdiag_value(\Imagick $image) {
		$histo = $image->getimagehistogram();
		foreach ($histo as $h) {
			$color = $h->getColor();
			if ($color['r'] == 0 && $color['g'] == 0 && $color['b'] == 0) {
				return $h->getColorCount();
			}
		}
		return 0;
	}
	
	private function getboxblackdiagvalues(\Imagick $image) {
		$geometry = $image->getimagegeometry();
		$dots= $geometry["width"]* $geometry["heigth"];
		$totaldiagvalue = 0;
		$totalnondiagvalue = 0;
		for($i=0;$i<$geometry["width"];$i++) {
			for($j=0;j<$geometry["heigth"];$j++) {
				if(pixelisblack($image, $i, $j)) {
					$totaldiagvalue+= getnormaldiagvalue($i,$j,$width,$height);
				}
				//else {
// 					$totalnondiagvalue += getnormalnondiagvalue($i,$j,$width,$height);
// 				}
			}
		}
		return $totaldiagvalue/$dots;
	}
	
	private function getnormaldiagvalue($i,$j,$width,$height) {
		$distance=getdiagdistance($i,$j,$width,$height);
		return stats_dens_normal($distance,0,1);
	}
	
	private function getdiagdistance($i,$j,$width,$height) {
		//the linear functions for the cross, the shift is 0 for downwards, $height for upwards
		$gradiantdownwardsdiag = $height/$width;
		$gradiantupwardsdiag = -$height/$width;
		//the orthogonal lines of these equations
		$orthogonalupwards = -1/$gradiantupwardsdiag;
		$orthogonaldownwards = -1/$gradiantdownwardsdiag;
		$shiftupwards = $j-$i*$orthogonalupwards;
		$shiftdownwards = $j-$i*$orthogonaldownwards;
		//some fancy linear equations here: find out the meeting points from
	}
}
