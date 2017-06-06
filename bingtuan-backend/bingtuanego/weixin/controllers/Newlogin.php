<?php
class NewloginController extends BaseController
{
    /*
     * 登录
     */
    public function loginAction(){
        if(isset($_COOKIE['add_info'])){
            setcookie('add_info','',time()-1,'/');
        }
        if (isset($_SESSION['goods_info'])){
            $_SESSION['goods_info']='';
        }
        $appId = Yaf_Application::app()->getConfig()->get('weixin')->get('APPID');
            require_once("jssdkjt.php");
            $appid = Yaf_Application::app()->getConfig()->get('weixin')->get('APPID');
            $AppSecret = Yaf_Application::app()->getConfig()->get('weixin')->get('AppSecret');
            $jssdk = new JSSDKJT($appid,$AppSecret);
            $signPackage = $jssdk->GetSignPackage();
            $this->_view->signPackage = $signPackage;
            setcookie('token', false, 0, '/');
            $account=$this->getPost('user')?$this->getPost('user'):(isset($_COOKIE['account'])?$_COOKIE['account']:'');
            $password=$this->getPost('password')?$this->getPost('password'):(isset($_COOKIE['password'])?$_COOKIE['password']:'');
        $account = '15301340826';
	    $password = 'bingtuan123';
	  //设置kookie信息
            if(!isset($_COOKIE['account']) && !isset($_COOKIE['password'])){
                $this->setkie($account,$password);//不存在就保存
            }elseif (isset($_COOKIE['account'])){
                if($_COOKIE['account']!=$account){
                    $this->setkie($account,$password);//是否更改用户名
                }
            }
            $arr=array('account'=>$account,'password'=>$password);
            $url=$this->_view->weixinapi."/user/login ";
            $result = Util::httpRequest($url,$arr);
    
            $result = json_decode($result,true);
//             var_dump($url);
            $this->_view->data = $_COOKIE;
            if(1){
	            if($result['success'] == 0){
	               echo "<script>alert('".$result['error']."')</script>";
	            } else{
	                setcookie('receiver',$result['data']['user_type'],time()+3600*24,'/');//用户类型
	                setcookie('auditType',$result['data']['auditType'],time()+3600*24,'/');//用户状态
	                Yaf_Registry::set( 'uid', $result['data']['id'] );
	                Yaf_Registry::set( 'receiver', $result['data']['user_type'] );
	                Yaf_Registry::set( 'userName', $result['data']['userName'] );
	                Yaf_Registry::set( 'user', $result['data'] );
	                $this->_setCookie($result['data']['id'],$result['data']['company_id']);
                    if($result['data']['auditType'] == '3')
                    {
                          foreach($_COOKIE as $key=>$value){
                            if($key!='account'){
                                if($key!='password'){
                                    setCookie($key,"",time()-60,'/');
                                }
                            }
                          }
                          echo "<script>alert('您已被加入黑名单');</script>"; 
                    }elseif($result['data']['auditType'] == '1') {
                        if (!empty($result['data']['auditContent'])){
                            echo "<script>alert('您尚未通过审核,原因是:{$result['data']['auditContent']}');window.location.href='/register/reginfo?uid={$_COOKIE['uid']}';</script>";
                        }else{
                            echo "<script>alert('您尚未通过审核');window.location.href='/register/reginfo?uid={$_COOKIE['uid']}';</script>";
                        }
                        //header('Location: /register/reginfo');
                         exit;
                    }else{
                         //header('Location: /goods/list');
                         header('Location: /login/wxauthorize');
                         exit;
                    }
	           }
	       }
    }
    /*
     * 忘记密码
     */
    public function lostpwdAction(){
        if($this->isPost()){
            $url= $url=$this->_view->weixinapi."/user/lostpwd";
            $arr['account']=$this->getPost('account');
            $arr['vcode']=$this->getPost('vcode');
            $arr['password']=$this->getPost('password');
            $arr['repassword']=$this->getPost('repassword');
            $result=Util::httpRequest($url,$arr);
            $result= json_decode($result,true);
            $this->_view->data=$arr;
            if($result['success']==0){
                echo "<script>alert('".$result['error']."')</script>";
                $this->_view->data=$arr;
            }else{
                echo "<script>alert('密码更改成功')</script>";
                header('Location: /login/login');
                exit;
            }
        }
    }
    /*
     * 忘记短信验证码
     */
    public function vcodeAction(){
        $url= $url=$this->_view->weixinapi."/user/lostverify";
        $arr['account']=$this->getPost('phone');
        $result=Util::httpRequest($url,$arr);
        exit($result);
    }
    
    /**
     * 退出登陆
     * @return boolean
     */
    public function logoutAction()
    {
        setcookie('token', false, 0, '/');
        foreach($_COOKIE as $key=>$value){
            if($key!='account'){
                if($key!='password'){
                    setCookie($key,"",time()-60,'/');
                }
            }
        }
        echo "<script>alert('已退出')</script>";
        header('Location: /login/login');
        die;
    }
    
    /**
     * 设置Cookie
     * @param unknown $developer
     * @param string $keep
     */
    protected function _setCookie($uid,$cid, $keep = true)
    {
        $key = Yaf_Application::app()->getConfig()->get('cookie')->get('key');
        $token = sprintf('%s', $uid);
        $token = Util::strcode($token, $key, 'encode');
        $expired = $keep ? time() + 86400 * 30 : 0;
        setcookie('token', $token, $expired, '/');
        setcookie('uid', $uid, $expired, '/');
        setcookie('cid', $cid, $expired, '/');

    }
    /*
     * 微信用户静默授权去商品列表页保存用户openid
     */
    public function wxauthorizeAction(){
        $appid = Yaf_Application::app()->getConfig()->get('weixin')->get('APPID');
        $redirect_uri=urlencode(Yaf_Registry::get('weixinurl').'goods/list');
        $url="https://open.weixin.qq.com/connect/oauth2/authorize?appid=$appid&redirect_uri=$redirect_uri&response_type=code&scope=snsapi_base&state=STATE#wechat_redirect";
        //echo $url;
        header('Location:'.$url);
        exit;
    }
    /*
     * 获取用户的access_token
     */
    public function authloginAction(){
        $appid = Yaf_Application::app()->getConfig()->get('weixin')->get('APPID');
        $AppSecret = Yaf_Application::app()->getConfig()->get('weixin')->get('AppSecret');
        $code = $_GET["code"];  // 經使用者授權後回傳的code
        $get_token_url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid='.$appid.'&secret='.$AppSecret.'&code='.$code.'&grant_type=authorization_code';
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,$get_token_url);
        curl_setopt($ch,CURLOPT_HEADER,0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        $res = curl_exec($ch);
        curl_close($ch);
        $token_obj=json_decode($res,true);
        $user_token=$this->getuserinfo($token_obj);
        $user_token=json_encode($user_token);
        $_COOKIE['access_token']=$user_token;
        setcookie('access_token',$user_token,time()+3600,'/');
        if(!empty($_COOKIE['access_token'])){
               echo "<script>alert('登录成功')</script>";
               header('Location:/goods/list');
         }else{
             echo "<script>alert('登录失败')</script>";
             header('Location:/login/login');
         }
        exit;
    
    }
    /*
     * 根据openid和access_token查询用户信息
     */
    public function getuserinfo($token_obj){
        //根据openid和access_token查询用户信息
        $access_token = $token_obj['access_token'];
        $openid = $token_obj['openid'];
        $_COOKIE['openid']=$openid;
        setcookie('openid',$openid,time()+7200,'/');
        $get_user_info_url = 'https://api.weixin.qq.com/sns/userinfo?access_token='.$access_token.'&openid='.$openid.'&lang=zh_CN';
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,$get_user_info_url);
        curl_setopt($ch,CURLOPT_HEADER,0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        $res = curl_exec($ch);
        curl_close($ch);
        return json_decode($res,true);
    }
    /**
     * 二维码解密
     */
    public function qrcodeAction()
    {
       if(empty($_POST['code'])){
           exit(json_encode(array('code'=>0,'message'=>'扫码失败')));
       }
       $code=$this->getPost('code');
       $url=$this->_view->weixinapi."/user/warehouseCode";
       $arr=array('code'=>$code);
       $result = Util::httpRequest($url,$arr);
       $result = json_decode($result,true);
       if($result['success']==1){
           exit(json_encode(array('code'=>1,'message'=>$result['data'])));
       }else{
           exit(json_encode(array('code'=>0,'message'=>$result['data'])));
       }
      
    }
 /*
  * 保存用户用户信息
  */
    public function setkie($account,$password){
        $_COOKIE['account'] = $account;
        setcookie('account',$account,time()+3600*24,'/');
        $_COOKIE['password'] = $password;
        setcookie('password',$password,time()+3600*24,'/');
    }
}
