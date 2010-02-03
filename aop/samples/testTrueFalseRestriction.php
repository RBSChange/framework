<?php
define("WEBEDIT_HOME", realpath("."));
require_once(WEBEDIT_HOME."/framework/Framework.php");

$provider = f_persistentdocument_PersistentProvider::getInstance();
var_export($provider->createQuery("modules_website/website")->add(Restrictions::false("alwaysappendtitle"))->setProjection(Projections::property('id'))->find());
echo "\n";
var_export($provider->createQuery("modules_website/website")->add(Restrictions::true("alwaysappendtitle"))->setProjection(Projections::property('id'))->find());
echo "\n";