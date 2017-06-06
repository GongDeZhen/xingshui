<?php
class OrderController extends BaseController
{
    public function init()
    {
        parent::init();

        if (  Yaf_Registry::get( 'isLogin' ) <> true )
        {
            $this->respon( 0 , "登录超时!"  );
        }
    }
    
    /**
     * 根据订单状态得到订单列表接口
     * 接口参数：用户ID，订单状态，配送状态
     * 条件：按订单状态
     * 订单状态：0全部 1待付款 2待发货 3待收货 4已收货 5已取消
     */
    public function getOrderListAction()
    {
        $userID = $this->getPost('uid')?$this->getPost('uid'):Yaf_Registry::get('uid');
        $status      = $this->getPost('status');
        $statusMap      = array(
            '0' => array('toi.user_id' => $userID, 'toi.is_delete' => 0),
            '1' => array('toi.user_id' => $userID, 'toi.order_status' => 1, 'toi.shipping_status' => 1, 'toi.pay_status' => 1, 'toi.sign_status'=>1),
            '2' => array('toi.user_id' => $userID, 'toi.order_status' => 3, 'toi.shipping_status' => 1, 'toi.sign_status'=>1),
            '3' => array('toi.user_id' => $userID, 'toi.order_status' => 3, 'toi.shipping_status' => 2, 'toi.pay_status' => 2, 'toi.sign_status'=>1),
            '4' => array('toi.user_id' => $userID, 'toi.order_status' => 3, 'toi.shipping_status' => 2, 'toi.pay_status' => 2, 'toi.sign_status'=>2),
            '5' => array('toi.user_id' => $userID, 'toi.order_status' => 2)
        );

        $result = Service::getInstance('order')->getOrderList($statusMap[$status]);
        if($result) {
            $this->respon(1, $result);
        } else {
            $this->respon(1, array());
        }
    }

    /**
     * 订单详情
     */
    public function orderInfoAction()
    {
        $order_id = $this->getPost('order_id');
        if ($order_id == '') $this->respon( 0 , "订单号不能为空!" );
        
        $orderInfo = Service::getInstance('order')->orderInfo($order_id);
        if ( ! $orderInfo ) {
            $this->respon(0,'订单不存在');
        }
        $this->respon(1,$orderInfo);
    }
    
    /**
     * 评价订单
     */
    public function commentAction() {
        $uid = $this->getPost('uid')?$this->getPost('uid'):Yaf_Registry::get('uid');
        $orderid = $this->getPost('orderid',0);
        $content = $this->getPost('content');
        $rank = $this->getPost('commentrank',0);
        
        if(!$uid) $this->respon(0,'参数异常');
        if(!$orderid) $this->respon(0,'参数异常');
        if(!$content) $this->respon(0,'评价内容不能为空');

        $data = array(
            'user_id'=>$uid,
            'content'=>$content,
            'comment_rank'=>$rank,
            'add_time'=>time(),
            'order_id'=>$orderid
        );
        $res = Service::getInstance('buyusers')->addOrderComment($data);
        if($res) {
            Service::getInstance('message')->addMessage($orderid,true,'','',1);
            $this->respon(1,'评价成功');
        } else {
            $this->respon(0,'评价失败');
        }
    }
    
    /**
     * 结算生成订单
     */
    //结算 [{id:商品id,count:商品数量,price:商品单价,activityId:活动id}] ,uid 
    public function balanceAction()
    {
    	$uid = $this->getPost('uid')?$this->getPost('uid'):Yaf_Registry::get('uid');
    	$postscript = $this->getPost('postscript','');
    	$addressID = $this->getPost( 'address_id','');
    	$couponArr = $this->getPost( 'couponId', 0 );
    	$voucher_id = $this->getPost( 'voucher_id', 0 );
    	$pay_id = $this->getPost( 'pay_id', 0 );
    	$goodsInfo = urldecode($this->getPost('goodsInfo',''));
    	$benefits_info = $this->getPost('benefitsInfo','');

        if ($addressID == '') $this->respon( 0 , "请选择配送地址" );
        
    	$info = json_decode($goodsInfo,true);

    	if( !is_array($info) ) {
            $this->respon(0,"json提交有误");
    	}
        
    	$list = Service::getInstance('order')->balance($uid,$postscript,$couponArr,$voucher_id,$addressID,$pay_id,$info,$benefits_info);
    	if($list['role']) {
    	    Service::getInstance("message")->addMessage($list['order']['order_id'],'newOrder');
            $this->respon(1,$list['order']);
    	}
        else{
            $this->respon(0,$list['goods']);
    	}
    }
    
    /**
     * 检查优惠卷是否可用
     */
    public function checkCouponsAction(){
    	$orderId = $this->getPost('orderId','');
    	$couponsId = $this->getPost('couponsId','');
    	if($orderId==''){
    		$this->respon( 0 , '订单id不能为空' );
    	}
    	if($couponsId==''){
    		$this->respon( 0 , '优惠券id不能为空' );
    	}
    	$retule=Service::getInstance('buyusers')->checkCoupons($orderId,$couponsId);
    	if($retule){
    		$this->respon(1,'可用');
    	}else{
    		$this->respon(0,'已过期');
    	}
    }
    
    /**
     * 添加收货地址
     */
    public function createBuyAddressAction()
    {
        $uid = $this->getPost('uid')?$this->getPost('uid'):Yaf_Registry::get('uid');
        $username = $this->getPost('user_name');
        $phone = $this->getPost('phone');
        $provinceID = $this->getPost('province_id');
        $cityID = $this->getPost('city_id');
        $areaID = $this->getPost('area_id');
        $street = $this->getPost('street');
        $address = $this->getPost('address');
        $longitude = $this->getPost('longitude');
        $latitude = $this->getPost('latitude');
         
        if ( $uid == "" ) $this->respon( 0 , "用户ID不能为空!" );
        if ( $username == "" ) $this->respon( 0 , "用户名不能为空!" );
        if ( $phone == "" ) $this->respon( 0 , "手机号不能为空!" );
        if ( $provinceID == "" ) $this->respon( 0 , "省不能为空!" );
        if ( $cityID == "" ) $this->respon( 0 , "市不能为空!" );
        if ( $areaID == "" ) $this->respon( 0 , "区不能为空!" );
        if ( $longitude == "" ) $this->respon( 0 , "经度不能为空!" );
        if ( $latitude == "" ) $this->respon( 0 , "纬度不能为空!" );
         
        $data = array(
            'user_id' => $uid,
            'user_name' => $username,
            'phone' => $phone,
            'province_id' => $provinceID,
            'city_id' => $cityID,
            'area_id' => $areaID,
            'street' => $street,
            'address' => $address,
            'longitude' => $longitude,
            'latitude' => $latitude,
            'create_time' => time()
        );
    
        $add = Service::getInstance('myaccount')->createBuyAddress($data);
         
        if (! $add )
        {
            $this->respon(0, '添加失败！');
        }
        $this->respon(1, '添加成功！');
    }
    
    /**
     * 根据城市名反查ID
     */
    public function getAddressAction()
    {
        $province = $this->getPost('province','');
        $area = $this->getPost('area','');
        if ( $province =='' || $area =='') $this->respon( 0 , "省市区不能为空!" );
        $ret = Service::getInstance('goods')->getAddress($province,$area);
        if ($ret) {
            $this->respon( 1 , $ret);
        }
        $this->respon( 1 , '地址无效');
    }
    
    /**
     * 关闭交易
     */
    public function closeAction()
    {
        $orderID = $this->getPost('order_id');
        if ( !$orderID ) $this->respon( 0, '订单ID错误' );
        if (Service::getInstance('order')->closeOrdser($orderID))
        {
            $this->respon( 1 , '您已成功关闭订单');
        } else {
            $this->respon( 1 , '关闭失败，请稍后重试');
        }
    }

    /**
     * 取消订单
     */
    public function cancelAction()
    {
        $orderID = $this->getPost('order_id');
        if ( !$orderID ) $this->respon( 0, '订单ID错误' );
        $orderInfo = Service::getInstance('order')->info( $orderID );
        if ( $orderInfo['order_status'] == 2 ) $this->respon( 0, '该订单已取消' );
        $order_status = 7;
        if ($orderInfo['pay_money'] <= 0) {
            $order_status = 4;
        }
        if ($orderInfo['pay_money'] == 0) {
            Service::getInstance("order")->updateOrderRefundStatusByWeixin($orderID,'','');
            Service::getInstance('order')->rebackGoodsNumber($orderID); // 把商品退回到库存
            Service::getInstance("message")->addMessage($orderID, 'cancel');
            $this->respon(1, '成功取消订单');
        }

        if ( Service::getInstance('order')->updateCancelOrder($orderID, $order_status) ) { // 执行修改状态
            $this->respon( 1 , '取消订单审核中');
        } else {
            $this->respon( 0 , '请求失败');
        }
    }
    
    /**
     * 删除订单
     */
    public function closeDealAction()
    {
        $uid = $this->getPost('uid')?$this->getPost('uid'):Yaf_Registry::get('uid');
        $orderID = $this->getPost('order_id');
        if($orderID == '') $this->respon( 0 , "订单号不为空!" );
        if($uid == '') $this->respon( 0 , "用户ID不为空!" );
        
        $data = Service::getInstance('order')->closeOrder($orderID, $uid);
        if ( $data ) {
            $this->respon(1,'删除成功');
        }else{
            $this->respon(0,'删除失败');
        }
    }
    
    /**
     * 立刻签收
     */
    public function receivingAction(){
        $orderID = $this->getPost('order_id','');
        if ($orderID=='') $this->respon( 0 , '订单id不能为空' );
        
        if (Service::getInstance('order')->receiving($orderID)) {
            Service::getInstance("message")->addMessage($orderID,'receiving');
            $this->respon(1,'签收成功');
        } else {
            $this->respon(0,'签收失败');
        }
    }
    
    //提醒发货  订单id
    public function remindSendAction(){
        $orderId = $this->getPost('order_id','');
        if($orderId==''){
            $this->respon( 0 , '订单id不能为空' );
        }
        $result = Service::getInstance('order')->remindSend($orderId);
        if(!$result){
            $this->respon(1,"提醒成功");
        }else{
            $this->respon(0,"已提醒过,请在".ceil($result)."小时之后再次提醒");
        }
    }
    /*
     * 支付成功更新订单状态
     */
    public function updateodersStatusAction(){
        $udata=array();
        $orderId = $this->getPost('attach');
        $udata['pay_id']=$this->getPost('pay_id');
        $udata['trade_no']=$this->getPost('trade_no');
        if($orderId==''){
            $this->respon( 0 , '订单id不能为空' );
        }
        if (Service::getInstance("order")->updateOrderPayStatus($orderId,$udata)) { //微信支付成功
           // Service::getInstance("message")->addMessage($orderId);
            $this->respon(1,'订单状态更新成功');
        }else{
            $this->respon(0,'订单状态更新失败');
        }
    }
}