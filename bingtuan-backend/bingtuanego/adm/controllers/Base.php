<?php

class BaseController extends Yaf_Controller_Abstract
{
    public $db;
    public $mongo;

    protected $_debug;

    protected $_developer;
    protected $_devid;
    
    protected $_service;

    protected $companyIds;

    protected $showAll = true;

    public function init()
    {
        $this->db = Db::getInstance();
//        $this->mongo = Mon::getInstance();
        $request = $this->getRequest();
        $this->_view->controller = $request->controller;
        $this->_view->action = $request->action;

        $developer = Yaf_Registry::get('developer');
        $this->companyIds = explode(',', $developer['company_id']);
        if(count($this->companyIds) > 1){
            $this->showAll = false;
        }

        if ($developer) {
            $this->_developer = $developer;
            $this->_devid = $developer['id'];
            $this->_view->developer = $developer;
            $this->_view->rbac = Service::getInstance('rbac')->getUserRbac();
        }
        
        $this->_view->_module = 'dev';
        $this->_view->m = $this->getQuery( "m", '');
		$this->_view->_moduleName = '后台管理';

        $this->_view->_controllerName = $this->getRequest()->getControllerName();
    }


    public function add_log($uid,$action,$action_id=0){
        switch($action){

            case '/warehouse/warehouselist':
                $goods['action'] = '经销商列表';
                $goods['action_id'] = $uid;
                break;
            case 'warehouse/add':
                $goods['action'] = '添加经销商';
                $goods['action_id'] = $uid;

                break;
        }
        $goods['log_type'] = 1;

        $goods['uid'] = $uid;
        $goods['create_time'] = time();

        Service::getInstance('systemlog')->addLog($goods);
    }

    public function isPost()
    {
        return $this->getRequest()->isPost();
    }
    
    public function getParam($name)
    {
        return $this->getRequest()->getParam($name);
    }
    
    public function getQuery($name, $default = '')
    {
        $value = $this->getRequest()->getQuery($name);
        if ($value === null) {
            $value = $default;
        }

        return $value;
    }

    public function getPost($name, $default = '')
    {
        $value = $this->getRequest()->getPost($name);
        if ($value === null) {
            $value = $default;
        }

        return $value;
    }

    /**
     * 得到GET数据
     * @param  string $name [description]
     * @return [type]       [description]
     */
    public function getGet($name = '')
    {
        if(!$name) {
            return $_GET;
        }

        return $_GET[$name];
    }

    public function setResponse($name, $value = '')
    {
        if (is_array($name)) {
            $this->getResponse()->data = $name;
            return;
        }

        $this->getResponse()->data[$name] = $value;
    }
    
    public function setError($code = 200, $error = '')
    {
        $this->getResponse()->code = $code;
        $this->getResponse()->error = $error;
    }

    public function error($error, $no = 0)
    {
        $this->_view->error = $error;
    }

    public function message($message = '')
    {
        $this->_view->message = $message ? $message : '操作完成';
        $this->display('../sys/message');
    }

    public function flash($url = '/', $message = '', $second = 2)
    {
        if ($second == 0) {
            $this->redirect($url);
            return;
        }

        $this->_view->url = $url;
        $this->_view->message = $message ? $message : '操作成功';
        $this->_view->second = $second;

        $this->display('../sys/flash');
    }

    public function fatal($message = '', $url = '/')
    {
        $this->_view->error = $message;
        if ($this->_view->isAjax()) {
            throw new Exception($message);
        } else {
            $this->display('../sys/fatal');
        }

        exit();
    }
    
    public function respon( $success = 0 , $res  )
    {
    
        $result['success'] = $success;
    
        if( $success )
        {
            $result['data'] = $res;
        }
        else
        {
            $result['error'] = $res;
        }
    
        //         if ( Yaf_Application::app()->getConfig()->get('dev')->get('debug') == 1 )
        //         {
        //             //Util::dump( $result );
        //         }
        header("Content-Type: application/json; charset=utf-8");
        exit( json_encode( $result ) );
    }

    /**
     * 得到图片路径
     * @param  [type] $imageUrl [description]
     * @return [type]           [description]
     */
    public static function getImagePath($imageUrl)
    {
        if(empty($imageUrl)) {
            return ;
        }

        return '/file/' . $imageUrl[0] . $imageUrl[1] . '/' . $imageUrl[2] . $imageUrl[3] . '/' . $imageUrl . '.jpg';
    }


    /**
     * 获取登录的用户信息
     */
    public function getUserInfo(){
        $key = Yaf_Application::app()->getConfig()->get('cookie')->get('key');
        $str = Util::strcode($_COOKIE['token'], $key, 'decode');
        $result = explode("|",$str);
        $user_info = Service::getInstance('Developer')->getRegisterInfoByEmail($result[0]);
        return $user_info;
    }
}