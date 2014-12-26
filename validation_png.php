<?php
/*
  $Id: validation_png.php,v 3 2008/08/25 18:44:27 Exp $

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2006 osCommerce

  Released under the GNU General Public License
*/

// include necessary pre-amble

$bitmap_font_size = 5;// 1 to 5

  //error_reporting(0);
/*
	require_once('includes/configure.php');
	define('DIR_WS_CATALOG', DIR_WS_HTTP_CATALOG);
	require_once(DIR_WS_INCLUDES . 'filenames.php');
	require_once(DIR_WS_INCLUDES . 'database_tables.php');
	require_once(DIR_WS_FUNCTIONS . 'database.php');
	require_once(DIR_WS_FUNCTIONS .  FILENAME_ACCOUNT_VALIDATION);
	tep_db_connect() or die('Unable to connect to database server!');
*/
        require_once('includes/application_top.php');
	require_once(DIR_WS_FUNCTIONS .  FILENAME_ACCOUNT_VALIDATION);
	$configuration_query = tep_db_query('select configuration_key as cfgKey, configuration_value as cfgValue from ' . TABLE_CONFIGURATION);
	while ($configuration = tep_db_fetch_array($configuration_query)) {
		define($configuration['cfgKey'], $configuration['cfgValue']);
	}
// End - include

// Derived from the original contribution by AlexStudio
// Note to potential users of this code ...
//
// Remember this is released under the _GPL_ and is subject
// to that licence. Do not incorporate this within software 
// released or distributed in any way under a licence other
// than the GPL. We will be watching ... ;)

// Do we have an id? No, then just exit
if(empty($HTTP_GET_VARS['rsid']))
{
  header('Content-Type: image/png');
  header('Cache-control: no-cache, no-store');
  $im = @imagecreatetruecolor(250, 60)
      or die("Cannot Initialize new GD image stream");
	$colour = imagecolorallocate($im, 255, 0, 0);
	imagefill($im, 0, 0, $colour);		
	$text_color = imagecolorallocate($im, 0, 0, 0);
	imagestring($im, 5, 5, 5,  "Generation Error:", $text_color);
	imagestring($im, 5, 50, 22,  "Missing SID", $text_color);
	imagestring($im, 5, 20, 40,  "Please Refresh browser", $text_color);
	imagepng($im);
	imagedestroy($im);
  exit;
}

$s_id = tep_db_output($HTTP_GET_VARS['rsid']);

// Try and grab reg_key for this id and session

$check_anti_robotreg_query = tep_db_query("select reg_key from anti_robotreg where session_id = '$s_id'");
$new_query_for_reg_key = tep_db_fetch_array($check_anti_robotreg_query);

$code = $new_query_for_reg_key['reg_key'];
$ttf = (file_exists(DIR_FS_CATALOG . DIR_WS_IMAGES . 'fonts/'. ANTI_ROBOT_IMAGE_TTF) && ANTI_ROBOT_IMAGE_USE_TTF=='true') ;
$whitespace = (ANTI_ROBOT_IMAGE_WHITESPACE != 0 ? ANTI_ROBOT_IMAGE_WHITESPACE : 6);
$font_size = (ANTI_ROBOT_IMAGE_FONT_SIZE != 0 ? ANTI_ROBOT_IMAGE_FONT_SIZE : 20);
$image_hieght = (ANTI_ROBOT_IMAGE_HEIGHT != 0 ? ANTI_ROBOT_IMAGE_HEIGHT : 50);
$image_width = (ANTI_ROBOT_IMAGE_WIDTH != 0 ? ANTI_ROBOT_IMAGE_WIDTH : 260);
$top_margin = (ANTI_ROBOT_IMAGE_TOP_MARGIN != 0 ? ANTI_ROBOT_IMAGE_TOP_MARGIN : 6);
$total_code_width = ($ttf) ? 0 : ((imagefontwidth($font_size)+$whitespace-1) * strlen($code));
$max_code_height = ($ttf) ? 0 : imagefontheight($font_size);
if ($ttf) {
  $angle = (ANTI_ROBOT_FONT_ANGLE != 0 ? ANTI_ROBOT_FONT_ANGLE : 14);$angle_char[]=array();
	for ($i=0; $i < strlen($code); $i++) {
    $angle_char[$i] = urand(-$angle, $angle);
    $char_bbox = imagettfbbox($font_size, $angle_char[$i], DIR_FS_CATALOG . DIR_WS_IMAGES . 'fonts/'. ANTI_ROBOT_IMAGE_TTF, $code[$i]);
	$width_char[$i] = max($char_bbox[2], $char_bbox[4]) - min($char_bbox[0], $char_bbox[6]);
    $total_code_width += $width_char[$i] + $whitespace;
    $max_code_height = max($max_code_height, max($char_bbox[1],$char_bbox[3],$char_bbox[5],$char_bbox[7]) -  min($char_bbox[1],$char_bbox[3],$char_bbox[5],$char_bbox[7]));
  }
}

$height = ($image_hieght > $max_code_height + $top_margin) ? $image_hieght : $max_code_height + $top_margin;
$width = ($image_width > $total_code_width) ? $image_width : $total_code_width;
$bgc = ANTI_ROBOT_IMAGE_BACKGROUND_COLOR; if (substr($bgc, 0, 2) != '0x') $bgc = '0x'.$bgc;
$tc = ANTI_ROBOT_IMAGE_TEXT_COLOR; if (substr($tc, 0, 2) != '0x') $tc = '0x'.$tc;
$lc = ANTI_ROBOT_IMAGE_LINES_COLOR; if (substr($lc, 0, 2) != '0x') $lc = '0x'.$lc;
$bgc = hexdec($bgc != '0x' ? $bgc : '0xFFFFFF');
$tc = hexdec($tc != '0x' ? $tc : '0x000000');
$lc = hexdec($lc != '0x' ? $lc : '0xFF00FF');
$image = @imagecreatetruecolor($total_code_width, $max_code_height + $top_margin);
imagefilledrectangle($image, 0, 0, $width, $height, $bgc);
$bg_color = imagecolorallocate($image, 0xFF & $bgc >> 0x10, 0xFF & ($bgc >> 0x8), 0xFF & $bgc);
$fg_color = imagecolorallocate($image, 0xFF & $tc >> 0x10, 0xFF & ($tc >> 0x8), 0xFF & $tc);

if (ANTI_ROBOT_IMAGE_FILTER_LINES == 'background') image_lines ($image, $lc, (ANTI_ROBOT_IMAGE_FILTER_NOISE=='true' || ANTI_ROBOT_IMAGE_FILTER_SCATTER=='true' || ANTI_ROBOT_IMAGE_FILTER_DISTORT=='true' ? 180 :100));

$pos_x = urand(2,($whitespace > 2 ? $whitespace : 2));
$top_margin = (int)($top_margin/2);
for ($i=0; $i < strlen($code); $i++) {
  $wspace = urand(0,$whitespace);
  if ($ttf) {
	      imagettftext($image, $font_size, $angle_char[$i], $pos_x, $max_code_height-$top_margin + urand(-$top_margin, $top_margin), $fg_color, DIR_FS_CATALOG . DIR_WS_IMAGES . 'fonts/' . ANTI_ROBOT_IMAGE_TTF, $code[$i]);
 } else {
      imagechar($image, $bitmap_font_size, $pos_x, rand(-$top_margin, $top_margin), $code[$i], $fg_color); 
 }
  $pos_x += ($ttf) ? $width_char[$i] + $wspace: imagefontwidth($font_size) + $wspace;
	$width -= ($whitespace - $wspace);
}
$resized_image = @imagecreatetruecolor($width, $height);
if (($image_hieght != 0) || ($image_width != 0))
    imagecopyresized($resized_image, $image, 0, 0, 0, 0, ($image_width) ? $image_width : $width, ($image_hieght) ? $image_hieght : $height, $total_code_width, $max_code_height + $top_margin);
else
    $resized_image = $image;
		
if (ANTI_ROBOT_IMAGE_FILTER_DISTORT!='none')
		image_distort ($resized_image);
if (ANTI_ROBOT_IMAGE_FILTER_GREYSCALE=='true')
    image_greyscale($resized_image);
if (ANTI_ROBOT_IMAGE_FILTER_NOISE=='true')
	image_noise($resized_image); 
if (ANTI_ROBOT_IMAGE_FILTER_SCATTER=='true')
    image_scatter($resized_image);
if (ANTI_ROBOT_IMAGE_FILTER_INTERLACE=='true')
    image_interlace($resized_image, $fg_color, $bg_color);		
if (ANTI_ROBOT_IMAGE_FILTER_LINES == 'foreground') 
		image_lines ($resized_image, $lc, 400);

header('Content-Type: image/png');
header('cache-control: no-store, no-cache, must-revalidate');
imagepng($resized_image);
imagedestroy($image);
imagedestroy($resized_image);

?>
