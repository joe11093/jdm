<?php
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Content-Type: text/html;charset=ISO-8859-1");

ini_set("pcre.backtrack_limit", "50000000");

require_once(dirname(__FILE__) . "/classLoader.php");

$controller = new SearchController("http://www.jeuxdemots.org/rezo-dump.php");
echo $controller->process($_GET);
