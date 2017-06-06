<?php
class Service_Admactivitybrand extends Service
{
    
    public function getList($first,$pageSize,$data)
    {
        $sql = Util::companyIdIsArray($data['company_id']);
        if ($sql != 1) {
            if($data['query_id']){
                $data['company_id'] = "cid = {$data['query_id']}";
                unset($data['query_id']);
            }else{
                $data['company_id'] = "c{$sql}";
            }
        }else{
            if($data['query_id']){
                $data['company_id'] = "cid = {$data['query_id']}";
                unset($data['query_id']);
            }
        }
        $where = '';
        if ($data) {
            $where = ' AND '.implode(' AND ', $data);
        }
        $sql = "SELECT a.* FROM `activity` AS a WHERE a.`type`=3 AND a.is_del=0 $where order by a.`update_time` DESC,a.`order` DESC limit $first,$pageSize";
        $list = $this->db2->fetchAll($sql);
        foreach ($list as $k => $v) {
            $brandName = $this->db2->fetchOne("SELECT `name` FROM brand where id={$v['activity_brand_id']}");
            $list[$k]['activity_brand_id'] = ($brandName)?$brandName:'未设置';
        }
        $count = "SELECT count(*) FROM `activity` AS a WHERE a.`type`=3 AND a.is_del=0 $where";
        return array($list,$this->db2->fetchOne($count));
    }
    
    /**
     * 搜索商品
     * @param unknown $data
     */
    public function getGoodsList($data)
    {
        $activityList = array();
        if (!empty($data['id'])) {
            $wheres = "where a.id={$data['id']} AND a.is_del=0 ";
            if ($data['company_id'] > 0) {
                $wheres .= 'AND a.cid ='.$data['company_id'];
            }
        } else {
            $wheres = "where a.is_del=0 ";
            if ($data['company_id'] > 0) {
                $wheres .= 'AND a.cid ='.$data['company_id'] ;
            }
            $wheres .= ' GROUP BY ag.`goods_id`';
        }
        // 当前活动商品
        $activityGoodsInfo = $this->db2->fetchAll("SELECT ag.`goods_id` FROM `activity` AS a INNER JOIN `activity_goods` AS ag ON ag.`activity_id`=a.`id` $wheres");
        $activityUp = array();
        foreach ($activityGoodsInfo as $k=>$v) {
            $activityUp[] = $v['goods_id'];
        }
        
        // 已删除的商品
        foreach ($activityUp as $k => $v) {
            if (in_array($v, $data['result'])) {
                unset($activityUp[$k]);
            }
        }
        
        $activityList = array_unique(array_merge($data['result'])); // 合并数组
        
        // 根据搜索条件获取商品
        $where = $json = '';
        if ($data['company_id'] > 0) {
            $where[] = 'g.cid ='.$data['company_id'];
        }
        if ($data['goodsName']) {
            $where[] = "g.goods_name LIKE '%{$data['goodsName']}%'";
        }
         $where[] = "g.brand_id={$data['activity_brand_id']}";
        if ($where) {
            $json = '  INNER JOIN `activity_goods` AS ag 
                ON g.`id` = ag.`goods_id` 
              INNER JOIN `activity` AS a 
                ON ag.`activity_id` = a.`id` ';
            
            $where = " WHERE ". implode(' AND ', $where).
            ' AND g.is_delete=0 
            AND g.is_show = 1 
            AND a.`is_del` = 0 
            AND '.time().' BETWEEN a.start_time 
            AND (a.end_time + 86399) 
            ORDER BY g.brand_id DESC, g.create_time DESC';
        }
        $sql = "SELECT g.id,g.goods_name,g.all_price FROM `goods` AS g $json $where";
        
        $goods = $this->db2->fetchAll($sql);
        $couponsGoods = Service::getInstance('Admcoupons')->getExistCoupons();
        $goodslist = '';
        if ($goods) {
            foreach ($goods as $k => $v) {
                if (in_array($v['id'], $couponsGoods)) {
                    unset($goods[$k]);
                }
            }
        
            // 过滤已有的商品
            foreach ($goods as $k => $v) {
                if (!in_array($v['id'], $activityList)) {
                    $goodslist .= "<option value='{$v['id']}'>{$v['goods_name']}：(原价￥{$v['all_price']})</option>";
                }
            }
        }
        return $goodslist;
    }
    
    /**
     * 添加限时抢购
     * @param unknown $data
     */
    public function add($data)
    {
        try {
            $time = time();
            $activity = array(
                'cid' => $data['cid']?$data['cid']:0,
                'name' => $data['name'],
                'start_time' => strtotime($data['start_time']),
                'end_time' => strtotime($data['end_time']),
                'description' => $data['description'],
                '`order`' => $data['order'],
                '`type`' => 3,
                'activity_brand_id' => $data['activity_brand_id'],
                'create_time' => $time
            );
            $this->db->insert('activity',$activity);
            $id = $this->db->lastInsertId();


            foreach ($data['goods_list'] as $k => $v) {
                $this->db->insert('activity_goods',array('activity_id'=>$id, 'goods_id'=>$k, 'activity_price'=>$v));
            }
            return $id;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * 编辑限时抢购
     * @param unknown $data
     */
    public function update($id,$data)
    {
        try {
            $goodsList = $data['goods_list'];
            unset($data['goods_list']);
            $this->db->update('activity', $data, "id=$id");
            if (isset($goodsList)) {
                $this->db->delete('activity_goods',"activity_id=$id");
                foreach ($goodsList as $k => $v) {
                    $this->db->insert('activity_goods',array('activity_id'=>$id, 'goods_id'=>$k, 'activity_price'=>$v));
                }
            }
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /*
     * 活动详情信息
     */
    public function getActivityInfo($id)
    {
        $sql = "SELECT * FROM `activity` AS a where a.id=$id AND a.is_del=0";
        return $this->db->fetchRow($sql);
    }
    
    public function getActivityGoods($id)
    {
        $sql = "SELECT 
          ag.`goods_id`,
          g.`goods_name`,
          g.`all_price`,
          ag.`activity_price` 
        FROM
          `activity_goods` AS ag 
          INNER JOIN `goods` AS g 
            ON ag.`goods_id` = g.`id` 
        WHERE ag.`activity_id` = $id ";
        $goods = $this->db->fetchAll($sql);
        
        $goodslist = '';
        $goodsVal = '';
        foreach ($goods as $k => $v) {
            $goodslist .= "<option value='{$v['goods_id']}'>{$v['goods_name']}：(原价￥{$v['all_price']}),￥{$v['activity_price']}</option>";
            $goodsVal .= "{$v['goods_id']}:{$v['activity_price']}-";
        }
        return array($goodslist,substr($goodsVal,0,strlen($goodsVal)-1));
    }
    
    /**
     * 删除
     * @param unknown $id
     * @return number
     */
    public function delete($id)
    {
        return $this->db->update('activity', array('is_del'=>1), 'id='.$id);
    }
    
    /**
     * 排序
     * @param unknown $id
     * @return number
     */
    public function activityOrder($id)
    {
        $order = $this->db->fetchOne("SELECT a.`order` FROM `activity` AS a WHERE a.`type`=3 AND a.is_del=0 ORDER BY a.`order` DESC");
        return $this->db->update('activity', array('`order`'=>$order+1), 'id='.$id);
    }
}