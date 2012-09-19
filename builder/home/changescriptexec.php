<?php
ignore_user_abort(true);
define('PROJECT_HOME', dirname(realpath(__FILE__)));

set_time_limit(0);

require_once PROJECT_HOME . "/framework/Framework.php";
Framework::initialize();

RequestContext::getInstance()->setMode(RequestContext::BACKOFFICE_MODE);

$headers = array();
if (isset($_SERVER['HTTP_AUTHORIZATION']))
{
	$rawHeader = $_SERVER['HTTP_AUTHORIZATION'];
	if (strpos($rawHeader, 'OAuth') === 0)
	{
		foreach (explode(',', trim(substr($rawHeader, 5))) as $part)
		{
			$firstEqual = strpos($part, '=');
			$name = substr($part, 0, $firstEqual);
			if (strpos($name, 'oauth_') === 0)
			{
				$value = substr($part, $firstEqual+1);
				if (strlen($value) > 1 && $value[0] == '"' && $value[strlen($value)-1] == '"')
				{	
					$value = substr($value, 1, strlen($value)-2);
				}
				$headers[$name] = $value;
			}
		}
	}
}

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

list($name, $consumerSecret) = explode('#', file_get_contents(PROJECT_HOME . '/build/config/oauth/script/consumer.txt'));
if ($name !== $headers['oauth_consumer_key'])
{
	header("HTTP/1.1 401 Unauthorized");
	die("Invalid signature");
}

list($name, $tokenSecret) = explode('#', file_get_contents(PROJECT_HOME . '/build/config/oauth/script/token.txt'));
if ($name !== $headers['oauth_token'])
{
	header("HTTP/1.1 401 Unauthorized");
	die("Invalid signature");
}

if ($_SERVER["SERVER_PORT"] == 443)
{
	$protocol = 'https://';
}
else
{
	$protocol = 'http://';
}
$currentUrl = strpos($_SERVER['REQUEST_URI'], $protocol . $_SERVER['HTTP_HOST']) === 0 ? $_SERVER['REQUEST_URI'] : $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
$util = new \ZendOAuth\Http\Utility();

$mergedParams = f_web_HttpLink::flattenArray(array_merge($_POST, $headers));
if ($headers['oauth_signature'] !== \ZendOAuth\Http\Utility::urlEncode($util->sign($mergedParams, $headers['oauth_signature_method'], $consumerSecret, $tokenSecret, \Zend\Http\Request::METHOD_POST, $currentUrl)))
{
	header("HTTP/1.1 401 Unauthorized");
	die("Invalid signature");
}
if (isset($_POST['phpscript']) && (!isset($_POST['argv']) || is_array($_POST['argv'])))
{
	$scriptPath = PROJECT_HOME . '/' . $_POST['phpscript'];
	chdir(PROJECT_HOME);
	if (file_exists($scriptPath) && is_readable($scriptPath) && strrpos($scriptPath, '.php') === strlen($scriptPath) - 4)
	{
		$arguments = isset($_POST['argv']) ? $_POST['argv'] : array();
		Framework::info('HTTP script:' . $scriptPath);
		include_once $scriptPath;
		exit();
	}
}
header("HTTP/1.1 500 Internal Server Error");
die('Unable to execute [' . $_POST['phpscript'] . '].');