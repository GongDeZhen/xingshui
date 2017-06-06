<?php

class Bootstrap extends Yaf_Bootstrap_Abstract
{

    public function _initEnv()
    {
        date_default_timezone_set('Asia/Shanghai');
    }

    public function _initDb()
    {
        $conf = Yaf_Application::app()->getConfig();
        Db::setDefaultConfig($conf->get('db'));
    }
    
    public function _initConfig()
    {
        $conf = Yaf_Application::app()->getConfig();
        define('AP_APPID', $conf->get('app')->get('APPID'));
        define('AP_Mch_id', $conf->get('app')->get('Mch_id'));
        define('AP_KEY', $conf->get('app')->get('KEY'));
        define('AP_NotifyUrl', $conf->get('app')->get('NotifyUrl'));
    }

    static public function _initUser()
    {

        Yaf_Registry::set( 'isLogin', false );

        if (empty($_COOKIE['uid'])) {
            return false;
        }
        
        Service::getInstance('user')->checkUser();
        if ( !Yaf_Registry::get( 'uid' ) ) return false;

        Yaf_Registry::set( 'isLogin', true );
        Yaf_Registry::set( 'uid', Yaf_Registry::get( 'uid' ) );
    }


	public function _initRoute(Yaf_Dispatcher $dispatcher)
	{

	}
	

    public function _initView(Yaf_Dispatcher $dispatcher)
    {
        $uri = $dispatcher->getRequest()->getRequestUri();
        $view = new StupidView(APP_PATH . '/views');
        $dispatcher->setView($view);
    }
}
