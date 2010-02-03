<?php
define('CAPTCHA_SESSION_KEY', 'G0TCHA_CAPTCHA');

function checkCaptchaCode($code=null){
	return (isset($_SESSION[CAPTCHA_SESSION_KEY]) && (strcasecmp($code, $_SESSION[CAPTCHA_SESSION_KEY])==0));
}


?>