<?php

class Service_PlatformExcel extends Service
{
    /**
     * 单品数据
     * @param array $data
     * */
    public function getsingle($data)
    {
        if ($data['company_id']) {
            $condition = Util::companyIdIsArray($data['company_id']);
            if ($condition != 1) {
                if ($data['query_id']) {
                    $where[] = 'psd.cid=' . $data['query_id'];
                    unset($data['query_id']);
                } else {
                    $where[] = 'psd.c' . $condition;
                }
            }

        }
        if ($data['goods_type']) {
            $where[] = 'tid=' . $data['goods_type'];
        }
        if ($data['brand']) {
            $where[] = 'bid=' . $data['brand'];
        }
        if ($data['goods_name']) {
            $where[] = " goods_name LIKE '%{$data['goods_name']}%' ";
        }
        if (!empty($data['ktime'])) {
            $where[] = 'start_time >= ' . strtotime($data['ktime']);
        }
        if (!empty($data['gtime'])) {
            $where[] = 'end_time <= ' . (strtotime($data['gtime']) + 24 * 60 * 60);
        }
//         if(!empty($data['goods_total'])){
//             $sort="goods_total ".$data['goods_total'].',';
//         }
//         if(!empty($data['goods_count'])){
//             $sort="goods_count ".$data['goods_count'].',';
//         }
        if ($where) {
            $where = '  WHERE  ' . implode(' AND ', $where);
        }
//         if($pagenum == '' || $pagenum == '0'){
//             $start = '0';
//         }else{
//             $start = ($pagenum-1)*$pageSize;
//         }
//         $limit = " LIMIT  {$start},{$pageSize} ";
        $sql = "
            SELECT
            SUM(psd.day_total) AS goods_total,
            SUM(psd.day_count) AS goods_count,
            psd.type_name type_name,
            psd.bname bname,
            psd.goods_id goods_id, 
            psd.goods_name goods_name,
            g.pid pid,
            g.standard standard
            FROM platfrom_single_day psd
            LEFT JOIN goods g 
            ON psd.goods_id = g.id
            {$where}
            GROUP BY goods_id
            ORDER BY goods_total DESC
            /*{$limit} */
        ";
        $query = $this->db->fetchAll($sql);
        $sql1 = "
             SELECT SUM(goods_total) AS total_money,SUM(goods_count) AS total_count
             FROM(
             SELECT  
                SUM(day_total) AS goods_total,
                SUM(day_count) AS goods_count,
                type_name,
                bname,
                goods_id,
                goods_name
                FROM platfrom_single_day
                GROUP BY goods_id
             ) aaa
        ";
        $query1 = $this->db->fetchRow($sql1);
        return array($query, $query1);
    }

    /**
     * 品牌数据
     * @param unknown $data
     * */
    public function getbrand($data)
    {
        if ($data['brand']) {
            $where[] = "brand_id={$data['brand']} ";
        }
        if ($data['goods_type']) {
            $where[] = "goods_type={$data['goods_type']} ";
        }
        if ($data['company_id']) {
            $sql = Util::companyIdIsArray($data['company_id']);
            if ($sql != 1) {
                if ($data['query_id']) {
                    $where[] = " company_id={$data['query_id']} ";
                    unset($data['query_id']);
                } else {
                    $where[] = " company_$sql ";
                }
            }

        }
        if (!empty($data['ktime'])) {
            $where[] = 'start_time >= ' . strtotime($data['ktime']);
        }
        if (!empty($data['gtime'])) {
            $where[] = 'end_time <= ' . (strtotime($data['gtime']) + 24 * 60 * 60);
        }
//         if(!empty($data['brand_total'])){
//             $sort="brand_total ".$data['brand_total'].',';
//         }
//         if(!empty($data['bcount'])){
//             $sort="total_count ".$data['bcount'].',';
//         }
        if ($where) {
            $where = '  WHERE  ' . implode(' AND ', $where);
        }
        $sql = "
        SELECT
        SUM(sales) AS brand_total,
        SUM(sales_count) AS total_count,
        tname,
        bname
        FROM platform_brand
        $where
        GROUP BY brand_id
        ORDER BY brand_total DESC
        ";
        $query = $this->db->fetchAll($sql);
        $sql1 = "
        SELECT SUM(brand_total) AS total,SUM(total_count) AS total_count FROM
            (SELECT
            SUM(sales) AS brand_total,
            SUM(sales_count) AS total_count,
            tname,
            bname
            FROM platform_brand
            $where
            GROUP BY brand_id
        ) aaa
            ";
        $query1 = $this->db->fetchRow($sql1);
        return array($query, $query1);
    }

    /**
     * 单品牌数据
     * @param array $data
     * */
    public function getonebrand($data)
    {
        if ($data['brand'] > '0' && $data['goods_type'] == 0 && $data['query_id'] == 0) {//如果只选品牌，每个公司一条数据显示
            $ksi = empty($data['ktime']) ? strtotime('2016-4-20') : strtotime($data['ktime']);
            $jie = empty($data['gtime']) ? strtotime(date('Y-m-d', time())) : strtotime($data['gtime']) + 24 * 3600;
            $sql = "
            SELECT
            SUM(sales) AS today_money,
            SUM(sales_count) AS today_count,
            company_id,
            companyname,
            bname,
            tname
            FROM platform_brand
            WHERE brand_id = {$data['brand']}
            AND start_time >= {$ksi} AND end_time <= {$jie}
            GROUP BY company_id
            ORDER BY today_money DESC
        ";
            $query = $this->db->fetchAll($sql);
            foreach($query as $k => $v){
                $v['k'] = $ksi;
                $v['j'] = $jie - (24 * 3600);
                $query[$k] = $v;
            }
            //当前品牌销售额和销量
            $sql1 = "
                SELECT
                SUM(sales) AS total_money,
                SUM(sales_count) AS bcount
                FROM platform_brand
                WHERE brand_id = {$data['brand']}
                ";
            $query1 = $this->db->fetchRow($sql1);
            return array($query, $query1);
        } elseif (!empty($data['brand']) && !empty($data['query_id'])  && $data['query_id']) {//品牌,公司id不为空一天一行显示当天品牌销售额
            $kadd_time = empty($data['ktime']) ? strtotime('2016-4-20') : strtotime($data['ktime']);
            $gadd_time = empty($data['gtime']) ? strtotime(date('Y-m-d', time())) : strtotime($data['gtime']) + 24 * 3600;
            $num = ($gadd_time - $kadd_time) / (3600 * 24);
            $arr = array();
            $ksi = $kadd_time - (24 * 3600);
            $jie = $ksi + 24 * 3600;
            $companyId = explode(',', $data['company_id']);
            $sql = Util::companyIdIsArray($companyId);
            if ($data['query_id']) {
                $where = " company_id = {$data['query_id']}";
            } else {
                $where = " company_$sql";
            }
            for ($x = 1; $x <= $num; $x++) {
                $ksi = $jie;
                $jie = $ksi + 24 * 3600;
                $sql = "SELECT
            SUM(sales) AS today_money,
            SUM(sales_count) AS today_count,
            bname,
            tname,
            companyname,
            start_time AS k,
            end_time AS j
            FROM platform_brand
            WHERE brand_id = {$data['brand']} AND start_time >= {$ksi} AND end_time <= {$jie} AND $where
            GROUP BY brand_id
            ";
                $query = $this->db->fetchRow($sql);
                $arr[] = $query;
            }
            $sql = "SELECT
            SUM(sales) AS total_money,
                SUM(sales_count) AS bcount
                FROM platform_brand
                WHERE brand_id = {$data['brand']} AND $where";
            foreach ($arr as $k => $v) {
                $integral[$k] = $v['j'];
            }
            array_multisort($integral, SORT_NUMERIC, SORT_DESC, $arr);
            $arrs = array_slice($arr, ($page - 1) * $pageSize, $pageSize);
            $query = $this->db->fetchRow($sql);
            return array($arrs, $query);
        }
    }

    /**
     * 销售统计数据
     * @param array $data
     * */
    public function getsale($data)
    {
        if ($data['company_id']) {
            $sql = Util::companyIdIsArray($data['company_id']);
            if ($sql != 1) {
                if ($data['query_id']) {
                    $where[] = " cid={$data['query_id']}";
                    unset($data['query_id']);
                } else {
                    $where[] = " c$sql";
                }
            }

        }
        if (!empty($data['ktime'])) {
            $where[] = ' start_time >= ' . strtotime($data['ktime']);
        }
        if (!empty($data['gtime'])) {
            $where[] = ' end_time <= ' . (strtotime($data['gtime']) + 24 * 60 * 60);
        }
        if ($where) {
            $where = '   WHERE' . implode(' AND ', $where);
        }
        $sql = "SELECT *
            FROM (
                SELECT
                SUM( day_sale ) AS total,
                SUM( day_salecount ) AS count,
                cid,
                operate_days,
                ucount,
                ucount_s,
                wname,
                unum
                FROM  `platfrom_sale_day`
                {$where}
                GROUP BY cid
            )aaa
        ORDER BY total DESC
        ";
        $list = $this->db->fetchAll($sql);
        return $list;
    }

    /**
     * [getInfo 在新的表中获取经销商的信息]
     * @param $company_id
     * @param $time
     * @param $first
     * @param $pageSize
     * @return array
     */
    public function getInfo($company_id, $time)
    {
        $where = '';
        $condition = 'where';
        if($company_id || $time){
            $where .= $condition ;
        }
        if( !$company_id && $time){
            $time = str_replace('AND','',$time);
            $company_id .=$time;
        }else{
            $company_id .=$time;
        }
        $sql = "SELECT companyname AS cname,warehousename as wname,MAX(user_total) as ucount,start_time,end_time,wid,cid,SUM(order_money) as order_money,SUM(order_num) as ordernum 
                FROM `platform_store` 
                ".$where.$company_id."
                GROUP BY cid";
        //echo $sql;exit;
        $data = $this->db->fetchAll($sql);
        //增加上线时间与运行状态
        foreach ($data as $k => $v) {
            $tmp_sql = "select status,create_time,uptime from warehouse where id = {$v['cid']} and status=1";
            $tmp_arr = $this->db->fetchRow($tmp_sql);
            $data[$k]['create_time'] = date('Y-m-d', $tmp_arr['create_time']);
            $data[$k]['status'] = $tmp_arr['status'] ? '正常' : '不正常';
            $data[$k]['uptime'] = date('Y-m-d', $tmp_arr['uptime']);

        }
        $sql1 = "SELECT id
                FROM `platform_store` 
                " . $where . $company_id . "
                GROUP BY cid;";

        $data2 = $this->db->fetchAll($sql1);
        $num = count($data2);
        $result['data'] = $data;
        $result['num'] = $num;
        return $result;
    }

    /**
     * [getOrderNum 获取活跃终端数]
     * @param $warehouse_id
     * @param $time
     * @return   string
     */
    public function getOrderNum($warehouse_id, $time)
    {
        if ($time) {
            $time = str_replace('start_time', 'add_time', $time);
            $time = str_replace('end_time', 'add_time', $time);
        }
        $sql = "SELECT COUNT(*) FROM order_info  WHERE warehouse_id=$warehouse_id AND order_status = 3 " . $time . " GROUP BY user_id ";
        $data = $this->db->fetchAll($sql);
        $num = count($data);
        return $num;
    }

    /**
     * [getUserOrder 获取下单的终端数]
     * @param $warehouse_id
     * @param $time
     * @return   string
     */
    public function getUserOrder($warehouse_id, $time)
    {
        if ($time) {
            $time = str_replace('start_time', 'add_time', $time);
            $time = str_replace('end_time', 'add_time', $time);
        }
        $sql = "SELECT count(*) AS num
                  FROM(
		          SELECT COUNT(u.id) AS uorder,u.id AS uid,SUBSTRING(i.order_number, 2, 4) AS corder
		          FROM users AS u LEFT JOIN order_info AS i ON i.user_id = u.id
		          WHERE u.cid=$warehouse_id AND u.auditType = 2 $time
		          GROUP BY
			      u.id) aaa
                  WHERE aaa.corder = $warehouse_id";
        $data = $this->db->fetchAll($sql);
        $num = $data[0];
        return $num;
    }

    /*
     * 获取所有订单的商品的信息
     */
    public function getGoodsStatistics($cid, $brand, $goods_type, $search_name, $time)
    {
        $where = '';
        if ($brand) {
            $where .= " AND b.id =" . $brand;
        }
        if ($goods_type) {
            $where .= " AND gt.id =" . $goods_type;
        }
        if ($search_name) {
            $where .= " AND  g.goods_name like '%{$search_name}%'";
        }
        if ($time) {
            $where .= " AND " . $time;
        }
        $condition = Util::companyIdIsArray($cid);
        if ($condition != 1) {
            $sql1 = " g.c$condition ";
        } else {
            $sql1 = " g.cid = 0";
        }
        $sql = "SELECT gs.goods_id,SUM(gs.goods_number) AS goods_number,g.goods_name,b. NAME AS brand,gt.name
               FROM order_goods AS gs
               INNER JOIN order_info AS oi ON gs.order_id = oi.id
               INNER JOIN goods AS g ON gs.goods_id = g.id
               LEFT JOIN brand AS b ON g.brand_id = b.id
               LEFT JOIN goods_type AS gt ON g.goods_type = gt.id
               WHERE oi.order_status = 3  AND $sql1 $where 
               GROUP BY gs.activity_id,g.goods_name";
        $data = $this->db2->fetchAll($sql);
        $result = array();
        $result['data'] = $data;
        return $result;
    }

    /**
     * [getWarehouseByCid 根据公司id查询经销商id]
     * @param $company_id
     * @return
     */
    public function getWarehouseByCid($company_id)
    {
        $condition = Util::companyIdIsArray($company_id);
        if ($condition != 1 && $condition != 'id = 1') {
            $where = " company_$condition";
        } else {
            $where = " company_id= 0";
        }
        $sql = "SELECT id FROM warehouse WHERE $where";
        $data = $this->db->fetchAll($sql);
        return $data;
    }

    /*
     * 获取经销商用户的信息
     */
    public function getTerminalstatistics($cid, $range, $judge, $time)
    {
        $cid = $cid ? $cid : 1;
        $sql = "SELECT u.auditType,u.id,u.account,oi.id as oid,count(u.id) as num ,u.userName, u.comName,MAX(oi.add_time) as last_time
                FROM users as u
                LEFT JOIN order_info as oi
                ON oi.user_id = u.id
                WHERE ($cid) $range $time $judge AND auditType=2
                group by u.id ";
        //echo $sql;exit;
        $data = $this->db->fetchAll($sql);
        $result = array();
        $result['data'] = $data;
        return $result;
    }

    public function getTerminalstatisticss($cid, $time)
    {
        $sql = "SELECT u.auditType,u.id,u.account,oi.id AS oid,COUNT(u.id) AS num,u.userName,u.comName,MAX(oi.add_time) AS last_time
              FROM users u
              JOIN order_info oi ON u.id = oi.user_id
              LEFT JOIN (SELECT u.id FROM users u JOIN order_info oi ON u.id = oi.user_id WHERE ($cid) AND oi.order_status = 3 GROUP BY u.id) AS a ON a.id = u.id
              WHERE a.id IS NULL AND ($cid) $time
              GROUP BY u.id ";
        //echo $sql;exit;
        $data = $this->db2->fetchAll($sql);
        $result = array();
        $result['data'] = $data;
        return $result;
    }

    /*
     * 根据用户id查找用户地址
     * @param $user_id
     * @return array
     */
    public function getAddressById($user_id)
    {
        $sql = "SELECT user_name,phone FROM user_address WHERE state=1 AND user_id={$user_id}";
        $data = $this->db->fetchRow($sql);
        return $data;
    }

    /*
     * 根据用户id查找用户最后登录的时间
     * @param $user_id
     * @return array
     */
    public function getUpdateById($user_id)
    {
        $sql = "SELECT MAX(updated_at) updatetime FROM login_log WHERE user_id=$user_id";
        $data = $this->db->fetchRow($sql);
        return $data;
    }

    /*
     * 获取所有订单的商品的信息
     */
    public function getGoodsStatistics1($cid, $brand, $goods_type, $search_name, $time)
    {
        $where = '';
        if ($brand) {
            $where .= " AND b.id =" . $brand;
        }
        if ($goods_type) {
            $where .= " AND gt.id =" . $goods_type;
        }
        if ($search_name) {
            $where .= " AND  g.goods_name like '%{$search_name}%'";
        }
        if ($time) {
            $where .= " AND " . $time;
        }
        $condition = Util::companyIdIsArray($cid);
        if ($condition != 1) {
            $sql1 = " g.c$condition ";
        } else {
            $sql1 = " g.cid = 0";
        }
        $sql = "SELECT gs.goods_id,SUM(gs.goods_number) AS goods_number,g.goods_name,b. NAME AS brand,gt.name
               FROM order_goods AS gs
               INNER JOIN order_info AS oi ON gs.order_id = oi.id
               INNER JOIN goods AS g ON gs.goods_id = g.id
               LEFT JOIN brand AS b ON g.brand_id = b.id
               LEFT JOIN goods_type AS gt ON g.goods_type = gt.id
               WHERE oi.order_status = 3  AND $sql1 $where 
               GROUP BY gs.activity_id,g.goods_name";
        $data = $this->db2->fetchAll($sql);
        $result = array();
        $result['data'] = $data;
        return $result;
    }

}