<?php
header("Content-type:text/html;charset=utf-8");
date_default_timezone_set('PRC');
define("ROOT_PATH",  realpath(dirname(__FILE__) . '/../../'));
define("APP_PATH",  realpath(dirname(__FILE__) . '/../'));
define('VIEW_PATH', APP_PATH . '/views');
define('STATIC_URL', '/static');
define('PASSPORT_KEY', '?3@d#s$7^');
define('APP_NAME','冰团e购');
session_start();
$app  = new Yaf_Application(ROOT_PATH . "/conf/weixin.ini");
$app->bootstrap()->run();
?>
