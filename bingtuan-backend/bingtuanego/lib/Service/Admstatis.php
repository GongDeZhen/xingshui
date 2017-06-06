<?php
/**
 *
 * @author waixiu
 *
 * 单品销量
 *  */
class Service_Admstatis extends Service{

    const SECOND_DAY = 86400;

    /**
     * [getInfo 在新的表中获取经销商的信息]
     * @param $company_id
     * @param $time
     * @param $first
     * @param $pageSize
     * @return array
     */
    public function getInfo($company_id,$time,$first,$pageSize)
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
                GROUP BY cid limit $first,$pageSize;";
        //echo $sql;exit;
        $data = $this->db2->fetchAll($sql);
        $sql1 = "SELECT id
                FROM `platform_store` 
                ".$where.$company_id."
                GROUP BY cid;";

        $data2 = $this->db2->fetchAll($sql1);
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
    public function getOrderNum($warehouse_id,$time){
        if ($time){
            $time = str_replace('start_time','add_time',$time);
            $time = str_replace('end_time','add_time',$time);
        }
        $sql = "SELECT COUNT( DISTINCT (i.`user_id`) ) AS c_1
                FROM order_info AS i
                LEFT JOIN users AS u ON i.`user_id` = u.id
                WHERE i.`warehouse_id` = {$warehouse_id}
                AND i.`order_status` =3
                AND  `u`.`auditType` =2
                {$time}";
        $data = $this->db2->fetchAll($sql);
        $num = $data[0]['c_1'];
        return $num;
    }

    /**
     * [getUserOrder 获取下单的终端数]
     * @param $warehouse_id
     * @param $time
     * @return   string
     */
    public function getUserOrder($warehouse_id,$time){
        if ($time){
            $time = str_replace('start_time','add_time',$time);
            $time = str_replace('end_time','add_time',$time);
        }
        $sql = "SELECT count(DISTINCT(o.user_id)) uu 
                FROM order_info o 
                JOIN users u ON o.user_id = u.id
                WHERE o.warehouse_id = {$warehouse_id} 
                AND u.auditType = 2
                {$time}";
        $data = $this->db2->fetchAll($sql);
        $num = $data[0]['uu'];
        return $num;
    }


    /**
     * 所有经销商
     */
    /* 销售统计 */
    public function getwarehouse($datainfo,$cIds, $num,$pagesize)
    {
        $where = 1;
        if($datainfo['company_id']!=0){
            $where .=" AND company_id={$datainfo['company_id']}";
        }

        $startTime = empty($datainfo['ktime']) ? strtotime('2016-04-20') : strtotime( $datainfo['ktime']);
        $where .=' AND start_time >= ' . $startTime;

        $endTime = empty($datainfo['gtime']) ? strtotime(date('Y-m-d')) : strtotime( $datainfo['gtime']) + 3600 * 24;
        $where .=' AND end_time <= ' . $endTime;
        $condtion = Util::companyIdIsArray($cIds);
        if($condtion != 1){
            $where .= " AND company_{$condtion}";
        }

        $pags = ($num*$pagesize)-$pagesize;
        $limit = "{$pags},{$pagesize} ";
        //获取数量
        $count_sql = "SELECT COUNT(*) cc
                      FROM (
                            SELECT cid FROM platfrom_sale_day
                            WHERE {$where}
                            GROUP BY cid
                            )aaa";
        $counts = $this->db2->fetchRow($count_sql);

        $sql = "SELECT SUM(day_sale) total, SUM(day_salecount) AS count, cid, operate_days, 
                  MAX(ucount) ucount, MAX(ucount_s) ucount_s, wname, unum
                FROM  `platfrom_sale_day`
                WHERE {$where}
                GROUP BY cid
                ORDER BY total DESC
                LIMIT {$limit}";
        $list = $this->db2->fetchAll($sql);
        foreach ($list as $k=>$v){
            //当日有效的订单数和订单金额
            $today = strtotime(date('Y-m-d', time()));
            $yesterday = $today - 24 * 60 * 60;
            if($endTime == strtotime(date('Y-m-d'))){
                $order_day="SELECT sum(goods_amount) day_money, warehouse_id, count(id) day_effective
                        FROM order_info
                        WHERE order_status = 3 AND warehouse_id = {$v['cid']} 
                        AND add_time >= {$yesterday} AND add_time <= {$today}
                        ORDER BY day_money DESC";
                $day_res=$this->db2->fetchRow($order_day);
            }

            //当月的订单数和订单金额
            $begin=mktime(0, 0, 0, date('m'), 1, date('Y'));
            $bend=mktime(0, 0, 0, date('m'), date('d'), date('Y'));
            $moth_order = "SELECT sum(goods_amount) month_money, warehouse_id, count(id) month_effective
                           FROM order_info
                           WHERE order_status = 3 AND warehouse_id = {$v['cid']} AND add_time >= {$begin} 
                           AND add_time <= {$bend}";
            $month_res=$this->db2->fetchRow($moth_order);

            if($startTime > strtotime('2016-04-20') || $endTime < strtotime(date('Y-m-d'))){
                $day = ($endTime - $startTime) / 3600 * 24;
                //时间段内的累计终端数
                $terminal_shops_sql = " SELECT count('id') AS unum FROM users WHERE cid={$v['cid']} AND auditType='2' AND createTime <= $endTime";
                $data = $this->db2->fetchRow($terminal_shops_sql);
                $list[$k]['unum'] = $data['unum'];
                $list[$k]['day'] = $day;
            }

            $list[$k]['day_num'] = $day_res['day_effective'] ? $day_res['day_effective'] : 0;
            $list[$k]['day_amount'] = $day_res['day_money'] ? $day_res['day_money'] : 0.00;
            $list[$k]['month_num'] = $month_res['month_effective'];
            $list[$k]['month_amount'] = $month_res['month_money'] ? $month_res['month_money'] : 0.00;
        }

        foreach ( $list as $k=>$v ) {
           $integral[$k] = $v['day_amount'];
        }
        //当日销售额排名
        array_multisort($integral,SORT_NUMERIC,SORT_DESC,$list);
        return array($list,$counts);
    }
//     public function getwarehouse($dataInfo,$pagesize,$num){
//         $data=array();
//         if($dataInfo['company_id']!=0){
//           $where="where company_id={$dataInfo['company_id']} AND";
//         }else{
//             $where='where ';
//         }
//         if(!empty($dataInfo['ktime'])){
//             $time[]='add_time >= '.strtotime( $dataInfo['ktime']);
//         }
//         if(!empty($dataInfo['gtime'])){
//             $time[]='add_time <= '.(strtotime( $dataInfo['gtime'])+24*60*60).' AND ';
//         }
//         $and = implode(' AND ',$time);
//         //所有经销商
//         $usql="select
//              count(cid) unum,
//              cid,
//             `warehouse`.`warehousename` AS wname,
//             `warehouse`.`company_id`,`warehouse`.`uptime`,users.id usid
//             from users
//             LEFT JOIN warehouse ON warehouse.id = cid
//             $where users.auditType =2
//             GROUP BY  warehouse.id
//             limit $pagesize,$num
//             ";
//         $data=$this->db->fetchAll($usql);

//              //分页总数
//         $countsql="select
//         count(cid) unum,
//         cid,
//         `warehouse`.`warehousename` AS wname,
//         `warehouse`.`company_id`
//         from users
//         LEFT JOIN warehouse ON warehouse.id = cid
//         GROUP BY warehouse.id";
//         $count = count($this->db->fetchAll($countsql));
//         foreach($data as $k => $v){
//             //有效的总订单数和总订单金额
//             $osql="SELECT
//             sum(goods_amount) AS total_money,
//             `order_info`.`warehouse_id`,
//             count(`order_info`.`id`) AS order_effective
//             FROM
//             order_info
//             where `order_info`.`order_status`=3 AND $and `order_info`.`warehouse_id`={$v['cid']}";
//             $query=$this->db->fetchRow($osql);
//             if(!$and)
//             {
//                 //当日有效的订单数和订单金额
//                 $today = strtotime(date('Y-m-d', time()));
//                 $end = $today + 24 * 60 * 60;
//                 $order_day="
//                 SELECT
//                 sum(goods_amount) AS day_money,
//                 `order_info`.`warehouse_id`,
//                 count(`order_info`.`id`) AS day_effective
//                 FROM
//                 order_info
//                 where `order_info`.`order_status`=3 AND  `order_info`.`warehouse_id`={$v['cid']} AND add_time >= {$today} AND add_time <= {$end}
//                 order by day_money desc
//                 ";
//                 $day_res=$this->db->fetchRow($order_day);
//                 //当月的订单数和订单金额
//                 $begin=mktime(0,0,0,date('m'),1,date('Y'));

//                 $bend=mktime(23,59,59,date('m'),date('t'),date('Y'));
//                 $moth_order = "
//                 SELECT
//                 sum(goods_amount) AS month_money,
//                 `order_info`.`warehouse_id`,
//                 count(`order_info`.`id`) AS month_effective
//                 FROM
//                 order_info
//                 where `order_info`.`order_status`=3 AND  `order_info`.`warehouse_id`={$v['cid']} AND add_time >= {$begin} AND add_time <= {$bend}";
//                 $month_res=$this->db->fetchRow($moth_order);
//                 $data[$k]['day_num'] = $day_res['day_effective'];
//                 $data[$k]['day_amount'] = $day_res['day_money'];
//                 $data[$k]['month_num'] = $month_res['month_effective'];
//                 $data[$k]['month_amount'] = $month_res['month_money'];
//             }
//             //下过单且成交的终端(活跃终端)
//             $f_terminal = "SELECT
//             `order_info`.`warehouse_id`,
//             count( DISTINCT (`order_info`.`user_id`)) AS u
//             FROM
//             order_info
//             left join users on `order_info`.`user_id`=users.id
//             Where `order_info`.`warehouse_id`={$v['cid']} AND `order_info`.`order_status`=3 AND `users`.`auditType`=2";
//             $effective_terminal=$this->db->fetchRow($f_terminal);
//             //所有下过单的终端(包括未成交的)
//             $sql = "SELECT
//             `order_info`.`warehouse_id`,
//             count( DISTINCT (`order_info`.`user_id`)) AS uu
//             FROM
//             order_info
//             left join users on `order_info`.`user_id`=users.id
//             Where `order_info`.`warehouse_id`={$v['cid']} AND `users`.`auditType`=2";
//             $query1=$this->db->fetchRow($sql);
//             //经销商总端数
//             $t_terminal = "SELECT COUNT(id) AS ucount FROM users Where cid={$v['cid']}  AND `users`.`auditType`=2";
//             $total_terminal=$this->db->fetchRow($t_terminal);
//             $data[$k]['total_money'] = $query['total_money'];//累计销售额
//             $data[$k]['order_effective'] = $query['order_effective'];//累积有效订单数
//             $data[$k]['count_w'] =$total_terminal['ucount'];
//             $data[$k]['count_u'] =$effective_terminal['u'];
//             $data[$k]['uu'] =$query1['uu'];
// //             $data[$k]['total_terminal'] = substr($effective_terminal['u']/$total_terminal['ucount']*100,0,5).'%';
//         }
//         return array('list'=>$data,'count'=>$count);
//     }

  /*
     * 查询公司和经销售
     */
    public function getwareho($companyIds){
        $condition = Util::companyIdIsArray($companyIds);
        $sql="SELECT id,companyname from company WHERE $condition";
        $day_res=$this->db2->fetchAll($sql);
        return $day_res;
    }

    /**
     *   所有商品单品销量
     *   */
    public function getsingList($data,$pagenum,$pageSize)
    {
        //return $data;
        $condition = 1;
        if($data['company_id'])
        {
            $sql = Util::companyIdIsArray($data['company_id']);
            if($sql != 1){
                if($data['query_id']){
                    $where[] = 'cid='.$data['query_id'];
                    $condition .= " AND cid = {$data['query_id']}";
                    unset($data['query_id']);
                }else{
                    $where[] = 'c'.$sql;
                    $condition .= " AND c$sql";
                }
            }

        }else{
            if($data['query_id']){
                $where[] = 'cid='.$data['query_id'];
                $condition .= " AND cid = {$data['query_id']}";
                unset($data['query_id']);
            }
        }
        if($data['goods_type'])
        {
            $where[] = 'tid='.$data['goods_type'];
        }
        if($data['brand'])
        {
            $where[] = 'bid='.$data['brand'];
        }
        if($data['goods_name'])
        {
            $where[] = " goods_name like '%{$data['goods_name']}%' ";
        }
        if(!empty($data['ktime'])){
            $where[]='start_time >= '.strtotime( $data['ktime']);
            $condition .= ' AND start_time >= '.strtotime( $data['ktime']);
        }
        if(!empty($data['gtime'])){
            $where[]='end_time <= '.(strtotime( $data['gtime'])+24*60*60);
            $condition .= ' AND end_time <= '.(strtotime( $data['gtime'])+24*60*60);
        }
        if(!empty($data['goods_total'])){
            $sort="goods_total ".$data['goods_total'].',';
        }
        if(!empty($data['goods_count'])){
            $sort="goods_count ".$data['goods_count'].',';
        }

        //没有销量的商品不统计
        $where[] ='day_count>0';
       if($where)
        {
            $where = '  WHERE  '.  implode(' AND ', $where);
        }
        if($pagenum == '' || $pagenum == '0'){
            $start = '0';
        }else{
            $start = ($pagenum-1)*$pageSize;
        }
        $limit = " LIMIT  {$start},{$pageSize} ";
        $sql = "
            SELECT  
            SUM(day_total) AS goods_total,
            SUM(day_count) AS goods_count,
            type_name,
            bname,
            goods_id,
            goods_name
            FROM platfrom_single_day
            {$where}
            GROUP BY goods_id
            ORDER BY {$sort} goods_total DESC
            {$limit}
            ";
         $query = $this->db2->fetchAll($sql);
         $sql1 = "
             SELECT COUNT(*) AS count 
             FROM(
                 SELECT
                 SUM(day_total) AS goods_total,
                 SUM(day_count) AS goods_count,
                 type_name,
                 bname,
                 goods_id,
                 goods_name
                 FROM platfrom_single_day
                 {$where}
                 GROUP BY goods_id
             ) aaa
         ";
         $query1 = $this->db2->fetchRow($sql1);

         $sql2 = "
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
                WHERE {$condition}
                GROUP BY goods_id
             ) aaa
             ";
         $query2 = $this->db2->fetchRow($sql2);
         return array($query,$query1,$query2);
    }
//     public function getsingList($cid,$brand,$type,$name,$gtime,$ktime,$pagenum,$pageSize,$sort)
//     {

//         if($brand) {
//             $where[] = " g.brand_id={$brand} ";
//         }
//         if($type) {
//             $where[] = " g.goods_type={$type} ";
//         }
//         if($name) {

//             $where[] = " g.goods_name LIKE '%$name%' ";
//         }
//         if($cid) {
//             $where[] = " g.cid={$cid} ";
//         }
//         if($ktime){
//             $where1[] = "i.add_time >=  $ktime";
//         }
//         if($gtime){
//             $where1[] = "i.add_time <= ".($gtime+24*60*60);
//         }
//         if ($where) {
//             $where = ' AND '. implode(' AND ', $where);
//         }
//         if($pagenum == '' || $pagenum == '0'){
//             $start = '0';
//         }else{
//             $start = ($pagenum-1)*$pageSize;
//         }
//         if($where1)
//         {
//             $where1 = ' AND '. implode(' AND ', $where1);
//         }
//         if($sort['goods_total']){
//             $order = "ORDER BY goods_total ".$sort['goods_total'];
//         }
//         if($sort['goods_count']){
//             $order = "ORDER BY goods_count ".$sort['goods_count'];
//         }
//         $limit = " LIMIT  {$start},{$pageSize} ";
//         //所有商品
//         $sql = "SELECT
//                 g.id AS goods_id,
//                 g.goods_name,
//                 g.cid,
//                 b.name AS bname,
//                 c.name AS type_name,
//                 b.id AS bid,
//                 c.id AS tid
//             FROM
//                 goods AS g
//                 LEFT JOIN brand b ON b.id = g.brand_id
//                 LEFT JOIN goods_type c ON c.id = g.goods_type
//             WHERE g.is_delete = '0' AND g.is_show = '1' {$where} AND b.is_show = '1'
//             {$limit}
//             ";
//         $query=$this->db->fetchAll($sql);
//         if(isset($cid) && $cid)
//         {
//             $sql_2 = "SELECT
//             sum(o.`goods_money`*o.`goods_number`) AS total_money,
//             sum(o.`goods_number`) AS total_count
//             FROM goods AS g
//             LEFT JOIN order_goods o ON o.goods_id = g.id
//             LEFT JOIN order_info AS i ON o.order_id = i.id
//             WHERE g.cid = $cid AND i.order_status = 3 $where1
//             ";
//         }else{
//             $sql_2 = "SELECT
//                          sum(goods_money*goods_number) AS total_money,
//                          sum(goods_number) AS total_count
//                       FROM order_goods AS o
//                 LEFT JOIN order_info AS i ON o.order_id = i.id
//                 WHERE i.order_status = 3 {$where1}
//                 ";
//         }
//        $total_count =$this->db->fetchRow($sql_2);
//         //return $sql_2;
//         foreach ($query as $k=>$v)
//         {
//             $sql = "SELECT
//                         SUM(o.`goods_money`*o.`goods_number`) AS goods_total,
//                         SUM(o.`goods_number`) AS goods_count
//                     FROM
//                         order_goods AS o
//                     LEFT JOIN order_info AS i ON o.order_id = i.id
//                     where o.`goods_id` = {$v['goods_id']} $where1 AND i.order_status = 3
//                 ";
//             $goods_total =$this->db->fetchRow($sql);
//             $query[$k]['goods_total'] = $goods_total['goods_total'];
//             $query[$k]['goods_count'] = $goods_total['goods_count'];
//             $query[$k]['total_count'] = $total_count['total_count'];
//             $query[$k]['total_money'] = $total_count['total_money'];
//         }
//         $gcount = "SELECT
//                     count(g.id) AS gcount
//                   FROM
//                     goods AS g
//                     LEFT JOIN brand b ON b.id = g.brand_id
//                     LEFT JOIN goods_type c ON c.id = g.goods_type
//             WHERE g.is_delete = '0' AND g.is_show = '1' {$where} AND b.is_show = '1'
//         ";
// //         foreach ( $query as $k=>$v ) {
// //             $integral[$k] = $v['goods_total'];
// //             $times[$k] = $v['goods_count'];
// //         }
// //        array_multisort($integral,SORT_NUMERIC,SORT_DESC,$times,SORT_NUMERIC,SORT_DESC,$query);
//         $counts=$this->db->fetchROW($gcount);
//         $query['gcount'] = $counts['gcount'];
//         return $query;
//     }
    /**
     *   所有品牌销售额
     *   */
    public function getbrandList($data,$pagenum,$pageSize)
    {
        if($data['brand']) {
            $where['brand_id'] = "brand_id={$data['brand']} ";
        }
        if($data['goods_type'])
        {
            $where['goods_type'] = "goods_type={$data['goods_type']} ";
        }
        if($data['query_id'] && !$data['company_id']){
            $where['company_id']= " company_id = {$data['query_id']} ";
            unset($where['query_id']);
        }
        if($data['company_id']) {
            $sql = Util::companyIdIsArray($data['company_id']);
            if($sql != 1){
                if($data['query_id']){
                    $where['company_id']= " company_id = {$data['query_id']} ";
                    unset($where['query_id']);
                }else{
                    $where['company_id']= " company_{$sql} ";
                }
            }
        }

       if(!empty($data['ktime'])){
            $where['start_time']='start_time >= '.strtotime( $data['ktime']);
        }
        if(!empty($data['gtime'])){
            $where['end_time']='end_time <= '.(strtotime( $data['gtime'])+24*60*60);
        }
        if(!empty($data['brand_total'])){
            $sort="brand_total ".$data['brand_total'].',';
        }
        if(!empty($data['bcount'])){
            $sort="total_count ".$data['bcount'].',';
        }
        //默认排序
        $sort = $sort ? $sort : 'brand_total DESC';

        if($pagenum == '' || $pagenum == '0'){
            $start = '0';
        }else{
            $start = ($pagenum-1)*$pageSize;
        }

        if($where)
        {
            $where1 = '  WHERE  '.  implode(' AND ', $where);
        }
        $limit = " LIMIT  {$start},{$pageSize} ";
        $sql = "
            SELECT
                SUM(sales) AS brand_total,
                SUM(sales_count) AS total_count,
                tname,
                bname
            FROM platform_brand
            $where1 
            GROUP BY brand_id
            ORDER BY  {$sort}
            {$limit}
            ";
        $query = $this->db2->fetchAll($sql);

        if($where)
        {
            if(array_key_exists('brand_id',$where)){
                unset($where['brand_id']);
            }
        }
        if($where){
            $where2 = '  WHERE  '.  implode(' AND ', $where);
        }
        $sql = "
            SELECT count(*) AS b_count,SUM(brand_total) AS total,SUM(total_count) AS total_count FROM
            (SELECT
                SUM(sales) AS brand_total,
                SUM(sales_count) AS total_count,
                tname,
                bname
            FROM platform_brand
            $where2
            GROUP BY brand_id
            ) aaa";
        $count = $this->db2->fetchRow($sql);
        //总的销售额
        if($where){
            if(array_key_exists('start_time',$where)){
                unset($where['start_time']);
            }
            if(array_key_exists('end_time',$where)){
                unset($where['end_time']);
            }
            if(array_key_exists('brand_id',$where)){
                unset($where['brand_id']);
            }
        }
        if($where){
            $where3 = '  WHERE  '.  implode(' AND ', $where);
        }
        $sql = "
        SELECT count(*) AS b_count,SUM(brand_total) AS total,SUM(total_count) AS total_count FROM
        (SELECT
        SUM(sales) AS brand_total,
        SUM(sales_count) AS total_count,
        tname,
        bname
        FROM platform_brand
        $where3
        GROUP BY brand_id
        ) aaa";
        $sale_total = $this->db2->fetchRow($sql);
        return array($query,$count,$sale_total);
    }
    //平台品牌总销售额
    public function getbrandInfo($data)
    {
//         if($data['goods_type'])
//         {
//             $where['goods_type'] = "goods_type={$data['goods_type']} ";
//         }
        $where = ' 1';
        if(!empty($data['ktime'])){
            $where .= ' AND start_time >= '.strtotime( $data['ktime']);
        }
        if(!empty($data['gtime'])){
            $where .= ' AND end_time <= '.(strtotime( $data['gtime'])+24*60*60);
        }
        $sql = "
            SELECT SUM(brand_total) AS total,SUM(total_count) AS total_count FROM
            (SELECT
            SUM(sales) AS brand_total,
            SUM(sales_count) AS total_count,
            tname,
            bname
            FROM platform_brand
            WHERE {$where}
            GROUP BY brand_id
            ) aaa";
        $count = $this->db2->fetchRow($sql);
        return $count;
    }
/*     public function getbrandList($data,$pagenum,$pageSize)
    {
            if($data['brand']) {
                $where[] = " g.brand_id={$data['brand']} ";
            }
            if($data['goods_type'])
            {
                $where[] = " g.goods_type={$data['goods_type']} ";
                $map[] = " g.goods_type={$data['goods_type']}";
            }
            if($data['company_id']) {
                $where[] = " g.cid={$data['company_id']} ";
                $map[] = " g.cid={$data['company_id']}";
            }
            if($data['ktime']){
                $ktime = strtotime($data['ktime']);
                $where[] = "i.add_time >= $ktime";
                $where1[] = "i.add_time >=  $ktime";
            }
            if($data['gtime']){
                $gtime = strtotime($data['gtime']);
                $where[] = "i.add_time <= ".($gtime+24*60*60);
                $where1[] = "i.add_time <= ".($gtime+24*60*60);
            }
            if ($where) {
                $where = ' AND '. implode(' AND ', $where);
            }
            if ($where1) {
                $where1 = ' AND '. implode(' AND ', $where1);
            }
            if ($map) {
                $map = ' AND '. implode(' AND ', $map);
            }
            if($data['total_money_sort']){
                $order = "ORDER BY total_money ".$data['total_money_sort'];
            }
            if($data['bcount_sort']){
                $order = "ORDER BY bcount ".$data['bcount_sort'];
            }
            if($pagenum == '' || $pagenum == '0'){
                $start = '0';
            }else{
                $start = ($pagenum-1)*$pageSize;
            }

            $limit = " LIMIT  {$start},{$pageSize} ";
            if($order)
            {
                $sql = "SELECT
                SUM( o.goods_number * o.goods_money ) total_money,
                SUM(o.goods_number) AS bcount,
                b.id AS bid,
                b.name AS bname,
                t.name AS tname,
                t.create_time AS ttime
                FROM  `order_goods` o
                LEFT JOIN goods g ON o.goods_id = g.id
                LEFT JOIN brand b ON g.brand_id = b.id
                LEFT JOIN goods_type t ON g.goods_type = t.id
                LEFT JOIN order_info i ON o.order_id = i.id
                WHERE i.order_status =3 AND b.is_show=1 {$where}
                GROUP BY b.id
                {$order}
                {$limit}
                ";
            }else{
                $sql = "SELECT
                SUM( o.goods_number * o.goods_money ) total_money,
                SUM(o.goods_number) AS bcount,
                b.id AS bid,
                b.name AS bname,
                t.name AS tname,
                t.create_time AS ttime
                FROM  `order_goods` o
                LEFT JOIN goods g ON o.goods_id = g.id
                LEFT JOIN brand b ON g.brand_id = b.id
                LEFT JOIN goods_type t ON g.goods_type = t.id
                LEFT JOIN order_info i ON o.order_id = i.id
                WHERE i.order_status =3 AND b.is_show=1 {$where}
                GROUP BY b.id
                ORDER BY total_money DESC
                {$limit}
                ";
            }
        $query=$this->db->fetchAll($sql);
        $sql1 = "SELECT
                SUM( total_money ) AS btotal_money,
                SUM( bcount ) AS brand_count
            FROM (
                SELECT SUM( o.goods_number * o.goods_money ) total_money,
                    SUM(o.goods_number) AS bcount,
                    b.id AS bid, b.name AS bname
                FROM  `order_goods` o
                    LEFT JOIN goods g ON o.goods_id = g.id
                    LEFT JOIN brand b ON g.brand_id = b.id
                    LEFT JOIN order_info i ON o.order_id = i.id
                WHERE i.order_status =3 AND b.is_show =1 {$where1} {$map}
                GROUP BY b.id
            )aaa";
       // COUNT( b.id ) AS bcount,
        $query1=$this->db->fetchROW($sql1);
        $count = "SELECT
                    SUM( o.goods_number * o.goods_money ) total_money,
                    count(b.id) AS bcount,
                    b.id AS bid,
                    b.name AS bname
                    FROM  `order_goods` o
                    LEFT JOIN goods g ON o.goods_id = g.id
                    LEFT JOIN brand b ON g.brand_id = b.id
                    LEFT JOIN order_info i ON o.order_id = i.id
                    WHERE i.order_status =3 AND b.is_show=1 {$where}
               GROUP BY b.id
        ";

        $counts=$this->db->fetchAll($count);
        $b_count = count($counts);
        $query['b_count'] = $b_count;
        return array($query,$query1);
    } */
    /**
     * 终端店统计
     *   */
    public  function getstoreList($cid,$gtime,$ktime,$pagenum,$pageSize)
    {
        if($cid) {
            //$where[] = " w.company_id={$cid} ";
            $where1[] = "w.company_id={$cid}";
        }
        if($ktime){
            $where[] = "i.add_time >=  ".strtotime($ktime);
            $map[] = "u.createTime >=  ".strtotime($ktime);
        }else{
            $where[] = "i.add_time >=  ".strtotime('2016-4-18');
        }
        if($gtime){
             $where[] = "i.add_time <= ".(strtotime($gtime)+24*60*60);
             $map[] = "u.createTime <= ".(strtotime($gtime)+24*60*60);
             $map1[] = "u.createTime <= ".(strtotime($gtime)+24*60*60);
        }else{
            $today = strtotime(date('Y-m-d', time()));
            $end = $today + 24 * 60 * 60;
            $where[] = "i.add_time <= $end";
            $map[] = "u.createTime <= $end";
            $map1[] = "u.createTime <= $end";
        }
        if ($where) {
            $where = ' AND '. implode(' AND ', $where);
        }
        if ($where1) {
            $where1 = ' AND '. implode(' AND ', $where1);
        }
        if($map)
        {
            $map = ' AND '. implode(' AND ', $map);
        }
        if($map1)
        {
            $map1 = ' AND '. implode(' AND ', $map1);
        }
        if($pagenum == '' || $pagenum == '0'){
            $start = '0';
        }else{
            $start = ($pagenum-1)*$pageSize;
        }
        $limit = " LIMIT  {$start},{$pageSize} ";
        //经销商和用户总数
         $sql1 = "
               SELECT 
                    COUNT(u.id) AS ucount, 
                    w.warehousename AS wname, 
                    w.id AS wid, 
                    c.companyname AS cname
                    FROM users AS u
                    LEFT JOIN warehouse AS w ON u.cid=w.id 
                    LEFT JOIN company AS c ON c.id=w.company_id 
                    WHERE  u.auditType = 2 {$where1} {$map1}
                GROUP BY wid
                ORDER by  `ucount` DESC 
             {$limit}
             ";
         $query=$this->db2->fetchAll($sql1);
         //return $query;
         foreach ($query as $k=>$v) {
             //下单终端总数
             $sql2 = "
               SELECT count(*) AS c from (
               SELECT 
                    COUNT( u.id ) AS uorder,
                    u.id AS uid,
                    SUBSTRING( i.order_number, 2, 4 ) AS corder
                    FROM users AS u
                    LEFT JOIN order_info AS i ON i.user_id = u.id
                    WHERE u.cid = {$v['wid']} AND u.auditType = 2 {$where} {$map1}
                    GROUP BY u.id
                    ) aaa
                    WHERE aaa.corder ={$v['wid']}
                 ";
             $uorder = $this->db2->fetchRow($sql2);
             $query[$k]['uorder'] = $uorder['c'];
             //(活跃终端)
             $sql3 = "SELECT c_1,to_money 
             FROM (
             SELECT
             i.`warehouse_id`,
             COUNT( DISTINCT (i.`user_id`)) AS c_1,
             SUM(i.goods_amount) AS to_money,
             SUBSTRING( i.order_number, 2, 4 ) AS corder
             FROM
             order_info AS i
             left join users AS u on i.`user_id`=u.id
             Where 
             i.`warehouse_id`={$v['wid']}
             AND i.`order_status`=3
             AND `u`.`auditType`=2 {$where} {$map1}
             ) aaa
             WHERE aaa.corder ={$v['wid']}"
                 ;
         //    return $sql3;
//              //活跃终端
//              $sql3 = "SELECT count(uorder_1) AS c_1,SUM(order_money) AS to_money from(
//                  SELECT
//                         COUNT( u.id ) AS uorder_1,
//                         SUM(i.goods_amount) AS order_money
//                  FROM users AS u
//                  right JOIN order_info AS i ON i.user_id = u.id
//                  WHERE u.cid = {$v['wid']} AND i.order_status =3 {$where}
//                  GROUP BY u.id
//                  ) bbb
//              ";

             $uorder_1 = $this->db2->fetchRow($sql3);
             $query[$k]['uorder_1'] = $uorder_1['c_1'];
             $query[$k]['order_money'] = $uorder_1['to_money'];
             //总有效单数
//              $sql4 = "
//              SELECT
//                  COUNT(*) AS ordernum,
//                  u.cid
//                  FROM users AS u
//                  LEFT JOIN order_info AS i ON i.user_id = u.id
//                  WHERE u.cid = {$v['wid']} AND u.auditType = 2 AND i.order_status =3 {$where} {$map}
//                  GROUP BY u.cid
//              ";
             $sql4 = "
                 SELECT COUNT( * ) AS ordernum
                    FROM (
                    SELECT SUBSTRING( i.order_number, 2, 4 ) AS corder, u.cid
                    FROM users AS u
                    LEFT JOIN order_info AS i ON i.user_id = u.id
                    WHERE u.cid = {$v['wid']} AND u.auditType = 2 AND i.order_status =3 {$where} {$map1}
                    AND u.auditType =2
                    AND i.order_status =3
                    )aaa
                    WHERE aaa.corder ={$v['wid']}
                 ";
             $ordernum = $this->db2->fetchRow($sql4);
             $query[$k]['ordernum'] = $ordernum['ordernum'];
         }
         $count = "SELECT COUNT(*) AS c from(
              SELECT 
                    COUNT(u.id) AS ucount, 
                    w.id AS wid
                    FROM users AS u
                    LEFT JOIN warehouse AS w ON u.cid=w.id 
                    LEFT JOIN company AS c ON c.id=w.company_id 
                    WHERE  u.auditType = 2 {$where1}
                GROUP BY wid
                )ccc
             ";
         $counts=$this->db2->fetchROW($count);
         $query['count'] = $counts['c'];
         return $query;
    }
    /*
     * 订单号添加前缀
     * 分公司id，
     * 前缀
     *   */
    public function add_order($cid,$order)
    {
        $sql="SELECT
        i.order_number,
        u.cid
        FROM users AS u
        LEFT JOIN order_info AS i ON i.user_id = u.id
        WHERE u.cid = {$cid} AND u.auditType = 2
        ";
         $query = $this->db->fetchAll($sql);
         foreach ($query as  $k=>$v){
                if(strlen($query[$k]['order_number']) == '18')
                {
                    //return  $query[$k]['order_number'];
                    $str = "{$order}".$query[$k]['order_number'];
                    $sql = "UPDATE order_info SET order_number = {$str} WHERE order_number = {$query[$k]['order_number']}";
                    $return = $this->db->fetchRow($sql);
                }else{
                    $return = 2;
                };
            }
            return $return;
    }
    /**
     *  首页
     *   */
    public function indexList($cIds)
    {
        $condition = Util::companyIdIsArray($cIds);
        //总终端数
        $sql = "
            SELECT COUNT(id) AS ucount FROM users WHERE users.auditType=2 
            ";
        if($condition != 1 && $condition != 'id = 1'){
            $sql .= " AND c{$condition}";
        }


        $ucount = $uorder_1 = $this->db->fetchRow($sql);

        //有效总流水，总订单数，总下单终端数
        $sql1 = "
            SELECT 
                COUNT(i.id) AS total_order,
                COUNT(distinct(i.user_id)) AS active_ucount,
                SUM(i.goods_amount) AS total_money
            FROM order_info AS i
            LEFT JOIN users u ON i.user_id = u.id
            WHERE i.order_status=3 AND u.auditType =2
            ";
        if($condition != 1 && $condition != 'id = 1'){
            $sql1 .= " AND u.c{$condition}";
        }
        $query = $this->db2->fetchRow($sql1);
        //总下单终端总数
        $sql2 = "
            SELECT 
                COUNT(distinct(i.user_id)) AS ucounts
            FROM  `order_info`  i 
            left join users u on i.user_id=u.id
            WHERE u. auditType=2  
         ";
        if($condition != 1 && $condition != 'id = 1'){
            $sql2 .= " AND u.c{$condition}";
        }
        $query1 = $this->db2->fetchRow($sql2);
        $query['ucounts'] = $query1['ucounts'];
        $query['ucount'] = $ucount['ucount'];
        return $query;
    }
    /**
     *  首页当日
     *   */
    public function indexList_1($cIds)
    {
        $today = strtotime(date('Y-m-d', time()));
        $end = $today + 24 * 60 * 60;
        //总终端数
        $condition = Util::companyIdIsArray($cIds);
        $sql = "
            SELECT COUNT(id) AS ucount FROM users
            ";
        if($condition != 1 && $condition != 'id = 1'){
            $sql .= " WHERE c{$condition}";
        }
        $ucount = $uorder_1 = $this->db2->fetchRow($sql);
        //当日有效总流水，总订单数，总下单终端数
//         $sql1 = "
//            SELECT
//                 COUNT( i.id ) AS total_order,
//                 COUNT( DISTINCT (i.user_id) ) AS active_ucount,
//                 SUM( i.goods_amount ) AS total_money
//             FROM order_info AS i
//             WHERE i.order_status =3 AND DATEDIFF( NOW( ) , FROM_UNIXTIME(`add_time` ) ) =1
//             ";
                // 当日有效总流水，总订单数，总下单终端数
          $sql1 = "
            SELECT
                 COUNT( i.id ) AS total_order,
                 COUNT( DISTINCT (i.user_id) ) AS active_ucount,
                 SUM( i.goods_amount ) AS total_money
            FROM order_info AS i
            LEFT JOIN users u ON i.user_id = u.id
            WHERE i.order_status =3 AND add_time >= {$today} AND add_time <= {$end}
        ";
        if($condition != 1 && $condition != 'id = 1'){
            $sql1 .= " AND u.c{$condition}";
        }
        $query = $uorder_1 = $this->db2->fetchRow($sql1);
        $query['ucount'] = $ucount['ucount'];
        return $query;
    }
    /**
     *  首页7天前
     *   */
    public function indexList_2($cIds)
    {
       $begin = strtotime('-6 days');
       $begin = date('Y-m-d',$begin);
       $begin = strtotime($begin);
       $today = strtotime(date('Y-m-d', time()));
       $end = $today + 24 * 60 * 60;
        //总终端数
        $sql = "
            SELECT COUNT(id) AS ucount FROM users
            ";
        $ucount = $uorder_1 = $this->db2->fetchRow($sql);
        //有效总流水，总订单数，总下单终端数
//         $sql1 = "
//             SELECT
//                 COUNT( i.id ) AS total_order,
//                 COUNT( DISTINCT (i.user_id) ) AS active_ucount,
//                 SUM( i.goods_amount ) AS total_money
//             FROM order_info AS i
//             WHERE i.order_status =3 AND DATEDIFF(NOW(),FROM_UNIXTIME(`add_time`))=7
//             ";
         $sql1 = "
             SELECT
                 COUNT( i.id ) AS total_order,
                 COUNT( DISTINCT (i.user_id) ) AS active_ucount,
                 SUM( i.goods_amount ) AS total_money
             FROM order_info AS i
             WHERE i.order_status =3 AND add_time >= {$begin} AND add_time <= {$end}
             ";

        $query = $this->db2->fetchRow($sql1);

        $sql2 = "
            SELECT
                COUNT( i.id ) AS total_orders
            FROM order_info AS i
            WHERE add_time >= {$begin} AND add_time <= {$end}
        ";

        $query1 = $this->db2->fetchRow($sql2);
        $query['ucount'] = $ucount['ucount'];

        $query['total_orders']=$query1['total_orders'];
        return $query;
    }
    /**
     *  首页自然月
     *   */
    public function indexList_3()
    {
        //$begin=mktime(0,0,0,date('m'),1,date('Y'));
        $begin = strtotime('-29 days');
        $begin = date('Y-m-d',$begin);
        $begin = strtotime($begin);
        $end=mktime(23,59,59,date('m'),date('t'),date('Y'));
        //自然月总流水，总订单数，总下单终端数
        $sql = "
        SELECT
        COUNT( i.id ) AS total_orders
        FROM order_info AS i
        WHERE add_time >= {$begin} AND add_time <= {$end}
        ";
        $query1 = $uorder_1 = $this->db2->fetchRow($sql);
        //自然有效总流水，总订单数，总下单终端数
        $sql1 = "
            SELECT
                COUNT( i.id ) AS total_order,
                COUNT( DISTINCT (i.user_id) ) AS active_ucount,
                SUM( i.goods_amount ) AS total_money
            FROM order_info AS i
            WHERE i.order_status =3 AND add_time >= {$begin} AND add_time <= {$end}
            ";
        $query = $uorder_1 = $this->db2->fetchRow($sql1);
        $query['total_orders']= $query1['total_orders'];
        return $query;
    }
    /**
     * 单品牌销量
     *
     *   */
    public  function getonebrand($where,$page,$pageSize)
    {
        if($page == '' || $page == '0'){
            $start = '0';
        }else{
            $start = ($page-1)*$pageSize;
        }

        //是否显示时间段
        $isShowTime = !empty($where['query_id']) && !empty($where['brand']) ? true : false;

        $condition = ' 1 ';
        $totalCondition = '';
        //判断开始时间
        if(!empty($where['ktime'])){
            $startTime = strtotime($where['ktime']);
        }else{
            $startTime = strtotime('2016-04-20');
        }
        $condition .= "AND start_time >= {$startTime} ";

        //判断结束时间
        if(!empty($where['gtime'])){
            $endTime = strtotime($where['gtime']) + 3600 * 24;
        }else{
            $endTime = strtotime(date('Y-m-d'));
        }
        $condition .= "AND end_time <= {$endTime} ";
        $totalCondition = $condition;

        //判断品类
        if(!empty($where['goods_type'])){
            $condition .= "AND goods_type = {$where['goods_type']} ";
        }

        //分组规则
        $groupBy = ' GROUP BY company_id, brand_id ';

        //判断品牌
        if(!empty($where['brand'])){
            $condition .= "AND brand_id = {$where['brand']} ";
            $groupBy = ' GROUP BY company_id ';
        }

        $groupBy = !empty($where['query_id']) && !empty($where['brand']) ? '' : $groupBy;

        //排序
        $orderBy = ' today_money DESC ';
        if($where['data']){
            $orderBy = 'SORT_DESC' == $where['data'] ? ' start_time DESC ' : ' start_time ASC';
        }

        if($where['today_money']){
            $orderBy = 'SORT_DESC' == $where['today_money'] ? $orderBy : ' today_money ASC ';
        }

        if($where['today_count']){
            $orderBy = 'SORT_DESC' == $where['today_count'] ? ' today_count DESC ' : ' today_count ASC ';
        }

        $orderBy = !empty($where['query_id']) && !empty($where['brand']) ? ' start_time DESC ' : $orderBy;

        $sumMoney = !empty($where['query_id']) && !empty($where['brand']) ? 'sales' : 'SUM(sales)';
        $sumCount = !empty($where['query_id']) && !empty($where['brand']) ? 'sales_count' : 'SUM(sales_count)';

        //判断公司
        if(!empty($where['company_id'])){
            $sql = Util::companyIdIsArray($where['company_id']);
            if($sql != 1){
                if($where['query_id']){
                    $condition .= "AND company_id = {$where['query_id']} ";
                    $totalCondition .= "AND company_id = {$where['query_id']} ";
                    unset($where['query_id']);
                }else{
                    $condition .= "AND company_{$sql} ";
                    $totalCondition .= "AND company_{$sql} ";
                }
            }
        }else{
            if($where['query_id']){
                $condition .= "AND company_id = {$where['query_id']} ";
                $totalCondition .= "AND company_id = {$where['query_id']} ";
                unset($where['query_id']);
            }
        }

        //分页
        $limit = "{$start},{$pageSize} ";

        //查询数据语sql
        $sql = "SELECT {$sumMoney} today_money, {$sumCount} today_count, company_id, companyname, bname, tname, start_time, end_time
            FROM platform_brand
            WHERE {$condition}
            {$groupBy}
            ORDER BY {$orderBy}
            LIMIT {$limit}";
        //echo $sql; die;
        $query = $this->db2->fetchAll($sql);

        //当前品牌销售额和销量
        $sql1 = "SELECT SUM(sales) total_money, SUM(sales_count) bcount
            FROM platform_brand
            WHERE {$totalCondition}";
        $query1 = $this->db2->fetchRow($sql1);
        if(!$isShowTime) {
            $query1['startTime'] = date('Y-m-d', $startTime);
            $query1['endTime'] = date('Y-m-d', $endTime - 3600 * 24);
        }



        //数量
        $sql2 = "SELECT COUNT(*) count 
            FROM ( SELECT {$sumMoney} today_money, {$sumCount} today_count, company_id, companyname, bname, tname, start_time, end_time
            FROM platform_brand
            WHERE {$condition}
            {$groupBy}
            ORDER BY {$orderBy}) aaa";
        $query2 = $this->db2->fetchRow($sql2);

        return array($query,$query1,$query2);
    }

    public function getwarehouse1($id)
    {
        $sql = "SELECT warehousename,id FROM warehouse WHERE company_id = {$id}";

        $query = $this->db2->fetchRow($sql);
        return $query;

    }
    /**
     * 销售统计--平台统计
     *   */
    public function getplatform($where)
    {
        $arr = array();
        $condition = '';
        $startTime = '';
        $endTime = '';
        $timeLimit = " i.add_time <= " . strtotime(date('Y-m-d')) . " ";
        if(!empty($where['ktime'])){
            $startTime = strtotime($where['ktime']);
            $condition = " i.add_time >= $startTime";
            //$time[]='i.add_time >= '.strtotime( $where['ktime']);
        }
        if(!empty($where['gtime'])){
            $endTime = strtotime($where['gtime']) + self::SECOND_DAY;
            $timeLimit = " i.add_time <= $endTime ";
            if($startTime) {
                $condition .= " AND i.add_time <= $endTime ";
            }else{
                $condition .= " i.add_time <= $endTime ";
            }
            //平台累计终端店数
            $terminal_sql = "SELECT count('id') AS total_terminal FROM users WHERE auditType='2' AND createTime <= $endTime";
        }else{
            $terminal_sql = "SELECT count('id') AS total_terminal FROM users WHERE auditType='2'";
        }
        $and = $condition;
        $arr['total_days'] = $startTime && $endTime ? (($endTime-$startTime)/self::SECOND_DAY) : 0;
        //平台昨日有效的订单数和订单金额
        if($endTime < strtotime(date('Y-m-d', time()))) {
            $endTime = strtotime(date('Y-m-d', time()));
            $startTime = $endTime - self::SECOND_DAY;
            $order_day = "  SELECT
                            sum(goods_amount) AS day_money,
                            count(`id`) AS day_effective
                            FROM
                            order_info
                            where `order_status`=3 AND add_time >= {$startTime} AND add_time <= {$endTime}";
            $day = $this->db2->fetchRow($order_day);
        }
        $arr['day_money'] = $day['day_money'] ? $day['day_money'] : 0;
        $arr['day_effective'] = $day['day_effective'] ? $day['day_effective'] : 0;

        //平台当月的订单数和订单金额
        $begin=mktime(0,0,0,date('m'),1,date('Y'));
        $bend=mktime(0,0,0,date('m'),date('d'),date('Y'));
        $moth_order = "
        SELECT
        sum(goods_amount) AS month_money,
        count(`order_info`.`id`) AS month_effective
        FROM
        order_info
        where `order_info`.`order_status`=3 AND add_time >= {$begin} AND add_time <= {$bend}";
        $month = $this->db2->fetchRow($moth_order);
        $arr['month_money'] = $month['month_money'];
        $arr['month_effective'] = $month['month_effective'];

        if($and){
            //平台累积有效总流水，总订单数，总下单终端数
            $zong_sql = "
            SELECT
            COUNT(i.id) AS zong_effective,
            COUNT(distinct(i.user_id)) AS active_ucount,
            SUM(i.goods_amount) AS zong_money
            FROM order_info AS i
            LEFT JOIN users u ON i.user_id = u.id
            WHERE i.order_status=3 AND {$and} AND u.auditType =2
            ";
            //平台所有下过单的终端(包括未成交的)
            $zongorderwu_sql = "SELECT
            count( DISTINCT (`i`.`user_id`)) AS uu
            FROM
            order_info AS i
            left join users on `i`.`user_id`=users.id
            Where  {$timeLimit} AND `users`.`auditType`=2 ";

            $zongorderwu = $this->db2->fetchRow($zongorderwu_sql);
            $arr['zongorderwu'] = $zongorderwu['uu'];
            //平台所有下过单且成交的终端(活跃终端)
            $zongorder_sql = "SELECT
            count( DISTINCT (`i`.`user_id`)) AS u
            FROM
            order_info AS i
            left join users on `i`.`user_id`=users.id
            Where `i`.`order_status`=3 AND {$timeLimit} AND `users`.`auditType`=2";
            $zongorder = $this->db2->fetchRow($zongorder_sql);
            $arr['total_terminal_1'] = $zongorder['u'];
        }else{
        //平台累积有效总流水，总订单数，总下单终端数
        $zong_sql = "
            SELECT
            COUNT(i.id) AS zong_effective,
            COUNT(distinct(i.user_id)) AS active_ucount,
            SUM(i.goods_amount) AS zong_money
                FROM order_info AS i
             LEFT JOIN users u ON i.user_id = u.id
             WHERE i.order_status=3 AND u.auditType =2
             ";
             //平台所有下过单的终端(包括未成交的)
             $zongorderwu_sql = "SELECT
             count( DISTINCT (`i`.`user_id`)) AS uu
             FROM
             order_info AS i
             left join users on `i`.`user_id`=users.id
                Where `users`.`auditType`=2 ";
             $zongorderwu = $this->db2->fetchRow($zongorderwu_sql);
             $arr['zongorderwu'] = $zongorderwu['uu'];
             //平台所有下过单且成交的终端(活跃终端)
             $zongorder_sql = "SELECT
                    count( DISTINCT (`i`.`user_id`)) AS u
                    FROM
                    order_info AS i
                        left join users on `i`.`user_id`=users.id
                    Where `i`.`order_status`=3 AND  `users`.`auditType`=2";
             $zongorder = $this->db2->fetchRow($zongorder_sql);
             $arr['total_terminal_1'] = $zongorder['u'];
         }
        $zong = $this->db2->fetchRow($zong_sql);
        $arr['zong_money'] = $zong['zong_money'];
        $arr['zong_effective'] = $zong['zong_effective'];

        $rerminal = $this->db2->fetchRow($terminal_sql);
        $arr['total_terminal'] = $rerminal['total_terminal'];


        return $arr;

    }
    /**
     * 终端店统计--平台统计
     * @param unknown $where
     * */
    public function getplatform_1($where) {
        $arr = array();
        if($where['gtime'])
        {
            $time[]='i.add_time <= '.(strtotime($where['gtime'])+24*60*60);
            $time[] = "u.createTime <= ".(strtotime($where['gtime'])+24*60*60);
            $times = strtotime($where['gtime'])+24*60*60;
            //平台累计终端店数
            $terminal_sql = "SELECT count('id') AS total_terminal FROM users WHERE auditType='2' AND createTime <= {$times}";
        }else{
            //平台累计终端店数
            $terminal_sql = "SELECT count('id') AS total_terminal FROM users WHERE auditType='2'";
        }
        $rerminal = $this->db2->fetchRow($terminal_sql);
        $arr['total_terminal'] = $rerminal['total_terminal'];

        $and = implode(' AND ',$time);
        if($and)
        {
            //平台累积有效总流水，总订单数，总下单终端数
            $zong_sql = "
            SELECT
            COUNT(i.id) AS zong_effective,
            COUNT(distinct(i.user_id)) AS active_ucount,
            SUM(i.goods_amount) AS zong_money
            FROM order_info AS i
            LEFT JOIN users u ON i.user_id = u.id
            WHERE i.order_status=3 AND $and AND u.auditType =2
            ";
            $zong = $this->db2->fetchRow($zong_sql);
            $arr['total_terminal_1'] = $zong['active_ucount'];
            $arr['zong_money'] = $zong['zong_money'];
            $arr['zong_effective'] = $zong['zong_effective'];
        //平台所有下过单的终端(包括未成交的)
            $zongorderwu_sql = "SELECT
            count( DISTINCT (`i`.`user_id`)) AS uu
            FROM
            order_info AS i
            left join users  AS u on `i`.`user_id`=u.id
            WHERE $and AND `u`.`auditType`=2 ";
            $zongorderwu = $this->db2->fetchRow($zongorderwu_sql);
            $arr['zongorderwu'] = $zongorderwu['uu'];
        }else{
            //平台累积有效总流水，总订单数，总下单终端数
            $zong_sql = "
            SELECT
            COUNT(i.id) AS zong_effective,
            COUNT(distinct(i.user_id)) AS active_ucount,
            SUM(i.goods_amount) AS zong_money
            FROM order_info AS i
            LEFT JOIN users u ON i.user_id = u.id
            WHERE i.order_status=3 AND u.auditType =2
            ";
            $zong = $this->db2->fetchRow($zong_sql);
            $arr['total_terminal_1'] = $zong['active_ucount'];
            $arr['zong_money'] = $zong['zong_money'];
            $arr['zong_effective'] = $zong['zong_effective'];
        //平台所有下过单的终端(包括未成交的)
            $zongorderwu_sql = "SELECT
            count( DISTINCT (`i`.`user_id`)) AS uu
            FROM
            order_info AS i
            left join users  AS u on `i`.`user_id`=u.id
            WHERE `u`.`auditType`=2 ";
            $zongorderwu = $this->db2->fetchRow($zongorderwu_sql);
            $arr['zongorderwu'] = $zongorderwu['uu'];
        }

        return $arr;
    }
    /**
     * 品牌统计 -- 平台统计
     * @param unknown $param
     *  */
    public function getplatform_2($data) {
         $arr = array();
//          1、不筛时，汇总行，全部汇总。  
//          2、筛公司时，汇总为该公司汇总。
//          3、筛品类时，汇总为该品类汇总。
//          4、筛品牌时，汇总为全部汇总。
//          5.筛公司且筛品类，汇总为该公司该品类汇总
//          6.筛公司且筛品牌，汇总为该公司全部汇总
            if($data['brand']) {
                //$where[] = " g.brand_id={$data['brand']} ";
//          4、筛品牌时，汇总为全部汇总。
                $sql = "SELECT
                SUM( total_money ) AS btotal_money,
                SUM( bcount ) AS brand_count
                FROM (
                SELECT SUM( o.goods_number * o.goods_money ) total_money,
                SUM(o.goods_number) AS bcount,
                b.id AS bid, b.name AS bname
                FROM  `order_goods` o
                LEFT JOIN goods g ON o.goods_id = g.id
                LEFT JOIN brand b ON g.brand_id = b.id
                LEFT JOIN order_info i ON o.order_id = i.id
                WHERE i.order_status =3 AND b.is_show =1 AND g.brand_id={$data['brand']}
                GROUP BY b.id
                )aaa";
            }
            if($data['goods_type'])
            {
                //$where[] = " g.goods_type={$data['goods_type']} ";
//          3、筛品类时，汇总为该品类汇总。
                $sql = "SELECT
                SUM( total_money ) AS btotal_money,
                SUM( bcount ) AS brand_count
                FROM (
                SELECT SUM( o.goods_number * o.goods_money ) total_money,
                SUM(o.goods_number) AS bcount,
                b.id AS bid, b.name AS bname
                FROM  `order_goods` o
                LEFT JOIN goods g ON o.goods_id = g.id
                LEFT JOIN brand b ON g.brand_id = b.id
                LEFT JOIN order_info i ON o.order_id = i.id
                WHERE i.order_status =3 AND b.is_show =1 AND g.goods_type={$data['goods_type']}
                GROUP BY b.id
                )aaa";
            }
            if($data['company_id']) {
                //$where[] = " g.cid={$data['company_id']} ";
//          2、筛公司时，汇总为该公司汇总。
                $sql = "SELECT
                SUM( total_money ) AS btotal_money,
                SUM( bcount ) AS brand_count
                FROM (
                SELECT SUM( o.goods_number * o.goods_money ) total_money,
                SUM(o.goods_number) AS bcount,
                b.id AS bid, b.name AS bname
                FROM  `order_goods` o
                LEFT JOIN goods g ON o.goods_id = g.id
                LEFT JOIN brand b ON g.brand_id = b.id
                LEFT JOIN order_info i ON o.order_id = i.id
                WHERE i.order_status =3 AND b.is_show =1 AND g.cid={$data['company_id']}
                GROUP BY b.id
                )aaa";
            }
//          5.筛公司且筛品类，汇总为该公司该品类汇总
            if($data['company_id'] && $data['goods_type'])
            {
                $sql = "SELECT
                SUM( total_money ) AS btotal_money,
                SUM( bcount ) AS brand_count
                FROM (
                SELECT SUM( o.goods_number * o.goods_money ) total_money,
                SUM(o.goods_number) AS bcount,
                b.id AS bid, b.name AS bname
                FROM  `order_goods` o
                LEFT JOIN goods g ON o.goods_id = g.id
                LEFT JOIN brand b ON g.brand_id = b.id
                LEFT JOIN order_info i ON o.order_id = i.id
                WHERE i.order_status =3 AND b.is_show =1 AND g.cid={$data['company_id']} AND g.goods_type={$data['goods_type']}
                GROUP BY b.id
                )aaa";
            }
//             if($data['ktime']){
//                 $ktime = strtotime($data['ktime']);
//                 $where[] = "i.add_time >= $ktime";
//             }
//             if($data['gtime']){
//                 $gtime = strtotime($data['gtime']);
//                 $where[] = "i.add_time <= ".($gtime+24*60*60);
//             }
//             if ($data) {
//                 $where = ' AND '. implode(' AND ', $where);
//             }
            if($sql)
            {
                $brand = $this->db2->fetchRow($sql);
            }
          //所有品牌销售额，销量
              $sql_1 = "SELECT
              SUM( total_money ) AS btotal_money,
              SUM( bcount ) AS brand_count
              FROM (
              SELECT SUM( o.goods_number * o.goods_money ) total_money,
              SUM(o.goods_number) AS bcount,
              b.id AS bid, b.name AS bname
              FROM  `order_goods` o
              LEFT JOIN goods g ON o.goods_id = g.id
              LEFT JOIN brand b ON g.brand_id = b.id
              LEFT JOIN order_info i ON o.order_id = i.id
              WHERE i.order_status =3 AND b.is_show =1
              GROUP BY b.id
              )aaa";
              $brand_z = $this->db2->fetchRow($sql_1);
             if($brand){
                 $arr['brand_total'] = $brand['btotal_money'];
                 $arr['brand_count'] = $brand['brand_count'];
             }else{
                 $arr['brand_total'] = '';
                 $arr['brand_count'] = '';
             }
             $arr['z_brand_total'] = $brand_z['btotal_money'];
             $arr['z_brand_count'] = $brand_z['brand_count'];
         return $arr;
    }

    /**
     * 单品牌销量
     *
     *   */
    public  function getonebrand1($where,$page,$pageSize)
    {
        if($page == '' || $page == '0'){
            $start = '0';
        }else{
            $start = ($page-1)*$pageSize;
        }

        //是否显示时间段
        $isShowTime = !empty($where['query_id']) && !empty($where['brand']) ? true : false;

        $condition = ' 1 ';
        $totalCondition = '';
        //判断开始时间
        if(!empty($where['ktime'])){
            $startTime = strtotime($where['ktime']);
        }else{
            $startTime = strtotime('2016-04-20');
        }
        $condition .= "AND start_time >= {$startTime} ";

        //判断结束时间
        if(!empty($where['gtime'])){
            $endTime = strtotime($where['gtime']) + 3600 * 24;
        }else{
            $endTime = strtotime(date('Y-m-d'));
        }
        $condition .= "AND end_time <= {$endTime} ";
        $totalCondition = $condition;

        //判断品类
        if(!empty($where['goods_type'])){
            $condition .= "AND goods_type = {$where['goods_type']} ";
        }

        //分组规则
        $groupBy = ' GROUP BY company_id, brand_id ';

        //判断品牌
        if(!empty($where['brand'])){
            $condition .= "AND brand_id = {$where['brand']} ";
            $groupBy = ' GROUP BY company_id ';
        }

        $groupBy = !empty($where['query_id']) && !empty($where['brand']) ? '' : $groupBy;

        //排序
        $orderBy = ' today_money DESC ';
        if($where['data']){
            $orderBy = 'SORT_DESC' == $where['data'] ? ' start_time DESC ' : ' start_time ASC';
        }

        if($where['today_money']){
            $orderBy = 'SORT_DESC' == $where['today_money'] ? $orderBy : ' today_money ASC ';
        }

        if($where['today_count']){
            $orderBy = 'SORT_DESC' == $where['today_count'] ? ' today_count DESC ' : ' today_count ASC ';
        }

        $orderBy = !empty($where['query_id']) && !empty($where['brand']) ? ' start_time DESC ' : $orderBy;

        $sumMoney = !empty($where['query_id']) && !empty($where['brand']) ? 'sales' : 'SUM(sales)';
        $sumCount = !empty($where['query_id']) && !empty($where['brand']) ? 'sales_count' : 'SUM(sales_count)';

        //判断公司
        if(!empty($where['company_id'])){
            $sql = Util::companyIdIsArray($where['company_id']);
            if($sql != 1){
                if($where['query_id']){
                    $condition .= "AND company_id = {$where['query_id']} ";
                    $totalCondition .= "AND company_id = {$where['query_id']} ";
                    unset($where['query_id']);
                }else{
                    $condition .= "AND company_{$sql} ";
                    $totalCondition .= "AND company_{$sql} ";
                }
            }
        }else{
            if($where['query_id']){
                $condition .= "AND company_id = {$where['query_id']} ";
                $totalCondition .= "AND company_id = {$where['query_id']} ";
                unset($where['query_id']);
            }
        }

        //分页
        $limit = "{$start},{$pageSize} ";

        //查询数据语sql
        $sql = "SELECT {$sumMoney} today_money, {$sumCount} today_count, company_id, companyname, bname, tname, start_time, end_time
            FROM platform_brand
            WHERE {$condition}
            {$groupBy}
            ORDER BY {$orderBy}
            LIMIT {$limit}";
        $query = $this->db2->fetchAll($sql);

        //当前品牌销售额和销量
        $sql1 = "SELECT SUM(sales) total_money, SUM(sales_count) bcount
            FROM platform_brand
            WHERE {$totalCondition}";
        $query1 = $this->db->fetchRow($sql1);
        if(!$isShowTime) {
            $query1['startTime'] = date('Y-m-d', $startTime);
            $query1['endTime'] = date('Y-m-d', $endTime - 3600 * 24);
        }



        //数量
        $sql2 = "SELECT COUNT(*) count 
            FROM ( SELECT {$sumMoney} today_money, {$sumCount} today_count, company_id, companyname, bname, tname, start_time, end_time
            FROM platform_brand
            WHERE {$condition}
            {$groupBy}
            ORDER BY {$orderBy}) aaa";
        $query2 = $this->db2->fetchRow($sql2);

        return array($query,$query1,$query2);
    }


}
