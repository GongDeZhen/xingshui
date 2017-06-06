<?php
date_default_timezone_set('PRC');
define('APPLICATION_PATH', dirname(__FILE__));

define("ROOT_PATH",  realpath(dirname(__FILE__) . '/../../'));
define("APP_PATH",  realpath(dirname(__FILE__) . '/../'));
define('VIEW_PATH', APP_PATH . '/views');
define('LOG_DIR', APP_PATH . '/public/LOG_DIR');
define('STATIC_URL', '/static');
define('PASSPORT_KEY', '?3@d#s$7^');
define('SSLCERT_PATH', APP_PATH . '/public/cert/apiclient_cert.pem');
define('SSLKEY_PATH', APP_PATH . '/public/cert/apiclient_key.pem');


$app  = new Yaf_Application(ROOT_PATH . "/conf/api.ini");

$app->bootstrap()->run();
?>
