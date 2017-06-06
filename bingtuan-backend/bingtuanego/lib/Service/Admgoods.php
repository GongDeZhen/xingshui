<?php
class Service_Admgoods extends Service
{
    /**
     * 商品列表SQL
     */
    public function getGoodslistsql($where, $tag = 0){
        $userinfo = Yaf_Registry::get('developer');
        $sort = array();

        $companyIds = explode(',', $userinfo['company_id']);
        $isHeadOffice = true;
        if(count($companyIds) == 1){
            $isHeadOffice = $companyIds[0] > 1 ? false : true;
        }else{
            $isHeadOffice = false;
        }

        if($where['query_id'] > 1){
            $isHeadOffice = false;
        }

        if ($where['is_show']) {
            if(!$isHeadOffice or $where['query_id']>1){
                $sort[] = ' ag.is_show ' . $where['is_show'];
            }else{
                $sort[] = ' g.is_show ' . $where['is_show'];
            }
        }
        if ($where['amount']) {
            if (!$isHeadOffice > 1 or $where['query_id'] > 1) {
                $sort[] = ' ag.amount ' . $where['amount'];
            }else{
                $sort[] = ' g.amount ' . $where['amount'];
            }
        }
        $sort = $sort ? implode(' , ',$sort) : ' g.brand_id desc, g.`create_time` desc ';
        //var_dump($where);
        $left = ' ';
        foreach($where as $k=>$v){
            switch($k):
                case 'brand':
                    if($v){
                        $wheresql[]=" g.brand_id=$v ";
                    }
                    break;
                case 'goods_type':
                    if($v){
                        $wheresql[]=" g.goods_type=$v ";
                    }
                    break;
                case'coupons':
                    if($v){
                        $left.=' inner join coupons_goods as cg on g.id=cg.goods_id ';
                        $wheresql[]=' cg.coupons_id='.$v;
                    }
                    break;
                case'activity':
                    if($v){
                        $left.=' inner join activity_goods as ag on g.id=ag.goods_id ';
                        $wheresql[]=' ag.activity_id='.$v;
                    }
                    break;
                case 'goods_name':
                    if($v){
                        $wheresql[]=" (g.goods_name LIKE '%$v%' or g.goods_abbreviation LIKE '%$v%') ";
                    }
                    break;
                case 'pid':
                    if($v!=''){
                        $wheresql[]=" g.pid = $v ";
                    }
                    break;
                case 'query_id':
                case 'company_id':
                    if($v!=''&&$v>0) {
                        if($isHeadOffice){
                            $wheresql[]=" g.cid = 1 ";
                        }else{
                            $wheresql[]=" g.cid = 1 ";
                        }
                    }
                    break;
            endswitch;
            if(($k=='create_time' || $k=='all_price') && $v){
                $wheresql[]=$v;
            }
        }
        /*
        if ($where['company_id'] >= '1') {
            $wheresql[]=" g.pid = 0 ";
        }
        */
        //如果查询的是分公司
        $flag = 0;
        if($where['query_id']>1) {
            $cid = $where['query_id'];
            $left .= ' left join (select id,pid,amount,is_show from goods where cid= '.$cid.' and pid is not null and is_delete=0) ag on g.id=ag.pid';
            $flag = 1;
        }

        $where = $wheresql?' AND '.implode(' AND ',$wheresql):'';
        if($flag){
            if(!$tag){
                $sql="SELECT g.`id`, g.`goods_name`, g.`brand_id`, g.`goods_type`, ag.`amount`, g.`goods_img`, g.`goods_thumb`,
                             g.`standard`, ag.`is_show`, g.`all_price`, g.city_price, g.`create_time`, g.cid, g.pid
                      FROM goods AS g
                      $left
                      WHERE g.is_delete = 0 $where
                      ORDER BY $sort";
            }else{
                $sql = "SELECT count(g.id) count
                        FROM goods AS g
                        $left
                        WHERE g.is_delete = 0 $where";
            }
        }else{
            if(!$tag){
                $sql="SELECT g.`id`, g.`goods_name`, g.`brand_id`, g.`goods_type`, g.`amount`, g.`goods_img`,
                             g.`goods_thumb`, g.`standard`, g.`is_show`, g.`all_price`, g.city_price, g.`create_time`,
                             g.cid, g.pid
                      FROM goods AS g
                      $left
                      WHERE g.is_delete = 0 $where
                      ORDER BY $sort";
            }else{
                $sql="SELECT count(g.id) count
                      FROM goods AS g
                      $left
                      WHERE g.is_delete = 0 $where";
            }

        }
        //如果是分公司，则有
        //echo $sql;exit;
        return $sql;
    }
    /**
     * 商品列表
     */
    public function getList($num,$pag,$where)
    {
        $userinfo = Yaf_Registry::get('developer');
        $companyIds = explode(',', $userinfo['company_id']);
        if(count($companyIds) == 1){
            $isHeadOffice = $companyIds[0] > 1 ? false : true;
        }else{
            $isHeadOffice = false;
        }
        if($where['query_id'] == 1){
            $isHeadOffice = true;
        }


        $sql = $this->getGoodslistsql($where);
        //如果公司ID大于0，则全部读取总公司
        /*
        if($where['company_id']>0){
            $where['company_id'] = 1;
        }
        */
        $sql .=" limit $num,$pag";
//         echo '<h5 style="color:red">'.$sql.'</h5>';
//        return $sql;
        /*if(!$isHeadOffice || $where['query_id'] > 1) {
            $cid = $where['query_id'] ?: $where['company_id'];
            if (!is_array($cid)) {
                $cid = explode(',', $cid);
            }
            foreach ($cid as $v) {
                $goodsList = $this->db->fetchAll($sql);
                $goods = [];
                foreach ($goodsList as $k => $good) {
                    $good['is_show'] = '0';
                    $good['cid'] = $v;
                    $sql = "SELECT
                                  g.`id`,
                                  g.`goods_name`,
                                  g.`brand_id`,
                                  g.`goods_type`,
                                  g.`amount`,
                                  g.`goods_img`,
                                  g.`goods_thumb`,
                                  g.`standard`,
                                  g.`is_show`,
                                  g.`all_price`,
                                  g.`city_price`,
                                  g.`create_time`,
                                  g.`cid`,
                                  g.`pid`
                              FROM goods AS g
                              WHERE g.is_delete = 0 AND g.cid = {$v} AND g.pid = {$good['id']}";
                    $query = $this->db->fetchRow($sql);
                    if ($query)
                        $good = $query;

                    $actsql = 'SELECT ta.`name`,ta.id,ta.type,tag.`activity_price`
                               FROM `activity_goods` AS tag
                               INNER JOIN `activity` AS ta ON tag.`activity_id` = ta.`id`
                               WHERE tag.goods_id=' . $good['id'] . '
                               AND ' . time() . ' BETWEEN ta.start_time AND (ta.end_time+86399) AND ta.is_del=0';
                    $activity = $this->db->fetchRow($actsql);
                    $good['activity'] = ($activity) ? $activity['name'] : '';
                    $good['activity_id'] = ($activity) ? $activity['id'] : '';
                    $good['activity_type'] = ($activity) ? $activity['type'] : '';
                    $good['activity_price'] = ($activity) ? $activity['activity_price'] : '';

                    $goods[$k] = $good;
                }
                $goodsList = $goods;
            }
        }*/


        $goodsList = $this->db->fetchAll($sql);
        if (!$isHeadOffice || $where['query_id'] > 1) {
            $cid = $where['query_id'] ?: $where['company_id'];
            if (!is_array($cid)) {
                $cid = explode(',', $cid);
            }
            if($cid[0] == 1){
                return $goodsList;
            }

            foreach ($goodsList as $key => $good) {

                $good['is_show'] = 0;
                $good['cid'] = $cid[0];

                $sql = "SELECT 
                              g.`id`,
                              g.`goods_name`,
                              g.`brand_id`,
                              g.`goods_type`,
                              g.`amount`,
                              g.`goods_img`,
                              g.`goods_thumb`,
                              g.`standard`,
                              g.`is_show`,
                              g.`all_price`,
                              g.`city_price`,
                              g.`create_time`,
                              g.`cid`,
                              g.`pid`
                          FROM goods AS g
                          WHERE g.is_delete = 0 AND g.cid = {$cid[0]} AND g.pid = {$good['id']}";
                $query = $this->db->fetchRow($sql);
                if ($query)
                    $good = $query;
                $isNochange = $this->recodeExists($cid[0], $good['brand_id']) && $userinfo['id'] != 1 ? 1 : 0;
                $good['nochange'] = $isNochange ? 1 : 0;
                $actsql = 'SELECT ta.`name`, ta.id,ta.type, tag.`activity_price`
                           FROM `activity_goods` AS tag 
                           INNER JOIN `activity` AS ta ON tag.`activity_id` = ta.`id` 
                           WHERE tag.goods_id=' . $good['id'] . ' 
                           AND ' . time() . ' BETWEEN ta.start_time AND (ta.end_time) AND ta.is_del=0';
                $activity = $this->db->fetchRow($actsql);
                $good['activity'] = ($activity) ? $activity['name'] : '';
                $good['activity_id'] = ($activity) ? $activity['id'] : '';
                $good['activity_type'] = ($activity) ? $activity['type'] : '';
                $good['activity_price'] = ($activity) ? $activity['activity_price'] : '';

                $list[$key] = $good;
            }
            return $list;
        }else{
            return $goodsList;
        }

    }
    /**
     * 品牌列表
     */
    public function getBrand(){
        $sql="select id,name from brand where is_show=1 order by `order` asc, create_time desc";
        return $this->db->fetchAll($sql);
    }
    /**
     * 品类列表
     */
    public function get_goods_type(){
        $sql="select id,name from goods_type where is_show=1 order by `order` asc, create_time desc";
        return $this->db->fetchAll($sql);
    }
    /**
     * 优惠劵列表
     */
    public function getCoupons($company_id=0)
    {
        $where = ' AND is_del=0 AND '.time()." BETWEEN start_time AND (end_time+86399)";
        $sql = Util::companyIdIsArray($company_id);
        if ($sql != 1) {
            $where = " AND c$sql AND is_del=0 AND ".time()." BETWEEN start_time AND (end_time+86399)";
        }
        $sql="select * from coupons WHERE type != 3 $where";
        return $this->db->fetchAll($sql);
    }
    
    /**
     * 可用优惠劵
     */
    public function getCouponsList()
    {
        $time = time();
        $all = $this->db->fetchAll("SELECT c.`id`,name FROM coupons AS c where $time<c.end_time AND c.is_del=0 AND c.type != 3"); // 所有的
        $couponsArr = array();
        foreach ($all as $k => $v) {
            $couponsArr[$v['id']] = $v['name'];
        }
        return $couponsArr;
    }
    
    /**
     * 活动列表
     */
    public function getActivity($company_id=0)
    {
        $where = ' where is_del=0 AND '.time()." BETWEEN start_time AND (end_time+86399)";
        $sql = Util::companyIdIsArray($company_id);
        if ($sql != 1) {
            $where = " where c$sql AND is_del=0 AND ".time()." BETWEEN start_time AND (end_time+86399)";
        }
        $time = time();
        $sql="select * from activity $where ";
        return $this->db->fetchAll($sql);
    }
    
    public function getCouponsArr($id)
    {
        $time = time();
        $sql="SELECT c.`id`,c.name FROM coupons AS c INNER JOIN `coupons_goods` AS cg ON c.`id`=cg.`coupons_id` where cg.goods_id=$id";
        return $this->db->fetchAll($sql);
    }

    /**
     * 商品列表分页
     *
     */
    public function getGoodsPags($sql, $list, $perpage,$page,$url){
//         echo '<h3 style="color:red;">'.$sql.'</h3>';
        $total = $this->db->fetchOne($sql);
        //$total =count($goodsList);
//        if(is_array($list[0][0])){
//            $total = ceil(count($goodsList) * count($list[0]) / $perpage);
//        }
        return Util::buildPagebar($total,$perpage,$page,$url);
    }
    /**
     * 商品详情
     * @param unknown $id
     * @return unknown
     */
    public function getGoodsInfo($id)
    {
        $userinfo = Yaf_Registry::get('developer');
        
        $goods = $this->db->fetchRow('SELECT *,a1.id AS invid FROM inventory a1 LEFT JOIN goods a2 ON a1.goods_id = a2.id WHERE a1.`id` ='.$id);
        if ($goods['goods_desc_img']) {
            $img=explode(',',$goods['goods_desc_img']);
            foreach($img as $k=>$v){
                $goods['desc_img'][$k] = Util::getpicbyhash($v);
            }
        } else {
            $goods['desc_img']='';
        }
        if ($goods['original_img']) {
            $goods_desc_img=explode(',',$goods['original_img']);
            foreach($goods_desc_img as $k=>$v){
                $goods['images'][$k] = Util::getpicbyhash($v);
            }
        } else {
            $goods['images']='';
        }
        if ($goods['goods_thumb']) {$goods['goods_thumb'] = Util::getpicbyhash($goods['goods_thumb']);}
        if ($goods['goods_img']) {$goods['goods_img'] = Util::getpicbyhash($goods['goods_img']);}
        if ($goods['original_img']) {$goods['original_img'] = Util::getpicbyhash($goods['original_img']);}
        if ($goods['create_time']) {$goods['create_time'] = date('Y-m-d H:i:s', $goods['create_time']);}
        $goodsType = '';
        $goodsType = $this->db->fetchOne('select name from goods_type where id ='.$goods['goods_type']);
        $brands = '';
        $brands = $this->db->fetchOne('select brand_name from brand where id ='.$goods['brand_id']);
        $goods['brand_id'] = $brands;
        $goods['goods_type'] = $goodsType;
        return $goods;
    }
    
    /**
     * 添加商品
     * @param unknown $data
     * @return unknown
     */
    public function addGoods($goods,$coupons)
    {
        $return = $this->db->insert('goods', $goods);
        $id = $this->db->lastInsertId();
        if (!empty($coupons) && $id) {
            $this->db->insert('coupons_goods', array('coupons_id'=>$coupons, 'goods_id'=>$id));
        }
        return $id;
    }
    /**
     * 修改商品图片
     * @param unknown $id
     * @param unknown $goods_desc_img
     * @param unknown $goods_img
     * @param unknown $original_img
     */
    public function upGoodsImg($id, $goods_desc_img, $goods_img, $original_img)
    {
       
        $data = array(
            'goods_desc_img' => $goods_desc_img,
            'goods_img' => $goods_img['path'],
            'goods_thumb' => $goods_img['thumb'],
            'original_img' => $original_img
        );
        $data = array_filter($data);
        if ($data) {
            $this->db->update('goods', $data, "id=$id");
            //修改同时修改子公司

            $this->db->update('goods', $data, "pid=$id");

            //修改同时修改子公司
        }
    }
    /**
     * 获取商品信息
     * @return multitype:unknown
     */
    public function getGoods()
    {
        $get = $_GET['id'];
        if (isset($get)) {
            $goods = $this->db->fetchRow("select * from goods where id={$get} AND is_delete=0");
            if ($goods['goods_img']) {$goods['goods_img'] = Util::getpicbyhash($goods['goods_img']);}
            if ($goods['goods_thumb']) {$goods['goods_thumb'] = Util::getpicbyhash($goods['goods_thumb']);}
            if ($goods['goods_desc_img']) {
                $images = explode(',', $goods['goods_desc_img']);
                $goods['goods_desc_img'] = array();
                foreach ($images as $k) {
                    $goods['goods_desc_img'][] = Util::getpicbyhash($k);
                }
            }
            if ($goods['original_img']) {
                $images = explode(',', $goods['original_img']);
                $goods['original_img'] = array();
                foreach ($images as $k) {
                    $goods['original_img'][] = Util::getpicbyhash($k);
                }
            }
        }
        $goodsType = $this->db->fetchAll('select id,name from goods_type where is_show=1 order by `order` desc ');
        $brands = $this->db->fetchAll('select id,name from brand where is_show=1 order by `order` desc ');
        return array($goodsType,$brands,$goods);
    }
    
    /**
     * 地区
     */
    public function getAddress()
    {
        $province = $this->db->fetchAll("select * from province order by id");
        $city = $this->db->fetchAll("select * from city order by id");
        $area = $this->db->fetchAll("select * from area order by id");
        return array('province' => $province, 'city' => $city, 'area' => $area);
    }
    
    /**
     * 修改上下架
     * @param unknown $id
     * @param unknown $is_show
     * @return number
     */
    public function upShow($id, $is_show,$cid)
    {
        if ($is_show == 1) {
            $is_show = 0;
        } elseif ($is_show == 0) {
            $is_show = 1;
        }

        /*
        if($cid>'0' && $cid != '1')//子公司操作商品pid
        {
            $return = $this->db->update('goods',array('is_show' => $is_show,'pid' => $id,'cid' => $cid),"pid = $id AND cid = $cid");
        }else{
            $return = $this->db->update('goods', array('is_show' => $is_show), 'id='.$id);
        }
        */
        $data = ['is_show' => $is_show];
        if($is_show == 0){
            $data['amount'] = 0;
        }
        $return = $this->db->update('goods', $data, "id=$id");

        if ($return) {
            return $is_show;
        } else {
            return 400;
        }
    }
    
    /**
     * 删除商品
     * @param unknown $id
     * @param unknown $is_delete
     * @return number
     */
    public function delete($id, $is_delete,$cid)
    {
         if($cid>'0' && $cid != '1')//子公司操作商品pid
         {
            $return = $this->db->update('goods', array('is_delete' => $is_delete), 'id='.$id);
         }else{
            $return = $this->db->update('goods',array('is_delete' => $is_delete,'id' => $id,'cid' => $cid),"id = $id AND cid = $cid");
            $this->db->update('goods', array('is_delete' => $is_delete), "pid = $id");
         }
        if ($return) {
            return 200;
        } else {
            return 400;
        }
    }
    
    /**
     * 修改商品
     * @param unknown $data
     * @param unknown $id
     * @return unknown
     */
    public function updateGoods($id,$goods,$coupons, $condition)
    {
        try {
            if (!empty($coupons) || $coupons === '0') {
                $this->db->delete('coupons_goods', "goods_id=$id");
                if ($coupons !== '0' || !empty($coupons)) {
                    $this->db->insert('coupons_goods', array('coupons_id'=>$coupons, 'goods_id'=>$id));
                }
            }
//             $user = Yaf_Registry::get('user');
//             if ($user['cid'] > 0){
//                 $goods['company_id'] = $user['cid'];
//             }
            $return = $this->db->update('goods', $goods, "id = $id");
            //修改同时修改子公司
            $info = "SELECT * FROM goods WHERE pid={$id}";

            if(!$condition['city_price_is_update_all']){
                unset($goods['city_price']);
            }
            if(!$condition['all_price_is_update_all']){
                unset($goods['all_price']);
            }
            if(!$condition['amount_is_update_all']){
                unset($goods['amount']);
            }
            unset($goods['cid']);
            if(count($info) > 0)
                $return = $this->db->update('goods', $goods, "pid = $id");

            //修改同时修改子公司
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * 上传图片
     * @param unknown $id
     * @param unknown $file
     * @param unknown $select
     * @return string
     */
    public function uploadImg($id,$file,$select)
    {
        $path = $this->db->fetchOne('select '.$select.' from goods where id='.$id);
        $imgPath = (!empty($path)?$path.',':'').(!empty($file)?($file):'');
//         print_r($imgPath);die;
        $return = $this->db->update('goods', array($select=>$imgPath), "id=$id");

        $this->db->update('goods', array($select=>$imgPath), "pid=$id");
        if ($return) {
            return '200';
        } else {
            return '400';
        }
    }
    
    /**
     * 修改商品缩略图
     * @param unknown $id
     * @param unknown $file
     * @param unknown $thumb
     * @return string
     */
    public function uploadGoodsImg($id,$file,$thumb)
    {
        $path = $this->db->fetchRow('select goods_img,goods_thumb from goods where id='.$id);
        Util::delImage($path['goods_img']);
        Util::delImage($path['goods_thumb']);
        $return = $this->db->update('goods', array('goods_img'=>$file,'goods_thumb'=>$thumb), "id=$id");
        if ($return) {
            return '200';
        } else {
            return '400';
        }
    }
    
    /**
     * 删除图片
     * @param unknown $id
     * @param unknown $img
     * @param unknown $select
     * @return string
     */
    public function delImg($id,$img,$select)
    {
        try {
            $path = $this->db->fetchOne('select '.$select.' from goods where id='.$id);
            $data = explode(',', $path);
            Util::delImage($data[$img]);
            unset($data[$img]);
            $data = implode(',',$data);
            $return = $this->db->update('goods', array($select=>$data), "id=$id");
            return 'del';
        } catch (Exception $e) {
            return '400';
        }
    }
    /**
     * 检测分公司是否存在商品
     * @param 商品ID $id
     * @param 公司ID $cid
     * @return string
     */
    public function getCompanyInfo($id,$cid)
    {

        //查询是否有该商品
        $info = $this->db->fetchRow("select * from goods where id = $id AND cid = $cid AND is_delete='0'");
        if($info){
            return $id;
        }

        //如果没有才到总商品库中查询
        $info = $this->db->fetchRow("select * from goods where pid = $id AND cid = $cid AND is_delete='0'");
        if(!$info)
        {
            $arr = $this->db->fetchRow("select * from goods where id = $id");
            //查看一下是否改数据已经添加过
            $tmp_arr = $this->db->fetchRow("SELECT * FROM  `goods` WHERE  `goods_name` LIKE  '{$arr['goods_name']}'AND  `standard` LIKE  '{$arr['standard']}' and cid = {$cid} and is_delete=0");
            if($tmp_arr){
                return $tmp_arr['id'];
            }
            $arr1 = array_shift($arr);
            $newArr = $arr;
            $newArr['pid'] = $id;
            $newArr['cid'] = $cid;
            $newArr['is_show'] = 0;
            $re = $this->db->insert('goods', $newArr);
            if($re){
                return $this->db->lastInsertId();
            }
        }else{
            return $info['pid'];
        }
    }
    public function delImages($id,$select)
    {
        $path = $this->db->fetchOne('select '.$select.' from goods where id='.$id);
        $data = explode(',', $path);
        foreach ($data as $k => $v) {
            Util::delImage($v);
        }
    }
    
    public function getCity($id)
    {
        $city = $this->db->fetchAll("select * from city where parent_id=$id");
        return $city;
    }
    public function getArea($id)
    {
        $city = $this->db->fetchAll("select * from area where parent_id=$id AND id!=$id");
        return $city;
    }
    public function getInventory($id)
    {
       $inventory = $this->db->fetchRow("select ti.* from inventory as ti where id=$id");
       return $inventory;
    }
    
    public function getAddressName($user)
    {
        $address = $this->db->fetchRow("SELECT
        tp.name tp_name,
        tc.name tc_name,
        ta.name ta_name 
        FROM
        `province` AS tp
        LEFT JOIN `city` AS tc
        ON tc.`id` = {$user['city_id']}
        AND tc.`parent_id` = {$user['province_id']}
        LEFT JOIN `area` AS ta
        ON ta.`id` = {$user['area_id']}
        AND ta.`parent_id` = {$user['city_id']}
        WHERE tp.`id`={$user['province_id']}");
        return $address;
    }
    
    /**
     * 商品图片拖动排序
     * @param unknown $imgName
     * @param unknown $img
     * @param unknown $id
     */
    public function upImagePlace($imgName,$img,$id)
    {
        $imgStr = $this->db->fetchOne("SELECT $imgName from goods where id=$id");
        $imgStr = explode(',', $imgStr);
        $imgArr = array();
        foreach ($img as $k => $v) {
            $imgArr[] = $imgStr[$v];
        }
        return $this->db->update('goods', array($imgName => implode(',', $imgArr)), 'id='.$id);
    }
    
    /**
     * 修改库存
     * @param unknown $id
     * @param unknown $amount
     */
    public function upAmount($id, $amount,$cid)
    {
        //if($cid>'0' && $cid != '1')//子公司操作商品pid
            /*
        {
            $return = $this->db->update('goods',array('amount' => $amount,'pid' => $id,'cid' => $cid),"pid = $id AND cid = $cid");
        }else{
            $return = $this->db->update('goods', array('amount' => $amount), 'id='.$id);
        }
            */
        $return = $this->db->update('goods', array('amount' => $amount), 'id='.$id);
        if ($return == 0 || $return == 1) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * 修改价格
     * @param unknown $id
     * @param unknown $amount
     */
    public function upAllPrice($id, $price,$cid)
    {
        /*
        if($cid>'0' && $cid != '1')//子公司操作商品pid
        {
            $return = $this->db->update('goods',array('all_price' => $price,'pid' => $id,'cid' => $cid),"pid = $id AND cid = $cid");
        }else{
            $return = $this->db->update('goods', array('all_price' => $price), 'id='.$id);
         }
        */
        $return = $this->db->update('goods', array('all_price' => $price), 'id='.$id);
        if ($return == 0 || $return == 1) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * 修改参考价格
     * @param unknown $id
     * @param unknown $amount
     */
    public function upCityPrice($id, $price,$cid)
    {
        /*
        if($cid>'0' && $cid != '1')//子公司操作商品pid
        {
            $return = $this->db->update('goods',array('city_price' => $price,'pid' => $id,'cid' => $cid),"pid = $id AND cid = $cid");
        }else{
            $return = $this->db->update('goods', array('city_price' => $price), 'id='.$id);
        }
        */
        $return = $this->db->update('goods', array('city_price' => $price), 'id='.$id);
        if ($return == 0 || $return == 1) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 查看价格限定记录是否存在
     * @param $cid
     * @param $bid
     * @return boolean
     */
    public function recodeExists($cid, $bid){
        $sql = "SELECT count(*) 
                FROM goods_nochange
                WHERE company_id = $cid
                AND brand_id = $bid";
        $exists = $this->db->fetchOne($sql) ? true : false;
        return $exists;
    }

    /**
     * 添加经销商价格限定
     * @param $cid
     * @param $bid
     * @return bool
     */
    public function addNochange($cid, $bid){
        $data = ['company_id' => $cid, 'brand_id' => $bid];
        if($this->db->insert('goods_nochange', $data)){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 获取价格限定列表
     * @param $page
     * @param $pagesize
     * @return array
     */
    public function getNochangeList($page, $pagesize){
        $index = ($page - 1) * $pagesize;
        $sql = "SELECT gn.id id, d.companyname cname, b.name bname
                FROM goods_nochange gn 
                JOIN company d ON d.id = gn.company_id
                JOIN brand b ON b.id = gn.brand_id
                ORDER BY gn.id DESC 
                LIMIT $index, $pagesize";
        $list = $this->db->fetchAll($sql);
        $count = $this->db->fetchOne("SELECT count(id) number FROM goods_nochange");
        return ['list' => $list, 'count' => $count];
    }

    /**
     * 删除价格限定
     * @param $id
     * @return mixed
     */
    public function delNochange($id){
        return $this->db->delete('goods_nochange', "id=$id");
    }
}