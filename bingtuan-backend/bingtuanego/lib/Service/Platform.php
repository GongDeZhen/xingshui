<?php
class Service_Platform extends Service
{
    /* 获取所有公司 */
    public function getcompany()
    {
        $sql = "
            SELECT id,companyname AS `name` from company
        ";
        $query = $this->db->fetchAll($sql);
        return $query;
    }
    public function giblets($table_name)
    {
        $sql = "truncate table {$table_name}";
        $ob = $this->db->query($sql);
        return $ob;
    }
    /* 所有公司所有品牌按天查询 */
    public function getbrand($k,$j,$cid) {
//         $sql = "SELECT
//         SUM( o.goods_number * o.goods_money ) total_money,
//         SUM(o.goods_number) AS bcount,
//         b.id AS brand_id,
//         t.id AS goods_type,
//         b.name AS bname,
//         t.name AS tname
//         FROM  `order_goods` o
//         LEFT JOIN goods g ON o.goods_id = g.id
//         LEFT JOIN brand b ON g.brand_id = b.id
//         LEFT JOIN goods_type t ON g.goods_type = t.id
//         LEFT JOIN order_info i ON o.order_id = i.id
//         WHERE i.order_status =3 AND b.is_show=1 AND i.add_time >={$k} AND i.add_time<={$j} AND g.cid = {$cid}
//         GROUP BY b.id 
//         "
//         ;
        $sql = "SELECT 
        SUM( o.goods_number * o.goods_money ) total_money,
        SUM( o.goods_number ) AS bcount,
        b.name AS bname, 
        t.name AS tname,
        t.id AS goods_type,
        b.id AS brand_id
        FROM  `order_info` i
        LEFT JOIN order_goods o ON o.order_id = i.id
        LEFT JOIN goods g ON o.goods_id = g.id
        LEFT JOIN brand b ON g.brand_id = b.id
        LEFT JOIN goods_type t ON g.goods_type = t.id
        WHERE i.order_status =3
        AND b.is_show =1
        AND i.add_time >={$k}
        AND i.add_time <={$j}
        AND g.cid ={$cid}
        GROUP BY b.id";
        $query = $this->db->fetchAll($sql);
        return $query;
    }
    /* 品牌批量数据按天插入*/
    public function insertbrand($info) {
        $re = $this->db->insert('platform_brand', $info);
        return $re;
    }
    public function aaa(){
        $sql = "
            SELECT sales,sales_count,goods_type,brand_id,company_id FROM platform_brand GROUP BY brand_id
            ";
        $sql = "
           SELECT * 
            FROM (
            SELECT SUM(sales) AS sales, SUM(sales_count) AS sales_count, goods_type, brand_id, company_id, bname, tname, start_time, end_time
            FROM platform_brand
/*             WHERE company_id =65
            AND start_time >=1461859200
            AND end_time <=1461945600
            AND brand_id = 3 */
            GROUP BY brand_id
            )aaa
            ORDER BY sales DESC , sales_count DESC"
        ;
    }
    /* 销售统计按天查询 */
    public function getsale($startTime,$endTime)
    {
        //所有经销商名称,上线时间,终端数量
        $sql = "SELECT count(u.id) unum, w.warehousename wname, w.company_id, w.uptime, w.id cid
                FROM users u
                LEFT JOIN warehouse w ON u.cid = w.id
                WHERE w.status = 1 AND u.auditType = 2
                GROUP BY w.id";
        $dealers = $this->db->fetchAll($sql);
        $arr = [];

        //获取每个经销商从上线到今天的相关数据
        foreach ($dealers as $k=>$v)
        {
            //获取当前经销商当天的销售额及订单数量
            $sql = "SELECT sum(goods_amount) AS day_sale, count(id) day_salecount FROM order_info 
                    WHERE order_status = 3 AND warehouse_id = {$v['cid']} AND add_time BETWEEN {$startTime} AND {$endTime}";
            $orderInfos = $this->db->fetchRow($sql);
            $arr[$k] = array_merge($v, $orderInfos);

            //所有下过单的终端(包括未成交的)
            $sql_2 = "SELECT count(DISTINCT(o.user_id)) uu 
                      FROM order_info o 
                      JOIN users u ON o.user_id = u.id
                      WHERE o.warehouse_id = {$v['cid']} 
                      AND u.auditType = 2 
                      AND o.add_time <= {$endTime}";

            //下过单且成交的终端(活跃终端)
            $sql_1 = "{$sql_2} AND o.order_status = 3";
            $ucount = $this->db->fetchRow($sql_1);
            //该经销商总终端数
            $ucount1 = $this->db->fetchRow($sql_2);
            $arr[$k]['ucount'] = $ucount['uu'] ? $ucount['uu'] : 0;
            $arr[$k]['ucount1'] = $ucount1['uu'] ? $ucount1['uu'] : 0;
        }
        return $arr;
    }
    /* 销售统计sql */
    public function aaas()
    {
        $sql="SELECT *
                FROM (
                SELECT 
                    SUM( day_sale ) AS total, 
                    SUM( day_salecount ) AS count, 
                    cid,
                    operate_days,
                    ucount,
                    ucount_s,
                    wname
                FROM  `platfrom_sale_day` 
                GROUP BY cid
                )aaa
              ORDER BY total DESC
              
            ";
        $list = $this->db->fetchAll($sql);
        $sql="
            SELECT
                 MAX( start_time ) ,
                 MAX( end_time ) , 
                 wname,
                 day_sale,
                 day_salecount,
                 cid
            FROM  `platfrom_sale_day` 
            GROUP BY cid
            ";
    }
    /* 单品统计sql */
    public function test1()
    {
        //所有商品销售额，销量
        $sql = "
            SELECT 
                SUM(day_total) AS goods_total,
                SUM(day_count) AS goods_count,
                goods_id,
                goods_name,
                type_name,
                bname
            FROM platfrom_single_day 
            GROUP BY goods_id
            ORDER BY goods_total
            ";
        //总销售额，总销量
        $sql_1 = "
            SELECT SUM(goods_total) AS zong_total,SUM(goods_count) AS zong_count(
            SELECT 
                SUM(day_total) AS goods_total,
                SUM(day_count) AS goods_count,
                goods_id,
                goods_name,
                type_name,
                bname
            FROM platfrom_single_day 
            GROUP BY goods_id
            ) aaa
            ";
    }
    /* 品牌统计sql */
    public function test2()
    {
        $sql = "
            SELECT
                SUM(sales) AS brand_total,
                SUM(sales_count),
                tname,
                bname
            FROM platform_brand
            GROUP BY brand_id
            ORDER BY brand_total DESC
            ";
        $sql_1 = "
            SELECT SUM(brand_total), SUM(brand_count) FROM(
            SELECT
                SUM(sales) AS brand_total,
                SUM(sales_count) AS brand_count
            FROM platform_brand
            ) aaa
            ";
    }
    /**
     * 按天插入数据  
     * @param unknown $table 表名
     * @param array() $info  数据
     * @return number  */
    public function insertinfo($table,$info) {
        $re = $this->db->insert($table, $info);
        return $re;
    }

    public function insertDayInfo($table,$info){
        if($info){
            $sql = '';
            $all_sql = 'INSERT  INTO '.$table.' VALUES ';
            foreach($info as $k=>$v){
                $cols = array();
                $vals = array();
                foreach ($v as $col => $val) {
                    $cols[] = $col;
                    $vals[] = '\''.$val.'\'';
                }


                $sql[] = ' (' . implode(', ', $vals) . ')';
                //echo "1";
                //file_put_contents('/home/htdocs/bingtuanego/adm/public/sql.txt',$sql,FILE_APPEND);

                /*
                if($k>10){

                    exit;

                    //$all_sql = '';
                }
                */
            }
            $sql_str = implode(',',$sql);
            $all_sql .= $sql_str.';';
            //file_put_contents('/home/htdocs/bingtuanego/adm/public/sql.txt',$all_sql,FILE_APPEND);
            $rs = $this->db->query($all_sql);

            //var_dump($rs);
        }

        //$re = file_put_contents('/home/htdocs/bingtuanego/adm/public/sql.txt',$all_sql);
        //var_dump($re);
        //$re = $this->db->insert($table, $info);
       // $re = 1;
        return $re;
    }
    /* 所有商品 */
    public function goodslist()
    {
        $sql = "
            SELECT
                g.id AS goods_id,
                g.goods_name,
                g.cid,
                g.brand_id,
                g.goods_type,
                b.name AS bname,
                c.name AS type_name
            FROM
                goods AS g
                LEFT JOIN brand b ON b.id = g.brand_id
                LEFT JOIN goods_type c ON c.id = g.goods_type
            WHERE g.is_delete = '0' AND b.is_show = '1'"
            ;
            $goods = $this->db->fetchAll($sql);
            return $goods;
    }
    /* 单品统计按天查询 */
    public function getsingle($ksi,$jie,$goods_id)
    {
//         $sql = "
//             SELECT 
//                 g.id AS goods_id,
//                 g.goods_name,
//                 g.cid,
//                 g.brand_id,
//                 g.goods_type,
//                 b.name AS bname,
//                 c.name AS type_name
//             FROM
//                 goods AS g
//                 LEFT JOIN brand b ON b.id = g.brand_id
//                 LEFT JOIN goods_type c ON c.id = g.goods_type
//             WHERE g.is_delete = '0' AND g.is_show = '1'  AND b.is_show = '1'"
//         ;
//         $goods = $this->db->fetchAll($sql);
//         foreach ($goods as $k=>$v) {
            $sql = "SELECT
                        SUM(o.`goods_money`*o.`goods_number`) AS goods_total,
                        SUM(o.`goods_number`) AS goods_count
                    FROM
                        order_goods AS o
                        LEFT JOIN order_info AS i ON o.order_id = i.id
                    WHERE o.`goods_id` = {$goods_id} 
                          AND i.order_status = 3 
                          AND i.add_time >= {$ksi}
                          AND i.add_time <= {$jie}
                    ";
            $day_info = $this->db->fetchRow($sql);
            return $day_info;
//             $goods[$k]['day_total'] = $day_info['goods_total'];
//             $goods[$k]['day_count'] = $day_info['goods_count'];
//         }
//        return $goods;
    }

    public function getsingleAll($ksi,$jie)
    {
        //$good_ids = '('.implode(',',$ids).')';
        $sql = "SELECT
                        SUM(o.`goods_money`*o.`goods_number`) AS goods_total,
                        SUM(o.`goods_number`) AS goods_count,o.goods_id
                    FROM
                        order_goods AS o
                        LEFT JOIN order_info AS i ON o.order_id = i.id
                    WHERE i.order_status = 3
                          AND i.add_time >= {$ksi}
                          AND i.add_time <= {$jie}
                          group by o.goods_id
                    ";
        $day_info = $this->db->fetchAll($sql);
        return $day_info;
    }

    /**
     * [getstoreList 单日查询经销的数据]
     * @param $cid
     * @param $gtime
     * @param $ktime
     * @return
     */
    public  function getstoreList($gtime,$ktime)
    {
        $where1='';
        if($ktime){
            $where[] = "i.add_time >=  ".strtotime($ktime);
            $map[] = "u.createTime >=  ".strtotime($ktime);
        }else{
            $where[] = "i.add_time >=  ".strtotime('2016-4-18');
        }
        if($gtime){
            $where[] = "i.add_time <= ".(strtotime($gtime));
            $map[] = "u.createTime <= ".(strtotime($gtime));
            $map1[] = "u.createTime <= ".(strtotime($gtime));
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
        //经销商和用户总数
        $sql1 = "
               SELECT 
                    COUNT(u.id) AS ucount, 
                    w.warehousename AS wname, 
                    w.id AS wid, 
                    c.companyname AS cname,
                    c.id as cid
                    FROM users AS u
                    LEFT JOIN warehouse AS w ON u.cid=w.id 
                    LEFT JOIN company AS c ON c.id=w.company_id 
                    WHERE  u.auditType = 2 {$where1} {$map1} and u.cid !=0
                GROUP BY wid
             ";
        $query=$this->db->fetchAll($sql1);
        //return $query;
        foreach ($query as $k=>$v) {
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
            $uorder_1 = $this->db->fetchRow($sql3);
            $query[$k]['uorder_1'] = $uorder_1['c_1'];
            $query[$k]['order_money'] = $uorder_1['to_money'];
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
            $ordernum = $this->db->fetchRow($sql4);
            $query[$k]['ordernum'] = $ordernum['ordernum'];
        }
        return $query;
    }

    /**
     * 获取经销商订单列表
     * @param $start
     * @param $end
     * @return array
     */
    public function getDealerBillList($start, $end){
        //获取所有经销商
        $wSql = "SELECT warehousename wname, id wid, company_id cid
                 FROM warehouse 
                 WHERE status = 1";
        $wList = $this->db->fetchAll($wSql);
        //遍历经销商获取账单信息
        $data = [];
        foreach($wList as $key => $value){
            $wid = $value['wid'];
            //获取总订单金额
            $tatolSql = "SELECT SUM(goods_amount) money
                         FROM order_info
                         WHERE warehouse_id = {$wid}
                         AND pay_time >= $start 
                         AND pay_time <= $end
                         AND goods_amount != 0
                         AND order_status IN('3','4','7','8')";
            $tatolMoney = $this->db->fetchOne($tatolSql);
            $tatolMoney = $tatolMoney ? $tatolMoney : 0;

            //获取退款金额
            $refundSql = "SELECT SUM(goods_amount) money
                         FROM order_info
                         WHERE warehouse_id = {$wid}
                         AND refund_time >= $start 
                         AND refund_time <= $end
                         AND order_status = 8";
            $refundMoney =  $this->db->fetchOne($refundSql);
            $refundMoney = $refundMoney ? $refundMoney : 0;

            if(!$tatolMoney && !$refundMoney) continue;

            //获取微信支付
            $wechatMoney = $this->getPayTypeMoney($wid, $start, $end, 1);

            //获取支付宝支付
            $aliPayMoney = $this->getPayTypeMoney($wid, $start, $end, 2);
            //获取公众号支付
            $wechatPublicMoney = $this->getPayTypeMoney($wid, $start, $end, 4);
            //获取易极付支付
            $yijiPayMoney = $this->getPayTypeMoney($wid, $start, $end, 5);

            //获取有效订单id
            $validitySql = "SELECT id
                         FROM order_info
                         WHERE warehouse_id = {$wid}
                         AND pay_time >= $start 
                         AND pay_time <= $end
                         AND goods_amount != 0
                         AND order_status IN('3','4','7')";
            $validityIds = $this->db->fetchAll($validitySql);
            $validityIds = implode(',', array_column($validityIds, 'id'));
            $validityRefundSql = "SELECT id
                         FROM order_info
                         WHERE warehouse_id = {$wid}
                         AND pay_time >= $start 
                         AND pay_time <= $end
                         AND refund_time > $end
                         AND goods_amount != 0
                         AND order_status = 8";
            $ids = $this->db->fetchAll($validityRefundSql);
            if($ids){
                $validityRefundIds = implode(',', array_column($ids, 'id'));
                $validityIds = $validityIds . ',' . $validityRefundIds;
            }



            //退款订单id
            $refundIdSql = "SELECT id
                         FROM order_info
                         WHERE warehouse_id = {$wid}
                         AND refund_time >= $start 
                         AND refund_time <= $end
                         AND goods_amount != 0
                         AND order_status = 8";
            $refundIds = '';
            if($refundMoney){
                $refundIds = implode(',', array_column($this->db->fetchAll($refundIdSql), id));
            }

            //构建数据
            $temp = [
                'wid' => $wid,
                'name' => $value['wname'],
                'cid' => $value['cid'],
                'tatolMoney' => $tatolMoney,
                'refundMoney' => $refundMoney,
                'wechatMoney' => $wechatMoney,
                'aliPayMoney' => $aliPayMoney,
                'wechatPublicMoney' => $wechatPublicMoney,
                'yijiPayMoney' => $yijiPayMoney,
                'validityOrderIds' => $validityIds,
                'refundOrderIds' => $refundIds,
                'addTime' => $start
            ];
            $data[] = $temp;
        }
        return $data;
    }

    /**
     * 根据支付方式获取账单金额
     * @param $wid
     * @param $start
     * @param $end
     * @param $type
     * @return int
     */
    private function getPayTypeMoney($wid, $start, $end, $type){
        $sql = "SELECT SUM(goods_amount) money
                         FROM order_info
                         WHERE warehouse_id = {$wid}
                         AND pay_time >= $start 
                         AND pay_time <= $end
                         AND goods_amount != 0
                         AND order_status IN('3','4','7')
                         AND pay_id = {$type}";
        $money = $this->db->fetchOne($sql);
        return $money = $money ? $money : 0;
    }
    
}