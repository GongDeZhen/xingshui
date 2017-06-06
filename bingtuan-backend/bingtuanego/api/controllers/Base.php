<?php

class BaseController extends Yaf_Controller_Abstract
{
    protected $_login_user = false;
    protected $_login_name = '';
    protected $_login_uid = 0;
    public $redis;

    public function init()
    {
        $key = Yaf_Application::app()->getConfig()->get('cookie')->get('key');
        $cookieUid = Util::strcode($_COOKIE['uid'], $key, 'decode');
        $this->_login_uid =  isset($cookieUid) ? $cookieUid : Yaf_Registry::get('uid');
//         if ($uid==118||$uid==252) {
//         	Log::simpleappend('uri', $_SERVER['REQUEST_URI'].'_'.json_encode($_POST));
//         }
        $this->db = Db::getInstance();

//         $redis = new Redis(); 
//         $redis->connect('127.0.0.1',6379); 
//         $this->redis = $redis;

        $user = Yaf_Registry::get('user');
        $status = Yaf_Registry::get('auditType');
        //var_dump($status); die;
        /*if(isset($user) && is_numeric($status)){
            $currentUser = Service::getInstance('user')->getUserById($uid);
            if($currentUser['auditType'] != $status){
                Yaf_Registry::set('user', '');
                Yaf_Registry::set('uid', '');
                Yaf_Registry::set('isLogin', false);
                Yaf_Registry::set('auditType', '');
                setcookie('pwd', '', 0, '/');
                setcookie('uid', '', 0, '/');
                //$this->respon(0, '审核状态已变更，请重新登录!');
            }
            //记录登录时间
            $reIP=$_SERVER["REMOTE_ADDR"];
            $this->addLoginTime($user['id'], $reIP);
        }*/

        Bootstrap::_initUser();
//         $domain = $this->getPost("domain","haojindu.com");
//         $key = Yaf_Application::app()->getConfig()->get('hash')->get( $domain );

//         $t = (int) $this->getPost("t" , 0 );
//         $hash =  $this->getPost( "hash" );

//         $md5hash = md5( $key . $domain . $t);

//         if ( $md5hash != $hash && Yaf_Application::app()->getConfig()->get('dev')->get('debug') == 0 )
//         {
            //$this->respon( 0 , "Access Denied : " . $md5hash );
//         }

//         Yaf_Registry::set( 'domain', $domain );

    }

    public function isPost()
    {
        return $this->getRequest()->isPost();
    }
    
    public function getParam( $name,$default = '' )
    {
        $value =  $this->getRequest()->getParam($name);

        if ($value === null) {
            $value = $default;
        }

        return $value;
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
        $bug = $this->getQuery( 'debug',0 );
        
        if ( $bug == 1 )
        {
            return $this->getQuery( $name, $default );
        }
        
        $value = $this->getRequest()->getPost($name);
        if ($value === null) {
            $value = $default;
        }

        return $value;
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

    public function isLogin()
    {
        return $this->_login_uid ? true : false;
    }
    
    public function error($error = '', $errno = 0)
    {

    }

    public function flash($url = '/', $message = '', $second = 2)
    {

    }

    public function fatal($message = '', $url = '/')
    {
        $this->_view->error = $message;
        if (!$this->_view->isAjax()) {
            throw new Exception($message);
        }
    }

    /**
     * 记录用户登录时间
     * @param $uid
     */
    public function addLoginTime($uid, $ip){
        $data = Service::getInstance('user')->getLoginTime($uid);
        if($data){
            $time = $data['time'];
            $id = $data['id'];
            if(time() - $time > 3600 * 24){
                Service::getInstance('user')->saveLoginTime($id, $ip);
            }
        }
    }
}