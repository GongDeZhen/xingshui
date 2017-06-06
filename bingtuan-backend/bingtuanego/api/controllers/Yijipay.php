<?php

/**
 * Created by PhpStorm.
 * User: nsinm
 * Date: 2017/1/18
 * Time: 20:06
 */

class YijipayController extends BaseController
{
    /**
     * 支付配置信息
     * @var
     */
    private $config;

    /**
     * __construct()
     */
    public function init()
    {
        parent::init();
        $this->config = include "yijipay/config.php";
        include('yijipay/YijipayClient.class.php');
    }

    /**
     * 获取签名后的参数
     */
    public function indexAction(){
        $orderID = $this->getPost( 'order_id', $_GET['order_id'] );
        if ( !$orderID ) $this->respon( 0 , "订单错误！" );

        $info = Service::getInstance('order')->info( $orderID );
        if ( !isset( $info['id'] ) ) $this->respon( 0 , "订单错误！" );
        if($info['pay_status'] == 2) $this->respon( 0, '该订单已支付!' );

        $money = $info['pay_money']; // 支付价格
        if ( $money <= 0 ) {
            $resData = array('pay_id'=>2);
            if (Service::getInstance("order")->updateOrderPayStatus($orderID,$resData)) { //支付成功
                Service::getInstance("message")->addMessage($orderID);
            }
            $this->respon( 1 , '支付成功' );
        }

        $tradeInfo = [
            [
                'merchOrderNo' => $info['order_number'],
                'sellerUserId' => $this->config['partnerId'],
                'goodsName' => $info['goodsName'] ? $info['goodsName'] : '--',
                'tradeAmount' => floatval($money),
                'currency' => 'CNY',
                'memo' => $info['goodsName'] . $info
            ]
        ];

        include('yijipay/message/BaseRequest.class.php');
        $request = new BaseRequest();
        $request->setService($this->config['service']);
        $request->setPartnerId($this->config['partnerId']);
        $request->setOrderNo(date(YmdHis) . mt_rand(10000, 99999));
        $request->setMerchOrderNo($info['order_number']);
        $request->setSignType($this->config['signType']);
        $request->setNotifyUrl($this->config['notifyUrl']);
        $request->setTradeInfo($tradeInfo);
        $request->setPaymentType($this->config['paymentType']);

        $userId = $this->registe($request);
        $yijipay = new YijipayClient($this->config);
        //$resp = $yijipay->execute($request);
        //dump($resp);
        $parame = $yijipay->pageExecute($request);
        $this->respon(1, ['userId' => $userId, 'query' => http_build_query($parame)]);
    }

    /**
     * 取消/暂停支付
     */
    public function cancelAction(){
        $orderID = $this->getPost( 'orderId', $_GET['orderId'] );
        if ( !$orderID ) $this->respon( 0 , "订单错误！" );

        $tradeNo = $this->getPost('tradeNo', $_GET['tradeNo']);
        if(!$tradeNo) $this->respon(0, '订单错误!');

        $info = Service::getInstance('order')->info( $orderID );
        if ( !isset( $info['id'] ) ) $this->respon( 0 , "订单错误！" );
        if($info['pay_status'] == 2) $this->respon( 0, '该订单已支付!' );

        include('yijipay/message/BaseRequest.class.php');
        $request = new BaseRequest();
        $request->setService('guaranteePayFreezeUnfreeze');
        $request->setPartnerId($this->config['partnerId']);
        $request->setOrderNo(date(YmdHis) . mt_rand(10000, 99999));
        $request->setTradeNo($tradeNo);
        $request->setMerchOrderNo($info['order_number']);
        $request->setSignType($this->config['signType']);
        $request->setNotifyUrl($this->config['notifyUrl']);
        $request->setExecuteAction('ESCOW_TRADE_BUYER_ESCOW_PAY');

        $yijipay = new YijipayClient($this->config);
        $response = $yijipay->execute($request);
        $response = json_decode($response, true);
        if($response['success']){
            $this->respon(1, $response);
        }else{
            $this->respon(0, $response);
        }

    }

    /**
     * 注册易极付用户
     * @param BaseRequest $request
     * @return mixed
     */
    private function registe(BaseRequest $request){
        $req = $request;
        //根据uid获取易极付的userId
        $user = Service::getInstance('user')->getUserById($this->_login_uid);
        if($user){
            $userId = $user['yijipay_userid'];
            if(!$userId){
                $tel = intval($user['telphone']);
                $req->setService('ppmNewRuleRegisterUser');
                $req->setUserName('冰团E购-' . $user['userName'] . '_' . $user['id']);
                $req->setOrderNo(date(YmdHis) . mt_rand(10000, 99999));
                $req->setRegisterUserType('PERSONAL');
                $req->setMobile($tel);
                $yijipay = new YijipayClient($this->config);
                $response = $yijipay->execute($req);
                $response = json_decode($response, true);
                if($response['success']){
                    $userId = $response['userId'];
                    $data = ['yijipay_userid' => $userId];
                    //更新用户信息
                    Service::getInstance('user')->updateExam($data, $this->_login_uid);
                }
            }
            return $userId;
        }
    }

    /**
     * 获取易极付用户id
     */
    public function getUserIdAction(){
        $uid = $this->getPost( 'uid', $_GET['uid'] );
        if ( !$uid ) $this->respon( 0 , "参数错误" );

        //根据uid获取易极付的userId
        $user = Service::getInstance('user')->getUserInfoById($uid);
        if($user){
            $userId = $user['yijipay_userid'];
            if(!$userId){
                include('yijipay/message/BaseRequest.class.php');
                $req = new BaseRequest();
                $tel = intval($user['telphone']);
                $req->setService('ppmNewRuleRegisterUser');
                $req->setPartnerId($this->config['partnerId']);
                $req->setSignType($this->config['signType']);
                $req->setNotifyUrl($this->config['notifyUrl']);
                $req->setMerchOrderNo(date(YmdHis) . mt_rand(100000, 999999));
                $req->setUserName('冰团E购-' . $user['userName'] . '_' . $user['id']);
                $req->setOrderNo(date(YmdHis) . mt_rand(10000, 99999));
                $req->setRegisterUserType('PERSONAL');
                $req->setMobile($tel);
                $yijipay = new YijipayClient($this->config);
                $response = $yijipay->execute($req);
                $response = json_decode($response, true);
                if($response['success']){
                    $userId = $response['userId'];
                    $data = ['yijipay_userid' => $userId];
                    //更新用户信息
                    Service::getInstance('user')->updateExam($data, $uid);
                }
            }
            $this->respon(1, ['userId' => $userId]);
        }else{
            $this->respon(0, '未注册用户!');
        }
    }

    /**
     * 异步通知地址
     * @return bool
     */
    public function notifyAction(){
        sleep(5);
        Log::simpleappend('pay', json_encode($_REQUEST));
        $yijipay = new YijipayClient($this->config);
        //$verify_result = $yijipay->verify($_REQUEST);
        $out_trade_no = $_REQUEST['merchOrderNo'];
        $trade_no = $_REQUEST['tradeNo'];
        $apiStatus = $_REQUEST['resultCode'] == 'EXECUTE_SUCCESS';
        file_put_contents('notify.log', $_GET);

        Service::getInstance('admorder')->setLog(array('record'=>'我回调进来，进行支付:'.json_encode($_REQUEST), 'create_time'=>time()));

        if($apiStatus)
        {
            $trade_status = $_REQUEST['success'];
            if ($trade_status) {  // 支付成功
                //file_put_contents("paylog.1", "yes");
                echo 'success';

                $data = array('pay_id'=>5,'trade_no'=>$trade_no);

                // 循环获取ID，保证获取ID
                for ($i = 0; $i < 3; $i++) {
                    $order = Service::getInstance('order')->getOrderNumber($out_trade_no);
                    if ($order) {
                        break;
                    }
                }
                if ($order['trade_no']) {
                    return false;
                }
                if ( Service::getInstance('order')->updateOrderPayStatus( $order['id'],$data ) )
                {
                    file_put_contents("paylog.1", json_encode( $data ) ."||" . $out_trade_no );
                    Service::getInstance("message")->addMessage($order['id']);
                    return true;
                }
                else
                {
                    //                 $data = array('pay_id'=>2,'trade_no'=>$trade_no,'orderID'=>$orderID['id']);
                    //file_put_contents("paylog.err", json_encode( $data ) ."||" . $out_trade_no  );
                    logResult( "fail " . $out_trade_no );
                    return false;
                }
            }
        } else {  // 交易失败
            //file_put_contents("paylog.fail", "fail" );
            //将tradeNo加入订单
            /*if($trade_no){
                $data = array('trade_no'=>$trade_no);
                // 循环获取ID，保证获取ID
                for ($i = 0; $i < 3; $i++) {
                    $order = Service::getInstance('order')->getOrderNumber($out_trade_no);
                    if ($order) {
                        break;
                    }
                }
                Service::getInstance('order')->updateOrderPayStatus( $order['id'],$data );
            }*/
            logResult( "err " . $out_trade_no . " || " . json_encode( $_POST ) );
            return false;
        }

        return false;
    }

    /**
     * 更改订单状态
     */
    public function updateOrderStatusAction(){
        $orderId = $this->getPost('orderId', $_GET['orderId']);
        if(!$orderId) $this->respon(0, '参数错误!');

        $data = ['order_status' => 9];
        if ( Service::getInstance('order')->updateOrderStatus( $orderId,$data ) ){
            file_put_contents('processing.log', $_GET);
            $this->respon(1, '更新成功!');
        }else{
            $this->respon(0, '更新失败!');
        }
    }

    /**
     * 退款通知接口
     */
    public function refundNotifyAction(){
        echo 'success';
        $orderNo = $this->getQuery('orderNo');
        if(!$orderNo){
            return false;
        }else{
            $orderNo = substr($orderNo, 0, -5);
        }
        $order = Service::getInstance('order')->getOrderNumber($orderNo);
        if($order['order_status'] == 8){
            return true;
        }
        $isSuccess = $this->getQuery('success');
        $status = $this->getQuery('refundStatus') == 'FINISHED' ? true : false;
        if($isSuccess && $order && $status){
            $orderId = $order['id'];
            if(Service::getInstance('order')->upOrderStatus($orderId)) {
                Service::getInstance('order')->rebackGoodsNumber($orderId); // 把商品退回到库存
                Service::getInstance("message")->addMessage($orderId);
                return true;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }
}