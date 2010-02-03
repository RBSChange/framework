<?php
/**
 * Project:     GOTCHA!: the PHP implementation of captcha.
 * File:        index.php
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

session_start();


error_reporting(E_ALL);

include_once('util.php');

$MESSAGE = '&nbsp;';
$CAPTCHA_IMAGE_URI = 'captcha_image.php';



if(isset($_POST['code'])){
	
	$text = isset($_SESSION[CAPTCHA_SESSION_KEY])? $_SESSION[CAPTCHA_SESSION_KEY] : NULL;
	
	if(!$p =trim($_POST['code'])){
		
		$MESSAGE = '<div style="color: #FF0000;"><strong>Error</strong>: no code submited!!</div>';
	}
	else if(!checkCaptchaCode($p)){
		
		$MESSAGE = '<div style="color: #FF0000;"><strong>Error</strong>: Invalid code submited!!</div><div>YOUR CODE : "<strong>'.htmlentities(stripslashes($p)).'</strong>"<br/> Code: "<strong>'.htmlentities(stripslashes($text)).'</strong>"</div>' ;
	}
	else{
		$MESSAGE = '<strong style="color: #00CC99">Correct!!!</strong><br/> CODE:  "<strong>'.stripslashes($_POST['code']).'</strong>"';
	}
	$_SESSION[CAPTCHA_SESSION_KEY] = NULL;
}

?>
<html>
<head>
<title>GOTCHA!</title>
</head>
<body>
<div><?php echo $MESSAGE; ?></div>
<div> 
  <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
    <div>Enter the code below:</div>
    <div style="width: 200px; height: 80px;"><img src="<?php echo $CAPTCHA_IMAGE_URI; ?>" id="gotcha-captcha" style="width: 230px; height: 60px"></div>
    <div style="margin: 10px;"> 
      <label for="code">Code: </label>
      <input type="text" name="code" id="code" />
    </div>
    <div> 
      <input type="submit" class="submit-button" name="CHECK" value="VERIFY" />
    </div>
    <div>if you can't read the image text<span><input type="submit" onClick="document.getElementById('gotcha-captcha').src +='?'+ Math.round(Math.random()*100000); return false;" name="reload-captcha" style="background-color: transparent; border:none; font-weight: bold; text-decoration: underline; padding: 0px ; margin: 0px;" value="click here" /></span>to load another one. </div>
  </form>
</div>
</body>
</html>