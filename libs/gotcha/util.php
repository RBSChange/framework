<?php
function checkCaptchaCode($code=null){
	return (isset($_SESSION['CHANGE_CAPTCHA']) && (strcasecmp($code, $_SESSION['CHANGE_CAPTCHA'])==0));
}