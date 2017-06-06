<?php
class OptionsetController extends BaseController
{
    /*
     * 设置个人选项列表
     */
    public function setlistAction()
    {
       $auditType=empty($_COOKIE['auditType'])?0:$_COOKIE['auditType'];//用户状态
       $url= $this->_view->weixinapi."myaccount/personal ";
       $uid = Yaf_Registry::get('uid');
       $arr=array('uid'=>$uid);
       $result=Util::httpRequest($url,$arr);
       $result= json_decode($result,true);
       $this->_view->info=$result['data'];
       $this->_view->auditType =$auditType;
       $this->_view->title ="个人设置";         
       $jssdk = new Jssdk();
       $appId = Yaf_Application::app()->getConfig()->get('weixin')->get('APPID');
       $appSecret = Yaf_Application::app()->getConfig()->get('weixin')->get('AppSecret');
       $jssdk->getInstance($appId, $appSecret);
       $signPackage = $jssdk->GetSignPackage();
       $this->_view->signPackage = $signPackage;
    }
    /*
     * 设置密码
     */
    public function setpassAction()
    {
        $url= $this->_view->weixinapi."myaccount/editPersonalPwd  ";
        $oldpwd=isset($_POST['oldpwd'])?$_POST['oldpwd']:'';
        $newpwd=isset($_POST['newpwd'])?$_POST['newpwd']:'';
        $rnewpwd=isset($_POST['rnewpwd'])?$_POST['rnewpwd']:'';
        $uid = Yaf_Registry::get('uid');
        $arr=array(
            'uid'=>$uid,
            'oldpwd'=>$oldpwd,
            'newpwd'=>$newpwd,
            'rnewpwd'=>$rnewpwd
        );
        $result=Util::httpRequest($url,$arr);
        $result= json_decode($result,true);
        if($this->isPost('sub')){
	        if($result["success"]==1){
                $_COOKIE['password']=$newpwd;
                setcookie('password',$newpwd,time()+3600*24,'/');
	            echo "<script>alert('修改成功');window.location.href='/Login/login';</script>";
	        }else{
	            echo "<script>alert('".$result["error"]."');</script>";
	        }
        }
        //$this->_view->info=$result['data'];
        $this->_view->title ="设置密码";
    }
    /*
     * 设置电话
     */
    public function setphonAction()
    {
          $url= $this->_view->weixinapi."/editPersonalPhone";
          $account=isset($_POST['phone'])?$_POST['phone']:'';
          $vcode=isset($_POST['vcode'])?$_POST['vcode']:'';
          $uid = Yaf_Registry::get('uid');
          $arr=array(
              'uid'=>$uid,
              'account'=>$account,
              'vcode'=>$vcode
          );
          $result=Util::httpRequest($url,$arr);
          $result= json_decode($result,true);
          if($this->isPost('sub')){
	          if($result["success"]==1){
	              echo "<script>alert('修改成功');window.location.href='/optionset/setlist';</script>";
	          }else{
	            echo "<script>alert('".$result["error"]."');</script>";
	          }
            $this->_view->account=$account;
          }
    }
    /*
     * 设置姓名
     */
    public function setnameAction()
    {
        $url= $this->_view->weixinapi."myaccount/editPersonalName";
        $name=isset($_GET['name'])?$_GET['name']:'';
        $userName=isset($_POST['userName'])?$_POST['userName']:'';
        if($userName=="" && $this->isPost()){
            echo "<script>alert('用户名名称不能为空')</script>";
        }
        $uid = Yaf_Registry::get('uid');
        $arr=array(
            'uid'=>$uid,
            'user_name'=>$userName
        );
        if(empty($name)){
        	$name=$userName;
        }
        $result=Util::httpRequest($url,$arr);
        $result= json_decode($result,true);
   		if($this->isPost('sub')){
   			if($result["success"]==1){
   				echo "<script>alert('修改成功');window.location.href='/optionset/setlist';</script>";
   			}else{
   				echo "<script>alert('".$result["error"]."');</script>";
   			}
       }
        $this->_view->title ="编辑姓名";
        $this->_view->name =$name;
    }
    /*
     * 全部头像
     */
    public function setphotoAction()
    {
        $url=$this->_view->weixinapi."/myaccount/editPersonalAvatars ";
        $uid = Yaf_Registry::get('uid');
        $arr=array(
            'uid'=>$uid,
            'file'=>''
        );
        $result=Util::httpRequest($url,$arr);
        $result= json_decode($result,true);
        if($result["success"]==1){
            echo "<script>alert('修改成功');window.location.href='/optionset/setlist';</script>";
        }else{
            echo "<script>alert('".$result["error"]."');</script>";
        }
        $this->_view->title ="修改头像";
    }
    /*
     * 短信验证码
     */
    public function vcodeAction(){
        $url=$this->_view->weixinapi."/user/verify";
        $arr['account']=$this->getPost('phone');
        $result=Util::httpRequest($url,$arr);
        exit($result);
    }
    //上传头像
    public function dowuploadAction()
    { 
       //require_once './jssdkjt.php';
      // $appId = "wxe6e1cc76af59d8a1";
     //  $appSecret = "6c9c754baa9e00310abdc5f6c538a8e1";
      // $jssdk = new JSSDKJT ($appId,$appSecret);
   //    $accessToken = $jssdk->get_accesstoken();
       $jssdk = new Jssdk(); 
       $appId = Yaf_Application::app()->getConfig()->get('weixin')->get('APPID');
       $appSecret = Yaf_Application::app()->getConfig()->get('weixin')->get('AppSecret');
       $jssdk->getInstance($appId, $appSecret);
       $media_id = $this->getPost("serverId");
       $accessToken = $jssdk->getAccessToken();
       $url = "http://file.api.weixin.qq.com/cgi-bin/media/get?access_token={$accessToken}&media_id={$media_id}";
       $ch = curl_init($url);
       curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
       curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
       $output = curl_exec($ch);
       curl_close($ch); 
       if(file_put_contents('./a.jpg', $output)){
          $uid = Yaf_Registry::get('uid');
          $url = $this->_view->weixinapi.'myaccount/editPersonalAvatars';
          $data = array('file'=>'@'. dirname(__FILE__).'/../public/a.jpg','user_id'=>$uid,'uid'=>$_COOKIE['uid']);
          $result = Util::httpRequest($url,$data);
          //unlink('./a.jpg');
          exit ($result);         
       }else{
           exit('0');
       }
    }

    public function setphonsAction()
    {
        $url= $this->_view->weixinapi."/editPersonalPhone";
        $account=isset($_POST['phone'])?$_POST['phone']:'';
        $vcode=isset($_POST['vcode'])?$_POST['vcode']:'';
        $uid = Yaf_Registry::get('uid');
        $arr=array(
            'uid'=>$uid,
            'account'=>$account,
            'vcode'=>$vcode
        );
        $result=Util::httpRequest($url,$arr);
        $result= json_decode($result,true);
        if($this->isPost('sub')){
            if($result["success"]==1){
                echo "<script>alert('修改成功');window.location.href='/optionset/setlist';</script>";
            }else{
                echo "<script>alert('".$result["error"]."');</script>";
            }
            $this->_view->account=$account;
        }
    }
}