<?php
class Service_Company extends Service
{
	public function getList($page, $perpage,$whereSql){
		$whereSql = substr($whereSql, 4);
		// echo $whereSql;
		if ($whereSql) {
			$where =  ' WHERE ';
		}
	    $total = $this->db2->fetchOne("SELECT COUNT(*) FROM `company` $where $whereSql ");
	    $sql = "SELECT * FROM `company` $where $whereSql ";
	    $query = $this->db2->fetchAll( $sql.$this->db2->buildLimit($page, $perpage) );
	    $data = array();
	    $list = array();
	    foreach ($query as $k => $row) {
	        $row['province'] = Service::getInstance('region')->provinceInfo( $row['province_id'] );
	        $row['city'] = Service::getInstance('region')->provinceInfo( $row['city_id'] );
	        $row['area'] = Service::getInstance('region')->provinceInfo( $row['area_id'] );
	        array_push($list, $row);
	    }
	    $data['total'] = $total;
	    $data['list'] = $list;
	    return $data;
	}

	public function companyInfo($id)
	{
	    $return = $this->db2->fetchRow("select * from company where id=$id");
	    $return['license'] = Util::getpicbyhash($return['license']);
	    return $return;
	}
	
	public function addcompany($arr)
	{
	    $this->db->insert('company', $arr);
	    return $this->db->lastInsertId();
	}
	
	public function  updatecompany($id,$arr){
	    return $this->db->update( 'company', $arr ,'id='.$id);
	}
	
	public function roleInfo($id)
	{
	    $tam = '';
	    $tar = ($id)?$this->db2->fetchRow("select * from adm_role where company_id=$id"):'';
	    $tamAll = $this->db2->fetchAll("select * from `adm_menu`");
// 	    if (!empty($tar['permission'])) {
// 	        $tam = $this->db->fetchAll("select * from `adm_menu` where company_id IN ({$tar['permission']})");
// 	    }
	
	    return array($tar,$tam,$tamAll);
	}

    public function roleUpdate($id,$data)
    {
        $roleID = $this->db->fetchOne("select id from adm_role where company_id = $id");
        $this->db->update('company', array('roleId' => $roleID), "id=$id");
        return $this->db->update('adm_role', $data, "company_id=$id");
    }
    
    public function roleAdd($data)
    {
        return $this->db->insert('adm_role', $data);
    }
    
    public function zoneList($page, $perpage,$whereSql,$id){
        $whereSql = substr($whereSql, 4);
        if ($whereSql) {
            $where =  ' WHERE ';
        }
        $tc = $this->db2->fetchAll("SELECT * FROM `company` AS tc INNER JOIN `adm_developers` AS tad ON tc.`id`=tad.`roleId` WHERE tc.`id`=$id");
       
        return $tc;
    }

    /**
     * 保存图片
     * @param unknown $id
     * @param unknown $goods_desc_img
     * @param unknown $goods_img
     * @param unknown $original_img
     */
    public function upIicenseImg($id, $license)
    {
        $this->db->update('company', array('license'=>$license), "id=$id");
    }
    
    /**
     * 获取公司列表
     * @return multitype:
     */
    public function getCompany()
    {
        $user = Yaf_Registry::get('developer');
        if ($user['company_id'] > 0) {
            return array($this->db2->fetchRow("SELECT id,companyname name FROM `company` where id= {$user['company_id']} "));
        }
        return $this->db2->fetchAll("SELECT id,companyname name FROM `company` $where");
    }
    /**
     * 公司账单
     * @param unknown $first
     * @param unknown $pageSize
     * @param unknown $search
     * @return multitype:unknown Ambigous <string, multitype:>
     */
    public function companyAccountList($first, $pageSize, $search)
    {
        $where = '';
        if ($search['create_time']) {
            $where[] = $search['create_time'];
        }
        if ($search['company_id']) {
            if(is_array($search['company_id'])){
                if(count($search['company_id']) > 1){
                    $ids = implode(',', $search['company_id']);
                    $where[] = " ca.cid in ($ids)";
                }
            }else{
                $where[] = " ca.`cid` = {$search['company_id']} ";
            }
        }
        if($search['query_id']){
            $where[] = " ca.`cid` = {$search['query_id']} ";
        }
        $wheres = '';
        if ($where) {
            $wheres = ' WHERE ' . implode(' AND ', $where);
        }

        $sql = "SELECT * FROM `company_account` AS ca $wheres ORDER BY ca.create_time DESC limit $first,$pageSize";
        $list = $this->db2->fetchAll($sql);
        foreach ($list as $k => $v) {
            $companyInfo = $this->companyInfo($v['cid']);
            $list[$k]['companyname'] = $companyInfo['companyname'];
            $list[$k]['order_count'] = count(explode(',', $v['orderAll']));
            $list[$k]['status_name'] = ($v['status'] == 0) ? '未结算' : '已结算';
        }
        $sql = "SELECT * FROM `company_account` AS ca $wheres ";
        $count = count($this->db2->fetchAll($sql));
        return array($list, $count);
    }
    /**
     * 公司账单详情
     * @param unknown $id
     */
    public function companyAccountInfo($arr,$first, $pageSize)
    {
        $orderAll = $this->db2->fetchOne("SELECT orderAll FROM `company_account` as ca where ca.id={$arr['id']}");
        $where = '';
        if ($arr['order_number']) {
            $where[] = 'oi.order_number='.$arr['order_number'];
        }
        if (!empty($orderAll)) {
            $where[] = "oi.`id` IN ($orderAll)";
        }
        if ($arr['consignee']) {
            $where[] = "oi.consignee like '%{$arr['consignee']}%'";
        }
        if ($arr['order_status']) {
            switch($arr['order_status']):
                case'1':
                    $where[]=' oi.pay_status=1 and oi.order_status=1 ';
                    break;
                case'2':
                    $where[]=' oi.order_status=3 and oi.shipping_status=1 and oi.sign_status=1 ';
                    break;
                case'3':
                    $where[]=' oi.order_status=3 and oi.shipping_status=2 and oi.sign_status=1 ';
                    break;
                case'4':
                    $where[]=' oi.order_status=3 and oi.shipping_status=2 and oi.sign_status=2 ';
                    break;
                case'5':
                    $where[]=' oi.order_status=4 ';
                    break;
                case'6':
                    $where[]=' oi.order_status=2 ';
                    break;
            endswitch;
        }
        if ($arr['warehouse_id']) {
            $where[] = " oi.`warehouse_id` = {$arr['warehouse_id']} ";
        }
        $wheres = '';
        if ($where) {
            $wheres = ' WHERE ' . implode(' AND ', $where);
        }
        $sql = "SELECT * FROM `order_info` AS oi $wheres limit $first, $pageSize";
        $orderlist = $this->db2->fetchAll($sql);
        $data = array();
        if ($orderlist) {
            foreach ($orderlist as $k => $v) {
                $orderlist[$k]['warehousename'] = $this->db2->fetchOne("select warehousename from warehouse where id={$v['warehouse_id']}");
                $status = Util::getOrderInfoStatus($v['order_status'],$v['shipping_status'],$v['shipping_type'],$v['pay_status'],$v['sign_status'],$v['pay_id']);
                $orderlist[$k]['status'] = $status[0];
                $data['pay_money'] += $v['pay_money'];
                $data['list'] = $orderlist;
            }
        }
        $data['pay_money'] = ($orderAll)?$this->db2->fetchOne("SELECT SUM(pay_money) FROM `order_info` as oi where oi.`id` IN ($orderAll)"):0;
        $data['countOrderNumber'] = ($orderAll)?$this->db2->fetchOne("SELECT count(*) FROM `order_info` as oi where oi.`id` IN ($orderAll)"):0;
        return array('list'=>$data, 'count' => $this->db2->fetchOne($sql = "SELECT count(*) FROM `order_info` AS oi $wheres "));
    }
    /**
     * 修改账单状态
     * @param unknown $id
     * @param unknown $table
     */
    public function updateStatus($id, $table)
    {
        $time = time();
        if ($this->db->update($table, array('status' => 1, 'refund_time' => $time), 'id='.$id)) {
            return date('Y-m-d H:i:s', $time);
        } else {
            return false;
        }
    }
    /**
     * 经销商账单列表
     * @param unknown $first
     * @param unknown $pageSize
     * @param unknown $search
     * @return multitype:unknown Ambigous <string, multitype:>
     */
    public function warehouseAccountList($first, $pageSize, $search)
    {
        $where = '';
        if ($search['addTime']) {
            $where[] = $search['addTime'];
        }
        
        if ($search['company_id']) {
            if(is_array($search['company_id'])){
                if(count($search['company_id']) > 1){
                    $ids = implode(',', $search['company_id']);
                    $where[] = " wa.`cid` in ($ids)";
                }else{
                    $where[] = " wa.`cid` = {$search['company_id'][0]} ";
                }
            }else{
                $where[] = " wa.`cid` = {$search['company_id']} ";
            }
            if ($search['warehouse_id'] > 0) {
                $where[] = "wa.`wid` = {$search['warehouse_id']}";
            }
        }

        if($search['query_id']){
            $where[] = " wa.`cid` = {$search['query_id']} ";
            if ($search['warehouse_id'] > 0) {
                $where[] = "wa.`wid` = {$search['warehouse_id']}";
            }
        }
        $wheres = '';
        if ($where) {
            $wheres = ' WHERE ' . implode(' AND ', $where);
        }
        $sql = "SELECT wa.id, wa.addTime, wa.name, wa.tatolMoney, wa.refundMoney FROM `platform_dealer_bill` AS wa $wheres ORDER BY wa.addTime DESC limit $first,$pageSize";
        $list = $this->db2->fetchAll($sql);
        $count = count($this->db2->fetchAll("SELECT * FROM `platform_dealer_bill` AS wa $wheres "));
        return array($list, $count);
    }
    
    /**
     * 公司账单详情
     * @param unknown $id
     */
    public function warehouseAccountInfo($id)
    {
        $bill = $this->db2->fetchRow("SELECT * FROM `platform_dealer_bill` where id={$id}");
        $validityOrderIds = $bill['validityOrderIds'];
        $refundOrderIds = $bill['refundOrderIds'];
        $vOrderList = $this->getOrderList($validityOrderIds);
        $rOrderList = $this->getOrderList($refundOrderIds);
        return array('list'=>$bill, 'vOrderList' => $vOrderList, 'rOrderList' => $rOrderList);
    }

    /**
     * 获取账单订单
     * @param $ids
     * @return mixed
     */
    private function getOrderList($ids){
        if($ids){
            $orderList = $this->db2->fetchAll("SELECT id, order_number, goods_amount, add_time FROM order_info WHERE id in($ids)");
        }
        return $orderList ? $orderList : [];
    }
}