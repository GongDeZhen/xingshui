<?php
class Service_Goods extends Service
{
    public function getList($activity,$brand,$type,$pageNum,$pageSize, $goods_name)
    {
        $user = Yaf_Registry::get('user');
        if (!$user['company_id']) {
            return array();
        }
        $start=($pageNum-1)*$pageSize;
        $where = $brand_limit = '';
        $join = ' LEFT ';
        $limit = " LIMIT  {$start},{$pageSize} ";
        if($activity && $activity != 4) {
            $where[] = " activity.type={$activity} ";
            $join = ' INNER ';
        }
        if($brand) {
            $where[] = " a.brand_id={$brand} ";
        }
        if($type) {
            $where[] = " a.goods_type={$type} ";
        }
        if ($goods_name) {
            $where[] = " (a.goods_name like '%{$goods_name}%' or a.goods_abbreviation like '%{$goods_name}%') ";
        }
        if ($where) {
            $where = ' AND '. implode(' AND ', $where);
        }

        $list=array();
        $time = time();
        $sql="SELECT
                a.id AS goods_id,
                a.goods_name,
                a.goods_thumb,
                a.city_price,
                CASE WHEN activity.type IS NOT NULL THEN activity.activity_price
                    WHEN activity.type IS NULL THEN a.all_price
                END AS all_price,
                a.all_price allPrice,
                a.`amount`,
                a.unit goods_unit,
                activity.`activity_id`,
                activity.`type`,
                a.brand_id,
                a.sort,
                a.goods_type,
                a.standard,
                a.goods_abbreviation
            FROM
                goods AS a
                LEFT JOIN brand b ON b.id = a.brand_id
                LEFT JOIN goods_type gt ON gt.id = a.goods_type
                $join JOIN (
                    SELECT
                        activity_goods.goods_id,
                		c.`name`,
                		c.type,
                		activity_goods.goods_num,
                		activity_goods.activity_id,
                		activity_goods.activity_price
                		FROM
                		  activity AS c,
                          activity_goods LEFT JOIN activity_gifts AS tag ON tag.goods_id = activity_goods.goods_id
                    WHERE
                        activity_goods.activity_id = c.id AND c.is_del=0 AND c.is_show = 1
                        AND {$time} BETWEEN c.start_time AND (c.end_time)
                        AND c.cid = {$user['company_id']}
                    ) AS activity ON a.id = activity.goods_id
            WHERE  a.cid = {$user['company_id']} AND a.is_delete = 0 AND a.amount>0 {$where} AND a.is_show = 1 AND gt.name != '赠品'
            GROUP BY a.id
            ORDER BY b.order ASC,a.create_time DESC
           $limit";
        $query=$this->db->fetchAll($sql);

        foreach ($query as $k=>$row) {
            //把多张图片分隔开，取出一张作为商品图
            $row['activity_goods'] = $this->activityInfo($row['activity_id'],$row['goods_id']);
            if ($row['type'] == 1) {
                $row['all_price'] = $row['allPrice'];
            }
            if(!$row['activity_id']){$row['activity_id']='0';}
            if ($row['goods_thumb']) {$row['goods_thumb'] = Util::getpicbyhash($row['goods_thumb']);}
            unset($row['activity_id']);
            unset($row['goods_number']);
            unset($row['type']);
            unset($row['brand_id']);
            unset($row['goods_type']);
            array_push($list, $row);
        }
         return $list;
    }

    /**
     * 商品详情
     */
    public function goodsInfo($id,$activity_id=0){
        //首先判断商品是否参加活动
        $time=time();

        $sql="SELECT
            a.id goods_id,
            a.goods_name,
            a.unit goods_unit,
            a.brand_id,
            b.name,
            a.goods_desc,
            a.original_img,
            a.goods_desc_img,
            a.goods_thumb,
            a.goods_weight,
            a.amount,
            a.is_show,
            a.city_price,
            a.all_price,
            a.origin,
            a.cid,
            a.packaging,
            a.province,
            a.standard,
            a.production_time,
            a.storage,
            a.shelf_life
        FROM
            goods AS a LEFT JOIN brand AS b ON a.brand_id = b.id
        WHERE
             a.id = {$id}";
        $list = $this->db->fetchRow($sql);
        $list['production_time'] = ($list['production_time'])?$list['production_time']:'';
        $list['shelf_life'] = ($list['shelf_life'])?$list['shelf_life']:'';
        $list['activity_goods'] = $this->activityInfo($activity_id,$id);
        if ($list['activity_goods'] != null) {
            if ($list['activity_goods']['type'] != 1) {
                $list['all_price'] = $list['activity_goods']['activity_price'];
            }
        }
        $list['name'] = (!empty($list['name']))?$list['name']:'';
        $sql = "SELECT
          tc.`user_id`,
          tc.`order_id`,
          tu.`userName`,
          tc.`content`
        FROM
          `order_goods` AS tog
          LEFT JOIN `comment` AS tc
            ON tc.`order_id` = tog.`order_id`
          LEFT JOIN `users` AS tu ON tc.`user_id`=tu.`id`
        WHERE tog.`goods_id` = $id
          AND tc.`user_id` <> ''
        ORDER BY tc.create_time DESC";
        $comment = $this->db->fetchRow($sql);
        $count = $this->db->fetchOne("SELECT COUNT(*) FROM `order_goods` AS tog LEFT JOIN `comment` AS tc ON tc.`order_id`=tog.`order_id` WHERE tog.`goods_id`=$id AND tc.`user_id`<>'' AND tc.`is_show`=1 ORDER BY tc.create_time DESC");
        $comment['count'] = ($count)?$count:'0';
        $list['comment'] = ($comment)?$comment:null;
        if (!empty($list['original_img'])) {
            $img=explode(',',$list['original_img']);
            unset($list['original_img']);
            foreach($img as $k=>$v){
                $list['images'][$k] = Util::getpicbyhash($v);
            }
        } else {
            $list['images']=null;
        }
        if (!empty($list['goods_desc_img'])) {
            $goods_desc_img=explode(',',$list['goods_desc_img']);
            unset($list['goods_desc_img']);
            foreach($goods_desc_img as $k=>$v){
                $list['goods_desc_images'][$k] = Util::getpicbyhash($v);
            }
        } else {
            $list['goods_desc_images']=null;
        }
        if (!empty($list['goods_thumb'])) {
            $list['goods_thumb'] = Util::getpicbyhash($list['goods_thumb']);
        } else {
            $list['goods_thumb']=null;
        }
        return $list;
    }

    /**
    * 商品简单详情,包括商品的品牌名称和品类名称
    */
    public function goodsInfoSimple($id){
        $sql = "SELECT
          g.`goods_name` good,
          g.`goods_thumb` goods_thumb,
          b.`name` brand,
          t.`name` class,
          g.`unit` unit,
          g.`standard` standard,
          g.`city_price` city_price,
          g.`all_price` all_price
        FROM
          `goods` AS g
          LEFT JOIN `brand` AS b
            ON g.`brand_id` = b.`id`
          LEFT JOIN `goods_type` AS t ON g.`goods_type`=t.`id`
        WHERE g.`id` = ".$id;
        $goods_info = $this->db2->fetchRow($sql);
        if(!isset($goods_info)){ return Null;}
        $goods_info['brand-class'] = $goods_info['brand'].'-'.$goods_info['class'];
        $goods_info['sku'] = $goods_info['good'].'-'.$goods_info['standard'];
        if (!empty($goods_info['goods_thumb'])) {
            $goods_info['goods_thumb'] = Util::getpicbyhash($goods_info['goods_thumb']);
        }
        $goods_info['all'] = 'item';
        return $goods_info;
    }
    /**
     * 根据城市名反查ID
     * @param unknown $address
     */
    public function getAddress($province, $area)
    {
        $province = substr( $province, 0, 6 );

        $sql = "SELECT
        tp.name tp_name,
        tc.name tc_name,
        ta.name ta_name,
        IFNULL(tp.`id`,0) province_id,
        IFNULL(tc.`id`,0) city_id,
        IFNULL(ta.`id`,0) area_id
        FROM `province` AS tp,
        `area` AS ta
        LEFT JOIN `city` AS tc
        ON ta.`parent_id`=tc.`id`
        AND ta.`name` LIKE '$area'
        WHERE tc.`parent_id`=tp.`id`
        AND tp.`name` LIKE '%$province%'";
        $address = $this->db->fetchRow($sql);
        return $address;
    }

    /**
     * 活动信息
     */
    public function activityInfo($activityID = 0, $goodsID = 0)
    {
        if ($activityID == 0) return null;

        $sql = 'SELECT
              tag.`id` activity_goods_id,
              ta.`id` activity_id,
              ta.*,
              ta.`receiving_num`,
              ta.`free_num`,
              tag.`activity_price`,
              tag.`goods_num`,
              tag.`goods_id`
            FROM
              `activity` AS ta
            LEFT JOIN `activity_goods` AS tag
                ON ta.`id` = tag.`activity_id`
            WHERE ta.`id` = '.$activityID.' AND ta.is_del=0 AND ta.is_show=1';
        $sql .= " AND tag.`goods_id` = $goodsID";
        $activityInfo = $this->db->fetchRow($sql);

        if (time() <= $activityInfo['end_time']+86399) {
            $time = $activityInfo['end_time']-time();
            $activityInfo['activity_time'] = date("剩d天H小时", $time);
        } else {
            $activityInfo['activity_time'] = '已结束';
        }
        if (time() <= $activityInfo['end_time']+86399) {
            $sql = 'SELECT
              tg.`id` goods_id,
              tg.`goods_thumb`,
              tg.`goods_name`,
              tg.`unit` goods_unit,
              tag.`goods_num`
            FROM
              `activity_gifts` AS tag
              LEFT JOIN goods AS tg
                ON tag.`goods_id` = tg.`id`
            WHERE tag.`activity_goods_id` = '.$activityInfo['activity_goods_id'];
            $activityInfo['gifts_list'] = $this->db->fetchRow($sql);
            unset($activityInfo['activity_goods_id']);
            unset($activityInfo['id']);
            $activityInfo['gifts_list']['goods_thumb'] = Util::getpicbyhash($activityInfo['gifts_list']['goods_thumb']);
        }
        unset($activityInfo['order']);
        if ($activityInfo) {
            return $activityInfo;
        } else {
            return null;
        }
    }

    /**
     * 品牌和品类列表
     * @return multitype:
     */
    public function typelist()
    {
        $user = Yaf_Registry::get('user');
        if (!$user) {
            return array();
        }
        //var_dump($user);
        $sql = "SELECT distinct(b.`id`),b.`name` FROM `goods` AS g LEFT JOIN `brand` AS b on g.`brand_id` = b.`id` WHERE g.`brand_id` = b.`id`  AND g.`cid` = {$user['company_id']} AND b.`is_show` = 1  AND g.`is_show` = 1 and g.is_delete=0 order by b.`order` asc, b.`create_time` desc ";
        //$sql = "SELECT id,`name` FROM brand WHERE is_show=1 order by `order` asc, create_time desc";
        $brandList = $this->db->fetchAll($sql);
        $sql = "SELECT id,`name` FROM `goods_type` WHERE is_show=1 order by `order` asc, create_time desc";
        $goodsTypeList = $this->db->fetchAll($sql);
        $data = array(
            'brand_list' => $brandList,
            'goods_type_list' => $goodsTypeList
        );
        return $data;
    }


    public function typeAlllist()
    {
        //var_dump($user);
        $sql = "SELECT distinct(b.`id`),b.`name` FROM `goods` AS g LEFT JOIN `brand` AS b on g.`brand_id` = b.`id` WHERE g.`brand_id` = b.`id`  order by b.`order` asc, b.`create_time` desc ";
        //$sql = "SELECT id,`name` FROM brand WHERE is_show=1 order by `order` asc, create_time desc";
        $brandList = $this->db->fetchAll($sql);
        $sql = "SELECT id,`name` FROM `goods_type` WHERE is_show=1 order by `order` asc, create_time desc";
        $goodsTypeList = $this->db->fetchAll($sql);
        $data = array(
            'brand_list' => $brandList,
            'goods_type_list' => $goodsTypeList
        );
        return $data;
    }
    /**
     * 评价列表
     */
    public function commentList($orderID)
    {
        $sql = "SELECT user_id,content,comment_rank,create_time FROM `order_goods` AS tog LEFT JOIN `comment` AS tc ON tc.`order_id`=tog.`order_id` WHERE tog.`goods_id`=1 AND tc.`user_id`<>'' AND STATUS=0 ORDER BY create_time DESC";
        $dataList = $this->db->fetchAll($sql);
        foreach ($dataList as $k=>$v) {
            $name = $this->db->fetchOne("select userName from users where id={$v['user_id']}");
            $dataList[$k]['user_name']=($name)?$name:null;
        }
        return $dataList;
    }

    /**
     * 获取品类列表
     * @return mixed
     */
    public function getGoodsTypes()
    {
        $user = Yaf_Registry::get('user');
        $sql = "SELECT g.goods_type id, gt.name
                FROM goods g 
                JOIN goods_type gt 
                ON g.goods_type = gt.id
                WHERE g.is_delete = 0 AND g.is_show = 1 AND g.amount > 0 AND g.cid = {$user['company_id']}
                GROUP BY id
                ORDER BY id ASC";
        $list = $this->db->fetchAll($sql);
        return $list;
    }

    /**
     * 根据品类id获取品牌
     * @param $typeId
     * @return mixed
     */
    public function getBrandsByTypeId($typeId)
    {
        $user = Yaf_Registry::get('user');
        $sql = "SELECT g.brand_id id, b.name 
                FROM goods g 
                JOIN brand b 
                ON g.brand_id = b.id
                WHERE g.goods_type = $typeId AND g.is_delete = 0 AND g.is_show = 1 AND g.amount > 0 AND g.cid = {$user['company_id']}
                GROUP BY id";
        return $this->db->fetchAll($sql);
    }
}
