<?php
class Bootstrap extends Yaf_Bootstrap_Abstract
{
    public function _initConfig() {
        //把配置保存起来
        $arrConfig = Yaf_Application::app()->getConfig();
        Yaf_Registry::set('config', $arrConfig);
    }

    public function _initPlugin(Yaf_Dispatcher $dispatcher) {
        //注册一个插件
        $objSamplePlugin = new DeveloperPlugin();
        $dispatcher->registerPlugin($objSamplePlugin);
    }

    public function _initRoute(Yaf_Dispatcher $dispatcher) {
        //在这里注册自己的路由协议,默认使用简单路由
    }

    public function _initView(Yaf_Dispatcher $dispatcher) {
        //在这里注册自己的view控制器，例如smarty,firekylin
    }
             
    public function _initwConfig()  
    {
        $conf = Yaf_Application::app()->getConfig();
        define('APPID', $conf->get('weixin')->get('APPID'));
        define('AppSecret', $conf->get('weixin')->get('AppSecret'));
        define('MCHID', $conf->get('weixin')->get('MCHID'));
        define('KEY', $conf->get('weixin')->get('KEY'));
    }
}
