<?php

define("ROOT_PATH",  realpath(dirname(__FILE__) . '/../../'));
define("APP_PATH",  realpath(dirname(__FILE__) . '/../'));

$app  = new Yaf_Application(ROOT_PATH . "/conf/adm.ini");
$app->bootstrap()->run();


        $conf = Yaf_Application::app()->getConfig();
        Db::setDefaultConfig($conf->get('db'));


$fields = $this->db->desc( 'artron_company' );


print_r( $fields );

?>