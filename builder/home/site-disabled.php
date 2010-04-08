<?php
ob_start();
header('HTTP/1.1 503 Service Temporarily Unavailable');
header('Status: 503 Service Temporarily Unavailable');
header('Retry-After: 3600');

$customFileName = "site_disabled/custom-content.html";

if(file_exists($customFileName))
{
	$content = file_get_contents($customFileName);
}
else
{
	$content = '<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr">
    <head>
        <meta http-equiv="content-type" content="text/html; charset=utf-8" />
        <title>Site en cours de maintenance</title>
		<style type="text/css">
		body, p, h1 {
			color: black;
			font-family: Arial, Helvetica, Sans-serif;
		}
		body {
			background-color: #ddd;
			margin :0;
			padding: 0;
		}
		div.box {
			border-top: 2px solid silver;
			border-bottom: 2px solid silver;
			background-color: white;
			margin: 100px 0px;
			padding: 15px 100px;
		}
		</style>
	</head>
	<body>
		<div class="box">
			<h1>Ce site est en cours de maintenance.</h1>
			<p>Ce site a été désactivé car il est en cours de maintenance ; il sera rétabli dans les meilleurs délais.</p>
			<p>Nous nous excusons pour le désagrément encouru et vous prions de bien vouloir revenir nous voir un peu plus tard.</p>
			<p>Merci de votre compréhension.</p>
		</div>
	</body>
</html>';
}
echo($content);
