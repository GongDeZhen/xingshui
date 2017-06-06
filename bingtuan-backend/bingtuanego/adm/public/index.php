<?php
date_default_timezone_set('PRC');
//error_reporting(E_ALL);
header("Content-type:text/html;charset=utf-8");
define("ROOT_PATH",  realpath(dirname(__FILE__) . '/../../'));
define("APP_PATH",  realpath(dirname(__FILE__) . '/../'));
define("DATA_PATH",  '/web/data');
define('SSLCERT_PATH', APP_PATH . '/public/cert/apiclient_cert.pem');
define('SSLKEY_PATH', APP_PATH . '/public/cert/apiclient_key.pem');

//微信公众号证书
define('SSLCERT_PATH_WEIXIN', APP_PATH . '/public/wcert/apiclient_cert.pem');
define('SSLKEY_PATH_WEIXIN', APP_PATH . '/public/wcert/apiclient_key.pem');
//微信公众号证书

//define('SSLCERT_PATH', APP_PATH . '/../api/public/cert/apiclient_cert.pem');
//define('SSLKEY_PATH', APP_PATH . '/../api/public/cert/apiclient_key.pem');

error_reporting(E_ERROR);
$app  = new Yaf_Application(ROOT_PATH . "/conf/adm.ini");
$app->bootstrap()->run();
