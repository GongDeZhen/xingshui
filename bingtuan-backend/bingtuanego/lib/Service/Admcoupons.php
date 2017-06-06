<?php
/**
 * 后台代金券、优惠券管理数据模型
 */
class Service_Admcoupons extends Service
{
    /**
     * 列表
     */
    public function getCouponsList($first,$pageSize,$data)
    {
        $sql = Util::companyIdIsArray($data['company_id']);
        if ($sql != 1) {
            if($data['query_id']){
                $data['company_id'] = "c.cid = {$data['query_id']}";
                unset($data['query_id']);
            }else{
                $data['company_id'] = "c.c{$sql}";
            }
        }
        
        $wheres = '';
        if ($data) {
            $wheres = ' AND '.implode(' AND ', $data);
        }
        $sql = "SELECT * FROM `coupons` AS c WHERE c.type != 3 AND c.`is_del`=0 $wheres ORDER BY c.update_time desc limit $first,$pageSize";
        $list       = $this->db2->fetchAll($sql);
        $count = $this->db2->fetchOne("SELECT count(*) FROM `coupons` AS c WHERE c.type = 3 AND c.`is_del`=0 $wheres");
        return array($list,$count);
    }

    /**
     * 搜索商品
     * @param unknown $data
     */
    public function getGoodsList($data)
    {
       
        $activityList = array();
        // 所有优惠券的商品
        $couponsGoods = $this->db2->fetchAll("SELECT cg.`goods_id` FROM `coupons` AS c INNER JOIN `coupons_goods` AS cg ON c.`id`= cg.`coupons_id` WHERE c.type=2 AND c.is_del=0 AND ".time()." BETWEEN c.start_time AND (c.end_time+86399)");
        $arr = array();
        if ($data['id']) {
            $couponsArr = $this->db2->fetchAll("SELECT cg.`goods_id` FROM `coupons` AS c INNER JOIN `coupons_goods` AS cg ON c.`id`= cg.`coupons_id` WHERE c.type=2 AND c.is_del=0 AND c.`id`={$data['id'] } AND ".time()." BETWEEN c.start_time AND (c.end_time+86399)");
            foreach ($couponsArr as $k=>$v) {
                $arr[] = $v['goods_id'];
            }
        }
        $coupons = array();
        foreach ($couponsGoods as $k=>$v) {
            if (empty($data['result'])) {
                if (!in_array( $v['goods_id'] ,$arr)) {
                    $coupons[] = $v['goods_id'];
                }
            } else {
                $coupons[] = $v['goods_id'];
            }
        }
        // 所有参加活动的商品
        $activityGoods = $this->db2->fetchAll("SELECT ag.`goods_id` FROM `activity` AS a INNER JOIN `activity_goods` AS ag ON ag.`activity_id`=a.`id` WHERE a.is_del=0 AND ".time()." BETWEEN a.start_time AND (a.end_time+86399) GROUP BY ag.`goods_id`");
        $activity = array();
        foreach ($activityGoods as $k=>$v) {
            $activity[] = $v['goods_id'];
        }
        $couponsGoodsList = array_unique(array_merge($data['result'], $coupons,$activity,$couponsArr)); // 合并数组
       
        // 根据搜索条件获取商品
        $where = '';
        if ($data['company_id'] > 0) {
            $where[] = 'g.cid ='.$data['company_id'];
        }
        if ($data['goodsName']) {
            $where[] = "g.goods_name LIKE '%{$data['goodsName']}%'";
        }
        if ($where) {
            $where = " WHERE ". implode(' AND ', $where).' AND g.is_delete=0 AND g.is_show = 1';
        }
        $goods = $this->db2->fetchAll("SELECT g.id,g.goods_name,g.all_price FROM `goods` AS g $where ");
        $goodslist = '';
        // 过滤已有的商品
        foreach ($goods as $k => $v) {
            if (!in_array($v['id'], $couponsGoodsList)) {
                $goodslist .= "<option value='{$v['id']}'>{$v['goods_name']}：￥{$v['all_price']}</option>";
            }
        }
        return $goodslist;
    }
    
    public function add($data)
    {
        $data['start_time'] = strtotime($data['start_time']);
        $data['end_time'] = strtotime($data['end_time']);
        $data['type'] = 2;
        $goods_list = $data['goods_list'];
        $data['createdTime'] = time();
        unset($data['goods_list']);
        try {
            $data['coupons_number']   = strftime("%Y%m%d%H%M%S") . mt_rand(10,99);
            unset($data['id']);
            $this->db->insert('coupons', $data);
            $id = $this->db->lastInsertId();
            foreach ($goods_list as $k => $v) {
                $this->db->insert('coupons_goods', array('coupons_id'=>$id, 'goods_id'=>$v));
            }
            return $id;
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function update($id, $data)
    {
        $data['start_time'] = strtotime($data['start_time']);
        $data['end_time'] = strtotime($data['end_time']);
        $data['update_time'] = date('Y-m-d H:i:s', time());
        $data['type'] = 2;
        $goods_list = $data['goods_list'];
        unset($data['goods_list']);
        try {
            $this->db->update('coupons', $data, "id=$id");
            if ($goods_list) {
                $this->db->delete('coupons_goods', 'coupons_id='.$id);
                foreach ($goods_list as $k => $v) {
                    $this->db->insert('coupons_goods', array('coupons_id'=>$id, 'goods_id'=>$v));
                }
            }
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function getCouponsInfo($id)
    {
        return $this->db->fetchRow("SELECT * FROM `coupons` AS c WHERE c.`is_del`=0 AND id=$id");
    }
    
    public function getCouponsGoods($id)
    {
        $sql = "SELECT 
          cg.`goods_id`,
          g.`goods_name`,
          g.`all_price` 
        FROM
          `coupons` AS c 
          INNER JOIN `coupons_goods` AS cg 
            ON c.`id` = cg.`coupons_id` 
          INNER JOIN `goods` AS g 
            ON g.`id` = cg.`goods_id`
        WHERE c.`id` = $id ";
        $goods = $this->db->fetchAll($sql);
    
        $goodslist = '';
        $goodsVal = '';
        if ($goods) {
            foreach ($goods as $k => $v) {
                $goodslist .= "<option value='{$v['goods_id']}'>{$v['goods_name']}：￥{$v['all_price']}</option>";
                $goodsVal .= "{$v['goods_id']}-";
            }
            return array($goodslist,substr($goodsVal,0,strlen($goodsVal)-1));
        } else {
            return array(array(),'');
        }
    }
    
    /**
     * 删除
     * @param unknown $id
     * @return number
     */
    public function delete($id)
    {
        return $this->db->update('coupons', array('is_del'=>1), 'id='.$id);
    }
    
    /**
     * 发送优惠券
     * @param unknown $id
     * @return boolean
     */
    public function resend($id,$user_type,$status)
    {
        try {
            $online = ($user_type == 1 ? 0 :($user_type == 2 ? 1 : 0));
            $coupons = $this->db->fetchRow("SELECT c.cid,c.name FROM coupons AS c WHERE c.id=$id");
            if ($status == 'yhq') {
                $name = $coupons['name'].'优惠券';
                $q = '优惠券';
            } elseif ($status == 'djq') {
                $name = $coupons['name'].'代金券';
                $q = '代金券';
            }
            $content = $name.'已入您的账户,请注意查收';
            
            $ret = $this->db->insert('message', array('title'=>'官方消息', 'content'=>$content,'receiver'=>$user_type, 'type'=>4,'create_time'=>time()));
            $messageid = $this->db->lastInsertId();
            
            $appkey = Yaf_Application::app()->getConfig()->get('umeng')->get('appkey');
            $appMasterSecret = Yaf_Application::app()->getConfig()->get('umeng')->get('appMasterSecret');
            $sql = 'SELECT w.id FROM `company` AS c INNER JOIN `warehouse` AS w ON w.`company_id`=c.`id` WHERE c.`id`=?';
            $warehouse = $this->db->fetchAll($sql, array($coupons['cid']));
            $userArr = array();
            foreach ($warehouse as $k => $v) {
                $userArr[] = $v['id'];
            }
            $userArr = implode(',', $userArr);
            $userList = $this->db->fetchAll("SELECT u.id FROM `users` AS u WHERE u.cid in ($userArr)");
            if ($userList) {
                $push = new Push();
                foreach ($userList as $k => $v) {
                    $this->db->insert('coupons_associated', array('coupons_id'=>$id, 'user_id'=>$v['id'], 'online' => $online));
                    $token = $this->db->fetchOne("select device_token from users where id={$v['id']}");
                    if ($token) {
                        if ($status == 'djq') {
                            
                        }
                        $push->sendAndroidUnicast($appkey, $appMasterSecret, $token, '官方消息', '官方消息', '天降一张'.$q.'到您账户啦，快去使用吧，查看我的优惠券');
                    }
                    $this->db->insert('message_order', array('message_id' => $messageid, 'user_id' => $v['id'], 'coupons'=>$id,'create_time'=>time()));
                }
            }
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function getExistCoupons()
    {
        $couponsGoods = $this->db->fetchAll("SELECT cg.`goods_id` FROM `coupons` AS c INNER JOIN `coupons_goods` AS cg ON  c.`id`= cg.`coupons_id` where c.is_del=0 AND ".time()." BETWEEN c.start_time AND (c.end_time+86399)");
        $array = array();
        foreach ($couponsGoods as $k => $v) {
            $array[] = $v['goods_id'];
        }
        return array_unique($array);
    }
}