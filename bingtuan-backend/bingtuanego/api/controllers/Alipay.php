<?php
class AlipayController extends BaseController
{
    public function init()
    {
        parent::init();
    }

    //支付宝支付
    public function indexAction ()
    {

        if (  Yaf_Registry::get( 'isLogin' ) <> true )
        {
            $this->respon( 0 , "登录超时!"  );
        }


        $orderID = $this->getPost( 'order_id', $_GET['order_id'] );
        //$orderID = ;
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


        $data['partner'] = '2088912138458597';
        //$data['seller_id'] = $data['partner'];
        $data['seller_id'] = $data['partner'];
        $data['out_trade_no'] = $info['order_number'];
        $data['subject'] = $info['goodsName'] ? $info['goodsName'] : "--";
        $data['body'] = $info['goodsDesc'] ? $info['goodsDesc'] : "--";
        $data['payment_type']  = 1;
        $data['total_fee'] = $money;

//         $data['total_fee'] = 0.01;
//         $data['notify_url'] = "http://api.bingtuanego.net/alipay/pay";
        $data['notify_url']=AP_NotifyUrl."/alipay/pay";
        //$data['notify_url'] = "http://xingshui.todoo.im/alipay/pay";

        $data['service'] = 'mobile.securitypay.pay';
        $data['_input_charset'] = 'utf-8';
        //var_dump($data);
        $data   = $this->_getSign($data);
        //$data['sign_type'] = strtoupper('RSA');
        //$data['sign'] = urlencode($data['sign']);
        Service::getInstance('admorder')->setLog(array('record'=>json_encode($data), 'create_time'=>time()));
        //Service::getInstance('admorder')->setLog(array('record'=>'第一次，进行支付', 'create_time'=>time()));
        $this->respon( 1 , $data );
        return false;
    }


    public function paytestAction(){
        require_once("ali/alipay.config.php");
        require_once("ali/lib/alipay_notify.class.php");
        require_once("ali/lib/alipay_rsa.function.php");
        require_once("ali/lib/alipay_core.function.php");



        $orderID = $this->getPost( 'order_id', $_GET['order_id'] );
        //$orderID = ;
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

        $data['partner'] = '2088912138458597';
        //$data['seller_id'] = $data['partner'];
        $data['seller_id'] = $data['partner'];
        $data['out_trade_no'] = $info['order_number'];
        $data['subject'] = $info['goodsName'] ? $info['goodsName'] : "--";
        $data['body'] = $info['goodsDesc'] ? $info['goodsDesc'] : "--";
        $data['payment_type']  = 1;
        $data['total_fee'] = $money;

//         $data['total_fee'] = 0.01;
//         $data['notify_url'] = "http://api.bingtuanego.net/alipay/pay";
        $data['notify_url']=AP_NotifyUrl."/alipay/pay";
        //$data['notify_url'] = "http://xingshui.todoo.im/alipay/pay";

        $data['service'] = 'mobile.securitypay.pay';
        $data['_input_charset'] = 'utf-8';

//确认PID和接口名称是否匹配。
        date_default_timezone_set("PRC");
        if (str_replace('"','',$data['partner'])==$alipay_config['partner']&&str_replace('"','',$data['service'])==$alipay_config['service']) {

            //将post接收到的数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串。
            $data=createLinkstring($data);

            //打印待签名字符串。工程目录下的log文件夹中的log.txt。
            logResult($data);

            //将待签名字符串使用私钥签名,且做urlencode. 注意：请求到支付宝只需要做一次urlencode.
            $rsa_sign=urlencode(rsaSign($data, $alipay_config['private_key']));

            //把签名得到的sign和签名类型sign_type拼接在待签名字符串后面。
            $data = $data.'&sign='.'"'.$rsa_sign.'"'.'&sign_type='.'"'.$alipay_config['sign_type'].'"';

            $abc['the_string'] = $data;
            //返回给客户端,建议在客户端使用私钥对应的公钥做一次验签，保证不是他人传输。
            $this->respon( 1 , $abc );
            //echo $data;
        }
        else{
            echo "不匹配或为空！";
            logResult(createLinkstring($_POST));
        }
    }




    /**
     * 得到数据签名
     *
     * @author xjiujiu <xjiujiu@foxmail.com>
     * @access private
     * @param $data 数据
     * @return String 数据签名
     */
    private function _getSign($data)
    {
        $alipay_config = require_once("alipay.config.php");
        require_once("lib/alipay_submit.class.php");
        $submit     = new AlipaySubmit($alipay_config);
        return $submit->buildRequestPara($data);

        /*
        require_once("ali/alipay.config.php");
        require_once("ali/lib/alipay_notify.class.php");
        require_once("ali/lib/alipay_rsa.function.php");
        require_once("ali/lib/alipay_core.function.php");
        //$alipay_config = require_once("ali/alipay.config.php");
        //require_once("lib/alipay_submit.class.php");
        //$submit     = new AlipaySubmit($alipay_config);
        //将post接收到的数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串。
        $str=createLinkstring($data);
        return $rsa_sign=urlencode(rsaSign($str, $alipay_config['private_key']));
        */

    }

    //回调方法
    public function payAction()
    {
        Log::simpleappend('pay', json_encode($_REQUEST));
        $alipay_config = require_once("alipay.config.php");
        require_once("lib/alipay_notify.class.php");
        $alipayNotify = new AlipayNotify($alipay_config);
        $verify_result = $alipayNotify->verifyNotify();

        $out_trade_no = $_REQUEST['out_trade_no'];
        $trade_no = $_REQUEST['trade_no'];
        $total_fee = $_REQUEST['total_fee'];
        $trade_status = $_REQUEST['trade_status'];

        Service::getInstance('admorder')->setLog(array('record'=>'我回调进来，进行支付:'.json_encode($_REQUEST), 'create_time'=>time()));
        if( $verify_result )
        {
            $trade_status = $_REQUEST['trade_status'];
            if( $trade_status == 'TRADE_FINISHED' ) {  // 交易成功
                logResult('pay_finish'.json_encode($_REQUEST));
            } else if ($trade_status == 'TRADE_SUCCESS') {  // 支付成功
                //file_put_contents("paylog.1", "yes");

                $data = array('pay_id'=>2,'trade_no'=>$trade_no);

                // 循环获取ID，保证获取ID
                for ($i = 0; $i < 3; $i++) {
                    $orderID = Service::getInstance('order')->getOrderNumber($out_trade_no);
                    if ($orderID) {
                        break;
                    }
                }
                if ($orderID['trade_no']) {
                    return false;
                }
                if ( Service::getInstance('order')->updateOrderPayStatus( $orderID['id'],$data ) )
                {
                    file_put_contents("paylog.1", json_encode( $data ) ."||" . $out_trade_no );
                    Service::getInstance("message")->addMessage($orderID['id']);
                    exit("success");
                }
                else
                {
                    //                 $data = array('pay_id'=>2,'trade_no'=>$trade_no,'orderID'=>$orderID['id']);
                    //file_put_contents("paylog.err", json_encode( $data ) ."||" . $out_trade_no  );
                    logResult( "fail " . $out_trade_no );
                    exit("update fail");
                }
            }
        } else {  // 交易失败
            //file_put_contents("paylog.fail", "fail" );
            logResult( "err " . $out_trade_no . " || " . json_encode( $_POST ) );
            exit("verify fail");
        }

        return false;
    }
}
