<?php
class Service_Admactivitylimittime extends Service
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
            $where = ' AND '.implode(' AND ', $data);
        }
        
        //$sql = "SELECT a.* $select FROM `activity` AS a $join WHERE a.`type`=2 AND a.is_del=0 $where order by a.`update_time` DESC,a.`order` desc limit $first,$pageSize";
        $sql = "SELECT a.* $select FROM `activity` AS a $join WHERE a.`type`=2 AND a.is_del=0 $where order by a.`is_show` DESC,a.`end_time` asc limit $first,$pageSize";
        //echo $sql; die;
        $list = $this->db->fetchAll($sql);
        //var_dump($list);
        $count = "SELECT count(*) FROM `activity` AS a $join WHERE a.`type`=2 AND a.is_del=0 $where";
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
        
        $where[] = 'g.cid ='.$data['company_id'];
        
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
                    $goodslist .= "<option value='{$v['id']}'>{$v['goods_name']}：(原价￥{$v['all_price']}) 规格:{$v['standard']}</option>";
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
                'putaway' => $data['putaway'],
                'hour_start' => $data['hour_start'],
                'hour_end' => $data['hour_end'],
                'description' => $data['description'],
                '`order`' => $data['order'],
                '`type`' => 2,
                'create_time' => $time
            );
            $this->db->insert('activity',$activity);
            $id = $this->db->lastInsertId();
            //添加登陆日志id
            $userinfo = Yaf_Registry::get('developer');
            $log_system['log_type'] = 10;
            $log_system['action'] = '添加限时抢购';
            $log_system['uid'] = $userinfo['id'];
            $log_system['create_time'] = time();
            $log_system['action_id'] = $id;
            $log_system['action_json'] = json_encode($data);
            $log_system['ip'] = $_SERVER["REMOTE_ADDR"];
            Service::getInstance('systemlog')->addLog($log_system);
            //添加结束
            foreach ($data['goods_list'] as $k => $v) {
                $this->db->insert('activity_goods',array('activity_id'=>$id, 'goods_id'=>$k, 'activity_price'=>$v));
            }
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * 添加限时抢购
     * @param unknown $data
     */
    public function update($id,$data)
    {
        try {
            $goodsList = $data['goods_list'];
            unset($data['goods_list']);
            $this->db->update('activity', $data, "id=$id");
            //添加登陆日志id
            $userinfo = Yaf_Registry::get('developer');
            $log_system['log_type'] = 10;
            $log_system['action'] = '修改限时抢购';
            $log_system['uid'] = $userinfo['id'];
            $log_system['create_time'] = time();
            $log_system['action_id'] = $id;
            $log_system['action_json'] = json_encode($data);
            $log_system['ip'] = $_SERVER["REMOTE_ADDR"];
            Service::getInstance('systemlog')->addLog($log_system);
            //添加结束
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
          g.standard,
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
            $goodslist .= "<option value='{$v['goods_id']}'>{$v['goods_name']}：(原价￥{$v['all_price']}),￥{$v['activity_price']} 规格:{$v['standard']}</option>";
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
        //添加登陆日志id
        $userinfo = Yaf_Registry::get('developer');
        $log_system['log_type'] = 10;
        $log_system['action'] = '删除限时抢购';
        $log_system['uid'] = $userinfo['id'];
        $log_system['create_time'] = time();
        $log_system['action_id'] = $id;
        $log_system['ip'] = $_SERVER["REMOTE_ADDR"];
        Service::getInstance('systemlog')->addLog($log_system);
        //添加结束
        return $this->db->update('activity', array('is_del'=>1), 'id='.$id);
    }
    
    /**
     * 排序
     * @param unknown $id
     * @return number
     */
    public function activityOrder($id)
    {
        $order = $this->db->fetchOne("SELECT a.`order` FROM `activity` AS a WHERE a.`type`=2 AND a.is_del=0 ORDER BY a.`order` DESC");
        $order = $order+1;
        return $this->db->update('activity', array('`order`'=> $order), 'id='.$id);
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
            //添加登陆日志id
            $userinfo = Yaf_Registry::get('developer');
            $log_system['log_type'] = 10;
            $log_system['action'] = '限时抢购上下架';
            $log_system['uid'] = $userinfo['id'];
            $log_system['create_time'] = time();
            $log_system['action_id'] = $id;
            $log_system['action_json'] = json_encode($is_show);
            $log_system['ip'] = $_SERVER["REMOTE_ADDR"];
            Service::getInstance('systemlog')->addLog($log_system);
            //添加结束
            return $is_show;
        } else {
            return 400;
        }
    }
    /**
     * 批量修改上下架
     * @param unknown $data
     */
    public function updateCheck($data)
    {
        try {
            $data = explode(',', $data);
            foreach ($data as $k => $v) {
                if (!empty($v)) {
                    $val = explode(':', $v);
                    $this->db->update('activity', array('is_show'=>($val[1] == 1?0:1)), "id={$val[0]}");
                }
            }
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    /**
     * 验证活动时间
     */
    public function isActivityTime($id, $name, $time)
    {
        $time = strtotime($time);
        // 全部活动商品
        $sql = 'SELECT 
          COUNT(DISTINCT ag.`goods_id`) count,a.`id`,ag.`goods_id`,a.`start_time`
        FROM
          `activity` AS a 
          INNER JOIN `activity_goods` AS ag 
            ON a.`id` = ag.`activity_id` 
        WHERE a.`is_del` = 0 AND a.`id` <> '.$id.'
          AND '.$time.' BETWEEN a.start_time 
          AND (a.end_time + 86399)
          GROUP BY ag.`goods_id`';
        $allGoods = $this->db->fetchAll($sql);

        $all_goods = array();
        foreach ($allGoods as $k => $v) {
            $all_goods[$k] = $v['goods_id'];
        }
        // 当前活动商品
        $sql = 'SELECT 
          a.`id`,
          ag.`goods_id`,
          a.`start_time` 
        FROM
          `activity` AS a 
          INNER JOIN `activity_goods` AS ag 
            ON a.`id` = ag.`activity_id` 
        WHERE a.`is_del` = 0 
          AND a.`id`=?';
        $thisGoods = $this->db->fetchAll($sql, array($id));

        $this_goods = array();
        $count = 0;
        foreach ($thisGoods as $k => $v) {
            if (in_array($v['goods_id'], $all_goods)) {
                $this_goods[] = $allGoods[array_search($v['goods_id'], $all_goods)]['start_time'];
            }
        }
        arsort($this_goods);
        return array(count($this_goods), current($this_goods));
    }
}