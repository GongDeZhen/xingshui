<?php
class WeixinController extends BaseController
{
    public function init()
    {
        parent::init();
    }
    
    //微信支付
    public function indexAction ()
    {
        $orderID = $this->getPost( 'order_id', 0 );
        if ( !$orderID ) $this->respon( 0 , "订单错误！" );
        $info = Service::getInstance('order')->info( $orderID );
        if ( !$info ) $this->respon( 0 , "订单错误！" );
        if ($info['pay_status'] != 1) $this->respon( 0, '该订单已支付!' );
        $money = $info['pay_money'];
        if ( $money <= 0 ) {
            $resData = array('pay_id'=>1);
            if(Service::getInstance("order")->updateOrderPayStatus($orderID,$resData)) { //微信支付成功
                Service::getInstance("message")->addMessage($orderID);
            }
            $this->respon( 1 , '支付成功' );
        }
        
        Log::simpleappend('order_wx', json_encode($info));
        $data = array();
        $data['appid'] = AP_APPID;//Weixinpay::Appid; //公众账号ID
        $data['partnerid'] = AP_Mch_id;//Weixinpay::Mch_id; //商户号
        $data['prepayid'] = Weixinpay::getPrepayId($info['order_number'],$money,$orderID); //预支付交易会话ID
        $data['package'] = 'Sign=WXPay'; //扩展字段
        $data['noncestr'] = Weixinpay::createNoncestr();// 随机字符串
        $data['timestamp'] = time(); //时间戳
        $data['sign'] = Weixinpay::getSign($data); // 签名
        
        $this->respon(1,$data);
        return false;
    }
    
    //退款
    public function refundAction() {
        $orderId = $this->getPost('orderId');
        if ( !$orderId ) $this->respon( 0, '参数错误' );
        $orderInfo = Service::getInstance('orders')->info( $orderId );
        
        $refund_num = strftime("%Y%m%d%H%M%S").mt_rand(1000,9999);
        
        if ( $orderInfo['order_status'] == 2 ) $this->respon( 0, '该订单已取消' );
        if ( $orderInfo['order_status'] == 3 ) {
            if ($orderInfo['pay_money'] == 0 && $orderInfo['refund_num'] != '') {
                Service::getInstance("orders")->updateOrderRefundStatusByWeixin($orderId,'',$refund_num);
                Service::getInstance('usersorder')->rebackGoodsNumber($orderId); // 把商品退回到库存
                Service::getInstance("message")->addMessage($orderId);
                $this->respon(1, '退款成功');
            }
            $data = Weixinpay::getResult($orderInfo['trade_no'], $refund_num,$orderInfo['pay_money']*100);
            if ($data["return_code"] == "FAIL") {
                $strMSG = date("Y-m-d H:i:s")."【通信出错】:\n" . json_encode($data) . "\n";
                Log::simpleappend('refund_weixin', $strMSG);
                $this->respon(0, '退款失败');
            } elseif ($data["result_code"] == "FAIL"){
                // 此处应该更新一下订单状态,商户自行增删操作
                $strMSG = date("Y-m-d H:i:s")."【业务出错】:\n" . json_encode($data) . "\n";
                Log::simpleappend('refund_weixin', $strMSG);
                $this->respon(0, '退款失败');
            } else {
                // 此处应该更新一下订单状态,商户自行增删操作
                $orderid = $data["transaction_id"];
                $strMSG = date("Y-m-d H:i:s")."【退款成功】:\n" . $orderid . "\n";
                Log::simpleappend('refund_weixin', $strMSG);
                Service::getInstance("order")->updateOrderRefundStatusByWeixin($orderId,'',$refund_num);
                Service::getInstance('usersorder')->rebackGoodsNumber($orderId); // 把商品退回到库存
                Service::getInstance("message")->addMessage($orderId);
                $this->respon(1, '退款成功');
            }
        } else {
            $this->respon( 0, '该订单退款信息错误' );
        }
    }
}
