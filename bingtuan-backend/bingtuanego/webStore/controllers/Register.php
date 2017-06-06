<?php
class RegisterController extends BaseController
{
    public function agreementAction(){
        $qrcode = $_GET['qrcode'];
        if(!$qrcode){
            //echo "<script>alert('获取信息出错，请重新注册')</script>";
            //header("Location: /");
            $this->Alert('获取信息出错，请重新注册','/');
        }
        $this->_view->title="用户注册协议";
    }
    public function registerAction(){
            include_once("../../lib/wxpay/demo/log_.php");
            if(isset($_POST['tijiao'])){
                $url=$this->_view->weixinapi."/user/reg";
                $arr['account']=$this->getPost('account');
                $arr['vcode']=$this->getPost('vcode');
                $arr['password']=$this->getPost('password');
                $arr['repassword']=$this->getPost('repassword');
                $arr['qrcode']=$this->getPost('qrcode');
                //注册信息写入日志
                $xml=Util::arrayToXml($arr);
                $log_ = new Log_();
                $log_name="../public/register.log";//log文件路径
                $log_->log_result($log_name,"【注册信息】:\n".$xml."\n");
                //注册信息写入日志
                //exit();
                $result=Util::httpRequest($url,$arr);
                $result= json_decode($result,true);
                $this->_view->data=$arr;
                    if($result['success']==1){
                        $_COOKIE['wuid']=$result['data'];
                        setcookie('wuid',$result['data'],time()+60*60*24,'/');
                        header("Location: /register/reginfo?uid={$result['data']}");
                        exit;
                    } else{
                        echo "<script>alert('".$result['error']."')</script>";
                        $this->_view->data=$arr;
                }
        }
    }
    /*
     * 用户信息
     */
    public function reginfoAction(){
        //获取用户审核未通过原因   
        $uinfo = parent::isshow();
        $uid = isset($_GET['uid'])?intval($_GET['uid']):0;
        $auditContent = $uinfo['data']['userInfo']['auditContent'];
        //获取审核状态 解决审核通过后，再次提交材料变成审核中问题
        $auditType = $uinfo['data']['userInfo']['auditType'] ? $uinfo['data']['userInfo']['auditType'] : 0;
         if(isset($auditContent))
         {
            $this->_view->auditContent = $auditContent;
        }
         //获取用户审核未通过原因 
        //$uid = Yaf_Registry::get('uid');
        //unset($_SESSION);
        $uid = isset($_GET['uid'])?intval($_GET['uid']):0;
        $this->_view->uid = $uid;

        if($this->isPost('submit')){
            $url=$this->_view->weixinapi."/user/exam";
            $city_id=isset($_POST['city'])?$_POST['city']:'';
            $cname=isset($_POST['cname'])?$_POST['cname']:'';
            $address=isset($_POST['address'])?$_POST['address']:'';
            $uname=isset($_POST['uname'])?$_POST['uname']:'';
            $telphone=isset($_POST['telphone'])?$_POST['telphone']:'';
            $email=isset($_POST['email'])?$_POST['email']:'';
            $arr_id=@explode(',', $city_id);
            if(!$arr_id){
               echo "<script>alert('请完善信息')</script>"; 
            }
            $arr=array(
                'uid'=> $uid,
                'type'=>0,//审核中   
                'cname'=>$cname,//公司名称
                'provinceId'=>@$arr_id[0],//省份
                'cityId'=>@$arr_id[1],//城市id
                'areaId'=>@$arr_id[2],//地区id
                'address'=>$address,//详细地址
                'uname'=>$uname,//联系人姓名
                'telphone'=>$telphone,//联系电话
                'email'=>$email,
                'auditType' => $auditType
            );
           
            $result=Util::httpRequest($url,$arr);
            $result= json_decode($result,true);
            if($result['success']==1){
                setcookie ("uid", "", time() - 3600);
                //header('Location:/login/login');  
                echo "<script>alert('您的资料已提交请耐心等待现在可以进入首页浏览');window.location.href='/login/login';</script>";
            }else{
                echo "<script>alert('{$result['error']}')</script>";
            }   
        }
    }
    /*
     * 注册短信验证码
     */
    public function vcodeAction(){
        $url=$this->_view->weixinapi."/user/verify";
        $arr['account']=$this->getPost('phone');
        $result=Util::httpRequest($url,$arr);
        exit($result);
    }
}