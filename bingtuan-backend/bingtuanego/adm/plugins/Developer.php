<?php
class DeveloperPlugin extends Yaf_Plugin_Abstract
{
 
    public function preDispatch(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
    {        
        $whiteList = array(
            'admin' => 'jason@suxin8.com',
            'developer' => array('signin', 'signout', 'errors','alipaynotify','unifiedgoods')
        );
        $developer = Yaf_Registry::get('developer');
        $controller = strtolower($request->getControllerName());
        $action = $request->getActionName();
        $this->_view->controller = $controller;
        $this->_view->admin = $whiteList['admin'];
        $this->_view->action = $action;
        if (!in_array($action,$whiteList['developer'])) {
            foreach ($whiteList as $c => $actions) {
                if ($actions == $developer['email']) {
                    return;
                }
            }
            if ($developer) {
                $rbac = '';
                $rbac = Service::getInstance('rbac')->getUserRbac();
                $controller_action = array( // 白名单
                    'dev_index',
                    $controller.'_getcitylist',
                    $controller.'_getarealist',
                    $controller.'_getwarehouselist',
                    $controller.'_uploadimages',
                    $controller.'_alipaynotify'
                );
                if (in_array($controller.'_'.$action, $controller_action) || in_array($controller.'_'.$action, $rbac['action'])) {
                    $this->_view->rbac = $rbac;
                    return;
                } else {
                    header('Location: /errors/errors');
                    exit;
                }
            } else {
                header('Location: /developer/signin');
                exit;
            }
        }
    }
}