<?php

class BaseController extends Yaf_Controller_Abstract
{
    protected $_login_user = false;
    protected $_login_name = '';
    protected $_login_uid = 0;
    public $redis;

    public function init()
    {
    	   Yaf_Registry::set('uid',$this->_isUserID());
//         if ($uid==118||$uid==252) {
//         	Log::simpleappend('uri', $_SERVER['REQUEST_URI'].'_'.json_encode($_POST));
//         }
//         $this->db = Db::getInstance();
        $this->_view->weixinUrl = Yaf_Application::app()->getConfig()->get('weixin')->get('url');
        $this->_view->weixinapi = Yaf_Application::app()->getConfig()->get('weixinapi')->get('url');
//         $redis = new Redis(); 
//         $redis->connect('127.0.0.1',6379); 
//         $this->redis = $redis;

        Yaf_Registry::set('weixinurl',$this->_view->weixinUrl );
        $user = Yaf_Registry::get('user');
    }
    
    static public function _isUserID()
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
        
//         list($username, $pwd) = explode('|', $token);
        return $token;
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

    //获取用户信息
    public function isshow()
    {      
          $url = $this->_view->weixinapi."/myaccount/index";
          $uid = Yaf_Registry::get('uid');
          $arr = array('uid' =>$uid);
          $result = Util::httpRequest($url,$arr);
          return json_decode($result,true);

    }
//     public function fatal($message = '', $url = '/')
//     {
//         $this->_view->error = $message;
//         if (!$this->_view->isAjax()) {
//             throw new Exception($message);
//         }
//     }

    public function Alert($Str,$Typ="back",$TopWindow="",$Tim=100){
        echo "<script>".chr(10);
        if(!empty($Str)){
            echo "alert(\"Warning:\\n\\n{$Str}\\n\\n\");".chr(10);
        }
        echo "function _r_r_(){";
        $WinName=(!empty($TopWindow))?"top":"self";
        switch (StrToLower($Typ)){
            case "#":
                break;
            case "back":
                echo $WinName.".history.go(-1);".chr(10);
                break;
            case "reload":
                echo $WinName.".window.location.reload();".chr(10);
                break;
            case "close":
                echo "window.opener=null;window.close();".chr(10);
                break;
            case "function":
                echo "var _T=new function('return {$TopWindow}')();_T();".chr(10);
                break;
            //Die();
            Default:
                if($Typ!=""){
                    //echo "window.{$WinName}.location.href='{$Typ}';";
                    echo "window.{$WinName}.location=('{$Typ}');";
                }
        }
        echo "}".chr(10);
        //為防止Firefox不執行setTimeout
        echo "if(setTimeout(\"_r_r_()\",".$Tim.")==2){_r_r_();}";
        if($Tim==100){
            echo "_r_r_();".chr(10);
        }else{
            echo "setTimeout(\"_r_r_()\",".$Tim.");".chr(10);
        }
        echo "</script>".chr(10);
        Exit();
    }
}