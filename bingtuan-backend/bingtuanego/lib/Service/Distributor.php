<?php
class Service_Distributor extends Service
{   
    /*
     * 通过品牌名查询品牌的id
     * @param $brand_name
     * @return array
     */
    public function getBrandId($name){
        $sql = "SELECT id from brand WHERE name='$name'";
        $id = $this->db2->fetchRow($sql);
        return $id;
    }

    public function getGood($name){
        $sql = "SELECT id,TRIM(goods_name) AS  goods_name FROM `goods` WHERE `goods_name`='$name' AND cid=1 AND pid=0";
        $arr = $this->db2->fetchAll($sql);
        return $arr;
    }


    public function getGoodstandard($name,$standard){
        $sql = "SELECT id,TRIM(goods_name) AS  goods_name FROM `goods` WHERE `goods_name`='$name' AND cid=1 AND pid=0 and standard='{$standard}' order by id Desc";
        $arr = $this->db2->fetchAll($sql);
        return $arr;
    }


    /**
     * @param $oldname
     * @param $oldstandard
     * @param $name
     * @param $standard
     * @return mixed
     * 修改改名或者改规格的商品
     */
    public function getGoodsname($oldname,$oldstandard,$name,$standard){
        echo $sql = "UPDATE  `goods` SET  `goods_name` =  '{$name}',`standard` =  '{$standard}'  WHERE   `goods_name` =  '{$oldname}' and `standard` =  '{$oldstandard}';";
        $arr = $this->db->query($sql);
        //$arr = 1;
        return $arr;
    }


    /*
     * 通过商品名查询商品信息
     * @param $goods_name
     * @return array
     */
    public function getGoodInfostandard($name,$standard){
        $sql = "SELECT *,TRIM(goods_name) AS  goods_name FROM `goods` HAVING replace(goods_name,' ','')='$name' and standard='{$standard}' ORDER BY id DESC";
        $arr = $this->db2->fetchAll($sql);
        return $arr;
    }


    public function getGoodInfo($name){
        $sql = "SELECT *,TRIM(goods_name) AS  goods_name FROM `goods` HAVING replace(goods_name,' ','')='$name' ORDER BY id DESC";
        $arr = $this->db2->fetchAll($sql);
        return $arr;
    }

    /**
     * 修改商品名称和其他
     * @param unknown $id
     * @param unknown $amount
     */
    public function upGoodsInfo($id,$cid,$goods_name,$brand_id,$goods_type)
    {
        $return = $this->db->update('goods', array('goods_name' => $goods_name,'brand_id' => $brand_id,'goods_type' => $goods_type), 'cid='.$cid.' and id='.$id);
        if ($return == 0 || $return == 1) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * @param $goods_id
     * @return bool
     * 修改要删除商品ID的订单
     */
    public function uporderid($old_goods_id,$goods_id)
    {
        $return = $this->db->update('order_goods', array('goods_id' => $goods_id), 'goods_id='.$old_goods_id);
        if ($return == 0 || $return == 1) {
            return true;
        } else {
            return false;
        }
    }

    public function deletegoods($goods_id){
        echo $sql = "DELETE FROM `goods` WHERE `goods`.`id` = {$goods_id}";
        $arr = $this->db->query($sql);
        return $arr;
    }
    /**
     * 修改商品品牌和类型
     * @param unknown $id
     *
     * @param unknown $amount
     */
    public function upGoodsInfos($id,$brand_id,$goods_type)
    {
        $return = $this->db->update('goods', array('brand_id' => $brand_id,'goods_type' => $goods_type), 'id='.$id);
        if ($return == 0 || $return == 1) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * 添加商品
     * @param unknown $data
     * @return unknown
     */
    public function addGoods($goods)
    {
        $return = $this->db->insert('goods', $goods);
        $id = $this->db->lastInsertId();
        return $id;
    }



    /*
     * 替换订单号
     * @param $cid
     * @param $order
     *
     */
    public function add_order($cid,$order)
    {
        $sql="SELECT i.id,i.order_number, u.cid FROM order_info AS i LEFT JOIN  users AS u ON i.user_id = u.id WHERE u.cid = $cid AND u.auditType = 2 AND LENGTH(order_number)=18";
        $query = $this->db->fetchAll($sql);
        foreach ($query as  $k=>$v){

            if(strlen($v['order_number']) == 18)
            {
                $str = "{$order}".$v['order_number'];
                $sql = "UPDATE order_info SET order_number = {$str} WHERE id ={$v['id']}";
                $this->db->query($sql);
                echo '已修改';
            }else{
                echo '未修改';
            }
        }

    }

    /*
     * 获取所有订单的商品的信息
     */
    public function getGoodsStatistics($cid,$page,$pagesize,$brand,$goods_type,$search_name,$time,$order){
        $where = '';
        if($brand){
            $where.= " AND b.id =".$brand;
        }
        if ($goods_type){
            $where.=" AND gt.id =".$goods_type;
        }
        if($search_name){
            $where.= " AND  g.goods_name like '%{$search_name}%'";
        }
        if($time){
            $where.= " AND ".$time;
        }
        if($order){
            $order = "ORDER BY goods_number ".$order;
        }
        //var_dump($where);exit;
        /*$sql = "SELECT
                gs.goods_id,SUM(gs.goods_number) as goods_number,g.goods_name,b.name as brand,gt.name
                FROM order_goods as gs
                left join goods as g
                on gs.goods_id=g.id
                LEFT JOIN order_info as oi 
                ON gs.order_id=oi.id
                left join brand as b
                on g.brand_id=b.id
                left join goods_type as gt
                on g.goods_type=gt.id WHERE g.cid=$cid $where AND oi.order_status=3
                group by g.goods_name $order limit $page,$pagesize";*/
        $condition = Util::companyIdIsArray($cid);
        if($condition != 1){
            $sql1 = "g.c$condition";
        }else{
            $sql1 = "g.cid = 0";
        }
        $sql ="SELECT gs.goods_id,SUM(gs.goods_number) AS goods_number,g.goods_name,b. NAME AS brand,gt.name
               FROM order_goods AS gs
               INNER JOIN order_info AS oi ON gs.order_id = oi.id
               INNER JOIN goods AS g ON gs.goods_id = g.id
               LEFT JOIN brand AS b ON g.brand_id = b.id
               LEFT JOIN goods_type AS gt ON g.goods_type = gt.id
               WHERE oi.order_status = 3  AND $sql1 $where 
               GROUP BY gs.activity_id,g.goods_name $order limit $page,$pagesize";
        $data = $this->db2->fetchAll($sql);
        $sql ="SELECT gs.goods_id
               FROM order_goods AS gs
               INNER JOIN order_info AS oi ON gs.order_id = oi.id
               INNER JOIN goods AS g ON gs.goods_id = g.id
               LEFT JOIN brand AS b ON g.brand_id = b.id
               LEFT JOIN goods_type AS gt ON g.goods_type = gt.id
               WHERE oi.order_status = 3  AND $sql1 $where 
               GROUP BY gs.activity_id,g.goods_name $order ";
        $num = count($this->db2->fetchAll($sql));
        $result = array();
        $result['data'] = $data;
        $result['num'] = $num;
        return $result;
    }

    /*
     * 获取活动的id
     * @param $goods_id
     * @return array
     */
    public function getActivityId($goods_id){
        $sql = "SELECT a.type FROM activity_goods as ag LEFT JOIN activity as a  on ag.activity_id = a.id WHERE goods_id={$goods_id}";
        //echo $sql;exit;
        $data = $this->db2->fetchRow($sql);
        return $data;
    }
    /*
     * 获取赠品的id
     * @param $goods_id
     * @return array
     */
    public function getActivityGifts($goods_id){
        $sql = "SELECT id FROM activity_gifts WHERE goods_id={$goods_id}";
        $data = $this->db2->fetchRow($sql);
        return $data;
    }

    public function getWarehouseByCid($company_id){
        $where = Util::companyIdIsArray($company_id);
        if($where == "id = 1" || $where == 1){
            $data = "";
        }else{
            $sql = "SELECT id FROM warehouse WHERE company_{$where}";
            $data = $this->db2->fetchAll($sql);
        }

        return $data;
    }

    public function getWarehouseTimeByCid($company_id){
        $condition = Util::companyIdIsArray($company_id);
        if($condition != 1 && $condition != 'id = 1'){
            $where = " company_$condition ";
        }else{
            $where = " company_id = 0 ";
        }
        $sql = "SELECT uptime as online_time FROM warehouse WHERE $where";
        $data = $this->db2->fetchRow($sql);
        return $data;
    }

    /*
     * 获取经销商用户的信息
     */
    public function getTerminalstatistics($cid,$page,$pagesize,$range,$judge,$time,$order){
        if(!$cid){
            $cid = 1;
        }
        $sql = "SELECT u.auditType,u.id,u.account,oi.id as oid,count(u.id) as num ,u.userName, u.comName,MAX(oi.add_time) as last_time
                FROM users as u
                LEFT JOIN order_info as oi
                ON oi.user_id = u.id
                WHERE ($cid) $range $time $judge AND auditType=2
                group by u.id
                ORDER BY num $order ,oid DESC limit $page,$pagesize";
       //echo $sql;exit;
        $data = $this->db2->fetchAll($sql);
        $sql = "SELECT count(u.id) as num
                FROM users as u
                LEFT JOIN order_info as oi
                ON oi.user_id = u.id
                WHERE ($cid) $range $time $judge AND auditType=2
                group by u.id
                ORDER BY num $order ";
        $num = count($this->db2->fetchAll($sql));
        $result = array();
        $result['data'] = $data;
        $result['num'] = $num;
        return $result;
    }

    /**
     * 获取上次登录时间
     * @param $uid
     * @return mixed
     */
    public function getPreviousLoginTime($uid){
        $sql = "SELECT MAX(add_time) pre_time
                FROM order_info 
                WHERE add_time < (SELECT MAX(add_time) FROM order_info WHERE user_id = $uid)
                AND user_id = $uid";
        return $this->db2->fetchRow($sql);
    }

    public function getTerminalstatisticss($cid,$page,$pagesize,$time,$order){
        $cid = $cid ? : 1;
        $sql="SELECT u.auditType,u.id,u.account,oi.id AS oid,COUNT(u.id) AS num,u.userName,u.comName,MAX(oi.add_time) AS last_time
              FROM users u
              JOIN order_info oi ON u.id = oi.user_id
              LEFT JOIN (SELECT u.id FROM users u JOIN order_info oi ON u.id = oi.user_id WHERE ($cid) AND oi.order_status = 3 GROUP BY u.id) AS a ON a.id = u.id
              WHERE a.id IS NULL AND ($cid) $time
              GROUP BY u.id
              ORDER BY num $order ,oid DESC limit $page,$pagesize
              ";
        //echo $sql;exit;
        $data = $this->db2->fetchAll($sql);
        $sql="SELECT u.auditType,u.id,u.account,oi.id AS oid,COUNT(u.id) AS num,u.userName,u.comName,MAX(oi.add_time) AS last_time
              FROM users u
              JOIN order_info oi ON u.id = oi.user_id
              LEFT JOIN (SELECT u.id FROM users u JOIN order_info oi ON u.id = oi.user_id WHERE ($cid) AND oi.order_status = 3 GROUP BY u.id) AS a ON a.id = u.id
              WHERE a.id IS NULL AND ($cid) $time
              GROUP BY u.id
              ORDER BY num $order ,oid DESC
              ";
        $num = count($this->db2->fetchAll($sql));
        $result = array();
        $result['data'] = $data;
        $result['num'] = $num;
        return $result;
    }



    /*
     * 根据用户id查找用户地址
     * @param $user_id
     * @return array
     */
    public function getAddressById($user_id){
        $sql="SELECT user_name,phone FROM user_address WHERE state=1 AND user_id={$user_id}";
        $data = $this->db2->fetchRow($sql);
        return $data;
    }

    /*
     * 根据用户id查找用户最后登录的时间
     * @param $user_id
     * @return array
     */
    public function getUpdateById($user_id){
        $sql = "SELECT MAX(updated_at) updatetime FROM login_log WHERE user_id=$user_id";
        $data = $this->db2->fetchRow($sql);
        return $data;
    }


    /*
     * 根据经销商查询经销商的订单总数和金额
     * @param $warehouse_id
     * @return array
     */
    public function getOrderInfoNum($warehouse_id,$time){
        $where_data =[];
        if($warehouse_id){
            $where_data[] = "($warehouse_id)";
        }
        if($time){
            $where_data[] = $time;
        }
        $where = '';
        if(!empty($where_data)){
             $where =  ' where '.trim(implode(' AND ',$where_data),' AND ');
        }
        $sql = "SELECT SUM(pay_money) as money,COUNT(*) as order_num FROM order_info ". $where;
        $data = $this->db2->fetchRow($sql);
        return $data;
    }


    /*
     * 根据经销商查询终端的成交的额数量
     * @param $warehouse_id
     * @return array
     */
    public function getOrderNum($warehouse_id,$time){
        $warehouse_id = $warehouse_id ? : 1;
        $sql = "SELECT COUNT(*) FROM order_info  WHERE (".$warehouse_id .") AND order_status = 3 AND" .$time ." GROUP BY user_id ";
        $data = $this->db2->fetchAll($sql);
        $num = count($data);
        return $num;
    }

    /*
     * 根据经销商查询经销商的订单总数和金额
     * @param $warehouse_id
     * @return array
     */
    public function getOrderInfo($warehouse_id,$time){
        $where_data =[];
        if($warehouse_id){
            $where_data[] = "($warehouse_id)";
        }
        if($time){
            $where_data[] = $time;
        }
        $where = '';
        if(!empty($where_data)){
            $where =  ' where '.trim(implode(' AND ',$where_data),' AND ');
        }
        $sql = "SELECT SUM(pay_money) as money,COUNT(*) as order_num FROM order_info ". $where." AND order_status=3 AND pay_status=2";
        $data = $this->db2->fetchRow($sql);
        return $data;
    }


    /*
     * 根据公司查询经销商的用户
     * @param $warehouse_id
     * @return array
     */
    public function getUserNum($warehouse_id){
        foreach($warehouse_id as $key=>$value){
            $warehouse_id[$key] = 'cid = '.$value;
        }
        $warehouse_id = implode(' or ',$warehouse_id);
        $warehouse_id = $warehouse_id ? : 1;
        $sql = "SELECT COUNT(id) as total_number FROM users WHERE ($warehouse_id) AND auditType = 2";
        $data = $this->db2->fetchRow($sql);
        return $data;
    }
    /*
     * 查询订单的状态
     * @param $warehouse_id
     * @return array
     */
    public function getOderState($company_id,$time){
        $company_id = $company_id ? : 1;
        if($time){
            $where = " AND ".$time;
        }
        $sql = "SELECT
        COUNT(CASE order_status WHEN 7 THEN 1 END) AS refund,
        count(CASE order_status WHEN 8 THEN 1 END) AS refunded,
        COUNT(CASE order_status WHEN 2 THEN 1 END) AS canceled,
        COUNT(CASE sign_status WHEN 2 THEN 1 END) AS confirmed
        FROM order_info WHERE ($company_id) $where ";
        //echo $sql;exit;
        $data = $this->db2->fetchAll($sql);
        return $data;
    }

    /*
     * 查询订单的状态代发货
     * @param $warehouse_id
     * @return array
     */
    public function getOderStateDai($company_id,$time){
        $company_id = $company_id ? : 1;
        if($time){
            $where = " AND order_status=3 AND shipping_status=1 AND pay_status=2 AND ".$time;
        }
        $sql = "SELECT count(*) num
        FROM order_info WHERE ($company_id) $where ";
        //echo $sql;exit;
        $data = $this->db2->fetchAll($sql);
        return $data;
    }

    /*
     * 查询订单的状态代付款
     * @param $warehouse_id
     * @return array
     */
    public function getOderStateDaiPay($company_id,$time){
        $company_id = $company_id ? : 1;
        if($time){
            $where = " AND order_status = 1 AND pay_status = 1 AND ".$time;
        }
        $sql = "SELECT count(*) num
        FROM order_info WHERE ($company_id) $where ";
        //echo $sql;exit;
        $data = $this->db2->fetchAll($sql);
        return $data;
    }

    /*
     * 查询订单的状态代收货
     * @param $warehouse_id
     * @return array
     */
    public function getOderStateShou($company_id,$time){
        $company_id = $company_id ? : 1;
        if($time){
            $where = " AND order_status=3 AND shipping_status=2 AND sign_status=1 AND pay_status=2 AND" .$time;
        }
        $sql = "SELECT count(*) num
        FROM order_info WHERE ($company_id) $where ";
        //echo $sql;exit;
        $data = $this->db2->fetchAll($sql);
        return $data;
    }


    /*
     * 查询下单的用户(只要下单的就算，都算在内)
     * @param $warehouse_id
     * @param $time
     * @return array
     */
      public function getUserOrder($warehouse_id,$cid,$time){
          if($time){
              $where = " AND".$time;
          }
          $warehouse_id = str_replace('warehouse_id','u.cid',$warehouse_id);
          $warehouse_id = $warehouse_id ? : 1;

          //$sql = "SELECT * FROM order_info oi LEFT JOIN users u ON oi.user_id = u.id WHERE ($warehouse_id) $where AND u.auditType=2 AND oi.is_delete=0 GROUP BY oi.user_id";
          $sql = "SELECT count(*) AS num
                  FROM(
		          SELECT COUNT(u.id) AS uorder,u.id AS uid,SUBSTRING(i.order_number, 2, 4) AS corder
		          FROM users AS u LEFT JOIN order_info AS i ON i.user_id = u.id
		          WHERE ($warehouse_id) AND u.auditType = 2
                  $where
                  AND u.createTime <= 1468598400
		          GROUP BY
			      u.id) aaa";
          $data = $this->db2->fetchAll($sql);
          $num = $data[0];
          return $num;
      }

    /*
     * 查询登陆的用户
     * @param $warehouse_id
     * @param $time
     * @return array
     */
    public function getUserLogged($warehouse_id){
        $stime = strtotime(date('Y-m-d'));
        $etime = strtotime(date('Y-m-d',strtotime('+1 day')));
        if($stime){
            $where = " AND updated_at>$stime AND updated_at<$etime";
        }
        if(count($warehouse_id)){
            foreach($warehouse_id as $key=>$value){
                $warehouse_id[$key] = 'cid = '.$value;
            }
            $warehouse_id = implode(' or ',$warehouse_id);
        }else{
            $warehouse_id = 1;
        }
        //$sql = "SELECT COUNT(*) as num FROM users WHERE ($warehouse_id) $where";
        $sql ="SELECT count(*) FROM  login_log l LEFT JOIN users u on l.user_id=u.id WHERE ($warehouse_id) $where group by l.user_id";
        //echo $sql;exit;
        $data = $this->db2->fetchAll($sql);
        $num = count($data);
        return $num;
    }
    /*
     * 查询商品的状态
     * @param $company_id
     * @return array
     */
     public function getGoodsState($company_id){
         $condition = Util::companyIdIsArray($company_id);
         if($condition != 1 && $condition != 'id = 1'){
             $where = "c$condition AND ";
         }else{
             $where = "";
         }
         $where .= 'is_delete=0';
         $sql = "SELECT COUNT(*) as total,
         COUNT(CASE is_show WHEN 1 THEN 1 END) AS isshow
         FROM goods WHERE $where";
         $data = $this->db2->fetchAll($sql);
         return $data;
     }
    /*
     * 查询不同种商品的销售数量
     * @param $warehouse_id
     * @param $time
     * @return array
     */
     public function getGoodsSales($company_id,$time,$type){
         $condition = Util::companyIdIsArray($company_id);
         if($condition != 1 && $condition != 'id = 1'){
             $where = " AND g.c$condition ";
         }else{
             $where = "";
         }
         $time = str_replace('add_time','os.create_time',$time);
         /*$sql = "SELECT
                COUNT(CASE goods_type WHEN 1 THEN 1 END) AS ice_cream,
                COUNT(CASE goods_type WHEN 3 THEN 1 END) AS frozen,
                COUNT(CASE goods_type WHEN 4 THEN 1 END) AS drink
                FROM
                order_goods AS os
                LEFT JOIN goods AS g ON os.goods_id = g.id
                WHERE g.cid = $company_id AND $time";*/
         $sql = "SELECT SUM(goods_number)  as num 
                 FROM
                 order_goods AS os
                 INNER JOIN goods AS g ON os.goods_id = g.id
                 INNER JOIN order_info as oi ON oi.id=os.order_id
                 WHERE order_status=3 AND g.goods_type=$type AND $time $where";
         //echo $sql;exit;
         $data = $this->db2->fetchAll($sql);
         return $data;
     }
    /*
     * 库存预警商品总数
     * @param $warehouse_id
     * @return array
     */
     public function getGoodsWarning($company_id){
         $condition = Util::companyIdIsArray($company_id);
         if($condition != 1 && $condition != 'id = 1'){
             $where = "AND c$condition ";
         }else{
             $where = "";
         }
         $sql = "SELECT COUNT(amount) num FROM  goods WHERE amount<10 AND is_delete=0 AND is_show = 1 $where";
         $data = $this->db2->fetchRow($sql);
         return $data;
     }









}