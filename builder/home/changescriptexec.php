<?php
ignore_user_abort(true);
define('WEBEDIT_HOME', dirname(realpath(__FILE__)));
set_time_limit(0);
session_start();
if (!isset($_POST['noframework']) || $_POST['noframework'] !== 'true')
{
	require_once WEBEDIT_HOME . "/framework/Framework.php";
	RequestContext::getInstance()->setMode(RequestContext::BACKOFFICE_MODE);
}
else
{
	require_once WEBEDIT_HOME . "/framework/f_web/oauth/Request.class.php";
	require_once WEBEDIT_HOME . "/framework/f_web/oauth/Token.class.php";
	require_once WEBEDIT_HOME . "/framework/f_web/oauth/Consumer.class.php";
	require_once WEBEDIT_HOME . "/framework/f_web/oauth/Signature.class.php";
	require_once WEBEDIT_HOME . "/framework/util/StringUtils.class.php";
	require_once WEBEDIT_HOME . "/framework/util/FileUtils.class.php";
	require_once WEBEDIT_HOME . "/framework/f_web/http/Link.class.php";
	require_once WEBEDIT_HOME . "/framework/f_web/http/HttpLink.class.php";
}
$headers = f_web_oauth_Util::parseOauthAutorizationHeader();
if (!isset($headers['oauth_signature']) || !isset($headers['oauth_consumer_key']) || !isset($headers['oauth_token']) || !isset($headers['oauth_timestamp']))
{
	header("HTTP/1.1 401 Unauthorized");
	die("Invalid signature");
}
if (abs(time()-intval($headers['oauth_timestamp'])) > 60)
{
	header("HTTP/1.1 401 Unauthorized");
	die("Invalid Timestamp");
}
list($name, $secret) = explode('#', file_get_contents(WEBEDIT_HOME . '/build/config/oauth/script/consumer.txt'));
if ($name !== $headers['oauth_consumer_key'])
{
	header("HTTP/1.1 401 Unauthorized");
	die("Invalid signature");
}
$consumer = new f_web_oauth_Consumer($name, $secret);
list($name, $secret) = explode('#', file_get_contents(WEBEDIT_HOME . '/build/config/oauth/script/token.txt'));
if ($name !== $headers['oauth_token'])
{
	header("HTTP/1.1 401 Unauthorized");
	die("Invalid signature");
}
$token = new f_web_oauth_Token($name, $secret);
if ($_SERVER["SERVER_PORT"] == 443)
{
	$protocol = 'https://';
}
else
{
	$protocol = 'http://';
}
$currentUrl = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
$request = new f_web_oauth_Request($currentUrl, $consumer, f_web_oauth_Request::METHOD_POST);
$request->setToken($token);

f_web_oauth_Util::setParametersFromArray($request, $_POST);
f_web_oauth_Util::setParametersFromArray($request, $headers);

if ($headers['oauth_signature'] !== $request->getSignature())
{
	header("HTTP/1.1 401 Unauthorized");
	die("Invalid signature");
}
if (isset($_POST['phpscript']) && (!isset($_POST['argv']) || is_array($_POST['argv'])))
{
	$scriptPath = WEBEDIT_HOME . '/' . $_POST['phpscript'];
	if (defined('FRAMEWORK_HOME'))
	{
		if (Framework::isInfoEnabled())
		{
			Framework::info("execute $scriptPath with (" . (isset($_POST['argv']) ? count($_POST['argv']) : 'null') . " args)");
		}
	}
	chdir(WEBEDIT_HOME);
	
	if (file_exists($scriptPath) && is_readable($scriptPath) && strrpos($scriptPath, '.php') === strlen($scriptPath) - 4)
	{
		include_once $scriptPath;
		exit();
	}
}
header("HTTP/1.1 500 Internal Server Error");
die('Unable to execute [' . $_POST['phpscript'] . '].');