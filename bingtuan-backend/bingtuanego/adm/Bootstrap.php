<?php

class Bootstrap extends Yaf_Bootstrap_Abstract
{
    public function _initEnv(Yaf_Dispatcher $dispatcher)
    {
        date_default_timezone_set('Asia/Shanghai');
    }

    public function _initView(Yaf_Dispatcher $dispatcher)
    {
        $uri = $dispatcher->getRequest()->getRequestUri();
        $view = new StupidView(APP_PATH . '/views');
        if ($uri == '/' || $uri == '/index.php') {
            //
        } else {        
            $view->setLayout('dev.layout');
        }

        $dispatcher->setView($view);
    }

    public function _initConfig()
    {
        $conf = Yaf_Application::app()->getConfig();  
        define('AP_APPID', $conf->get('app')->get('APPID'));
        define('AP_Mch_id', $conf->get('app')->get('Mch_id'));
        define('WX_Mch_id', $conf->get('weixin')->get('Mch_id'));
        define('WX_app_id', $conf->get('weixin')->get('APPID'));
        define('WX_CERT', $conf->get('weixin')->get('CERT'));
        define('WX_KEYPEM', $conf->get('weixin')->get('KEYPEM'));
        define('AP_KEY', $conf->get('app')->get('KEY'));
        define('AP_NotifyUrl', $conf->get('app')->get('NotifyUrl'));
        define('ADM_NotifyUrl',$conf->get('adm')->get('NotifyUrl'));

    }

    public function _initDb()
    {
        $conf = Yaf_Application::app()->getConfig();
        Db::setDefaultConfig($conf->get('db'));
    }

//     public function _initUmeng()
//     {
//         $conf = Yaf_Application::app()->getConfig();
//         umeng_Push::setDefaultConfig($conf->get('umeng'));
//     }


    public function _initDeveloper()
    {
        $token = empty($_COOKIE['token']) ? '' : $_COOKIE['token'];
        $key = Yaf_Application::app()->getConfig()->get('cookie')->get('key');
        
        if (!$token) {
            return false;
        }

        $token = Util::strcode($token, $key, 'decode');
        if (!$token) {
            return false;
        }

        list($username, $pwd) = explode('|', $token);
        $developer = Service::getInstance('developers')->getDeveloperByEmail($username);
        if ($pwd !== $developer['pwd']) {
            return false;
        }
        Yaf_Registry::set('developer', $developer);
    }

    public function _initPlugins(Yaf_Dispatcher $dispatcher)
    {
        $developerPlugin = new DeveloperPlugin();
        $dispatcher->registerPlugin($developerPlugin);
    }
}
