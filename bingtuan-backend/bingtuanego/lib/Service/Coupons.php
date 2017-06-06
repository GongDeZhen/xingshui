<?php
class Service_Coupons extends Service
{
    /**
     * 查询是否有优惠劵
     * @param unknown $orderId
     * @param unknown $couponsId
     * @return boolean
     */
    public function checkCoupons($orderId,$couponsId)
    {
        $time = time();
        $uid = $this->db->fetchOne("SELECT user_id FROM order_info WHERE id= {$orderId}");
        $sql="SELECT
            a.id
        FROM
            coupons_associated AS a
            LEFT JOIN coupons AS b ON a.coupons_id = b.id
        WHERE
            a.state = 0
            AND a.user_id = {$uid}
            AND {$time} < b.end_time";
        
        $coupons = $this->db->fetchAll($sql);
        $result = false;
        foreach($coupons as $k => $v){
            if($v['id'] == $couponsId){
                $result = true;
                continue;
            }
        }
        return $result;
    }
    
    //通过ID获得优惠券
    public function getCouponById($id,$goodsID) {
        $data = $this->db->fetchRow('SELECT 
          b.id,
          a.type_num,
          a.type,
          c.`goods_id`
        FROM
          coupons_associated AS b 
          INNER JOIN coupons AS a 
            ON a.id = b.coupons_id 
          INNER JOIN coupons_goods AS c ON a.`id`=c.`coupons_id`
        WHERE b.id = '.$id.' AND c.`goods_id`='.$goodsID);
        if ($data['type'] == 2) {
            $data['type_num'] = $data['type_num'];
        }
        return $data;
    }
    
    //通过兑换码获得优惠券
    public function getCouponByNumber($id) {
        $data = $this->db->fetchRow('SELECT b.id,a.type_num,a.type FROM coupons_associated as b inner join coupons as a on a.id=b.coupons_id WHERE a.coupons_number='.$id);
        if ($data['type'] == 2) {
            $data['type_num'] = $data['type_num'];
        }
        return $data;
    }
    
    /**
     * 获取订单可用优惠劵
     * @param unknown $orderId
     * @param unknown $uid
     * @return multitype:
     */
    public function getCoupons($goodsArr,$goods_amount)
    {
        $user = Yaf_Registry::get("uid");
        if (!$user) {
            return array();
        }
        $time=time();
        $sql="SELECT
        a.id,
        b.name,
        b.least,
        b.start_time,
        b.end_time,
        b.type_num,
        b.id coupons_id,
        b.`type`,
        a.state
        FROM
        coupons_associated AS a
        INNER JOIN coupons AS b
        ON a.coupons_id = b.id
        INNER JOIN coupons_goods AS c
        ON b.id = c.coupons_id
        WHERE a.user_id = {$user}
        AND a.state = 0
        AND b.`type` <> 3
        AND {$goods_amount} >= b.least
        AND {$time} < b.end_time+86399 AND c.goods_id in ($goodsArr)
        GROUP BY b.`id`
        ORDER BY b.type ASC,
        b.type_num ASC ";
        $coupons = $this->db->fetchAll($sql);
        if (!$coupons) {
            return array();
        }
        foreach ($coupons as $k => $v) {
            $sql = 'SELECT g.id,g.`goods_name` FROM `coupons_goods` AS cg INNER JOIN `goods` AS g ON cg.`goods_id`=g.`id` WHERE cg.`coupons_id`=?';
            $goods = $this->db->fetchAll($sql, array($v['coupons_id']));
            
            $goodsName = $goodsId = array();
            foreach ($goods as $key => $val) {
                $goodsId[] = $val['id'];
                $goodsName[] = $val['goods_name'];
            }
            $goodsName = implode(',', $goodsName);
            $coupons[$k]['goods_name'] = ($goodsName)?$goodsName:'';
            $coupons[$k]['goods_id'] = $goodsId;
            $coupons[$k]['type_name'] = ($v['type']==1? '元':($v['type']==2?'折':''));
        }
        sort($coupons);
        return $coupons;
    }
    
    /**
     * 删除优惠劵
     * @param unknown $userID
     * @param unknown $couponArr
     */
    public function delCoupon($userID, $couponArr)
    {
        try {
            $couponArr = explode(',', $couponArr);
            foreach ($couponArr as $k => $v) {
                $this->buydb->delete('coupons_associated', "user_id=$userID AND id=$v");
            }
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    //通过ID获得优惠券
    public function getCouponInfo($id) {
        $data = $this->db->fetchRow('SELECT
          b.id,
          a.`name`,
          a.type_num,
          a.type,
          a.start_time,
          a.end_time
        FROM
          coupons_associated AS b
          INNER JOIN coupons AS a
            ON b.coupons_id = a.id
        WHERE a.id = '.$id);
        if (!$data) {
            return '';
        }
        $sql = 'SELECT cg.`goods_id` FROM `coupons_goods` AS cg WHERE cg.`coupons_id`='.$id;
        $goodsID = $this->db->fetchAll($sql);
        $goods_name = array();
        if ($goodsID) {
            foreach ($goodsID as $k => $v) {
                $goods_name[] = $this->db->fetchOne("select goods_name from goods where id={$v['goods_id']}");
            }
        }
        $goods_name = implode(',', $goods_name);
        if ( $goods_name ) $data['goods_name'] = $goods_name;
        $data['type_name'] = $data['type']==2?'折':'';

        if ($data['type'] == 2) {
            $data['type_num'] = $data['type_num'];
        }
        return $data;
    }
    
    //获取代金券
    public function getVoucher($id) {
        $sql = "SELECT 
          a.type_num
        FROM
          coupons_associated AS b 
          INNER JOIN coupons AS a 
            ON a.id = b.coupons_id
        WHERE b.id = $id ";
        $money = $this->db->fetchOne($sql);
        if (!$money) {
            return 0;
        } else {
            return $money;
        }
    }
}