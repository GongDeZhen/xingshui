<?php
class Service_Myaccount extends Service
{
    public function index($uid){
        $sql = "SELECT userName,account,avatar,user_type,auditType,auditContent FROM users WHERE id={$uid}";
        $data['userInfo'] = $this->db->fetchRow($sql);
        $data['userInfo']['avatar'] = Util::getpicbyhash($data['userInfo']['avatar']);
        $sql = "SELECT
          COUNT(*)
        FROM
          `order_action` AS oa
          INNER JOIN `order_info` AS oi
            ON oa.`order_id` = oi.`id`
            AND oa.`action_user` = oi.`user_id`
        WHERE oi.user_id = $uid ";
        //待付款
        $noPayNumSql = ' AND oi.pay_status = 1 AND oi.order_status = 1 ';
        $data['noPayNum'] = $this->db->fetchOne($sql.$noPayNumSql);
        //待发货
        $noSendNumSql = ' AND oi.order_status = 3 AND oi.shipping_status= 1 AND oi.sign_status=1 ';
        $data['noSendNum'] = $this->db->fetchOne($sql.$noSendNumSql);
        //待收货
        $noReciveNumSql = ' AND oi.order_status = 3 AND oi.shipping_status= 2 AND oi.sign_status=1 ';
        $data['noReciveNum'] = $this->db->fetchOne($sql.$noReciveNumSql);
        //已收货
        $reciveNumSql = ' AND oi.order_status = 3 AND oi.shipping_status= 2 AND oi.sign_status=2 ';
        $data['reciveNum'] = $this->db->fetchOne($sql.$reciveNumSql);
        //已取消
        $deleteNumSql = ' AND oi.order_status = 2 ';
        $data['deleteNum'] = $this->db->fetchOne($sql.$deleteNumSql);
        return $data;
    }
    
    public function addressInfo($uid){
        $sql = "SELECT
                    id,
                    user_name,
                    phone,
                    province_id,
                    city_id,
                    area_id,
                    address
                FROM
                    user_address
                WHERE
                    user_id = {$uid} 
                ORDER BY 
                    updateTime DESC";

        $address = $this->db->fetchAll($sql);
        
        foreach($address as $k => $v ){
            $province = $this->db->fetchOne("SELECT name FROM city WHERE id={$v['province_id']}");
            
            $city = $this->db->fetchOne("SELECT name FROM city WHERE id={$v['city_id']}");
            
            $area = $this->db->fetchOne("SELECT name FROM city WHERE id={$v['area_id']}");
            
            $address[$k]['add']=$province.$city.$area.$v['address'];
        }
        
        return $address;
    }
    public function addressbyId($uid,$add_id){
        if(empty($add_id)){
            $sql = "SELECT
            id,
            user_name,
            phone,
            province_id,
            city_id,
            area_id,
            address
            FROM
            user_address
            WHERE
            user_id = {$uid}
            ORDER BY updateTime DESC";
        }else{ 
            $sql = "SELECT
            id,
            user_name,
            phone,
            province_id,
            city_id,
            area_id,
            address
            FROM
            user_address
            WHERE
            user_id = {$uid} AND id = {$add_id} 
            ";
        }
        $address = $this->db->fetchROW($sql);
        return $address;
    }
    public function getCityInfo($id)
    {
        $sql = "SELECT * FROM `city` WHERE `id` = {$id}";
        $rs = $this->db->fetchRow($sql);
        return $rs['name'];
    }
    
    public function addAddress($uid,$user_name,$phone,$province_id,$city_id,$area_id,$address){
        $result = $this->db->insert('user_address',array(
                'user_id'        =>$uid,
                'user_name'     => $user_name,
                'phone'         => $phone,
                'province_id'     =>$province_id,
                'city_id'        =>$city_id,
                'area_id'        =>$area_id,
                'address'        =>$address,
                'create_time'    =>time(),
        ));
        if( $result ){
            return true;
        }else{
            return false;
        }
    }
    
    public function editAddress($address_id,$uid,$user_name,$phone,$province_id,$city_id,$area_id,$address){
        $result = $this->db->update('user_address',array(
                'user_id'        =>$uid,
                'user_name'     => $user_name,
                'phone'         => $phone,
                'province_id'     =>$province_id,
                'city_id'        =>$city_id,
                'area_id'        =>$area_id,
                'address'        =>$address,
                'updatetime'     =>date('Y-m-d H:i:s')
        ),"id = {$address_id}");
        if( $result ){
            return true;
        }else{
            return false;
        }
    }
    
    public function delAddress($address_id){
        $result = $this->db->delete('user_address',"id={$address_id}");
        if( $result ){
            return true;
        }else{
            return false;
        }
    }
    
    public function personal($uid){
        $sql = "SELECT comName,userName,account,avatar FROM users WHERE id ={$uid}";
        $user = $this->db->fetchRow($sql);
        $user['avatar'] = Util::getpicbyhash($user['avatar']);
        $data['list'] = $user;
        
        return $data;
    }
    
    public function editPersonalName($uid,$userName){
        $result = $this->db->update('users',array('userName'=>$userName),"id={$uid}");
        if( $result ){
                return true;
        }else{
                return false;
        }
    }
    
    public function editPersonalPhone($uid,$phone){
        $result = $this->db->update('users',array('account'=>$phone),"id={$uid}");
        if( $result ){
            return true;
        }else{
            return false;
        }
    }
    
    public function editPersonalPwd($uid,$oldpwd,$newpwd){
        //检查密码是否正确
        $check = $this->db->fetchOne("SELECT id FROM users WHERE id = {$uid} AND  password='".md5($oldpwd)."'");
        if($check){
            $result = $this->db->update('users',array('password'=>md5($newpwd)),"id={$uid}");
            if( $result ){
                return '2';
            }else{
                return false;
            }
        }else{
            return '1';
        }
    }
    
    public function editPersonalAvatars($uid, $img)
    {
        $avatar = $this->db->fetchOne("select avatar from users where id=$uid");
        if (isset($avatar) &&  $avatar != '') {
            Util::delImage($avatar);
        }
        $ret = $this->db->update('users', array('avatar'=>$img), "id=$uid");
        $return  = ($ret)?Util::getpicbyhash($img):false;
        return  $return;
    }
    /**
     * 我的优惠券
     * @param unknown $uid
     * @param unknown $online
     * @return multitype:
     */
    public function coupons($uid,$online)
    {
        $time = time();
        $str = ($online == 0 || $online == 1) ? " AND a.status = {$online} AND ({$time} between  a.start_time AND a.end_time+86399) AND b.state = 0 " : " AND ({$time} not between  a.start_time AND a.end_time+86399 OR b.`state`=1)";
        $sql = "SELECT
          b.id,
          a.`id` coupons_id,
          a.least,
          a.`name`,
          a.start_time,
          a.end_time,
          a.type_num,
          a.`type`,
          b.state  
        FROM
          coupons AS a
          INNER JOIN coupons_associated AS b 
            ON a.`id` = b.`coupons_id` 
        WHERE
            b.user_id = {$uid}
            AND a.type = 2 
            AND b.state = 0 
            AND a.is_del=0
            $str ";
        $coupons = $this->db->fetchAll($sql);
        foreach ($coupons as $k => $v){
            $coupons[$k]['end_time'] = $v['end_time']+86399;
            $sql = 'SELECT g.`goods_name` FROM `coupons_goods` AS cg INNER JOIN `goods` AS g ON cg.`goods_id`=g.`id` WHERE cg.`coupons_id`=?';
            $goods = $this->db->fetchAll($sql, array($v['coupons_id']));
            $goodsName = array();
            foreach ($goods as $key => $val) {
                $goodsName[] = $val['goods_name'];
            }
            $goodsName = implode(',', $goodsName);
            $coupons[$k]['goods_name'] = ($goodsName)?$goodsName:'';
            if ($v['type'] == 1) {
                $coupons[$k]['type_name'] = '元';
            } else {
                $coupons[$k]['type_name'] = '折';
            }
        }
        return array_values($coupons);
    }
    
    public function addCoupons($uid , $couponsNumber){
        //是否有效
        $coupons = $this->db->fetchOne("SELECT id FROM coupons WHERE coupons_number='{$couponsNumber}'");
        if($coupons){
            //验证是否使用过
            $check = $this->db->fetchOne("SELECT a.id FROM coupons as a,coupons_associated as b WHERE a.id=b.coupons_id AND a.coupons_number='{$couponsNumber}'");
            if($check){
                return 2;
            }else{
                $result = $this->db->insert('coupons_associated',array('coupons_id'=>$coupons,'user_id'=>$uid,'online'=>1));
                if( $result ){
                    $sql = "SELECT
                    a.id,
                    a.least,
                    a.start_time,
                    a.end_time,
                    a.type_num,
                    d.goods_name,
                    a.`type`,
                    b.state
                    FROM
                    coupons_associated AS b
                    INNER JOIN coupons AS a ON b.coupons_id = a.id
                    INNER JOIN coupons_goods AS c ON a.id = c.coupons_id
                    INNER JOIN goods AS d ON c.goods_id = d.id
                    WHERE b.user_id = $uid
                    AND b.state = 0
                    AND a.coupons_number = $couponsNumber";
                    $data = $this->db->fetchRow($sql);
                    return $data;
                }else{
                    return 3;
                }
            }
        }else{
            return 2;
        }
    }
    
    /**
     * 选择默认地址
     * @param unknown $id
     * @param unknown $uid
     */
    public function choiceAddress($id, $uid)
    {
        $this->db->update('`user_address`', array('`state`'=>0), "`user_id`=$uid");
        $return = $this->db->update('`user_address`', array('`state`'=>1), "`id`=$id AND `user_id`=$uid");
    
        return $return;
    }
    
    /**
     * 我的账单
     * @param unknown $uid
     * @return multitype:unknown
     */
    public function myAccountOrder($uid,$year)
    {
        $user = Yaf_Registry::get('user');
        $list = $this->db->fetchAll("SELECT ua.id,ua.refund_time,ua.status,ua.money,ua.create_time,ua.end_time FROM `user_account` AS ua WHERE ua.user_id=$uid AND FROM_UNIXTIME(ua.end_time,'%Y') = $year");
        $data = array(
            'list' => $list,
            'refundMoney' => $user['refundMoney'],
        );
        return $data;
    }
    
    /**
     * 账单详情
     */
    public function accountInfo($id)
    {
        $account = $this->db->fetchRow("SELECT ua.`orderAll` FROM `user_account` AS ua WHERE ua.`id`=$id");
        if (empty($account)) {
            return array();
        }
        $orderAll = explode(',', $account['orderAll']);
        $money = 0;
        $couponsMoney = 0;
        foreach ($orderAll as $k => $v) {
            $orderList = $this->getOrderInfo($v);
            $money += $orderList['goods_amount'];
            $couponsMoney += $orderList['goods_amount']-$orderList['pay_money'];
            $account['orderList'][] = $orderList;
        }
        $account['countMoney'] = $money;
        $account['couponsMoney'] = $couponsMoney;
        $account['accountMoney'] = $money-$couponsMoney;
        
        unset($account['orderAll']);
        return $account;
    }
    
    public function getOrderInfo($id)
    {
        $sql = "SELECT
        toi.id,
        toi.order_number,
        toi.goods_amount,
        toi.pay_money,
        toi.add_time
        FROM
        order_action AS toa
        LEFT JOIN order_info AS toi
        ON toi.`id` = toa.`order_id`
        AND toi.`user_id` = toa.`action_user`
        WHERE toi.id=$id
        ORDER BY toi.id DESC";
        $order = $this->db->fetchRow($sql);
        
        if(empty($order)) {
            return array();
        }
        
        $num = 0;
        $sql = "SELECT
        og.`goods_id`,
        og.`goods_number`,
        g.`goods_thumb`
        FROM
        `order_goods` AS og
        INNER JOIN `goods` AS g
        ON og.`goods_id` = g.`id`
        WHERE og.`order_id` = {$order['id']} ";
        $orderGoodsList = $this->db->fetchAll($sql);
        if ($orderGoodsList) {
            foreach ($orderGoodsList as $key => $val) {
                if ($val['goods_thumb']) {
                    $orderGoodsList[$key]['goods_thumb'] = Util::getpicbyhash($val['goods_thumb']);
                }
                $num += $val['goods_number'];
            }
            $order['goods_num'] = $num;
            $order['goods_list'] = $orderGoodsList;
        }
        return $order;
    }
    
    /**
     * 代金券
     * @param unknown $uid
     * @param unknown $online
     * @return multitype:
     */
    public function vouchers($uid)
    {
        $sql = "SELECT 
          b.id,
          a.least,
          a.`name`,
          a.start_time,
          a.end_time,
          a.type_num,
          a.`type`,
          b.state 
        FROM
          coupons AS a 
          INNER JOIN coupons_associated AS b 
            ON a.id = b.coupons_id 
        WHERE 
           b.user_id = $uid 
          AND b.state = 0 
          AND a.`type` = 3
          AND a.is_del=0
          AND (".time()." between  a.start_time AND a.end_time+86399)
        ";
        $coupons = $this->db->fetchAll($sql);
        foreach ($coupons as $k => $v) {
            $coupons[$k]['end_time'] = $v['end_time']+86399;
            $coupons[$k]['type_name'] = '元';
        }
        return (empty($coupons)?array():$coupons);
    }
}