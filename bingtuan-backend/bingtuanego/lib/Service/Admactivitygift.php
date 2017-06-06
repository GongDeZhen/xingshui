<?php
class Service_Admactivitygift extends Service
{
    
    public function getList($first,$pageSize,$data)
    {
        $where = $join = '';
        if ($data) {
            $sql = Util::companyIdIsArray($data['company_id']);
            if ($sql != 1) {
                if($data['query_id']){
                    $data['company_id'] = "a.cid = {$data['query_id']}";
                    unset($data['query_id']);
                }else{
                    $data['company_id'] = "a.c{$sql}";
                }
            } else {
                if($data['query_id']){
                    $data['company_id'] = "a.cid = {$data['query_id']}";
                    unset($data['query_id']);
                }
            }
            
            if ($data['goods_name']) {
                $select = ',g.`goods_name` ';
                $join = ' INNER JOIN `activity_goods` AS ag ON a.`id`=ag.`activity_id` INNER JOIN `goods` AS g ON ag.`goods_id`=g.`id` ';
                $data['goods_name'] = "g.goods_name LIKE '%{$data['goods_name']}%'";
            }
            
            if ($data) {
                $where = ' AND '.implode(' AND ', $data);
            }
        }
        $sql = "SELECT a.* $select FROM `activity` AS a $join WHERE a.`type`=1 AND a.is_del=0 $where order by a.`is_show` DESC,a.`end_time` asc limit $first,$pageSize";
        $list = $this->db->fetchAll($sql);
        $count = "SELECT count(*) FROM `activity` AS a $join WHERE a.`type`=1 AND a.is_del=0 $where";
        return array($list,$this->db->fetchOne($count));
    }
    
    /**
     * 搜索商品
     * @param unknown $data
     */
    public function getGoodsList($data)
    {
        $activityList = array();
        if (!empty($data['id'])) {
            $wheres = "where a.id={$data['id']} AND a.is_del=0 AND {$data['start_time']} < a.end_time ";
            if ($data['company_id'] > 0) {
                $wheres .= 'AND a.cid ='.$data['company_id'];
            }
        } else {
            $wheres = "where a.is_del=0 AND {$data['start_time']} < a.end_time ";
            if ($data['company_id'] > 0) {
                $wheres .= 'AND a.cid ='.$data['company_id'] ;
            }
            $wheres .= ' GROUP BY ag.`goods_id`';
        }
        // 当前活动商品
        $activityGoodsInfo = $this->db->fetchAll("SELECT ag.`goods_id` FROM `activity` AS a INNER JOIN `activity_goods` AS ag ON ag.`activity_id`=a.`id` $wheres");
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
        // 所有参加活动的商品
        $sql = "SELECT
            ag.`goods_id`
        FROM `activity` AS a
        INNER JOIN `activity_goods` AS ag
            ON ag.`activity_id`=a.`id`
        WHERE a.is_del=0
            AND ".time()." BETWEEN a.start_time
        AND a.end_time
        AND {$data['start_time']} < a.end_time+86400";

        if ($data['company_id'] > 0) {
            $sql .= ' AND a.cid ='.$data['company_id'] ;
        }
        $sql .= ' GROUP BY ag.`goods_id`';
        $activityGoods = $this->db->fetchAll($sql);
        
        $activity = array();
        foreach ($activityGoods as $k=>$v) {
            if (!in_array($v['goods_id'], $activityUp) && $data['id']) {
                $activity[] = $v['goods_id'];
            } elseif (empty($data['id'])) {
                $activity[] = $v['goods_id'];
            }
        }
        $activityList = array_unique(array_merge($data['result'], $activity)); // 合并数组
        
        // 根据搜索条件获取商品
        $where = '';
        if ($data['company_id'] > 0) {
            $where[] = 'g.cid ='.$data['company_id'];
        }
        if ($data['goodsName']) {
            $where[] = "g.goods_name LIKE '%{$data['goodsName']}%'";
        }
        if ($where) {
            $where = " WHERE ". implode(' AND ', $where).' AND g.is_delete=0 AND g.is_show = 1 ORDER BY g.brand_id DESC, g.create_time DESC';
        }
        $goods = $this->db->fetchAll("SELECT g.id,g.goods_name,g.all_price,g.standard FROM `goods` AS g $where");
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
                    $goodslist .= "<option value='{$v['id']}'>{$v['goods_name']}：￥{$v['all_price']} 规格:{$v['standard']}</option>";
                }
            }
        }
        return $goodslist;
    }
    
    /**
     * 搜索赠品商品
     * @param unknown $data
     */
    public function getGiftGoodsList($id,$keyword,$company_id)
    {
        $where = '';
        if ($keyword) {
            $where[]= "g.goods_name LIKE '%{$keyword}%' ";
        }
        if ($company_id) {
            $where[]= "g.cid=$company_id";
        }
        if ($where) {
            $where = " WHERE ".implode(' AND ', $where).' AND g.is_delete=0 ORDER BY g.brand_id DESC, g.create_time DESC';
        }
        $goods = $this->db->fetchAll("SELECT g.id,g.goods_name,g.all_price,g.standard FROM `goods` AS g $where");
        $goodslist = '';
        // 过滤已有的商品
        foreach ($goods as $k => $v) {
            if ($v['id'] != $id) {
                $goodslist .= "<option value='{$v['id']}'>{$v['goods_name']}：￥{$v['all_price']} 规格:{$v['standard']}</option>";
            }
        }
        return $goodslist;
    }
    
    /**
     * 添加活动
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
                '`type`' => 1,
                'receiving_num' => $data['receiving_num'],
                'free_num' => $data['free_num'],
                'create_time' => $time
            );
            $this->db->insert('activity',$activity);
            $act_id = $this->db->lastInsertId();
            $goodIds = explode('-', $data['goods_list']);
            foreach($goodIds as $goodId){
                $this->db->insert('activity_goods',array('activity_id'=>$act_id, 'goods_id'=>$goodId));
                $id = $this->db->lastInsertId();
                $this->db->insert('activity_gifts',array('activity_goods_id'=>$id, 'goods_id'=>$data['gift_goods_list']));
            }
//            $this->db->insert('activity_goods',array('activity_id'=>$id, 'goods_id'=>$data['goods_list']));
//            $id = $this->db->lastInsertId();
//            $this->db->insert('activity_gifts',array('activity_goods_id'=>$id, 'goods_id'=>$data['gift_goods_list']));
            return $act_id;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * 编辑活动
     * @param unknown $data
     */
    public function update($id,$data)
    {

        try {
            $goods_list = $data['goods_list'];
            $gift_goods_list = $data['gift_goods_list'];
            $activity_goods_id = $data['activity_goods_id'];
            unset($data['goods_list']);
            unset($data['gift_goods_list']);
            unset($data['activity_goods_id']);
            unset($data['update_time']);

            $newids = [];
            $this->db->update('activity', $data, "id=$id");
            if (isset($goods_list)) {
                $goods_list = explode('-', $goods_list);
                if(isset($activity_goods_id)){
                    $this->db->delete('activity_goods', "activity_id=$id");
                    foreach ($goods_list as $gId) {
                        $this->db->insert('activity_goods', array('activity_id' => $id, 'goods_id' => $gId));
                        $newid = $this->db->lastInsertId();
                        $newids[] = $newid;
                    }
                }
            }

            if (isset($gift_goods_list)) {
                if($activity_goods_id){
                    $goodsList = explode('-', $activity_goods_id);
                    foreach($goodsList as $good){
                        $this->db->delete('activity_gifts',"activity_goods_id=$good");
                    }
                }
                $activity_goods_id = ($goods_list)?$newids:explode('-', $activity_goods_id);
                foreach($activity_goods_id as $agid){
                    $this->db->insert('activity_gifts',array('activity_goods_id'=>$agid, 'goods_id'=>$gift_goods_list));
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
    /**
     * 活动商品
     * @param unknown $id
     * @return multitype:string
     */
    public function getActivityGoods($id)
    {
        $sql = "SELECT 
          ag.id,
          ag.`goods_id`,
          g.`goods_name`,
          g.`all_price`
        FROM
          `activity_goods` AS ag 
          INNER JOIN `goods` AS g 
            ON ag.`goods_id` = g.`id` 
        WHERE ag.`activity_id` = $id ";
        $goods = $this->db->fetchAll($sql);
        if ( count($goods) > 0 ) {
            $options = [];
            $gIds = '';
            $aGids = '';
            foreach($goods as $good){
                $option = "<option value='{$good['goods_id']}'>{$good['goods_name']}：￥{$good['all_price']}</option>";
                $good_id = $good['goods_id'];
                $gid = $good['id'];
                $options[] = $option;
                $gIds .= $good_id . '-';
                $aGids .= $gid . '-';
            }
            return array($options, trim($gIds, '-'), trim($aGids, '-'));
        } else {
            return array('','');
        }
        
    }
    /**
     * 活动赠品
     * @param unknown $id
     * @return multitype:string
     */
    public function getActivityGoodsGift($id)
    {
        $sql = "SELECT 
          gift.`goods_id`,
          g.`goods_name`,
          g.`all_price`
        FROM
          `activity` AS a 
          INNER JOIN `activity_goods` AS ag 
            ON a.`id` = ag.`activity_id` 
          INNER JOIN `activity_gifts` AS gift 
            ON ag.`id` = gift.`activity_goods_id` 
          INNER JOIN `goods` AS g 
            ON gift.`goods_id` = g.`id` 
        WHERE a.`id` = $id ";
        $goods = $this->db->fetchRow($sql);
        if ($goods) {
            $goodslist = "<option value='{$goods['goods_id']}'>{$goods['goods_name']}：￥{$goods['all_price']}</option>";
            return array($goodslist,$goods['goods_id']);
        } else {
            return array('','');
        }
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
        $order = $this->db->fetchOne("SELECT a.`order` FROM `activity` AS a WHERE a.`type`=1 AND a.is_del=0 ORDER BY a.`order` DESC");
        return $this->db->update('activity', array('`order`'=> $order+1 ), 'id='.$id);
    }
    /**
     * 修改上下架
     * @param unknown $id
     * @param unknown $is_show
     * @return number
     */
    public function upShow($id, $is_show)
    {
        if ($is_show == 1) {
            $is_show = 0;
        } elseif ($is_show == 0) {
            $is_show = 1;
        }
        $return = $this->db->update('activity', array('is_show'=>$is_show), "id=$id");
        if ($return) {
            return $is_show;
        } else {
            return 400;
        }
    }
}