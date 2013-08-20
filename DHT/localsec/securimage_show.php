<?php
require_once dirname(__FILE__) . '/securimage.php';

$switch_type = rand(1,6);
switch($switch_type)
{
	case 1:
		$bg='backgrounds/yxm_bg_120-1.gif';
		$ttf_file='AHGBold.ttf';
		$captcha_type=Securimage::SI_CAPTCHA_STRING;
		$code_length=5;
		break;
	case 2:
		$bg='backgrounds/yxm_bg_120-1.gif';
		$ttf_file='AHGBold.ttf';
		$captcha_type=Securimage::SI_CAPTCHA_MATHEMATIC;
		break;
	case 3:
		$bg='backgrounds/yxm_bg_120-2.gif';
		$ttf_file='AHGBold.ttf';
		$captcha_type=Securimage::SI_CAPTCHA_STRING;
		$code_length=5;
		break;
	case 4:	
		$bg='backgrounds/yxm_bg_120-2.gif';
		$ttf_file='AHGBold.ttf';
		$captcha_type=Securimage::SI_CAPTCHA_MATHEMATIC;
		break;
	case 5:
		$bg='backgrounds/yxm_bg_120-3.gif';
		$ttf_file='AHGBold.ttf';
		$captcha_type=Securimage::SI_CAPTCHA_STRING;
		$code_length=5;
		break;
	case 6:
		$bg='backgrounds/yxm_bg_120-3.gif';
		$ttf_file='AHGBold.ttf';
		$captcha_type=Securimage::SI_CAPTCHA_MATHEMATIC;
		break;
	default:
		$bg='backgrounds/yxm_bg_120-1.gif';
		$captcha_type=Securimage::SI_CAPTCHA_STRING;
		$ttf_file='AHGBold.ttf';
		$code_length=5;
		break;
}

$img = new securimage();
$img->switch_type     = $switch_type;
$img->ttf_file        = $ttf_file;
$img->captcha_type    = $captcha_type; // show a simple math problem instead of text
$img->image_height    = 120;                                // width in pixels of the image
$img->image_width     = 300;          // a good formula for image size
$img->code_length = $code_length;
$img->perturbation    = 0.2;                               // 1.0 = high distortion, higher numbers = more distortion
$img->num_lines       = 0; 
$img->text_color      = new Securimage_Color("#000");   // captcha text color

$img->show($bg); 
?>
