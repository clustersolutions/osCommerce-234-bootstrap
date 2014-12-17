<?php
/*
  $Id: account_validation.php,v 3.0 2008/09/01 20:19:27 chaicka Exp $

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2006 osCommerce

  Released under the GNU General Public License
*/
define('CODE_CHECKED', 'passed');

function gen_reg_key(){
	$key = '';
	$chars = 'abcdefghjklmnpqrstuvwxyz123456789';
	for($i = 0; $i < ENTRY_VALIDATION_LENGTH; $i++){
	  $key .= substr($chars,urand(0, strlen($chars)-1),1); 
	}
	$key = strtoupper($key);
    return($key);
  }

////
// The HTML image wrapper function
function tep_image_captcha($src, $parameters = '') {

    if ( (empty($src) || ($src == DIR_WS_IMAGES)) && (IMAGE_REQUIRED == 'false') ) {
      return false;
    }

    $image = '<img src="' . tep_output_string($src) . '" alt=""';

		if (tep_not_null($parameters)) $image .= ' ' . $parameters;

    $image .= '>';

    return $image;
  }
	
function urand($min = NULL, $max = NULL){
        static $alreadyGenerated = array();
				$full = $max + abs($min);
        $range = ($min || $max) ? ($max - $min) + 1 : NULL; 
        do{
            $randValue = ($range) ? rand($min, $max) : rand();
            $key = $randValue + $full;
            if(count($rangeList) == $range) unset($alreadyGenerated);
            if($range) $rangeList[$key] = $randValue;
        }while($alreadyGenerated[$key] == $randValue + $full);
        unset($rangeList);
        $alreadyGenerated[$key] = $randValue + $full;
				return $randValue;
    } 
		
function image_noise (&$image) {
   $imagex = imagesx($image);
   $imagey = imagesy($image);

   for ($x = 0; $x < $imagex; ++$x) {
      for ($y = 0; $y < $imagey; ++$y) {
         if (rand(0,1)) {
            $rgb = imagecolorat($image, $x, $y);
            $red = ($rgb >> 16) & 0xFF;
            $green = ($rgb >> 8) & 0xFF;
            $blue = $rgb & 0xFF;
            $modifier = rand(-128,128);
            $red += $modifier;
            $green += $modifier;
            $blue += $modifier;

            if ($red > 255) $red = 255;
            if ($green > 255) $green = 255;
            if ($blue > 255) $blue = 255;
            if ($red < 0) $red = 0;
            if ($green < 0) $green = 0;
            if ($blue < 0) $blue = 0;

            $newcol = imagecolorallocate($image, $red, $green, $blue);
            imagesetpixel($image, $x, $y, $newcol);
         }
      }
   }
}

function image_scatter(&$image) {
   $imagex = imagesx($image);
   $imagey = imagesy($image);

   for ($x = 0; $x < $imagex; ++$x) {
      for ($y = 0; $y < $imagey; ++$y) {
         $distx = rand(-1, 1);
         $disty = rand(-1, 1);

         if ($x + $distx >= $imagex) continue;
         if ($x + $distx < 0) continue;
         if ($y + $disty >= $imagey) continue;
         if ($y + $disty < 0) continue;

         $oldcol = imagecolorat($image, $x, $y);
         $newcol = imagecolorat($image, $x + $distx, $y + $disty);
         imagesetpixel($image, $x, $y, $newcol);
         imagesetpixel($image, $x + $distx, $y + $disty, $oldcol);
      }
   }
}

function image_interlace (&$image, $fg=0, $bg=255) {
      $imagex = imagesx($image);
      $imagey = imagesy($image);

      $fg_red = ($fg >> 16) & 0xFF;
      $fg_green = ($fg >> 8) & 0xFF;
      $fg_blue = $fg & 0xFF;
      $bg_red = ($bg >> 16) & 0xFF;
      $bg_green = ($bg >> 8) & 0xFF;
      $bg_blue = $bg & 0xFF;
	  $red = ($fg_red+$bg_red)/2;
	  $green = ($fg_green+$bg_green)/2;
	  $blue = ($fg_blue+$bg_blue)/2;

      $band = imagecolorallocate($image, $red, $green, $blue);

      for ($y = 0; $y < $imagey; $y+=2) {
            for ($x = 0; $x < $imagex; ++$x) {
               imagesetpixel($image, $x, $y, $band);
            }
      }
   }

function image_greyscale (&$image) {
   $imagex = imagesx($image);
   $imagey = imagesy($image);
   for ($x = 0; $x <$imagex; ++$x) {
      for ($y = 0; $y <$imagey; ++$y) {
         $rgb = imagecolorat($image, $x, $y);
         $red = ($rgb >> 16) & 0xFF;
         $green = ($rgb >> 8) & 0xFF;
         $blue = $rgb & 0xFF;
         $grey = (int)(($red+$green+$blue)/3);
         $newcol = imagecolorallocate($image, $grey, $grey, $grey);
         imagesetpixel($image, $x, $y, $newcol);
      }
   }
}

function image_lines (&$image, $lc=204, $mode=200) {
   $imagex = imagesx($image);
   $imagey = imagesy($image);
	  $red = ($lc >> 16) & 0xFF;
    $green = ($lc >> 8) & 0xFF;
    $blue = $lc & 0xFF;
	 $line_color = imagecolorallocate($image, $red, $green, $blue);
   for ($x = 0; $x <($imagex*$imagey)/$mode; ++$x) {
         imageline($image, urand(0,$imagex), urand(0,$imagey), urand(0,$imagex), urand(0,$imagey), $line_color);
   }
}

function image_distort($image, $amp = 10,$prd = 12)
    {   
		    $imagex = imagesx($image);
   			$imagey = imagesy($image);
        $mlt = 2;
        $image2 = imagecreatetruecolor($imagex * $mlt, $imagey * $mlt);
        imagecopyresampled ($image2,$image,0,0,0,0,$imagex * $mlt,$imagey * $mlt,$imagex, $imagey);
				if (ANTI_ROBOT_IMAGE_FILTER_DISTORT=='both' || ANTI_ROBOT_IMAGE_FILTER_DISTORT=='vertical') {
        for ($i = 0;$i < ($imagex * $mlt);$i += 2)
        {
          imagecopy($image2,$image2,$i - 2,sin($i / $prd) * $amp,$i,0,2,($imagey * $mlt));
        }    }
				if (ANTI_ROBOT_IMAGE_FILTER_DISTORT=='both' || ANTI_ROBOT_IMAGE_FILTER_DISTORT=='horizontal') {
				for ($i = 0;$i < ($imagey * $mlt);$i += 2)
        {
				  imagecopy($image2,$image2,sin($i/($prd*2))*$amp*2,$i-1,0,$i,($imagex * $mlt),2);
        }   }
				imagecopyresampled ($image,$image2,0,0,0,0,$imagex, $imagey,$imagex * $mlt,$imagey * $mlt);
        imagedestroy($image2);
    } 	
?>
