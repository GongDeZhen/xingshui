<?php
class Service_Admorder extends Service
{
    /**
     * 返回买家列表sql
     */
    public function getBuyOrderSql($where='',$flag=0){
        $queryId = $where['query_id'];
        unset($where['query_id']);
        if ($where) {
            $left = '';
            foreach ($where as $k => $v) {
                if ($k == 'company_id') {
                    if ($v > 0) {
                        $sql = Util::companyIdIsArray($v);
                        if ($k == 'warehouse_id' && $sql != 1) {
                            if (!$queryId) {
                                $whsql[] = ' toi.warehouse_' . $sql;
                            } else {
                                $whsql[] = ' toi.warehouse_id=' . $queryId;
                            }
                        } elseif ($k != 'warehouse_id' && $sql != 1) {
                            if (!$queryId) {
                                $whsql[] = ' w.company_' . $sql;
                            } else {
                                $whsql[] = ' w.company_id=' . $queryId;
                            }
                        }
                    } else {
                        if ($k == 'warehouse_id' && $queryId > 1) {
                            $whsql[] = ' toi.warehouse_id=' . $queryId;
                        } elseif ($k != 'warehouse_id' && $queryId > 1) {
                            $whsql[] = ' w.company_id=' . $queryId;
                        }
                    }
                    $left .= ' INNER JOIN `warehouse` AS w ON toi.`warehouse_id`=w.`id` INNER JOIN `company` AS c ON w.`company_id`=c.`id` ';
                } else if ($v && $k != 'goods_amount' && $k != 'add_time' && $k != 'order_status' && $k != 'order_number' && $k != 'account' && $k != 'tel') {
                    $whsql[] = ' toi.' . $k . '=' . $v . ' ';
                } else if ($k == 'goods_amount' && $v) {
                    $whsql[] = $v;
                } else if ($k == 'order_number' && $v) {
                    $whsql[] = ' toi.' . $k . " like '%" . $v . "%' ";
                } else if ($k == 'tel' && $v) {
                    $whsql[] = ' toi.' . $k . " like '%" . $v . "%' ";
                } else if ($k == 'add_time' && $v) {
                    $whsql[] = $v;
                } else if ($k == 'account' && $v) {
                    $left .= ' INNER JOIN `users` AS u ON toi.`user_id`=u.`id` ';
                    $whsql[] = 'u.account like' . "'%" . $v . "%'";
                } else if ($k == 'order_status' && $v) {
                    switch ($v):
                        case'1':
                            $whsql[] = ' toi.pay_status=1 and toi.order_status=1 ';
                            break;
                        case'2':
                            $whsql[] = ' toi.order_status=3 and toi.shipping_status=1 and toi.sign_status=1 ';
                            break;
                        case'3':
                            $whsql[] = ' toi.order_status=3 and toi.shipping_status=2 and toi.sign_status=1 ';
                            break;
                        case'4':
                            $whsql[] = ' toi.order_status=3 and toi.shipping_status=2 and toi.sign_status=2 ';
                            break;
                        case'5':
                            $whsql[] = ' (toi.order_status=4 or toi.order_status=7)';
                            break;
                        case'6':
                            $whsql[] = ' toi.order_status=2 ';
                            break;
                        case'7':
                            $whsql[] = ' toi.order_status=8 ';
                            break;
                        case '9':
                            $whsql[] = ' toi.order_status=9 ';
                            break;
                    endswitch;
                }
            }
        }
        $where = $whsql?' where '.implode(' and ',$whsql):'';
            $sql='
                SELECT 
                toi.`id` , 
                toi.`user_id` , 
                toi.`order_status` , 
                toi.`order_number` , 
                toi.`shipping_status` , 
                toi.`sign_status` , 
                toi.`shipping_type` , 
                toi.`pay_status` , 
                toi.`pay_id` ,
                CONCAT( toi.`order_status` , toi.`shipping_status` ) new_status, 
                toi.`seller_address` , 
                toi.warehouse_id, 
                toi.`seller_tel` , 
                toi.`address_id` , 
                toi.`order_address` , 
                toi.`goods_amount` , 
                toi.pay_money, 
                toi.pay_time, 
                toi.shipments_time, 
                toi.`add_time` ,
                toi.consignee,
                toi.postscript,
                toi.tel, 
                toi.province_id,
                toi.city_id, 
                toi.area_id
                FROM order_info AS toi '.$left . $where.'
                ORDER BY
                    toi.order_status =7 DESC ,
                    new_status =31 DESC ,
                    toi.add_time DESC 
            ';
//         $sql='SELECT toi.`id`,
//             toi.`user_id`,
//             toi.`order_status`,
//             toi.`order_number`,
//             toi.`shipping_status`,
//             toi.`sign_status`,
//             toi.`shipping_type`,
//             toi.`pay_status`,
//             toi.`pay_id`,
//             toi.`seller_address`,
//             toi.warehouse_id,
//             toi.`seller_tel`,
//             toi.`address_id`,
//             toi.`order_address`,
//             toi.`goods_amount`,
//             toi.pay_money,
//             toi.pay_time,
//             toi.shipments_time,
//             toi.`add_time` ,
//             toi.consignee,
//             toi.postscript,
//             toi.tel,
//             toi.province_id,
//             toi.city_id,
//             toi.area_id
//              FROM 
//             order_info AS toi 
//             '.$left . $where.' 
//              ORDER BY 
//                 toi.order_status=7
//                 DESC,
//                 toi.shipping_status=1
//                 DESC,
//                 toi.order_status=3
//                 Desc,
//                 toi.add_time DESC,
//                 toi.remind_time desc';
        if($flag==1){
            $sql='SELECT count(*) num
             FROM
            order_info AS toi
            '.$left . $where.'
             ORDER BY
                toi.order_status=7
                DESC,
                toi.shipping_status=1
                DESC,
                toi.order_status=3
                Desc,
                toi.add_time DESC,
                toi.remind_time desc';
            /*
             and toi.order_status=3
             AND toi.shipping_status=1
             AND toi.sign_status=1
             */
        }
         //echo $sql;
        return $sql;
    }
    /**
     * 买家列表
     */
    public function getBuyOrderList($pag,$pags,$where,$type='')
    {
        $sql = $this->getBuyOrderSql($where);
        //return $sql;
        if ($type == 'excel') {
            $ordersList = $this->db->fetchAll($sql);
            return $ordersList;
        }
        $sql.=" limit $pag,$pags";
        $ordersList = $this->db->fetchAll($sql);
//         echo $sql;
        foreach ($ordersList as $k => $v) {
            $goods_num = $this->db->fetchOne("SELECT COUNT(*) goods_num FROM `order_goods` WHERE order_id={$v['id']} ");
            $ordersList[$k]['goods_num'] = ($goods_num)?$goods_num:0;
            $status = Util::getOrderInfoStatus($v['order_status'],$v['shipping_status'],$v['shipping_type'],$v['pay_status'],$v['sign_status'],$v['pay_id']);
            $ordersList[$k]['status'] = $status[0];
        }
        return $ordersList;
    }

    public function getOrderListSql($sorank,$address='',$status='',$order_number='',$add_time='',$amount='')
    {
        $userinfo = Yaf_Registry::get('developer');
        $addresswheres = '';
        switch ($status) {
            case 1:
                $addresswheres[] = 'a1.order_status=1 AND a1.shipping_status=1';
                break;
            case 2:
                $addresswheres[] = 'a1.order_status=3 AND a1.shipping_status=2';
                break;
            case 3:
                $addresswheres[] = 'a1.order_status=3 AND a1.shipping_status=2 AND a1.sign_status=2';
                break;
            case 4:
                $addresswheres[] = 'a1.order_status=6 ';
                break;
            case 5:
                $addresswheres[] = 'a1.order_status=2';
                break;
        }
        if ($order_number) {
            $addresswheres[] = " a1.order_number LIKE '%{$order_number}%'";
        }
        if ($add_time) {
            $addresswheres[] = $add_time;
        }
        if ($amount) {
            $addresswheres[] = $amount;
        }

        $where = '';
        switch($userinfo['type']):
        //总公司
        case'1':
            if($address['province_id'] || $address['city_id'] || $address['area_id']){
                foreach($address as $k=>$v){
                    if($k =='province_id' && $v){
                        $addresswheres[]=' a1.province_id='.$v;
                    }
                    if($k =='city_id' && $v){
                        $addresswheres[] =' a1.city_id='.$v;
                    }
                    if($k =='area_id' && $v){
                        $addresswheres[] =' a1.area_id='.$v;
                    }
                }
            }
            $addresswheres = implode(' AND ',$addresswheres);
            if ($addresswheres) {
                $where = ' where ';
            }
            break;
        //市公司
        case'2':
            if ($address['city_id']) {
                $whereStr = " a1.city_id='{$address[city_id]}' and a1.area_id='{$address['area_id']}' ";
            } else {
                $whereStr = " a1.province_id='{$userinfo[province_id]}' and a1.city_id='{$userinfo['city_id']}' ";
            }
            $addresswheres = implode(' AND ',$addresswheres);
            $where = ' WHERE ';
            if ($addresswheres) {
                $and = ' AND ';
            }
            break;
         //区公司 
        case'3':
            $whereStr = " a1.province_id='$userinfo[province_id]' and a1.city_id='$userinfo[city_id]' and a1.area_id='$userinfo[area_id]' ";
            $addresswheres = implode(' AND ',$addresswheres);
            $where = ' WHERE ';
            if ($addresswheres) {
                $and = ' AND ';
            }
            break;
        endswitch;

        $sql="SELECT * FROM `tb_order_info` as a1 $where $whereStr $and $addresswheres order by a1.order_status=1 AND a1.remind_time desc,a1.add_time desc";
        return $sql;
    }
    
    /**
     * 进货列表
     */
    public function getPurchaseList($num,$pag,$sorank,$address,$status,$order_number,$add_time,$amount)
    {
        $sql = ' ';
        $sql = $this->getOrderListSql($sorank,$address,$status,$order_number,$add_time,$amount);
        $sql .=" limit $num,$pag";
//         echo '<h5 style="color:red">'.$sql.'</h5>';
        $ordersList = $this->selldb->fetchAll($sql);
        foreach ($ordersList as $k => $v) {
            $status = Util::getSellOrderStatus($v['order_status'],$v['shipping_status'],$v['sign_status']);
            $ordersList[$k]['status'] = ($status)?$status:'';
            $goods_num = $this->selldb->fetchOne("SELECT SUM(goods_number) goods_num FROM `tb_order_goods` WHERE order_id={$v['id']} ");
            $ordersList[$k]['goods_num'] = ($goods_num)?$goods_num:0;
        }
        return $ordersList;
    }
    
    /**
     * 取消订单
     * @param unknown $data
     * @return boolean
     */
    public function cancelStock($data)
    {
        $userinfo = Yaf_Registry::get('developer');
        $ret = $this->selldb->update('tb_order_info', array('order_status'=>2), "id={$data['order_id']}");
        if ($ret) {
            $this->selldb->insert('tb_order_record', array('content'=>$data['content'], 'order_id'=>$data['order_id'], 'user_id'=>$userinfo['id'],'add_time'=>time()));
            return true;
        }
        return false;
    } 
    
    /**
     * 卖家订单列表分页
     *
     * $sql str 查询表
     * $perpage int 分页几条数据
     * $page int 当前第几页
     * $url 连接
     */
    public function getOrderPags($sql,$perpage,$page,$url){
        $goodsList = $this->selldb->fetchAll($sql);
        $total =ceil(count($goodsList)/$perpage);
        return Util::buildPagebar($total,1,$page,$url);
    }

    /**
     * 买家订单列表分页
     *
     * $sql str 查询表
     * $perpage int 分页几条数据
     * $page int 当前第几页
     * $url 连接
     */
    public function getmaiOrderPags($sql,$perpage,$page,$url){
//         echo '<h3 style="color:red;">'.$sql.'</h3>';
        //$goodsList = $this->db->fetchAll($sql);
        $goodsnum = $this->db->fetchROW($sql);

        //var_dump($goodsnum);exit;
        $total =ceil($goodsnum['num']/$perpage);
        return Util::buildPagebar($total,1,$page,$url);
    }
    
    /**
     * 修改订单 支付状态
     */
    public function updateOrder($data,$id)
    {
        return $this->db->update('order_info',$data,"id=$id");
    }
    /**
     * 发货
     */
    public function deliverGoods($orderID)
    {
        $return = $this->db->update('order_info', array('order_status'=>3, 'shipping_status'=>2, 'pay_status'=>2, 'shipments_time' => time()), 'id='.$orderID);
        if ($return) {
            return 200;
        }
        return 400;
    }

    /**
     * 订单信息
     */
    public function orderInfo($orderID)
    {
        $sql = "SELECT
        toi.id order_id,
        toi.order_number,
        toi.user_id,
        toi.`consignee`,
        toi.goods_amount,
        toi.pay_money,
        toi.add_time,
        toi.order_status,
        toi.shipping_status,
        toi.shipping_type,
        toi.shipments_time,
        toi.sign_status,
        toi.order_address,
        toi.`seller_address`,
        toi.`seller_tel`,
        toi.`warehouse_id`,
        toi.couponsId,
        toi.voucher_id,
        toi.postscript,
        toi.pay_status,
        toi.pay_time,
        toi.pay_id,
        toi.trade_no,
        toi.print_num,
        toi.benefit_info
        FROM
        `order_info` AS toi
        WHERE toi.id = $orderID";
        $orderInfo = $this->db->fetchRow($sql);
        $company = $this->db->fetchRow("SELECT c.id,c.`companyname` FROM `warehouse` AS w INNER JOIN `company` AS c ON w.`company_id`=c.`id` WHERE w.`id`={$orderInfo['warehouse_id']}");
        $orderInfo['companyName'] = ($company['companyname'])?$company['companyname']:'未设置';
        $$orderInfo['company_id'] = ($company['id'])?$company['id']:0;
        $orderInfo['warehouseName'] = $this->db->fetchOne("select warehousename from warehouse where id={$orderInfo['warehouse_id']}");

        return $orderInfo;
    }

    /**
     * 获取订单商品列表
     */
    public function orderGoods($orderID, $userId = '')
    {
        $sql = "SELECT
        og.`goods_id`,
        og.`activity_id`,
        og.`goods_number`,
        g.goods_type,
        g.brand_id,
        g.`goods_name`,
        og.goods_money,
        g.`goods_thumb`,
        g.`standard`
        FROM
        `order_goods` AS og
        INNER JOIN `goods` AS g
        ON og.`goods_id` = g.`id`
        WHERE og.`order_id` = $orderID ";
        $orderGoods = $this->db->fetchAll($sql);
        
        foreach ($orderGoods as $k => $v) {
            $orderGoods[$k]['activity_info'] = Service::getInstance('order')->activityInfo($orderGoods[$k]['activity_id'], $orderGoods[$k]['goods_id']);
        }
        return $orderGoods;
    }
    
    /**
     * 获取未发货的进货单
     * @return string
     */
    public function getUnreadOrder()
    {
        $time = strtotime(date('Y-m-d', time()))+86400;
        return $this->selldb->fetchOne("SELECT COUNT(*) FROM tb_order_info AS toi WHERE toi.order_status=1 AND toi.shipping_status=1 AND toi.remind_time<$time AND toi.`remind_time` <> 0");
    }

    /**
     * 取消订单退款
     */
    public function weixinCancel($orderID)
    {
        $orderInfo = Service::getInstance('order')->info( $orderID );
        $refund_num = strftime("%Y%m%d%H%M%S").mt_rand(1000,9999);
        $user_type = $this->db->fetchOne('SELECT user_type FROM `users` AS u WHERE u.`id`=?', array($orderInfo['user_id']));
        //如果是1和4的话都算微信支付
        if ( ($orderInfo['order_status'] == 7 || $orderInfo['order_status'] == 4) && ($orderInfo['pay_id'] == 1 or $orderInfo['pay_id'] == 4) && $user_type == 1) { // 微信支付
            if($orderInfo['pay_id'] == 1){
                $data = Weixinpay::getResult($orderInfo['trade_no'], $refund_num, $orderInfo['pay_money']*100);
            }else{
                $data = Weixinpay1::getResult($orderInfo['trade_no'], $refund_num, $orderInfo['pay_money']*100);
            }
            if ($data["return_code"] == "FAIL") {
                $strMSG = date("Y-m-d H:i:s")."【通信出错】:\n" . json_encode($data) . "\n";
                Log::simpleappend('refund_weixin', $strMSG);
                return '退款失败';
            } elseif ($data["result_code"] == "FAIL"){
                // 此处应该更新一下订单状态,商户自行增删操作
                $strMSG = date("Y-m-d H:i:s")."【业务出错】:\n" . json_encode($data) . "\n";
                Log::simpleappend('refund_weixin', $strMSG);
                return '退款失败';
            } elseif ($data["transaction_id"]) {

                // 此处应该更新一下订单状态,商户自行增删操作
                $transactionID = $data["transaction_id"];
                $strMSG = date("Y-m-d H:i:s")."【退款成功】:\n" . $transactionID . "\n";
                Log::simpleappend('refund_weixin', $strMSG);
//                 Service::getInstance("order")->updateOrderRefundStatusByWeixin($orderID,'',$refund_num);
                Service::getInstance('order')->upOrderStatus($orderID);
                Service::getInstance('order')->rebackGoodsNumber($orderID); // 把商品退回到库存
                Service::getInstance("message")->addMessage($orderID, 'refund');
                return '退款成功';
            } else {
                return '退款失败';
            }
        } else {
            return '该订单退款信息错误';
        }
    }
    /**
     * 拒绝退款
     * @param unknown $id
     */
    public function refusedOrder($id)
    {
        $orderInfo = $this->orderInfo( $id );
        if ($orderInfo) {
//             if (empty($orderInfo['trade_no'])) {
//                 $data = array(
//                     'order_status' => 1
//                 );
//             } else {
                $data = array(
                    'order_status' => 3
                );
//             }
            if ($this->db->update('order_info', $data, 'id='.$id)) {
                return '拒绝成功';
            } else {
                return '拒绝失败';
            }
        }
        return '该订单信息错误';
    }
    
    /**
     * 修改交易号
     * @param unknown $id
     */
    public function updateTradeNo($id, $trade_no)
    {
        $orderInfo = $this->orderInfo( $id );
        if ($orderInfo) {
            $data = array(
                'trade_no' => $trade_no
            );
            if ($this->db->update('order_info', $data, 'id='.$id)) {
                return '修改成功';
            } else {
                return '修改失败';
            }
        }
        return '该订单信息错误';
    }
    
    public function setLog($data)
    {
        $this->db->insert('log', $data);
    }

    /**
     * 获取代发货订单数量
     * @param $where
     */
    public function getOrderNum($where){
        $numSql = "SELECT count(*) num FROM order_info AS toi INNER JOIN `warehouse` AS w ON toi.`warehouse_id`=w.`id` INNER JOIN `company` AS c ON w.`company_id`=c.`id` where toi.order_status=3 and toi.shipping_status=1 and toi.sign_status=1";

        $queryId = $where['query_id'];
        unset($where['query_id']);
        foreach ($where as $k => $v) {
            if ($k == 'company_id') {
                if ($v > 0) {
                    $sql = Util::companyIdIsArray($v);
                    if ($sql != 1) {
                        if (!$queryId) {
                            $whsql = ' w.company_' . $sql;
                        } else {
                            $whsql = ' w.company_id=' . $queryId;
                        }
                    }
                } else {
                    if ($queryId > 1) {
                        $whsql = ' w.company_id=' . $queryId;
                    }
                }
            }
        }

        if($whsql){
            $numSql .= " and $whsql";
        }

        return $this->db->fetchROW($numSql);
    }
}