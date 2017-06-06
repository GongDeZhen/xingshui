<?php

class UserController extends BaseController
{
    public $covers = array();

    public function init()
    {
        parent::init();
    }

    public function indexAction()
    {
        if (  !Service::getInstance('user')->checkUser()  ) 
        {
            $this->respon( 0 , "Login Access Denied" );
        }

        $this->respon( 1 , Yaf_Registry::get("User") );       
    }

    //登录 @/user/login/
    public function loginAction()
    {
        $reIP=$_SERVER["REMOTE_ADDR"];
        $phone = $this->getPost('account');

        if ( !$phone ) $this->respon( 0 , "用户名不能为空!" );

        $pwd = $this->getPost('password');
        
        if ( $pwd == "" ) $this->respon( 0 , "密码不能为空!" ); 
        $token = $this->getPost('device_token','');

        $result = Service::getInstance('user')->doLogin( $phone , $pwd , $token);
        if ( $result === true )
        {
            setcookie('auditType', $result['auditType']);
            //开始记录登录日志
            $rs = Service::getInstance('user')->addLoginLog($phone,$reIP);
            if(!$result){
                $this->respon( 0, "由于网络原因，请重试!" );
            }
            $this->respon( 1 , Yaf_Registry::get( 'user' ) );
        }

        $this->respon( 0 , $result );
    }
    
    /**
     * 注册验证码
     * @param   account:手机号码
     * @return  array
     */
    public function verifyAction()
    {
        if($this->isPost()) {
            $phone = $this->getPost( 'account' );
            if ( !Util::isValidMobile( $phone ) ) $this->respon( 0 , "账号不合法!" );
            $user = Service::getInstance( 'user' )->getUserByaccount( $phone );
            if ( $user )
            {
                $this->respon( 0 , "您的账号已经存在，请重新填写!" );
            }
            if ( Service::getInstance( 'user' )->sendsmsCode( $phone ) )
            {
                $this->respon( 1, "验证码已发送到您的手机，请查收!" );
            }
            $this->respon( 0, "验证码发送失败，请重试!" );
        }
        return false;
    }

    public function BatchFixPwdAction($pwd = 'd41d8cd98f00b204e9800998ecf8427e') {
      $result = Service::getInstance('user')->FixPassword($pwd);
      //var_dump($result);
      return false;
    }
    public function BatchRegAction($cid)
    {
//        $csvfile = dirname(__FILE__) . "/../../conf/ego_account.txt";
        $csvfile = "/home/www/bingtuanego/conf/ego_account.txt";
        $handle = fopen($csvfile, 'r');   
        while ($data = fgetcsv($handle, 10000, "\t")) {
          if ( strlen($data[1]) == 11 ) {
                $pwd = mt_rand(100000,999999);
                $user = array(
                  'account' => $data[1],
                    'password' => $pwd,
                  'cid' => $cid,
                  'userName' => $data[4],
                  'email' => $data[6],
                  'comName' => $data[0],
                  'address' => $data[7],
                  'telphone' => $data[5],
                    'user_type' => 1,
                    'auditType' => 2,
                    'auditTime' => time(),
                    'createTime' => time()
                );
                $uid = Service::getInstance('user')->register( $user );
                if ( $uid )
                {
                    echo (++$n) . " " . $data[0] . " $pwd\n";
                }
                else {
                    var_dump($data);
                }
            }
        }
        return false;
    }
    //注册 @/user/reg/
    public function regAction()
    {
        $phone = $this->getPost('account');
        $vcode = $this->getPost('vcode');
        $qrcode = $this->getPost('qrcode');
        if ( $phone == "" ) $this->respon( 0 , "手机号不能为空!" );
        if ( !Util::isValidMobile( $phone ) ) $this->respon( 0 , "手机号格式不正确!" );
        
        if ( $vcode == "" ) $this->respon( 0 , "验证码不能为空!" );

        //增加判断qrcode，解决注册用户没有归属公司问题
        if(!$qrcode) $this->respon(0, '获取公司信息失败，请重试');

        if ( !Service::getInstance('user')->checkCode( $phone , $vcode ) )
        {
            $this->respon( 0 , "验证码输入错误!" );
        }

        $pwd = $this->getPost('password');
        $repwd = $this->getPost('repassword');
        if ( $pwd == "" ) $this->respon( 0 , "密码不能为空!" );
        if ( $pwd != $repwd ) $this->respon( 0 , "两次密码输入不一致!" );
        

        if ( Service::getInstance( 'user' )->getUserByaccount( $phone ) )
        {
            $this->respon( 0 , "您手机号已被注册，请更换别的手机号!" );
        }

        $user = array(
            'account' => $phone,
            'password' => $pwd,
            'cid' => intval( $qrcode ),
            'createTime' => time()
        );
        $uid = Service::getInstance('user')->register( $user );
        if ( $uid )
        {
            $this->respon( 1 , $uid );
        }

        $this->respon( 0 , "注册失败，请重试!" );
    }
    
    /**
     * 提交审核材料
     * @method  post
     * @param   uid:用户id
     * @param   cname:公司名称
     * @param   provinceId:省份id
     * @param   cityId:城市id
     * @param   areaId:区域id
     * @param   address:详细地址
     * @param   uname:用户姓名
     * @param   telphone:联系电话
     * @param   email:邮箱
     * @return  array
     */
    public function examAction()
    {
        $uid = $this->getPost('uid');
        //根据uid获取用户信息
        $user = Service::getInstance('user')->getUserInfoById($uid);
        if($user['auditType'] == 2){
            $this->respon(0, '请勿重复提交审核信息!');
        }
        $cname = $this->getPost('cname');
        $pid = $this->getPost('provinceId');
        $cid = $this->getPost('cityId');
        $aid = $this->getPost('areaId');
        $address = $this->getPost('address');
        $uname = $this->getPost('uname');
        $telphone = $this->getPost('telphone');
        $email = $this->getPost('email','');
        $auditType = $this->getPost('type','0');
        if ( !$uid ) $this->respon( 0, '参数有误，请检查！' );
        if(!$cname){
            $this->respon(0, '公司名称不能为空!');
        }
        if ( !Util::isValidMobile( $telphone ) ) $this->respon( 0 , "联系电话格式不正确!" );
        if ($email != '') {
            if ( Util::isValidEmail( $email ) == false) $this->respon( 0 , "邮箱格式不正确!" );
        }
        if(!$cid || !$pid){
            $this->respon(0, "请选择省市信息!");
        }

        if(!$address){
            $this->respon(0, '请填写您的地址!');
        }

        if(!$uname){
            $this->respon(0, '联系人姓名不能为空!');
        }

        $province_name = Service::getInstance('region')->provinceInfo($pid);
        $city_name = Service::getInstance('region')->provinceInfo($cid);
        $area_name = Service::getInstance('region')->provinceInfo($aid);
        
        $user = array(
            'auditType'=>$auditType,
            'comName' => $cname,
            'address' => $province_name.$city_name.$area_name.$address,
            'userName'=>$uname,
            'telphone'=>$telphone,
            'email'=>$email
        );
        
        $res = Service::getInstance('user')->updateExam( $user, $uid );
        if ( $res )
        {
            Service::getInstance('Myaccount')->addAddress($uid, $uname, $telphone, $pid, $cid, $aid, $address);
            $this->respon( 1 , '提交成功' );
        }

        $this->respon( 0 , "提交失败!" );
    }
    
    /**
     * 忘记密码验证码
     * @method  post
     * @param   account:手机号码
     * @return  array
     */
    public function lostverifyAction()
    {
        if($this->isPost()) {
            $phone = $this->getPost( 'account' );
            if ( !Util::isValidMobile( $phone ) ) $this->respon( 0 , "手机号码不合法!" );
            $user = Service::getInstance( 'user' )->getUserByaccount( $phone );
            if ( !$user )
            {
                $this->respon( 0 , "该手机号码未注册，请确认手机号码!" );
            }
            if ( Service::getInstance( 'user' )->sendsmsCode( $phone, '忘记密码' ) )
            {
                $this->respon( 1, "验证码已发送到您的手机，请查收!" );
            }
            $this->respon( 0, "验证码发送失败，请重试!" );
        }
        return false;
    }

    /**
     * 忘记密码
     * @method  post
     * @param   account:手机号码;
     * @param   password:新密码;
     * @param   repassword:确认密码;
     * @param   vcode:验证码;
     * @return  array
     */
    public function lostpwdAction()
    {
        if ( $this->isPost() ) {
            $account = trim( $this->getPost('account') );
            $pwd = trim( $this->getPost('password') );
            $repwd = trim( $this->getPost('repassword') );
            $vcode = trim( $this->getPost('vcode') );
            if ( !Util::isValidMobile( $account ) ) $this->respon( 0 , "手机号格式不正确!" );
            
            if ( $vcode == "" ) $this->respon( 0 , "验证码不能为空!" );
            
            if ( !Service::getInstance('user')->checkCode( $account , $vcode ) )
            {
                $this->respon( 0 , "验证码输入错误!" );
            }
            
            if ( $pwd == "" ) $this->respon( 0 , "新密码不能为空!" );
            
            if ( $pwd !=  $repwd ) $this->respon( 0 , "两次输入密码不一致!" );
            
            if ( Service::getInstance( 'user' )->updatePassword( $account , $pwd ) >= 0 )
            {
                $this->respon( 1 , "密码修改成功!" );
            }
            
            $this->respon( 0 , "密码修改失败!" );
        }
        return false;
    }
    /**
     * 获取二维码信息
     */
    public function warehouseCodeAction()
    {
        $code = urldecode($this->getPost('code'));
        $key = Yaf_Application::app()->getConfig()->get('cookie')->get('key');
        $code = explode('_', Util::strcode($code, $key, 'decode')); 
    
        if ($code[0] != 'xingshui') {
            $this->respon( 0 , "二维码无效" );
        }
        
        if (!is_numeric($code[1])) {
            $this->respon( 0 , "二维码无效" );
        }
        
        $info = Service::getInstance('warehouse')->warehouseInfo($code[1]);
        
        if (!$info) {
            $this->respon( 0 , "二维码无效" );
        }
        
        if ($info['status'] == 0) {
            $this->respon( 0 , "二维码无效" );
        }
        $this->respon( 1 , $info['id'] );
    }
    
    /**
     * 获取省市区
     */
    public function getCityAction()
    {
        $parent_id = $this->getPost('parent_id');
        $type = $this->getPost('type');
        switch ($type) {
            case 1:
                $return = Service::getInstance('region')->getAddress();
            break;
            case 2:
                if ($parent_id == '') $this->respon(0, '请选择省');
                $return = Service::getInstance('region')->getCitylist($parent_id);
            break;
            case 3:
                if ($parent_id == '') $this->respon(0, '请选择市');
                $return = Service::getInstance('region')->getArealist($parent_id);
            break;
            default:
                $return = array();
            break;
        }
        $this->respon(1, $return);
    }

    public function getStatusAction(){
        $uid = $this->getPost('uid');
        if(!$uid){
            $this->respon(0, '参数错误!');
        }


    }
}