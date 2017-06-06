<?php

class OrdersController extends BaseController
{
    public function init()
    {
        parent::init();
    }
    
    /**
     * 订单列表
     */
    public function orderListAction()
    {
        $userinfo = Yaf_Registry::get('developer');
        $orderid = $this->getQuery('order_number','');
        $minprice = $this->getQuery('minprice','');
        $maxprice = $this->getQuery('maxprice','');
        $ktime = $this->getQuery('ktime','');
        $gtime = $this->getQuery('gtime','');
        $account = $this->getQuery('account','');
        $tel = $this->getQuery('tel','');
        if($this->_view->rbac == '*'){
            $company_id = 0;
        }else{
            $company_id = $this->companyIds;
        }
        $queryId = $this->getQuery('company_id',0);
        //$company_id = ($this->_view->rbac == '*') ? $this->getQuery('company_id',0) : $userinfo['company_id'] ;
        $warehouse_id = $this->getQuery('warehouse_id', 0);
        $order_status = $this->getQuery('order_status','');
        if($minprice!='' || $maxprice!=''){
            $amount = ($minprice!=''? " toi.goods_amount>=$minprice":'') . ($minprice!='' && $maxprice!='' ? ' and ':'') . ($maxprice!='' ? " toi.goods_amount<=$maxprice ": '' );
        }
        if(trim($ktime) != '' || trim($gtime) != ''){
            $add_time = (!empty($ktime) ? ' toi.add_time>='.strtotime($ktime) : '').((!empty($ktime) && !empty($gtime))?' and ':'').(!empty($gtime)?' toi.add_time<='.(strtotime($gtime)+86399).' ':'');
        }
        $where = array(
            'order_number' => $orderid,
            'goods_amount' => $amount,
            'add_time' => $add_time,
            'order_status' => $order_status,
            'company_id' => $company_id,
            'warehouse_id' => $warehouse_id,
            'account' => $account,
            'tel' => $tel,
            'query_id' => $queryId
        );
        $sqlwhere=$where;
        unset($sqlwhere['goods_amount']);
        unset($sqlwhere['add_time']);
        $sqlwhere['minprice'] = $minprice;
        $sqlwhere['maxprice'] = $maxprice;
        $sqlwhere['ktime'] = $this->getQuery('ktime','');
        $sqlwhere['gtime'] = $this->getQuery('gtime','');
        //分页
        $pag = $this->getQuery('page','')?$this->getQuery('page',''):'1';
        $pageSize = 10;
        $pags = ($pag*$pageSize)-$pageSize;
        $order_list = Service::getInstance('admorder')->getBuyOrderList($pags,$pageSize,$where);
        $this->_view->order_list = $order_list;
        $this->_view->get = $sqlwhere;
        $sql = Service::getInstance('admorder')->getBuyOrderSql($where);
        $sqlwhere['company_id'] = $queryId;
        unset($sqlwhere['query_id']);
        $url = "http://".$_SERVER['SERVER_NAME']."/orders/orderList?page=__page__&".http_build_query($sqlwhere);
        //获取总数量的SQL
        $page_sql = Service::getInstance('admorder')->getBuyOrderSql($where,1);
        $num = Service::getInstance('admorder')->getOrderNum($where);
        $listInfos = Service::getInstance('admorder')->getmaiOrderPags($page_sql,$pageSize,$pag,$url);
        $this->_view->paglist = $listInfos;
        $num = $num['num'];
        if($_COOKIE['number']){
            if($num > $_COOKIE['number']){
                $this->_view->hasNewOrder = 1;
                setcookie('number', $num);
            }elseif($num == $_COOKIE['number']){
                $this->_view->hasNewOrder = 0;
            }else{
                $this->_view->hasNewOrder = 0;
                setcookie('number', $num);
            }
        }else{
            setcookie('number', $num);
        }

        $this->_view->getCompanyDealersList = Service::getInstance('region')->getCompanyDealersList(array('company' => $company_id, 'warehouse' => $warehouse_id,'query_id'=>$queryId));
    }
    /**
     * 修改订单状态
     */
    public function ajaxOrderStatesAction()
    {
        $order_id = $this->getPost('id','');
        $type = $this->getPost('type','');
        
        $data = array();
        switch ($type) {
            case 1:
                $data['order_status'] = 1;
                $data['shipping_status'] = 1;
                $data['sign_status'] = 1;
                $data['pay_status'] = 1;
                break;
            case 2:
                $data['order_status'] = 3;
                $data['shipping_status'] = 1;
                $data['sign_status'] = 1;
                $data['pay_status'] = 2;
                break;
            case 3:
                $data['order_status'] = 3;
                $data['shipping_status'] = 2;
                $data['sign_status'] = 1;
                $data['pay_status'] = 2;
                break;
            case 4:
                $data['order_status'] = 3;
                $data['shipping_status'] = 2;
                $data['sign_status'] = 2;
                $data['pay_status'] = 2;
                break;
            case 5:
                $data['order_status'] = 7;
                break;
            case 6:
                $data['order_status'] = 2;
                break;
            case 7:
                $data['order_status'] = 8;
                break;
            default:
                echo false;
                exit;
            break;
        }
        //添加登陆日志id
        $userinfo = Yaf_Registry::get('developer');
        $goods['log_type'] = 5;
        $goods['action'] = '编辑订单状态';
        $goods['uid'] = $userinfo['id'];
        $goods['create_time'] = time();
        $goods['action_id'] = $order_id;
        $goods['action_json'] = json_encode($data);
        $goods['ip'] = $_SERVER["REMOTE_ADDR"];
        Service::getInstance('systemlog')->addLog($goods);
        //添加结束
        $return = Service::getInstance('Admorder')->updateOrder($data,$order_id);
        echo $return;
        exit;
    }
    /* *
     * 功能：即时到账批量退款有密接口接入页
     * 版本：3.3
     * 修改日期：2012-07-23
     * 说明：
     * 以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己网站的需要，按照技术文档编写,并非一定要使用该代码。
     * 该代码仅供学习和研究支付宝接口使用，只是提供一个参考。

     *************************注意*************************
     * 如果您在接口集成过程中遇到问题，可以按照下面的途径来解决
     * 1、商户服务中心（https://b.alipay.com/support/helperApply.htm?action=consultationApply），提交申请集成协助，我们会有专业的技术工程师主动联系您协助解决
     * 2、商户帮助中心（http://help.alipay.com/support/232511-16307/0-16307.htm?sh=Y&info_type=9）
     * 3、支付宝论坛（http://club.alipay.com/read-htm-tid-8681712.html）
     * 如果不想使用扩展功能请把扩展功能参数赋空值。
     */
    public function refundAction() {
        header("Content-type:text/html;charset=utf-8");
        $orderId = $this->getPost('orderId');
        if ( !$orderId ) $this->respon( 0, '参数错误' );
        $orderInfo = Service::getInstance('order')->info( $orderId );
        if ( $orderInfo['order_status'] == 8 ) $this->respon( 0, '该订单已退款' );

        $user_type = $this->db->fetchOne('SELECT user_type FROM `users` AS u WHERE u.`id`=?', array($orderInfo['user_id']));

        if ($user_type == 2) {
            Service::getInstance("order")->updateOrderRefundStatusByWeixin($orderId,2);
            Service::getInstance('order')->rebackGoodsNumber($orderId); // 把商品退回到库存
            Service::getInstance("message")->addMessage($orderId, 'cancel');
            return '成功取消订单';
        }

        if ( $orderInfo['order_status'] == 4 || $orderInfo['order_status'] == 7) {
            Log::simpleappend('reorder1', $orderId);
            $refundInfo = Service::getInstance('order')->getRefundInfoByOrderId( $orderId );
            Log::simpleappend('reorder2', $orderInfo);
            require_once("alipayre.config.php");
            require_once("relib/alipay_submit.class.php");

            /**************************请求参数**************************/

            //服务器异步通知页面路径
//             $notify_url = "http://api.bingtuanego.net/orders/alipayNotify";
           // $notify_url = "http://xingshuiadm.todoo.im/orders/alipayNotify";
           
             $notify_url = ADM_NotifyUrl."/orders/alipayNotify";
            
            //需http://格式的完整路径，不允许加?id=123这类自定义参数

            //卖家支付宝帐户
            $seller_email = 'epay@bingtuanwang.com';
            //必填

            //退款当天日期
            $refund_date = date('Y-m-d H:i:s');
            //必填，格式：年[4位]-月[2位]-日[2位] 小时[2位 24小时制]:分[2位]:秒[2位]，如：2007-10-01 13:13:13
            $num = rand(3, 24);
            $code = '';
            for ($i = 0; $i < $num; $i++) {
                if ($i == 0) {
                    $code .= rand(1, 9);
                } else {
                    $code .= rand(0, 9);
                }
            }
            //批次号
            $batch_no = date('Ymd').trim($code);
            //必填，格式：当天日期[8位]+序列号[3至24位]，如：201008010000001

            //退款笔数
            $batch_num = "1";
            //必填，参数detail_data的值中，“#”字符出现的数量加1，最大支持1000笔（即“#”字符出现的数量999个）

            //退款详细数据
            $detail_data = $orderInfo['trade_no']."^"."{$orderInfo['pay_money']}"."^".trim($refundInfo['content']);
            //必填，具体格式请参见接口技术文档


            /************************************************************/

            //构造要请求的参数数组，无需改动
            $parameter = array(
                "service" => "refund_fastpay_by_platform_pwd",
                "partner" => '2088912138458597',
                "notify_url"	=> $notify_url,
                "seller_email"	=> $seller_email,
                "refund_date"	=> $refund_date,
                "batch_no"	=> $batch_no,
                "batch_num"	=> $batch_num,
                "detail_data"	=> $detail_data,
                "_input_charset"	=> trim(strtolower($alipay_config['input_charset']))
            );
            Log::simpleappend('reorder', json_encode($parameter));
            Service::getInstance('admorder')->setLog(array('record'=>$orderInfo['order_number'].'进行退款', 'create_time'=>time()));
            //建立请求
            $alipaySubmit = new AlipaySubmit($alipay_config);
            $html_text = $alipaySubmit->buildRequestForm($parameter,"get", "确认");
            //添加登陆日志id
            $userinfo = Yaf_Registry::get('developer');
            $goods['log_type'] = 5;
            $goods['action'] = '支付宝退款';
            $goods['uid'] = $userinfo['id'];
            $goods['create_time'] = time();
            $goods['action_id'] = $orderId;
            $goods['ip'] = $_SERVER["REMOTE_ADDR"];
            Service::getInstance('systemlog')->addLog($goods);
            exit($html_text);
        } else {
            exit( '该订单退款信息错误' );
        }
        return false;
    }
    
    /* *
     * 功能：支付宝服务器异步通知页面
     * 版本：3.3
     * 日期：2012-07-23
     * 说明：
     * 以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己网站的需要，按照技术文档编写,并非一定要使用该代码。
     * 该代码仅供学习和研究支付宝接口使用，只是提供一个参考。
     *************************页面功能说明*************************
     * 创建该页面文件时，请留心该页面文件中无任何HTML代码及空格。
     * 该页面不能在本机电脑测试，请到服务器上做测试。请确保外部可以访问该页面。
     * 该页面调试工具请使用写文本函数logResult，该函数已被默认关闭，见alipay_notify_class.php中的函数verifyNotify
     * 如果没有收到该页面返回的 success 信息，支付宝会在24小时内按一定的时间策略重发通知
     */
    public function alipayNotifyAction() {
        require_once("alipayre.config.php");
        require_once("relib/alipay_notify.class.php");
        //计算得出通知验证结果
        $alipayNotify = new AlipayNotify($alipay_config);
        $verify_result = $alipayNotify->verifyNotify();
        Service::getInstance('admorder')->setLog(array('record'=>'进入回调', 'create_time'=>time()));
        
        Service::getInstance('admorder')->setLog(array('record'=>"$verify_result", 'create_time'=>time()));
        if($verify_result) {//验证成功
            /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
            //请在这里加上商户的业务逻辑程序代
            //——请根据您的业务逻辑来编写程序（以下代码仅作参考）——
            //获取支付宝的通知返回参数，可参考技术文档中服务器异步通知参数列表
            //批次号
            $batch_no = $_POST['batch_no'];
            //批量退款数据中转账成功的笔数
            $success_num = $_POST['success_num'];
            //批量退款数据中的详细信息
            $result_details = $_POST['result_details'];
            //判断是否在商户网站中已经做过了这次通知返回的处理
            //如果没有做过处理，那么执行商户的业务程序
            //如果有做过处理，那么不执行商户的业务程序
            Log::simpleappend('notify_refund', $_POST);
            $arr = explode('^', $result_details);
            Service::getInstance('admorder')->setLog(array('record'=>$arr, 'create_time'=>time()));
            if (end($arr) == 'SUCCESS') {
                $trade_no = $arr[0];
                Service::getInstance('admorder')->setLog(array('record'=>$trade_no, 'create_time'=>time()));
                $orderID = Service::getInstance('order')->getOrderByTrade($trade_no);
                Service::getInstance('admorder')->setLog(array('record'=>$orderID, 'create_time'=>time()));
                if(Service::getInstance('order')->upOrderStatus($orderID)) {
                    Service::getInstance('order')->rebackGoodsNumber($orderID); // 把商品退回到库存
                    Service::getInstance("message")->addMessage($orderID);
                    exit("success");		//请不要修改或删除
                } else {
                    exit("fail");
                }
            } else {
                exit("fail");
            }
             
            //调试用，写文本函数记录程序运行情况是否正常
            //logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
            //——请根据您的业务逻辑来编写程序（以上代码仅作参考）——
            /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        } else {
            //验证失败
            exit("fail");
            //调试用，写文本函数记录程序运行情况是否正常
            //logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
        }
    }

    /**
     * 易极付退款
     * @return string
     */
    public function yijiRefundAction(){
        $orderId = $this->getPost('orderId');
        if ( !$orderId ) $this->respon( 0, '参数错误' );
        $orderInfo = Service::getInstance('order')->info( $orderId );
        if ( $orderInfo['order_status'] == 8 ) $this->respon( 0, '该订单已退款' );

        $user_type = $this->db->fetchOne('SELECT user_type FROM `users` AS u WHERE u.`id`=?', array($orderInfo['user_id']));
        if ($user_type == 2) {
            $status = Service::getInstance("order")->afterUserRefund($orderInfo['user_id'], $orderId);
            if($status){
                Service::getInstance("order")->updateOrderRefundStatusByWeixin($orderId,2);
                Service::getInstance('order')->rebackGoodsNumber($orderId); // 把商品退回到库存
                Service::getInstance("message")->addMessage($orderId, 'cancel');
                $this->respon(1, '退款成功');
            }else{
                $this->respon(0, '退款失败');
            }
        }

        if ( $orderInfo['order_status'] == 4 || $orderInfo['order_status'] == 7) {
            Log::simpleappend('reorder1', $orderId);
            //组建退款数据
            $config = include "yijipay/config.php";
            include('yijipay/message/BaseRequest.class.php');
            $request = new BaseRequest();
            $orderNo = $orderInfo['order_number'];
            $request->setService($config['service']);
            $request->setPartnerId($config['partnerId']);
            $request->setOrderNo($orderNo . mt_rand(10000, 99999));
            $request->setMerchOrderNo($orderNo . mt_rand(10000, 99999));
            $request->setNotifyUrl($config['notifyUrl']);
            $request->setSignType($config['signType']);
            $request->setTradeNo($orderInfo['trade_no']);
            $request->setRefundAmount(floatval($orderInfo['pay_money']));
            $request->setRefundReason('订单取消');

            include('yijipay/YijipayClient.class.php');
            Log::simpleappend('reorder', json_encode($request));
            $yijipay = new YijipayClient($config);
            Service::getInstance('admorder')->setLog(array('record'=>$orderInfo['order_number'].'进行退款', 'create_time'=>time()));
            $resp = $yijipay->execute($request);
            $res = json_decode($resp, true);

            //添加登陆日志id
            $userinfo = Yaf_Registry::get('developer');
            $goods['log_type'] = 5;
            $goods['action'] = '易极付退款';
            $goods['uid'] = $userinfo['id'];
            $goods['create_time'] = time();
            $goods['action_id'] = $orderId;
            $goods['ip'] = $_SERVER["REMOTE_ADDR"];
            Service::getInstance('systemlog')->addLog($goods);
            if($res['success']){
                if($res['resultCode'] == 'EXECUTE_SUCCESS' || $res['resultCode'] == 'EXECUTE_PROCESSING'){
                    if(Service::getInstance('order')->upOrderStatus($orderId)){
                        Service::getInstance('order')->rebackGoodsNumber($orderId); // 把商品退回到库存
                        Service::getInstance("message")->addMessage($orderId);
                        $this->respon(1, '退款成功');
                    }else{
                        $this->respon(0, '退款失败');
                    }
                }else{
                    $this->respon(0, $res['resultMessage']);
                }
            }else{
                $this->respon(0, $res['resultMessage']);
            }
        }
    }

    /**
     * 易极付交易查询
     */
    public function tradeQueryAction(){
        $tradeNo = $this->getPost('tradeNo');
        if ( !$tradeNo ) $this->respon( 0, '参数错误' );
        $config = include "yijipay/config.php";
        include('yijipay/message/BaseRequest.class.php');
        $request = new BaseRequest();
        $request->setService('multipleTradeMergeQuery');
        $request->setPartnerId($config['partnerId']);
        $request->setOrderNo(date(YmdHis) . mt_rand(10000, 99999));
        $request->setNotifyUrl($config['notifyUrl']);
        $request->setSignType($config['signType']);
        $request->setTradeNos($tradeNo);

        include('yijipay/YijipayClient.class.php');
        $yijipay = new YijipayClient($config);
        $resp = $yijipay->execute($request);
        $res = json_decode($resp, true);
        if($res['resultCode'] == 'EXECUTE_SUCCESS'){
            if($res['singleTradeInfos'][0]['fastPayStatus'] == 'FINISHED'){
                $this->respon(1, '该订单处理完成!');
            }elseif($res['singleTradeInfos'][0]['fastPayStatus'] == 'CLOSED'){
                $this->respon(0, '该订单已关闭!');
            }else{
                $this->respon(0, '该订单支付未完成!');
            }
        }else{
            $this->respon(0, '未查询到该订单信息!');
        }
    }
    
    /**
     * 订单列表
     */
    public function purchaseListAction()
    {
        //分页
        $pag = $this->getQuery('page','')?$this->getQuery('page',''):'1';
        $pageSize = 10;
        $pags = ($pag*$pageSize)-$pageSize;
        $sorank = $this->getQuery('sorank','');
        //地区
        $province_id = $this->getQuery('province_id','');
        $city_id = $this->getQuery('city_id','');
        $area_id = $this->getQuery('area_id','');
        $status = $this->getQuery('status','');
        $ktime = $this->getQuery('ktime','');
        $gtime = $this->getQuery('gtime','');
        $minprice = $this->getQuery('minprice','');
        $maxprice = $this->getQuery('maxprice','');
        
        if($minprice!='' || $maxprice!=''){
            $amount = ($minprice!=''? " a1.goods_amount>=$minprice":'') . ($minprice!='' && $maxprice!='' ? ' and ':'') . ($maxprice!='' ? " a1.goods_amount<=$maxprice ": '' );
        }
        if(trim($ktime) != '' || trim($gtime) != ''){
            $add_time = (!empty($ktime) ? ' a1.add_time>='.strtotime($ktime) : '').((!empty($ktime) && !empty($gtime))?' and ':'').(!empty($gtime)?' a1.add_time<='.strtotime($gtime).' ':'');
        }
        
        $order_number = $this->getQuery('order_number','');
        $address = array('province_id'=>$province_id,'city_id'=>$city_id,'area_id'=>$area_id);
        $order_list = Service::getInstance('admorders')->getPurchaseList($pags,$pageSize,$sorank,$address,$status,$order_number,$add_time,$amount);
        $rank = explode('_',$sorank);
        $rank[1] = $rank[1]=='asc'?'desc':'asc';
        $sorank = implode('_',$rank);
        if($this->getQuery('time','')) $url.="&sorank=".$sorank;
        $soarray = array('time','money');
        foreach($soarray as $v){
            if($rank[0]==$v){
                $$v = $sorank;
            }else{
                $$v = $v."_desc";
            }
        }
        $sql=Service::getInstance('admorders')->getOrderListSql($sorank,$address,$status,$order_number,$add_time,$amount);
        
        $url = "http://".$_SERVER['SERVER_NAME']."/orders/purchaseList?page=__page__";
        $this->_view->paglist =Service::getInstance('admorders')->getOrderPags($sql,$pageSize,$pag,$url);
        $this->_view->jiliancaidan = Service::getInstance('admgoods')->getCAIDAN();
        $this->_view->order_list = $order_list;
    }
    
    /**
     * 进货订单详情
     * @param unknown $id
     * @return unknown
     */
    public function purchaseInfoAction()
    {
        $order_id = $this->getQuery('order_id');
        if ($order_id) {
            $orderGoods = Service::getInstance('admorders')->orderGoods($order_id,'selldb');
            $orderInfo = Service::getInstance('admorders')->orderInfo($order_id,'selldb');
            $data = array();
            foreach ($orderGoods as $key => $val) {
                $data['goods_list'][$key] = $val;
                foreach ($data['goods_list'] as $k ) {
                    if ($k['goods_thumb']) {
                        $data['goods_list'][$key]['goods_thumb'] = Util::getpicbyhash($k['goods_thumb']);
                    }
                }
            }
            //卖家注册有实名制，买家只有到下单时才有送货人信息.
            foreach ($orderInfo as $k ) {
                $data['order_id'] = ($orderInfo['order_id'])?$orderInfo['order_id']:'';
				$data['consignee'] = ($orderInfo['consignee'])?$orderInfo['consignee']:'';
				$data['seller_name'] = ($orderInfo['seller_name'])?$orderInfo['seller_name']:'';
				$data['order_number'] = ($orderInfo['order_number'])?$orderInfo['order_number']:'';
                $data['goods_amount'] = ($orderInfo['goods_amount'])?$orderInfo['goods_amount']:'';
                $data['order_address'] = ($orderInfo['order_address'])?$orderInfo['order_address']:'';
                $data['add_time'] = ($orderInfo['add_time'])?$orderInfo['add_time']:'';
                $data['postscript'] = ($orderInfo['postscript'])?$orderInfo['postscript']:'';
                $data['couponsId'] = ($orderInfo['couponsId'])?$orderInfo['couponsId']:'';
                $data['seller_address'] = ($orderInfo['seller_address'])?$orderInfo['seller_address']:'';
                $data['seller_tel'] = ($orderInfo['seller_tel'])?$orderInfo['seller_tel']:'';
                $data['return_reason'] = $orderInfo['return_reason'];
				$status = Util::getSellOrderStatus($orderInfo['order_status'],$orderInfo['shipping_status'],$orderInfo['sign_status'],true);
				$data['status'] = ($status)?$status:'';
                $data['shipping_status'] = ($orderInfo['shipping_status'])?$orderInfo['shipping_status']:'';
                $data['shipping_type'] = ($orderInfo['shipping_type'])?$orderInfo['shipping_type']:'';
                $data['pay_status'] = ($orderInfo['pay_status'])?$orderInfo['pay_status']:'';
                $data['order_status'] = ($orderInfo['order_status'])?$orderInfo['order_status']:'';
                $data['shipments_time'] = ($orderInfo['shipments_time'])?$orderInfo['shipments_time']:'';
                $data['shipping_name'] = ($orderInfo['shipping_type'] == 1)?'公司配送':'客户自提';
                $data['pay_id'] = $orderInfo['pay_id'];
            }
        }
//         util::dump($data);
        $this->_view->order_info = $data;
    }
    /**
     * 取消进货单
     */
    public function cancelStockAction()
    {
        $content = $this->getPost('content');
        $orderID = $this->getPost('order_id');
        if ($content == '') {$this->flash('purchaseList','请填写取消原因。'); return false;}

        if (Service::getInstance('admorders')->cancelStock(array('content'=>$content,'order_id'=>$orderID))) {
            $this->flash('purchaseList','取消进货单成功');
			return false;
        } else {
            $this->flash('purchaseList','取消进货单失败');
            return false;
        }
    }
    
    /**
     * 订单详情
     * @param unknown $id
     * @return unknown
     */
    public function orderInfoAction($id='')
    {
        if (!$id) {
            $order_id = $this->getQuery('order_id');
            $user_id = $this->getQuery('user_id');
        } else {
            $order_id = $id;
        }
        
        if ($order_id) {
            $orderUserInfo = Service::getInstance('order')->orderUserInfo($order_id, $user_id);
//             $this->_view->companyDealersList = Service::getInstance('region')->getCompanyDealersList(array('company'=>$where['company_id'], 'warehouse'=>$where['warehouse_id']));
            $data = array();
            
            $data['comName'] = $orderUserInfo['comName'];
            $data['consignee'] = ($orderUserInfo['consignee'])?$orderUserInfo['consignee']:'';
            $data['tel'] = ($orderUserInfo['tel'])?$orderUserInfo['tel']:'';
            $data['order_address'] = ($orderUserInfo['order_address'])?$orderUserInfo['order_address']:'';
            $data['account'] = ($orderUserInfo['account'])?$orderUserInfo['account']:'';
            $data['comName'] = ($orderUserInfo['comName'])?$orderUserInfo['comName']:'';
            $data['userName'] = ($orderUserInfo['userName'])?$orderUserInfo['userName']:'';
            $orderGoods = Service::getInstance('admorder')->orderGoods($order_id);
            foreach ($orderGoods as $key => $val) {
                $data['goods_list'][$key] = $val;
                foreach ($data['goods_list'] as $k ) {
                    if ($k['goods_thumb']) {
                        $data['goods_list'][$key]['goods_thumb'] = Util::getpicbyhash($k['goods_thumb']);
                    }
                }
            }
            $orderInfo = Service::getInstance('admorder')->orderInfo($order_id,$orderInfo['goods_amount']);
            $newMoney = 0;
            $countMoney = 0;
            if ($orderInfo['couponsId']) {
                foreach ($orderGoods as $key => $val) {
                    $couponsMoney = $val['goods_number']*$val['goods_money'];
                    if (empty($val['activity_info'])) {
                        $arr = explode(',', $orderInfo['couponsId']);
                        
                        foreach ($arr as $k => $v) {
                            $coupon = Service::getInstance('coupons')->getCouponById( $v, $val['goods_id']);
                            if ($coupon) {
                                if ($coupon['type'] == 2) {
                                    $countMoney = $couponsMoney - ($couponsMoney*($coupon['type_num']*0.1));
                                } else {
                                    $countMoney = $couponsMoney - ($couponsMoney-$coupon['type_num']);
                                }
                                $newMoney += ($countMoney <= 0) ? 0 : $countMoney;
                            }
                            
                        }
                    }
                }
            }
            $voucher = 0;
            if ($orderInfo['voucher_id']) {
                $voucher = Service::getInstance('coupons')->getVoucher($orderInfo['voucher_id']);
            }
            $this->_view->coupon_amount = $newMoney;
            $this->_view->offcut = 0;
            $giftInfo = json_decode($orderInfo['benefit_info'], true);
            if($giftInfo && $giftInfo['type'] == 0){
                $this->_view->offcut = $giftInfo['offcut'];
            }
            $money = $orderInfo['goods_amount']-$newMoney - $voucher - $this->_view->offcut;
            $this->_view->securities_amount = ($money <= 0) ? 0 : $money;
            
            //卖家注册有实名制，买家只有到下单时才有送货人信息.
            $data['order_id'] = ($orderInfo['order_id'])?$orderInfo['order_id']:'';
            $data['goods_amount'] = ($orderInfo['goods_amount'])?$orderInfo['goods_amount']:'';
            $data['pay_money'] = ($orderInfo['pay_money'])?$orderInfo['pay_money']:'';
            $data['order_number'] = ($orderInfo['order_number'])?$orderInfo['order_number']:'';
            $data['order_address'] = ($orderInfo['order_address'])?$orderInfo['order_address']:'';
            $data['add_time'] = ($orderInfo['add_time'])?$orderInfo['add_time']:'';
            $data['postscript'] = ($orderInfo['postscript'])?$orderInfo['postscript']:'';
            $data['seller_address'] = ($orderInfo['seller_address'])?$orderInfo['seller_address']:'';
            $data['seller_tel'] = ($orderInfo['seller_tel'])?$orderInfo['seller_tel']:'';
            $data['return_reason'] = $orderInfo['return_reason'];
		    $status = Util::getOrderInfoStatus($orderInfo['order_status'],$orderInfo['shipping_status'],$orderInfo['shipping_type'],$orderInfo['pay_status'],$orderInfo['sign_status']);
			$data['status'] = ($status[0])?$status[0]:'';
			$data['status_num'] = ($status[1])?$status[1]:'';
            $data['shipping_status'] = ($orderInfo['shipping_status'])?$orderInfo['shipping_status']:'';
            $data['shipping_type'] = ($orderInfo['shipping_type'])?$orderInfo['shipping_type']:'';
            $data['pay_status'] = ($orderInfo['pay_status'])?$orderInfo['pay_status']:'';
            $data['order_status'] = ($orderInfo['order_status'])?$orderInfo['order_status']:'';
            $data['pay_time'] = ($orderInfo['pay_time'])?$orderInfo['pay_time']:'';
            $data['seller_name'] = ($orderInfo['seller_name'])?$orderInfo['seller_name']:'';
            $data['shipments_time'] = ($orderInfo['shipments_time'])?$orderInfo['shipments_time']:'';
            $data['order_status'] = ($orderInfo['order_status'])?$orderInfo['order_status']:'';
            $data['pay_id'] = $orderInfo['pay_id'];
            $data['trade_no'] = (empty($orderInfo['trade_no']))?'':$orderInfo['trade_no'];
            $data['voucher'] = $voucher;
            $data['companyName'] = $orderInfo['companyName'];
            $data['warehouseName'] = $orderInfo['warehouseName'];
            $data['num'] = $orderInfo['print_num'];
            $data['giftInfo'] = $giftInfo;
            
            $coupons = Service::getInstance('order')->getOrderCoupons($orderInfo['couponsId'], $orderInfo['voucher_id']);
            
            $data['coupons'] = array_merge($coupons[0],array($coupons[1]));
        }
//Util::dump($data);
        if ($id) {
            return $data;
        } else {
            $this->_view->order_info = $data;
        }
    }
    
    /**
     * 获取订单优惠价格
     */
    public function orderYou($orderid,$goods_amount){
        $goodsinfo = Service::getInstance('order')->orderUserInfo($orderid);
        
        $couponsId = explode(',', $goodsinfo['couponsId']);
        $newMoney = 0;
        $activityMoney = 0;
        $count = 0;
        
        print_r($couponsId);die;
        
        $securities_amount = 0;
        if($goodsinfo['voucher_id']){
            $couponsinfo = $this->couponsinfo($goodsinfo['voucher_id'],1);
            $securities_amount = round($couponsinfo['line'],2);
        }
        
        return array('coupon_amount' => $coupon_amount, 'securities_amount' => $securities_amount);
    }
    
    /**
     * 获取优惠卷详情及优惠卷关联商品
     */
    public function couponsinfo($coupons){
        $sql="select *,a2.type as ctype from coupons_goods a1 left join coupons as a2 on a1.coupons_id=a2.id left join goods a3 on a1.goods_id=a3.id where a1.coupons_id='$coupons'";
        return $this->db->fetchRow($sql);
    }
    
    /**
     * 发货
     * @param unknown $data
     * @param unknown $id
     * @return unknown
     */
    public function deliverGoodsAction()
    {
        $id = $this->getPost('id');
        $return = Service::getInstance('admorder')->deliverGoods($id);
        if ($return) {
            Service::getInstance("message")->addMessage($id,'pay');
        }
        //添加登陆日志id
        $userinfo = Yaf_Registry::get('developer');
        $goods['log_type'] = 5;
        $goods['action'] = '发货';
        $goods['uid'] = $userinfo['id'];
        $goods['create_time'] = time();
        $goods['action_id'] = $id;
        $goods['ip'] = $_SERVER["REMOTE_ADDR"];
        Service::getInstance('systemlog')->addLog($goods);
        //添加结束
        echo json_encode($return);
        exit;
    }
    /**
     * 微信审核退款
     */
    public function weixinRefundAction()
    {
        $orderID = $this->getPost('id');
        $return = Service::getInstance('admorder')->weixinCancel($orderID);
        //添加登陆日志id
        $userinfo = Yaf_Registry::get('developer');
        $goods['log_type'] = 5;
        $goods['action'] = '微信退款';
        $goods['uid'] = $userinfo['id'];
        $goods['create_time'] = time();
        $goods['action_id'] = $orderID;
        $goods['ip'] = $_SERVER["REMOTE_ADDR"];
        Service::getInstance('systemlog')->addLog($goods);
        //添加结束
        echo json_encode($return);
        exit;
    }
    
    /**
     * 拒绝退款
     */
    public function refusedOrderAction()
    {
        $orderID = $this->getPost('id');
        $return = Service::getInstance('admorder')->refusedOrder($orderID);
        //添加登陆日志id
        $userinfo = Yaf_Registry::get('developer');
        $goods['log_type'] = 5;
        $goods['action'] = '拒绝退款';
        $goods['uid'] = $userinfo['id'];
        $goods['create_time'] = time();
        $goods['action_id'] = $orderID;
        $goods['ip'] = $_SERVER["REMOTE_ADDR"];
        Service::getInstance('systemlog')->addLog($goods);
        //添加结束
        echo json_encode($return);
        exit;
    }
    
    /**
     * 修改交易号
     */
    public function updateTradeNoAction()
    {
        $orderID = $this->getPost('id');
        $trade_no = $this->getPost('trade_no');
        $return = Service::getInstance('admorder')->updateTradeNo($orderID, $trade_no);
        echo json_encode($return);
        exit;
    }
    /**
     * 自动取消
     */
    public function timingCancelOrderAction()
    {
        Service::getInstance('timing')->cancelOrder();
        return true;
    }
    /**
     * 自动签收
     */
    public function timingReceiveOrderAction()
    {
        Service::getInstance('timing')->receiveOrder();
        return true;
    }
    /**
     * 打印订单
     */
    public function printOrderAction()
    {
        $id = $this->getQuery('id');
        $this->_view->orderInfo = $this->orderInfoAction($id);
    }

    /**
     * 更新订单打印数
     */
    public function printNumAction(){
        $id = $this->getQuery('oId');
        if($id){
            $data = Service::getInstance('order')->getOrderPrintNum($id);
            $num = $data['num'];
            $num += 1;
            Service::getInstance('order')->updateOrderPrintNum($id, $num);
            $this->respon(1, '更新成功');
        }else{
            $this->respon(0, '更新失败');
        }
    }
}