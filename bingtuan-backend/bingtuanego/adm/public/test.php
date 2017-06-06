<?php

define("ROOT_PATH",  realpath(dirname(__FILE__) . '/../../'));
define("APP_PATH",  realpath(dirname(__FILE__) . '/../'));
//$app  = new Yaf_Application(ROOT_PATH . "/conf/adm.ini");
//$app->getDispatcher()->dispatch(new Yaf_Request_Simple("CLI", "adm", "distributor", "addGoods", array("para" => 2)));


//$app = new Yaf_Application(ROOT_PATH . "/conf/adm.ini");
//$app->getDispatcher()->dispatch(new Yaf_Request_Simple());
//$app->bootstrap()->run();

//$request = new Yaf_Request_Simple("CLI", "addGoods", "Controller", "distributor", array("para" => 2));
//print_r($request);
//echo ROOT_PATH;exit;
$app = new Yaf_Application(ROOT_PATH . "/conf/adm.ini");
$conf = Yaf_Application::app()->getConfig();
Db::setDefaultConfig($conf->get('db'));
//$app->bootstrap()->run();
$app->getDispatcher()->dispatch(new Yaf_Request_Simple());