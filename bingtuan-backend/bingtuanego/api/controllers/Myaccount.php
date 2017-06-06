<?php 
class MyaccountController extends BaseController{
    public function init()
    {
        parent::init();

        if (  Yaf_Registry::get( 'isLogin' ) <> true )
        {
            //$this->respon( 0 , "登录超时!"  );

        }
    }
    /**
     * 个人中心首页
     * user_id
     */
    public function indexAction()
    {
        $uid = $this->getPost('uid')?$this->getPost('uid'):Yaf_Registry::get('uid');
        $list = Service::getInstance('myaccount')->index($uid);
        $this->respon( 1 , $list);
    }
    
    /**
     *    我的收货地址
     *     user_id
     */
    public function addressInfoAction()
    {
        $uid = $this->getPost('uid')?$this->getPost('uid'):Yaf_Registry::get('uid');
        $list = Service::getInstance('myaccount')->addressInfo($uid);
        $this->respon( 1 , $list);
    }
    /**
     *    我的收货地址
     *     user_id
     *     address_id
     */
    public function addressbyIdAction()
    {
        $uid = $this->getPost('uid')?$this->getPost('uid'):Yaf_Registry::get('uid');
        $add_id = $this->getPost('address_id')?$this->getPost('address_id'):'';
        $list = Service::getInstance('myaccount')->addressbyId($uid,$add_id);
        if(empty($list)){
            $this->respon(0,400);
        }else{
            $this->respon(1,$list);
        }
        
    }
    //获取全部地址
    public function getCityInfoAction()
    {
        $id = $this->getPost('id')?$this->getPost('id'):'';
        $list = Service::getInstance('myaccount')->getCityInfo($id);
        $this->respon( 1 , $list);
    }
    
    /**
     *    添加收货地址
     *     
     */
    public function addAddressAction()
    {
        $uid = $this->getPost('uid')?$this->getPost('uid'):Yaf_Registry::get('uid');
        $user_name = $this->getPost('user_name');
        $phone = $this->getPost('account');
        $province_id = $this->getPost('province_id');
        $city_id = $this->getPost('city_id'); 
        $area_id= $this->getPost('area_id');
        $address = $this->getPost('address');
        
        if( !$user_name )  $this->respon( 0 , "请填写联系人姓名" );
        if( !$phone )  $this->respon( 0 , "请填写手机号" );
        if( !$address )  $this->respon( 0 , "请完善地址信息" );
        
        if ( !Util::isValidMobile( $phone ) ) $this->respon( 0 , "手机号格式不正确" );
        
        $result= Service::getInstance('myaccount')->addAddress($uid,$user_name,$phone,$province_id,$city_id,$area_id,$address);
        if($result){
            $this->respon( 1 , "提交成功");
        }else{
            $this->respon( 0 , "提交失败");
        }
        
    }
    
    /**
     *    编辑收货地址
     */
    public function editAddressAction()
    {
        $uid = $this->getPost('uid')?$this->getPost('uid'):Yaf_Registry::get('uid');
        $address_id = $this->getPost('address_id');
        $user_name = $this->getPost('user_name');
        $phone = $this->getPost('account');
        $province_id = $this->getPost('province_id');
        $city_id = $this->getPost('city_id');
        $area_id= $this->getPost('area_id');
        $address = $this->getPost('address');
        
        if( !$address_id )  $this->respon( 0 , "地址id不能为空!" );
        if( !$user_name )  $this->respon( 0 , "请填写联系人姓名" );
        if( !$phone )  $this->respon( 0 , "请填写手机号" );
        if( !$address )  $this->respon( 0 , "请完善地址信息" );
        
        if ( !Util::isValidMobile( $phone ) ) $this->respon( 0 , "手机号格式不正确" );
        $result= Service::getInstance('myaccount')->editAddress($address_id,$uid,$user_name,$phone,$province_id,$city_id,$area_id,$address);
        if($result){
            $this->respon( 1 , "编辑成功");
        }else{
            $this->respon( 0 , "编辑失败");
        }
    }
    
    /**
     *    删除收货地址
     */
    public function delAddressAction()
    {
        $address_id = $this->getPost('address_id');
        if( !$address_id )  $this->respon( 0 , "地址id不能为空!" );
        $result= Service::getInstance('myaccount')->delAddress($address_id);
        if($result){
            $this->respon( 1 , "删除成功");
        }else{
            $this->respon( 0 , "删除失败");
        }
    }
    
    /**
     * 选择默认地址
     */
    public function choiceAddressAction()
    {
        $id = $this->getPost('id');
        $uid = $this->getPost('uid')?$this->getPost('uid'):Yaf_Registry::get('uid');
        if ( $id == "" ) $this->respon( 0 , "地址ID不能为空!" );
        if ( $uid == "" ) $this->respon( 0 , "用户ID不能为空!" );
        $bankCard = Service::getInstance('myaccount')->choiceAddress($id,$uid);
        if ($bankCard)
        {
            $this->respon(1, '选择成功');
        } else {
            $this->respon(0, '选择失败');
        }
        
    }
    
    /**
     *    个人设置
     */
    public function personalAction()
    {
        $uid = $this->getPost('uid')?$this->getPost('uid'):Yaf_Registry::get('uid');
        $list = Service::getInstance('myaccount')->personal($uid);
        
        $this->respon( 1 , $list);
    }
    
    /**
     *    修改联系人姓名
     */
    public function editPersonalNameAction()
    {
        $uid = $this->getPost('uid')?$this->getPost('uid'):Yaf_Registry::get('uid');
        $userName= $this->getPost('user_name');
        
        if( !$userName )  $this->respon( 0 , "请填写联系人姓名" );
        $result = Service::getInstance('myaccount')->editPersonalName($uid,$userName);
        if($result){
            Service::getInstance("message")->addMessage($uid,'editPersonalName');
            $this->respon( 1 , "确定");
        }else{
            $this->respon( 0 , "失败");
        }
    }
    
    /**
     *    修改手机号
     */
    public function editPersonalPhoneAction()
    {
        $uid = $this->getPost('uid')?$this->getPost('uid'):Yaf_Registry::get('uid');
        $phone = $this->getPost( 'account' );
        $vcode = $this->getPost('vcode');
        
        if ( $phone == "" ) $this->respon( 0 , "手机号不能为空!" );
        if ( !Util::isValidMobile( $phone ) ) $this->respon( 0 , "手机号格式不正确!" );
        if ( $vcode == "" ) $this->respon( 0 , "验证码不能为空!" );
        
        if ( !Service::getInstance('user')->checkCode( $phone , $vcode ) )
        {
            $this->respon( 0 , "验证码输入错误!" );
        }
        //验证码过期？
        
        $result = Service::getInstance('myaccount')->editPersonalPhone($uid,$phone);
        
        if($result){
            Service::getInstance("message")->addMessage($uid,'editPersonalPhone');
            $this->respon( 1 , "更改手机号成功");
        }else{
            $this->respon( 0 , "失败");
        }
        
    }
    
    /**
     *    修改密码
     */
    public function editPersonalPwdAction()
    {
        $uid = $this->getPost('uid')?$this->getPost('uid'):Yaf_Registry::get('uid');
        $oldpwd = $this->getPost('oldpwd');
        $newpwd = $this->getPost('newpwd');
        $rnewpwd = $this->getPost('rnewpwd');
        
        if( !$oldpwd )  $this->respon( 0 , "旧密码不能为空" );
        if( !$newpwd )  $this->respon( 0 , "新密码不能为空" );
        if($newpwd != $rnewpwd)  $this->respon( 0 , "两次新密码输入不一致" );
        
        $result = Service::getInstance('myaccount')->editPersonalPwd($uid,$oldpwd,$newpwd);
        if($result == 1){
            $this->respon( 1 , "旧密码输入错误");
        }elseif($result == 2){
            Service::getInstance("message")->addMessage($uid,'editPersonalPwd');
            $this->respon( 1 , "更改密码成功");
        }else{
            $this->respon( 0 , "失败");
        }
    }
    
    /**
     * 修改头像
     */
    public function editPersonalAvatarsAction()
    {
        $uid = $this->getPost('uid')?$this->getPost('uid'):Yaf_Registry::get('uid');
        if (!isset($_FILES['file']) && $_FILES['file']['size'] < 0) {
            $this->respon( 0 , "请选择上传图片" );
        }
        $img = Util::getImgPath($_FILES['file']);
        $data = Service::getInstance('myaccount')->editPersonalAvatars($uid, $img['path']);
        if ( $data )
        {
            $this->respon( 1 , $data);
        }
    
        $this->respon( 0 , "提交失败，请重试!" );
    }
    
    /**
     * 我的优惠券(线上线下)
     */
    public function couponsAction()
    {
        $uid = $this->getPost('uid')?$this->getPost('uid'):Yaf_Registry::get('uid');
        $online = $this->getPost('online');//0为线上，1为线下 2不可用

        if ( $online == "" )  $this->respon( 0 , "优惠劵类型不能为空!" );
        $list = Service::getInstance('myaccount')->coupons($uid , $online);
        
        $this->respon(1,$list);
    }
    
    /**
     * 我的代金券(线上线下)
     */
    public function vouchersAction()
    {
        $uid = $this->getPost('uid')?$this->getPost('uid'):Yaf_Registry::get('uid');
        $list = Service::getInstance('myaccount')->vouchers($uid);
        $this->respon(1,$list);
    }
    
    /**
     * 添加线下优惠卷
     */
    public function addCouponsAction()
    {
        $uid = $this->getPost('uid')?$this->getPost('uid'):Yaf_Registry::get('uid');
        $couponsNumber = $this->getPost('coupons_number');
        
        if ( $couponsNumber == "" )  $this->respon( 0 , "兑换券验证码不能为空!" );
        if(!is_numeric($couponsNumber)) $this->respon( 0 , "验证码填写有误!" );   // 全为数字
        if(mb_strlen($couponsNumber,'utf-8') != 16) $this->respon( 0 , "验证码为16位!" );    // 16位
    
        $result = Service::getInstance('myaccount')->addCoupons($uid, $couponsNumber);
        if ($result == 3) {
            $this->respon(0,'失败');
        } elseif ($result == 2) {
            $this->respon(0,'无效优惠券');
        } else {
            $this->respon(1, $result);
        }
    }
    
    /**
     * 我的账单
     */
    public function myAccountOrderAction()
    {
        $uid = $this->getPost('uid')?$this->getPost('uid'):Yaf_Registry::get('uid');
        $year = $this->getPost('year');
        if ( $year == "" )  $this->respon( 0 , "年份不能为空!" );
        
        $list = Service::getInstance('myaccount')->myAccountOrder($uid, $year);
        if ($list) {
            $this->respon(1, $list);
        } else {
            $this->respon(1, array());
        }
    }
    
    /**
     * 账单详情
     */
    public function accountInfoAction()
    {
        $accountID = $this->getPost('account_id');
        if( !$accountID )  $this->respon( 0 , "账单id不能为空!" );
    
        $list = Service::getInstance('myaccount')->accountInfo($accountID);
        $this->respon( 1 , $list);
    }

    /**
     *    编辑收货地址
     */
    public function editAddresAction()
    {
        $uid = $this->getPost('uid')?$this->getPost('uid'):Yaf_Registry::get('uid');
        $address_id = $this->getPost('address_id');
        $user_name = $this->getPost('user_name');
        $phone = $this->getPost('account');
        $province_id = $this->getPost('province_id');
        $city_id = $this->getPost('city_id');
        $area_id= $this->getPost('area_id');
        $address = $this->getPost('address');

        if( !$address_id )  $this->respon( 0 , "地址id不能为空!" );
        if( !$user_name )  $this->respon( 0 , "请填写联系人姓名" );
        if( !$phone )  $this->respon( 0 , "请填写手机号" );
        if( !$address )  $this->respon( 0 , "请完善地址信息" );

        if ( !Util::isValidMobile( $phone ) ) $this->respon( 0 , "手机号格式不正确" );
        $result= Service::getInstance('myaccount')->editAddress($address_id,$uid,$user_name,$phone,$province_id,$city_id,$area_id,$address);
        if($result){
            $this->respon( 1 , "编辑成功");
        }else{
            $this->respon( 0 , "编辑失败");
        }
    }
}

