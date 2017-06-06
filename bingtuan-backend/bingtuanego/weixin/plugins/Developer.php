<?php
class DeveloperPlugin extends Yaf_Plugin_Abstract
{
 
    public function preDispatch(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
    {        
        $whiteList = array('login','wxauthorize','authlogin','qrcode','getuserinfo','lostpwd','agreement','reginfo','register','register','vcode','notify');
        $developer = Yaf_Registry::get('developer');
        $controller = strtolower($request->getControllerName());
        $action = $request->getActionName();
        if (!in_array($action, $whiteList)) {
            if (empty($_COOKIE['token'])) {
                header('Location: /login/login');
                exit;
            }
        }
    }
}