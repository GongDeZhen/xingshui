<?php
class Service_Order extends Service
{
    //订单id,goods_amount
    public function info($id) {
        $sql = "SELECT 
          a.id,
          a.order_number,
          a.user_id,
          a.goods_amount money,
          a.order_status,
          a.pay_id,
          a.pay_status,
          a.pay_money,
          a.couponsId,
          a.trade_no,
          a.pay_time,
          c.goods_name AS goodsName,
          c.goods_desc AS goodsDesc,
          b.goods_number goodsNumber
        FROM
          order_info AS a 
          LEFT JOIN order_goods AS b 
            ON a.id = b.order_id 
          LEFT JOIN goods AS c 
            ON c.id = b.goods_id 
        WHERE a.id = $id";
        $data = $this->db->fetchRow($sql);
        return $data;
    }
    /**
     * 根据订单交易号获取ID
     * @param unknown $trade_no
     * @return string
     */
    public function getOrderByTrade($trade_no)
    {
        return $this->db->fetchOne("select id from order_info where trade_no='$trade_no'");
    }
    /**
     * 已退款
     * @param unknown $id
     * @return number
     */
    public function upOrderStatus($id)
    {
        Service::getInstance('admorder')->setLog(array('record'=>'update order_status = 8, id='.$id, 'create_time'=>time()));
        return $this->db->update('order_info', array('order_status'=>8,'refund_time'=>time()),'id='.$id);
    }
    
    /**
     * 订单列表
     * @param  [type] $data 数据
     * @return [type]
     */
    public function getOrderList($data)
    {
        $where = '';
        if(empty($data)){
            $where .= 1;
        }else{
            $temp = '';
            $keys   = array_keys($data);
            foreach($keys as $key) {
                if(!isset($data[$key])) {
                    continue;
                }
                $where .= $temp . $key . ' = \'' . $data[$key] . '\'';
                $temp   = ' AND ';
            }
            if ($where) {
                $where .= ' AND toi.is_delete=0 ';
            }
        }
        $sql = "SELECT 
          toi.id,
          toi.consignee,
          toi.tel,
          toi.postscript,
          toi.seller_address,
          toi.seller_tel,
          toi.order_number,
          toi.order_status,
          toi.shipping_status,
          toi.sign_status,
          toi.shipping_type,
          toi.pay_status,
          toi.pay_id,
          toi.order_address,
          toi.pay_money,
          toi.add_time,
          toi.benefit_info
        FROM
          order_action AS toa 
          LEFT JOIN order_info AS toi 
            ON toi.`id` = toa.`order_id` 
            AND toi.`user_id` = toa.`action_user` 
        WHERE $where 
        ORDER BY toi.id DESC";
        $orderList = $this->db->fetchAll($sql);
        if(empty($orderList)) {
            return array();
        }
        
        foreach ($orderList as $k => $v) {
            /*17-5-25 增加赠品信息 start*/
            if($v['benefit_info']){
                $orderList[$k]['benefit_info'] = json_decode($v['benefit_info']);
            }
            /*17-5-25 增加赠品信息 end*/
            $orderGoodsList = $this->orderGoods($v['id']); # TODO 这个函数逻辑要改下,把新的赠品也加到activity_goods里
            $orderGiftsList = $orderGoodsList; #TODO 这里要换成真实的逻辑
            $status = Util::getOrderInfoStatus($v['order_status'],$v['shipping_status'],$v['shipping_type'],$v['pay_status'],$v['sign_status'],$v['pay_id']);
            $orderList[$k]['status'] = $status[0];
            $orderList[$k]['status_num'] = $status[1];
            if ($orderGoodsList) {
                foreach ($orderGoodsList as $key => $val) {
                    if ($val['goods_thumb']) {
                        $orderGoodsList[$key]['goods_thumb'] = Util::getpicbyhash($val['goods_thumb']);
                    }
                    $num += $val['goods_number'];
                }
                $orderList[$k]['goods_num'] = $num;
                $orderList[$k]['goods_list'] = $orderGoodsList;
                $orderList[$k]['benefit_info'] = json_decode(urldecode($v['benefit_info']),true);
            }
        }
        return $orderList;
    }
    
    /**
     * 获取订单商品列表
     */
    public function orderGoods($orderID)
    {
        //17.5.25增加商品规格字段
        $sql = "SELECT
        og.`goods_id`,
        og.`activity_id`,
        og.`goods_number`,
        g.`goods_name`,
        g.`goods_thumb`,
        g.standard,
        og.goods_money all_price,
        g.city_price
        FROM
        `order_goods` AS og
        INNER JOIN `goods` AS g
        ON og.`goods_id` = g.`id`
        WHERE og.`order_id` = $orderID ";
        $orderGoods = $this->db->fetchAll($sql);
        foreach ($orderGoods as $k => $v) {
            $orderGoods[$k]['activity_goods'] = $this->activityInfo($orderGoods[$k]['activity_id'], $orderGoods[$k]['goods_id'],$orderID);
        }
        return $orderGoods;
    }
    
    /**
     * 活动信息
     */
    public function activityInfo($activityID = 0, $goods_id, $orderID=0)
    {
        if ($activityID == 0) return null;
    
        $sql = 'SELECT
	      ta.`id` activity_id,
          ta.*,
          ta.`receiving_num`,
          ta.`free_num`,
	      tag.`activity_price`,
          tag.id activity_goods_id,
          tag.`goods_id`
		FROM
		  `activity` AS ta
        LEFT JOIN `activity_goods` AS tag
		    ON ta.`id` = tag.`activity_id`
		WHERE ta.`id` = '.$activityID;
        $activityInfo = $this->db->fetchRow($sql);
        
        if (!empty($activityInfo)) {
            $time = $activityInfo['end_time']-time();
            $activityInfo['activity_time'] = date("剩d天H小时", $time);
            // 买就送活动-查询赠品信息
            if ($activityInfo['type'] == 1) {
                $sql = 'SELECT
                  tg.`id` goods_id,
        		  tg.`goods_thumb`,
                  tg.`goods_name`,
        	      tg.unit goods_unit,
                  tg.standard goods_standard
                FROM
                  `activity_gifts` AS tag
                  LEFT JOIN goods AS tg
                    ON tag.`goods_id` = tg.`id`
                WHERE tag.`activity_goods_id` = '.$activityInfo['activity_goods_id'];
                $activityInfo['gifts_list'] = $this->db->fetchRow($sql);
                unset($activityInfo['id']);
                $activityInfo['gifts_list']['goods_thumb'] = Util::getpicbyhash($activityInfo['gifts_list']['goods_thumb']);
                $gift = $this->db->fetchRow("SELECT receiving_num,free_num FROM activity WHERE id=$activityID");
                $activityInfo['gifts_list']['gifts_num'] = '';
                if ($gift && $orderID) {
                    $goods_num = $this->db->fetchOne("SELECT goods_number FROM order_goods WHERE goods_id=$goods_id AND order_id=$orderID");
                    //根据买的数据计算送赠品的数量
                    $activityInfo['gifts_list']['gifts_num'] = floor(($gift['free_num']/$gift['receiving_num'])*$goods_num);
                }
            }
        }
        if ($activityInfo) {
            return $activityInfo;
        } else {
            return null;
        }
    }
    
    //下单
    public function balance($uid,$postscript,$couponArr,$voucher_id,$addressID,$pay_id,$info,$benefits_Info){
        $list=array();
        $role=true;
        $time=time();
        
        $userInfo = Yaf_Registry::get('user') ? Yaf_Registry::get('user') : Service::getInstance('user')->getUserInfoById($uid);

        if ($userInfo['auditType'] != 2) {
            return array('role'=>false, 'goods'=> array(array('content'=>"抱歉您的账号暂未通过审核，请耐心等待。")));
        }
        if ($userInfo['user_type'] == 2) { // 大客户
            $refundRules = ($userInfo['refundRules'] == 1 ? 1 : ($userInfo['refundRules'] == 2 ? 3 : ($userInfo['refundRules'] == 3 ? 6 : ($userInfo['refundRules'] == 4 ? 12 : 1)))); // 月
            $refundTime = ($userInfo['refundTime'] == 1 ? 1 : ($userInfo['refundTime'] == 2 ? 10 : ($userInfo['refundTime'] == 3 ? 20 : 1))); // 日
            $account = $this->db->fetchRow("SELECT ua.id,ua.`create_time`, ua.`end_time`,ua.`status` FROM `user_account` AS ua WHERE ua.user_id = {$uid} ORDER BY ua.`create_time` DESC");
            
            $endThismonth = strtotime(date("Y-m-$refundTime H:i:s", strtotime(" +$refundRules month")));
            if (!$account['end_time']) {
                // 首次
                $countRefundOrder = $this->db->fetchOne("SELECT SUM(pay_money) from order_info where user_id=$uid AND order_status=3 AND pay_status=2 AND add_time<=$endThismonth");
            } else {
                // 往后
                $countRefundOrder = $this->db->fetchOne("SELECT SUM(pay_money) from order_info where user_id=$uid AND order_status=3 AND pay_status=2 AND add_time>={$account['create_time']} AND add_time<=$endThismonth");
            }
            $countMoney = 0;
            if ($countRefundOrder > $userInfo['refundMoney']) {
                $countMoney = $countRefundOrder-$userInfo['refundMoney'];
                return array('role'=>false, 'goods' => array(array('content' => "您的账单已超额,超额 {$countMoney}元")));
            }
            if ($account['end_time']) {
                if (time() >= $account['end_time'] && $account['status'] == 0) {
                    return array('role'=>false, 'goods'=> array(array('content'=>"您的账单还款期已到，请还款后再下单")));
                }
            }
        }
        //结算时对每件商品进行验证
        foreach($info as $k => $v) {
            $goods = $this->db->fetchRow("SELECT * FROM goods WHERE id={$v['id']}");

            //判断是否下架
            if($goods['is_delete']) {
                $role=false;
                $info[$k]['content']="{$goods['goods_name']} 已下架";
                continue;
            }
            //判断库存是否足够
            if($v['count'] > $goods['amount']){
                $role=false;
                $amount = ($goods['amount'])?$goods['amount']:0;
                $info[$k]['content']="{$goods['goods_name']} 库存仅有{$amount}件";
                continue;
            }
            
            if (isset($v['activityId'])) {
                $activity = $this->db->fetchRow("SELECT * FROM activity WHERE id={$v['activityId']} AND is_del=0"); // 活动信息
                $activity_goods = $this->db->fetchRow("select * from activity_goods where activity_id={$v['activityId']} AND goods_id={$v['id']}"); // 活动商品
                if ($activity){
                    //判断活动是否过期
                    if($time > $activity['end_time'] || $activity['is_show'] == 0){
                        $role=false;
                        $info[$k]['activityId']='0';
                        $info[$k]['content']="{$goods['goods_name']} 活动已过期";
                        continue;
                    }
                    
                    //判断库存是否足够
//                     if($v['count'] > $activity_goods['goods_num']){
//                         $role=false;
//                         $amount = ($activity_goods['goods_num'] > 0) ? $activity_goods['goods_num'] : 0;
//                         $info[$k]['content']="{$goods['goods_name']} 活动库存仅有{$amount}件";
//                         continue;
//                     }
                    
                    //判断赠品库存是否足够(买几赠几)
                    if ($activity['type'] == 1){    //送赠品
                        $current_gift_num = floor(($activity['free_num']/$activity['receiving_num'])*$v['count']); // 计算送赠品的数量

//                         $sql = "SELECT 
//                           gifts.`goods_num`,
//                           gifts.`goods_id`
//                         FROM
//                           `activity_goods` AS ag 
//                           INNER JOIN `activity_gifts` AS gifts 
//                             ON ag.`id` = gifts.`activity_goods_id` 
//                         WHERE ag.`activity_id` = {$v['activityId']} AND ag.goods_id = {$v['id']}";
//                         $gift_goods_num = $this->db->fetchAll( $sql ); // 获取赠品的数量
                        
//                         if( $v['count'] < $activity['receiving_num'] ){
//                             $role = false;
//                             $info[$k]['activityId'] = '0';
//                             $info[$k]['price'] = $goods['all_price'];
//                             $info[$k]['content'] = "{$goods['goods_name']}活动为买{$activity['receiving_num']}赠{$activity['free_num']}";
//                             break;
//                         }
//                         foreach ( $gift_goods_num as $key => $value){
//                             if ($v['id'] != $value['goods_id']) {
//                                 if( $value['goods_num'] < $current_gift_num ){
//                                     $role = false;
//                                     $num = ($value['goods_num']/$activity['free_num'])*$activity['receiving_num']; // 还剩的赠品
//                                     $info[$k]['content'] = "赠品少于 {$current_gift_num},最多购买数为：{$num}";
//                                     break;
//                                 }
//                             }
//                             if( $value['goods_num'] < 1){
//                                 $role = false;
//                                 $info[$k]['activityId'] = '0';
//                                 $info[$k]['content'] = "赠品已送完";
//                                 break;
//                             }
//                         }
                        //判断价格是否变动
                        if($v['price'] > $goods['all_price']){
                            $role=false;
                            $info[$k]['price'] = $goods['all_price'];
                            $differMoner = $v['price'] - $goods['all_price'];
                            $info[$k]['content'] = "{$goods['goods_name']} 已降价".$differMoner."元";
                            continue;
                        }
                        if($v['price'] < $goods['all_price']){
                            $role=false;
                            $info[$k]['price'] = $goods['all_price'];
                            $differMoner = $goods['all_price']-$v['price'];
                            $info[$k]['content']="{$goods['goods_name']} 已涨价".$differMoner."元";
                            continue;
                        }
                        continue;
                    }
                    if($activity['type'] == 2){    // 限时抢购
                        $info[$k]['price'] = $activity_goods['activity_price']; // 把活动价格赋给商品价格
                    }
                }
            }
        
            if ($v['activityId'] == 0) {
                //判断价格是否变动
                if($v['price'] > $goods['all_price']){
                    $role=false;
                    $info[$k]['price'] = $goods['all_price'];
                    $differMoner = $v['price'] - $goods['all_price'];
                    $info[$k]['content'] = "{$goods['goods_name']} 已降价".$differMoner."元";
                    continue;
                }
                if($v['price'] < $goods['all_price']){
                    $role=false;
                    $info[$k]['price'] = $goods['all_price'];
                    $differMoner = $goods['all_price']-$v['price'];
                    $info[$k]['content'] = "{$goods['goods_name']} 已涨价".$differMoner."元";
                    continue;
                }
            }
        }
        $list['role'] = $role;
        $list['goods'] = $info;
        
        if($list['role']){ // 去结算,开启事务
            try {
                $this->db->beginTransaction();
                $totalMoney = 0;
                $money = 0;
                $goods = array();
                $goods_area_id = array();
                foreach ($info as $k => $v) {
                    $goodsInfo = $this->db->fetchRow("SELECT * FROM goods WHERE id={$v['id']}");
                    // 判断商品参加的活动
                    $activity = '';
//                     $num = $this->db->fetchOne("SELECT goods_num FROM activity_goods WHERE activity_id={$v['activityId']} AND goods_id={$v['id']}");
                    if (isset($v['activityId'])) {
                        $activity = $this->db->fetchRow("SELECT * FROM activity WHERE id={$v['activityId']} AND is_del=0");
                        $goods[$k]['id'] = $v['id'];
                        $goods[$k]['activityId'] = $v['activityId'];
                        $goods[$k]['count'] = $v['count'];
                        $goods[$k]['price'] = $v['price'];

                    }
                 if (isset($v['id'])) {
                        $goods1 = $this->db->fetchRow("SELECT * FROM goods WHERE id={$v['id']}");
                        $goods[$k]['goods_name'] =$goods1['goods_name'];
                        $goods[$k]['standard'] = $goods1['standard'];
                    }
                    
                    if ($activity['type'] == 1) {   // 买就送
                        $totalMoney += $goodsInfo['all_price']*$v['count']; // 计算总价格
//                         $this->db->update("goods", array("goods_num"=>$num-$v['count']),"activity_id={$v['activityId']} AND goods_id={$v['id']}");  // 减去活动商品的数量
                        
                        $sql = "SELECT 
                          a.receiving_num,
                          a.`free_num`,
                          gifts.`goods_id` 
                        FROM
                          `activity_goods` AS ag 
                          INNER JOIN `activity_gifts` AS gifts 
                            ON ag.`id` = gifts.`activity_goods_id` 
                          INNER JOIN `activity` AS a 
                            ON ag.`activity_id` = a.`id`
                        WHERE ag.`activity_id` = {$v['activityId']} ";
                        $gift_goods = $this->db->fetchAll( $sql ); // 获取赠品的数量

                        foreach ( $gift_goods as $key => $value){
                            $giftNumber = floor(($value['free_num']/$value['receiving_num'])*$v['count']);
                            $giftInfo = $this->db->fetchRow("SELECT * FROM goods WHERE id={$value['goods_id']}");
                            $this->db->update("goods", array("amount"=>$giftInfo['amount']-$giftNumber),"id={$value['goods_id']}"); // 减去赠品库存
                        }
                    } elseif ($activity['type'] == 2){    // 限时抢购
                        $totalMoney += $v['price']*$v['count'];
//                         $this->db->update("activity_goods",array("goods_num"=>$num-$v['count']),"activity_id={$v['activityId']} AND goods_id={$v['id']}"); // 减去活动商品库存
                    } else {    // 没有参加活动
                        $totalMoney += $v['price']*$v['count']; // 计算总价格
//                         $this->db->update("goods",array("amount"=>$goodsInfo['amount']-$v['count']),"id={$goodsInfo['id']}");
                    }
                }
//                 $orderGoods = Service::getInstance('order')->getOrderGoods($OrderNum);
                $newMoney = 0;
                $countMoney = 0;
                foreach ($goods as $k => $v) {
                    $couponsMoney = $v['count']*$v['price'];
                    if ($couponArr) {// 优惠劵 计算 无活动商品价
                        $arr = explode(',', $couponArr);
                        foreach ($arr as $ks => $vs) {
                            $coupon = Service::getInstance('coupons')->getCouponById( $vs, $v['id']);
                            if ($coupon) {
                                if ($coupon['type'] == 2) {
                                    $countMoney = ($couponsMoney*($coupon['type_num']*0.1)); 
                                } else {
                                    $countMoney = ($couponsMoney-$coupon['type_num']);
                                }
                            }
                            $newMoney += ($countMoney <= 0) ? 0 : $countMoney;
                        }
                    }
                }
                $voucher = 0;
                if ($voucher_id) {
                    $voucher = Service::getInstance('coupons')->getVoucher($voucher_id);
                }
                if ($newMoney <= 0) {
                    $newMoney = $totalMoney;
                }
                $offcut = 0;
                if($benefits_Info){
                    $benefits = json_decode($benefits_Info, true);
                    if($benefits['type'] == 0){
                        $offcut = $benefits['offcut'];
                    }
                }

                $money = $newMoney - $voucher - $offcut; // 支付价格
                $money = ($money <= 0) ? 0 : $money;
                $countMoney = 0;
                if ($userInfo['user_type'] == 2) { // 月结用户
                    if ($money+$countRefundOrder > $userInfo['refundMoney']) {
                        $countMoney = $money+$countRefundOrder-$userInfo['refundMoney'];
                        return array('role'=>false, 'goods' => array(array('content' => "您的账单已超额,超额 {$countMoney}元")));
                    }
                }

                $address = $this->db->fetchRow("select * from user_address where id=$addressID");
                if (!$address) {
                    return array('role'=>false, 'goods' => array(array('content' => "配送地址无效")));
                }
                $province_name = Service::getInstance('region')->provinceInfo($address['province_id']);
                $city_name = Service::getInstance('region')->provinceInfo($address['city_id']);
                $area_name = Service::getInstance('region')->provinceInfo($address['area_id']);

                $warehousename = $this->db->fetchRow("SELECT w.id,w.warehousename,w.mobile FROM `users` AS u INNER JOIN `warehouse` AS w ON u.`cid`=w.`id` WHERE u.`id`= $uid");
                $order_number = (10000+$warehousename['id']) . strftime("%Y%m%d%H%M%S") . mt_rand(1000,9999);  // 经销商+年月日时分秒+4位随机数
                // 组装订单信息
                $arr = array(
                    'order_number' => $order_number,
                    'user_id'=>$uid,
                    'order_status'=>1,
                    'shipping_status'=>1,
                    'pay_id' => $pay_id,
                    'goods_amount'=>$totalMoney,
                    'pay_money' => $money,
                    'postscript' => $postscript,
                    'add_time'=>time(),
                    'shipping_type'=>1,
                    'couponsId' => $couponArr,
                    'voucher_id' => $voucher_id,
                    'consignee' => $address['user_name'],
                    'tel' => $address['phone'],
                    'warehouse_id' => $warehousename['id'],
                    'seller_address' => $warehousename['warehousename'],
                    'seller_tel' => $warehousename['mobile'],
                    'address_id' => $addressID,
                    'order_address' => $province_name.$city_name.$area_name.$address['address'],
                    'province_id' => $address['province_id'],
                    'city_id' => $address['city_id'],
                    'area_id' => $address['area_id'],
                    'benefit_info' => $benefits_Info
                );

                if ($money == 0 || $userInfo['user_type'] == 2 ) {
                    $arr['order_status'] = 3;
                    $arr['pay_status'] = 2;
                    $arr['pay_id'] = 0;
                    $arr['pay_time'] = time();
                }
                Util::write_log('即将写入订单表: '.$arr);
                $this->db->insert('order_info',$arr);//订单表
                $orderId = $this->db->lastInsertId();
                if ($pay_id == 3) {
                    Service::getInstance('message')->addMessage($orderId);
                }
                $this->createAccount($orderId, $userInfo['company_id'], $userInfo['cid']); // 创建与更新（公司、经销商）账单
                
                if (!empty($couponArr)) { // 使用优惠券
                    $this->updateOrderCouponId($couponArr, $uid);
                }
                if (!empty($voucher_id)) { // 使用代金券
                    $this->updateOrderCouponId($voucher_id, $uid);
                }
                $this->db->insert('order_action',array('action_user'=>$uid,'order_id'=>$orderId,'add_time'=>time())); // 订单操作表
                
                //向订单商品表插入数据
                foreach($goods as $k => $v) {
                    $amount = $this->db->fetchOne('select amount from goods where id='.$v['id']);
                    $this->db->update("goods",array( 'amount' => $amount-$v['count'] ),'id='.$v['id']);
                    $this->db->insert("order_goods",array('order_id'=>$orderId,'goods_id'=>$v['id'],'goods_number'=>$v['count'],'activity_id'=>$v['activityId'], 'goods_money'=>$v['price'], 'create_time'=>time(), 'goods_name'=>$v['goods_name'],'standard'=>$v['standard']));

                }
                
                $list['order'] = array('order_id'=>$orderId,'order_num'=>$order_number,'pay_money'=>$money);
                $this->db->commit();//提交事务
                return $list;
            } catch (Exception $e) {
                $this->db->rollBack();
                $list['role']=false;
                return $list;
            }
        }else{
            return $list;
        }
    }
    
    /**
     * 根据订单号获取订单ID
     * @param unknown $order_number
     */
    public function getOrderNumber($order_number)
    {
        return $this->db->fetchRow("select id,trade_no,order_status from order_info where order_number='$order_number'");
    }

    /**
     * 获取订单商品
     * @param unknown $id
     */
//     public function getOrderGoods($id)
//     {
//         return $this->db->fetchAll("SELECT goods_number,activity_id,goods_money FROM `order_goods` WHERE order_id=$id");
//     }
    
    //支付使用优惠券，更新订单优惠券
    public function updateOrderCouponId($cid,$uid)
    {
        $carr = explode(',', $cid);
        foreach ($carr as $k => $v) {
            $this->db->update('coupons_associated', array('state'=>1),  "id=$v AND user_id=$uid");
        }
    }
    
    //支付成功修改订单信息
    public function updateOrderPayStatus($oid,$udata)
    {
        date_default_timezone_set('PRC');
        try {
            $order = $this->info($oid);
            if($order['trade_no'] || $order['pay_time']){
                return true;
            }

            //file_put_contents("/mnt/wwwroot/xingshui/api/public/LOG_DIR/pay_id",$order['pay_id']);
            //如果是微信公众号支付，则不修改支付方式  --by slash 2016.5.19
            if($order['pay_id']==4){
                $udata['pay_id']=4;
            }
            //修改结束
            $udata['pay_time'] = time();
            //对于微信异步支付做处理
            if($order['order_status']<3 || $order['order_status'] == 9){
                $udata['order_status'] = 3;
            }

            $udata['pay_status'] = 2;
            $return = $this->db->update('order_info', $udata,'id='.$oid);
            Service::getInstance('admorder')->setLog(array('record'=>$order['order_number'].'进行支付', 'create_time'=>time()));
            $user = Yaf_Registry::get('user');
            $this->createAccount($oid, $user['company_id'], $user['cid']); // 创建与更新（公司、经销商）账单
            if($order['couponsId']) {
                $this->db->update('coupons_associated', array('state'=>1),' id='.$order['couponsId']);
            }
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * 易极付更新订单状态为交易处理中
     * @param $oid
     * @param $udata
     * @return bool
     */
    public function updateOrderStatus($oid,$udata){
        if($this->db->update('order_info', $udata,'id='.$oid)){
            return true;
        }
        return false;
    }
    
    /**
     * 获取订单用户信息
     */
    public function orderUserInfo($orderID, $userId = '')
    {
        $where = " oi.`id` = $orderID";
        if($userId) $where .= " AND oi.user_id = $userId";
        $sql = "SELECT 
          oi.user_id,
          oi.`consignee`,
          oi.`tel`,
          oi.`order_address`,
          oi.`goods_amount`,
          oi.`couponsId`,
          oi.`voucher_id`,
          u.account,
          u.userName,
          u.comName
        FROM
          `order_info` AS oi 
          LEFT JOIN users u ON oi.user_id = u.id
        WHERE $where";
        $userInfo = $this->db->fetchRow($sql);
        $user = Service::getInstance('user')->getUserInfoById($userInfo['user_id']);
        $userInfo['comName'] = $user['comName'];
//         print_r($user);die;
        
        return $userInfo;
    }

    /**
     * 订单信息
     */
    public function orderInfo($orderID)
    {
        $sql = "SELECT 
          toi.id,
          toi.consignee,
          toi.tel,
          toi.postscript,
          toi.seller_address,
          toi.seller_tel,
          toi.order_number,
          toi.order_status,
          toi.shipping_status,
          toi.sign_status,
          toi.shipping_type,
          toi.pay_status,
          toi.pay_id,
          toi.order_address,
          toi.couponsId,
          toi.voucher_id,
          toi.pay_money,
          toi.goods_amount,
          toi.add_time,
          toi.benefit_info
        FROM
          order_info AS toi 
          LEFT JOIN order_action AS toa 
            ON toi.`id` = toa.`order_id` 
            AND toi.`user_id` = toa.`action_user` 
        WHERE toi.`id` = $orderID
        ORDER BY toi.id DESC";
        $orderList = $this->db->fetchRow($sql);

        if(empty($orderList)) return array();

        $num = 0;
        $status = Util::getOrderInfoStatus($orderList['order_status'],$orderList['shipping_status'],$orderList['shipping_type'],$orderList['pay_status'],$orderList['sign_status'],$orderList['pay_id']);
        $orderList['status'] = $status[0];
        $orderList['status_num'] = $status[1];
        $orderGoodsList = $this->orderGoods($orderList['id']);
        $orderGiftsList = $orderGoodsList; # TODO 换成真实逻辑
        if ($orderGoodsList) {
            foreach ($orderGoodsList as $key => $val) {
                if ($val['goods_thumb']) {
                    $orderGoodsList[$key]['goods_thumb'] = Util::getpicbyhash($val['goods_thumb']);
                }
                $num += $val['goods_number'];
            }
            $orderList['goods_num'] = $num;
            $orderList['goods_list'] = $orderGoodsList;
            $orderList['benefit_info'] = json_decode(urldecode($orderList['benefit_info']),true);  # TODO 换成真实逻辑
        }
        $arr = $this->getOrderCoupons($orderList['couponsId'], $orderList['voucher_id']);
        $orderList['coupons'] = $arr[0];
        $orderList['voucher'] = $arr[1];
        return $orderList;
    }
    
    /**
     * 退货/退款将商品数量返回到库存里面
     * @param  [type] $orderID [description]
     * @return [type]          [description]
     */
    public function rebackGoodsNumber($orderID)
    {
        $list = $this->db->fetchAll("SELECT goods_id,goods_number,activity_id FROM `order_goods` WHERE order_id=$orderID");
        $amount = 0;
        foreach($list as $key => $val) {
            $amount = $this->db->fetchOne("SELECT amount FROM `goods` WHERE id={$val['goods_id']}");
            // 买就送活动存在就返回赠品
            if ($val['activity_id']) {
                $sql = "SELECT
                a.type,
                a.receiving_num,
                a.`free_num`,
                gifts.`goods_id`
                FROM
                `activity_goods` AS ag
                INNER JOIN `activity_gifts` AS gifts
                ON ag.`id` = gifts.`activity_goods_id`
                INNER JOIN `activity` AS a
                ON ag.`activity_id` = a.`id`
                WHERE ag.`activity_id` = {$val['activity_id']} ";
                $gift_goods = $this->db->fetchRow( $sql ); // 获取赠品的数量
                if ($gift_goods['type'] == 1) {
                    $giftNumber = floor(($gift_goods['free_num']/$gift_goods['receiving_num'])*$val['goods_number']);
                    $giftInfo = $this->db->fetchRow("SELECT * FROM goods WHERE id={$gift_goods['goods_id']}");
                    $this->db->update("goods", array("amount"=>$giftInfo['amount'] + $giftNumber),"id={$giftInfo['id']}");// 返还赠品库存
                    $this->db->update("goods", array("amount"=>$amount+($val['goods_number'])),"id={$val['goods_id']}"); // 返还商品库存
                } else {
                    $amount = $amount+$val['goods_number'];
                    $this->db->update('goods', array('amount' => $amount), "id={$val['goods_id']}");
                }
            } else {
                //只退商品库存不退活动库存和赠品库存
                $amount = $amount+$val['goods_number'];
                $this->db->update('goods', array('amount' => $amount), "id={$val['goods_id']}");
            }
        }
    }
    
    /**
     * 关闭订单
     * @param unknown $orderID
     * @return boolean
     */
    public function closeOrdser($orderID)
    {
        $orderInfo = $this->info($orderID);
        try {
            $this->rebackGoodsNumber($orderID); // 根据订单把商品退回到库存
            $this->db->update('order_info',array('order_status'=>2),"id = $orderID");
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * 取消订单
     * @param unknown $orderID
     * @return boolean
     */
    public function updateOrderRefundStatusByWeixin($id, $userType=1,$refund_num='')
    {
        $orderInfo = $this->info($id);
        $uid = Yaf_Registry::get('uid');
        $userInfo = Yaf_Registry::get('user');
        try {
            if ($userType == 1) {
                if (isset($orderInfo) && !empty($orderInfo) && $orderInfo['pay_id'] == 2) {
                    if ($orderInfo['pay_money'] == 0) {
                        return $this->db->update('order_info', array('order_status'=>2), " id= $id AND user_id={$orderInfo['user_id']}");
                    } else {
                        return $this->db->update('order_info', array('order_status'=>4), " id= $id AND user_id={$orderInfo['user_id']}");
                    }
                } else {
                    return $this->db->update('order_info', array('order_status'=>2, 'refund_num' => $refund_num, 'refund_time' => time()), " id= $id AND user_id={$orderInfo['user_id']}");
                }
            } else {
                return $this->db->update('order_info', array('order_status'=>2), " id= $id AND user_id={$orderInfo['user_id']}");
            }
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * 立刻签收
     */
    public function receiving($orderID)
    {
        $uid = Yaf_Registry::get('uid');
        $return = $this->db->update('order_info', array('sign_status' => 2, 'shipping_status' => 2), "id=$orderID");
        if ($return) {
            $userInfo = Yaf_Registry::get('user');
            if ($userInfo['user_type'] == 2) { // 大客户订单加入账单

                $refundRules = ($userInfo['refundRules'] == 1 ? 1 : ($userInfo['refundRules'] == 2 ? 3 : ($userInfo['refundRules'] == 3 ? 6 : ($userInfo['refundRules'] == 4 ? 12 : 1)))); // 月
                $refundTime = ($userInfo['refundTime'] == 1 ? 1 : ($userInfo['refundTime'] == 2 ? 10 : ($userInfo['refundTime'] == 3 ? 20 : 1))); // 日
                $account = $this->db->fetchRow("SELECT ua.id,ua.`create_time`, ua.`end_time`,ua.`status` FROM `user_account` AS ua WHERE ua.user_id = {$uid} AND ua.status = 0 ORDER BY ua.`create_time` DESC");

                $startThismonth = strtotime(date("Y-m-$refundTime"));
                $endThismonth = strtotime(date("Y-m-$refundTime", strtotime(" +$refundRules month")));

                if ($account['end_time']) {
                    $startThismonth = strtotime(date("Y-m-$refundTime", $account['cerate_time']));
                    $accountTime = " AND add_time>=$startThismonth AND add_time<={$account['end_time']}";
                } else {
                    $accountTime = " AND add_time >={$startThismonth} AND add_time<=$endThismonth";
                }
                $countOrder = $this->db->fetchAll("SELECT id from order_info where user_id={$uid} AND order_status=3 AND pay_status=2 $accountTime");
                $time = time();
                if (count($countOrder) > 0) {
                    $orderAll = array_column($countOrder, 'id');
                    $orderAll = implode(',', $orderAll);
                    $money = $this->db->fetchOne("SELECT SUM(pay_money) from order_info where user_id=$uid AND order_status=3 AND pay_status=2 $accountTime");
                    if ($account['end_time'] && $time < $account['end_time']) {
                        $this->db->update('user_account', array('orderAll' => $orderAll, 'money' => $money), " id={$account['id']}");
                    } else {
                        $user_account = array(
                            'user_id' => $uid,
                            'money' => $money,
                            'orderAll' => $orderAll,
                            'refund_time' => $endThismonth,
                            'end_time' => $endThismonth,
                            'create_time' => $time
                        );
                        $this->db->insert('user_account', $user_account);
                    }
                }
            }
        }

        return $return;
    }

    /**
     * 后支付用户退款
     * @param $uid
     * @param $orderId
     * @return bool
     */
    public function afterUserRefund($uid, $orderId){

        $userInfo = $this->db->fetchRow("SELECT * FROM users WHERE id=?", array($uid));

        $account = $this->db->fetchRow("SELECT ua.id,ua.orderAll,ua.`create_time`, ua.`end_time`,ua.`status` FROM `user_account` AS ua WHERE ua.user_id = {$uid} AND ua.status = 0 ORDER BY ua.`create_time` DESC");

        $refundRules = ($userInfo['refundRules'] == 1 ? 1 : ($userInfo['refundRules'] == 2 ? 3 : ($userInfo['refundRules'] == 3 ? 6 : ($userInfo['refundRules'] == 4 ? 12 : 1)))); // 月
        $refundTime = ($userInfo['refundTime'] == 1 ? 1 : ($userInfo['refundTime'] == 2 ? 10 : ($userInfo['refundTime'] == 3 ? 20 : 1))); // 日

        $startThismonth = strtotime(date("Y-m-$refundTime"));
        $endThismonth = strtotime(date("Y-m-$refundTime", strtotime(" +$refundRules month")));

        if ($account['end_time']) {
            $startThismonth = strtotime(date("Y-m-$refundTime", $account['create_time']));
            $accountTime = " AND add_time>=$startThismonth AND add_time<={$account['end_time']}";
        } else {
            $accountTime = " AND add_time >={$startThismonth} AND add_time<=$endThismonth";
        }

        $countOrder = $this->db->fetchAll("SELECT id from order_info where user_id={$uid} AND order_status=3 AND pay_status=2 $accountTime");

        if($account && count($countOrder) > 0){
            $orderAll = array_column($countOrder, 'id');
            $orderIds = explode(',', $account['orderAll']);

            $orderAll = array_unique(array_merge($orderAll, $orderIds));

            if(in_array($orderId, $orderAll)) {
                foreach ($orderAll as $k => $v) {
                    if ($orderAll[$k] == $orderId) {
                        unset($orderAll[$k]);
                    }
                }
                $orderAll = implode(',', $orderAll);
                $money = $this->db->fetchOne("SELECT SUM(pay_money) from order_info where user_id=$uid AND order_status=3 AND pay_status=2 $accountTime");
                if ($this->db->update('user_account', array('orderAll' => $orderAll, 'money' => $money), " id={$account['id']}")) {
                    return true;
                } else {
                    return false;
                }
            }
        }
    }
    
    /**
     * 提醒配送
     */
    public function remindSend($orderId)
    {
        //查询该订单的提醒时间,每隔24小时才能提醒一次
        $time = $this->db->fetchOne("SELECT remind_time FROM order_info WHERE id={$orderId}");
        if((time()-$time)/3600>24){
            $this->db->update("order_info",array('remind_time'=>time()),"id={$orderId}");
            return false;
        }else{
            $hour=24-(time()-$time)/3600;
            return $hour;
        }
    }
    
    /**
     * 删除订单
     * @param unknown $orderID
     * @param unknown $uid
     */
    public function closeOrder($orderID,$uid)
    {
        return $this->db->delete('order_action',"order_id=$orderID AND action_user=$uid");
    }
    /**
     * 创建账单
     * @param unknown $company_id
     * @param unknown $warehouse_id
     */
    public function createAccount($orderID,$company_id=0, $warehouse_id=0)
    {
        if ($company_id) {
            $data = array(
                'table' => 'company_account',
                'id' => $company_id,
                'field' => 'cid',
                'where' => ''
            );
            $this->_createAccount($data);
        }
        if ($warehouse_id) {
            $data = array(
                'table' => 'warehouse_account',
                'id' => $warehouse_id,
                'field' => 'wid',
                'where' => ' AND warehouse_id = '.$warehouse_id
            );
            $this->_createAccount($data);
        }
    }
    
    public function _createAccount($array)
    {
        $time = time();
        $endThismonth = strtotime(date("Y-m-d 23:59:59", $time)); // 今天的最后一刻
        $startThismonth = strtotime(date("Y-m-d",$time)); // 今天零点
        
        $sql = "SELECT
          ca.id,
          ca.`create_time`,
          ca.`end_time`,
          ca.`status`
        FROM
          `{$array['table']}` AS ca
        WHERE UNIX_TIMESTAMP(
            FROM_UNIXTIME(
              ca.`create_time`,
              '%Y-%m-%d 00:00:00'
            )
          ) = UNIX_TIMESTAMP(
            DATE_FORMAT(NOW(), '%Y-%m-%d 00:00:00')
          )";
        $account = $this->db->fetchRow($sql); // 获今天的账单
        $accountTime = " AND add_time>={$startThismonth} AND add_time<=$endThismonth {$array['where']}";
        $countOrder = $this->db->fetchAll("SELECT id from order_info where order_status=3 AND pay_status=2 $accountTime");
        
        if ($countOrder) {
            if (!empty($account['end_time']) && time() < $account['end_time']) {
                $orderAll = array();
                foreach ($countOrder as $k => $v) {
                    $orderAll[] = $v['id'];
                }
                $orderAll = implode(',', $orderAll);
                $money = $this->db->fetchOne("SELECT SUM(pay_money) from order_info where order_status=3 AND pay_status=2 $accountTime");
                $this->db->update('company_account', array('orderAll'=>$orderAll,'money'=>$money), " id={$account['id']}");
            } else {
                $money = $this->db->fetchOne("SELECT SUM(pay_money) from order_info where order_status=3 AND pay_status=2 $accountTime");
                $orderAll = array();
                foreach ($countOrder as $k => $v) {
                    $orderAll[] = $v['id'];
                }
                $orderAll = implode(',', $orderAll);
                $date = array(
                    $array['field'] => $array['id'],
                    'money' => $money,
                    'orderAll' => $orderAll,
                    'end_time' => $endThismonth,
                    'create_time' => $startThismonth
                );
                $this->db->insert($array['table'], $date);
            }
        }
    }
    /**
     * 根据支付编号获取订单ID
     * @param unknown $orderId
     */
    public function getRefundInfoByOrderId( $orderId ) {
        $sql = 'SELECT * FROM order_record WHERE order_id = '.$orderId.' ORDER BY add_time DESC LIMIT 1 ';
        $data = $this->db->fetchRow($sql);
        return $data;
    }
    
    /**
     * 获取订单优惠券、代金券
     */
    public function getOrderCoupons($couponsId='', $voucherId='')
    {
        $time=time();
        $coupons = $voucher = array();
        $join = '';
        $select = '';
        if ($couponsId) {
            $join = ' LEFT JOIN `coupons_goods` AS cg ON b.`id`=cg.`coupons_id` LEFT JOIN `goods` AS g ON cg.`goods_id`=g.`id` ';
            $select = ' g.`goods_name`, ';
            $couponsId = explode(',', $couponsId);
            $sql='SELECT
                  '.$select.'
                  b.name,
                  b.least,
                  b.type_num,
                  b.`type`
                FROM
                  `coupons_associated` AS ca
                  INNER JOIN `coupons` AS b
                    ON ca.coupons_id = b.id
                 '.$join.'
                WHERE ca.id = ? AND '.time().' between  b.start_time AND b.end_time+86399';
            foreach ($couponsId as $k => $v) {
                $data = $this->db->fetchRow($sql, array($v));
                $data['type_name'] = '折';
                $coupons[] = $data;
            }
        }
        
        if ($voucherId) {
            $sql='SELECT
              b.name,
              b.least,
              b.type_num,
              b.`type`
            FROM
              `coupons_associated` AS ca
              INNER JOIN `coupons` AS b
                ON ca.coupons_id = b.id
            WHERE ca.id = ? AND '.time().' between  b.start_time AND b.end_time+86399';
            $voucher = $this->db->fetchRow($sql, array($voucherId));
            $voucher['type_name'] = '元';
        }
        $coupons = empty($coupons)?null:$coupons;
        $voucher = empty($voucher)?null:$voucher;
        return array($coupons, $voucher);
    }

    /**
     * 取消订单审核
     * @param unknown $orderID
     */
    public function updateCancelOrder($orderID, $order_status)
    {
        return $this->db->update('order_info', array('order_status'=>$order_status), 'id='.$orderID);
    }

    /**
     * 根据订单id获取订单打印数量
     * @param $id
     * @return mixed
     */
    public function getOrderPrintNum($id){
        $sql = "SELECT print_num num
                FROM order_info
                WHERE id = $id";
        $num = $this->db->fetchRow($sql);
        return $num;
    }

    /**
     * 更新打印数
     * @param $id
     * @param $num
     * @return mixed
     */
    public function updateOrderPrintNum($id, $num){
         return $this->db->update('order_info', array('print_num' => $num), 'id='.$id);
    }

    //根据tradeNo获取订单信息
    public function getOrderByTradeNo($tradeNo){
        $sql = "SELECT id
                FROM order_info
                WHERE trade_no = '{$tradeNo}'";
        $order = $this->db->fetchRow($sql);
        var_dump($order); die;
        return $order;
    }
}