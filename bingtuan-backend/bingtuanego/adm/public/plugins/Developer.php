<?php

class DeveloperPlugin extends Yaf_Plugin_Abstract
{
 
    public function preDispatch(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response) {        

        $whiteList = array(
            'index' => '*',
            'doc' => '*',
            'sdk' => '*',
            'developer' => array('signin', 'signup', 'password', 'lost'),
        );

        $controller = strtolower($request->getControllerName());
        $action = $request->getActionName();

        foreach ($whiteList as $c => $actions) {
            if ($c == $controller && $actions == '*') {
                return;
            } else if ($c == $controller) {
                if (in_array($action, $actions)) {
                    return;
                }

                break;
            }
        }

        $developer = Yaf_Registry::get('developer');
        if (!$developer) {
        	header('Location: /developer/signin');
            exit;
        }
    }
}