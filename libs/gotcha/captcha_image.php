<?php

/**
 * Project:     GOTCHA!: the PHP implementation of captcha.
 * File:        gotcha_image.php
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * For questions, help, comments, discussion, etc., please write to: sol2ray at gmail dot com
 *
 * @link http://phpbtree.com/captcha/
 * @copyright 2003-2005 Smart Friend Network, Inc.
 * @author Sol Toure <sol2ray at gmail dot com>
 * @version alpha 0.01;
 */

error_reporting(E_ALL);
session_start();
//Please modify this file to match your environment.

include_once('util.php');
include_once('gotcha.php');


//Generate a random text.
//Feel free to replace this with a custom solution.
$t =  md5(uniqid(rand(), 1));


//You can eliminate the above variable ($CAPTCHA_SESSION_KEY) and use
// the key string literal directly below.

$_SESSION[CAPTCHA_SESSION_KEY] =  $text = substr($t, rand(0, (strlen($t)-6)), rand(3,6));
$image_width = 230;
$image_height = 60;
$font_size = 30;
$font_depth = 5; //this is the size of shadow behind the character creating the 3d effect.


$img = new GotchaPng($image_width, $image_height);


if($img->create()){

	//fill the background color.
	$img->apply(new GradientEffect());
	//Apply the Grid.
	$img->apply(new GridEffect(2));
	//Add dots to the background
	$img->apply(new DotEffect());
	//Add the text.
	$t  = new TextEffect($text, $font_size, $font_depth);
	$t->addFont(f_util_FileUtils::buildAbsolutePath(FRAMEWORK_HOME, 'libs', 'gotcha', 'SFTransRoboticsExtended.ttf'));
	$t->addFont(f_util_FileUtils::buildAbsolutePath(FRAMEWORK_HOME, 'libs', 'gotcha', 'arialbd.ttf'));
	// repeat the process for as much fonts as you want. Actually, the more the better.
	// A font type will be randomly selected for each character in the text code.
	$img->apply($t);
	//Add more dots
	$img->apply(new DotEffect());
	//Output the image.
	$img->render();
}
?>