<?php
class Service_Admad extends Service
{
    
    public function getList($first,$pageSize,$data)
    {
        $where = '';
        if ($data) {
            $where = ' AND '.implode(' AND ', $data);
        }
        $time = time();
        $sql = "SELECT a.* FROM `ad` AS a WHERE a.is_del=0 $where order by a.`sort` DESC limit $first,$pageSize";
        $list = $this->db->fetchAll($sql);
        $count = "SELECT count(*) FROM `ad` AS a WHERE a.is_del=0 $where";
        return array($list,$this->db->fetchOne($count));
    }

    public function getDescList($first,$pageSize,$data)
    {
        $where = '';
        if ($data) {
            $where = ' AND '.implode(' AND ', $data);
        }
        $time = time();
        $sql = "SELECT a.* FROM `ad` AS a WHERE a.is_del=0 $where order by a.`end_time` DESC limit $first,$pageSize";
        $list = $this->db->fetchAll($sql);
        $count = "SELECT count(*) FROM `ad` AS a WHERE a.is_del=0 $where";
        return array($list,$this->db->fetchOne($count));
    }


    /**
     * @param $first
     * @param $pageSize
     * @param $data
     * @return array
     * 后台广告排序
     */
    public function getBackDescList($first,$pageSize,$data)
    {
        $where = '';
        if ($data) {
            $where = ' AND '.implode(' AND ', $data);
        }
        $time = time();
        $sql = "SELECT a.* FROM `ad` AS a WHERE a.is_del=0 $where order by a.`sort` asc,a.create_time desc limit $first,$pageSize";
        $list = $this->db->fetchAll($sql);
        $count = "SELECT count(*) FROM `ad` AS a WHERE a.is_del=0 $where";
        return array($list,$this->db->fetchOne($count));
    }

    public function getAscList($first,$pageSize,$data)
    {
        $where = '';
        if ($data) {
            $where = ' AND '.implode(' AND ', $data);
        }
        $time = time();
        $sql = "SELECT a.* FROM `ad` AS a WHERE a.is_del=0 $where order by a.`end_time` ASC limit $first,$pageSize";
        $list = $this->db->fetchAll($sql);
        $count = "SELECT count(*) FROM `ad` AS a WHERE a.is_del=0 $where";
        return array($list,$this->db->fetchOne($count));
    }

    public function getBackAscList($first,$pageSize,$data)
    {
        $where = '';
        if ($data) {
            $where = ' AND '.implode(' AND ', $data);
        }
        $time = time();
        $sql = "SELECT a.* FROM `ad` AS a WHERE a.is_del=0 $where order by a.`sort` ASC,a.create_time DESC limit $first,$pageSize";
        $list = $this->db->fetchAll($sql);
        $count = "SELECT count(*) FROM `ad` AS a WHERE a.is_del=0 $where";
        return array($list,$this->db->fetchOne($count));
    }
    
    /**
     * 搜索商品
     * @param unknown $data
     */
    public function getGoodsList($data)
    {
        if (!empty($data['id'])) {
            $wheres = " where ag.ad_id={$data['id']} ";
        } else {
            $wheres = " GROUP BY ag.`goods_id` ";
        }
        // 当前广告商品
        $goodsInfo = $this->db->fetchAll("SELECT ag.`goods_id` FROM `ad_goods` AS ag $wheres");
        $up = array();
        foreach ($goodsInfo as $k=>$v) {
            $up[] = $v['goods_id'];
        }
        
        $newUp = array_unique(array_merge($data['result'], $up)); // 合并数组
        
        // 已删除的商品
        foreach ($newUp as $k => $v) {
            if (in_array($v, $up)) {
                unset($newUp[$k]);
            }
        }

        // 所有参加活动的商品
        $adGoods = $this->db->fetchAll("SELECT ag.`goods_id` FROM `ad_goods` AS ag  GROUP BY ag.`goods_id`");
        $goodsAll = array();
        foreach ($adGoods as $k=>$v) {
            if (!in_array($v['goods_id'], $up) && $data['id']) {
                $goodsAll[] = $v['goods_id'];
            } elseif (empty($data['id'])) {
                $goodsAll[] = $v['goods_id'];
            }
        }
        
        $goodslist = array_unique(array_merge($data['result'], $goodsAll)); // 合并数组
        
        // 根据搜索条件获取商品
        $where = '';
        if ($data['company_id'] > 0) {
            $where[] = 'g.cid ='.$data['company_id'];
        }
        if ($data['goodsName']) {
            $where[] = "g.goods_name LIKE '%{$data['goodsName']}%'";
        }
        if ($where) {
            $where = " WHERE ". implode(' AND ', $where);
        }
        $goods = $this->db->fetchAll("SELECT g.id,g.goods_name,g.all_price,g.standard FROM `goods` AS g $where AND is_delete=0 AND is_show=1");
        $list = '';
        if ($goods) {
            // 过滤已有的商品
            foreach ($goods as $k => $v) {
                if (!in_array($v['id'], $goodslist)) {
                    $list .= "<option value='{$v['id']}'>{$v['goods_name']}{$v['standard']}：(原价￥{$v['all_price']})</option>";
                }
            }
        }
        return $list;
    }
    
    /**
     * 添加广告
     * @param unknown $data
     */
    public function add($data)
    {
        try {
            $time = time();
            $newData = array(
                'cid' => $data['cid']?$data['cid']:0,
                'name' => $data['name'],
                'location' => $data['location'],
                '`type`' => $data['type'],
                'url' => $data['url'],
                'sort' => $data['sort'],
                'start_time' => strtotime($data['start_time']),
                'end_time' => strtotime($data['end_time']),
                'description' => $data['description'],
                'create_time' => $time
            );
            $this->db->insert('ad',$newData);
            $id = $this->db->lastInsertId();

            foreach ($data['goods_list'] as $k => $v) {
                $this->db->insert('ad_goods',array('ad_id'=>$id, 'goods_id'=>$v));
            }
            return $id;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * 编辑广告
     * @param unknown $data
     */
    public function update($id,$data)
    {
        try {
            $goodsList = $data['goods_list'];
            unset($data['goods_list']);
            $this->db->update('ad', $data, "id=$id");
            if (isset($goodsList)) {
                $this->db->delete('ad_goods',"ad_id=$id");
                foreach ($goodsList as $k => $v) {
                    $this->db->insert('ad_goods',array('ad_id'=>$id, 'goods_id'=>$v));
                }
            }
            return $id;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /*
     * 广告详情
     */
    public function getAdInfo($id)
    {
        $sql = "SELECT * FROM `ad` AS a where a.id=$id AND a.is_del=0";
        return $this->db->fetchRow($sql);
    }
    
    public function getAdGoods($id)
    {
        $sql = "SELECT 
          ag.`goods_id`,
          g.`goods_name`,
          g.`all_price`
        FROM
          `ad_goods` AS ag 
          INNER JOIN `goods` AS g 
            ON ag.`goods_id` = g.`id` 
        WHERE ag.`ad_id` = $id ";
        $goods = $this->db->fetchAll($sql);
        $goodslist = '';
        $goodsVal = '';
        foreach ($goods as $k => $v) {
            $goodslist .= "<option value='{$v['goods_id']}'>{$v['goods_name']}：(原价￥{$v['all_price']})</option>";
            $goodsVal .= "{$v['goods_id']}-";
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
        return $this->db->update('ad', array('is_del'=>1), 'id='.$id);
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
    
    /**
     * 修改商品图片
     * @param unknown $id
     * @param unknown $goods_desc_img
     * @param unknown $goods_img
     * @param unknown $original_img
     */
    public function upGoodsImg($id, $image, $bg_image)
    {
        $data = array(
            'image' => $image,
            'bg_image' => $bg_image
        );
        $data = array_filter($data);
        if ($data) {
          return $this->db->update('ad', $data, "id=$id");
        } else {
          return true;
        }
    }
    
    /**
     * 接口广告列表
     */
    public function adList($type)
    {
        $user = Yaf_Registry::get( 'user' );
        if (!$user) {
            return false;
        }
        $sql = "select ta.* from ad as ta  where ta.`cid`={$user['company_id']} AND ta.location = $type AND ta.is_del=0 AND ".time()." BETWEEN ta.start_time AND (ta.end_time) ORDER BY sort ASC";
        $return = $this->db->fetchAll($sql); // 获取广告
        foreach ($return as $k => $v) {
            $return[$k]['image'] = Util::getpicbyhash($v['image']);
            $return[$k]['bg_image'] = Util::getpicbyhash($v['bg_image']);
            unset($return[$k]['is_del']);
        }
        return $return;
    }
    
    /**
     * 接口广告商品列表
     */
    public function adGoodsList($id,$data,$where)
    {
        $start=($data['pageNum']-1)*$data['pageSize'];
        $pageSize = $data['pageSize']+$start;
        $time = time();
        
        $sql = "SELECT 
          tg.`id` goods_id,
          tg.goods_name,
          tg.goods_thumb,
          tg.`all_price`,
          tg.`city_price`,
          tg.amount,
          ag.ad_id,
          tg.unit as goods_unit,
          activity.activity_id
        FROM
          `ad_goods` AS ag 
          INNER JOIN `goods` AS tg 
            ON ag.`goods_id` = tg.`id` 
          LEFT JOIN 
            (SELECT 
              activity_goods.activity_id,
              activity_goods.`goods_id` 
            FROM
              activity AS a,
              activity_goods where
              activity_goods.activity_id = a.id AND a.is_del=0
                AND {$time} BETWEEN a.start_time AND a.end_time
              ) AS activity 
            ON tg.id = activity.goods_id 
        WHERE ag.`ad_id` = $id AND tg.`is_delete` = '0' AND tg.`is_show` = '1' $where
        limit {$start},{$pageSize}";
        $return = $this->db->fetchAll($sql); // 获取广告商品
        
        $uid = Yaf_Registry::get( 'uid' );
        foreach ($return as $k => $v) {
            $return[$k]['goods_thumb'] = Util::getpicbyhash($v['goods_thumb']);
            if ($return[$k]['activity_id'] > 0) {
                $return[$k]['activity_goods'] = Service::getInstance('goods')->activityInfo($return[$k]['activity_id']); // 根据活动ID获取活动信息
            }
            unset($return[$k]['ad_id']);
        }
        return $return;
    }
}