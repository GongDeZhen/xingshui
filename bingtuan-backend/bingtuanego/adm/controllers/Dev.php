<?php

class DevController extends BaseController
{
    public function init()
    {
        parent::init();
        
        $this->_view->_module = 'dev';        
        $this->_view->_moduleName = '后台管理';
    }

    public function indexAction()
    {
        $this->_view->developer= Yaf_Registry::get('developer');
    }
}

