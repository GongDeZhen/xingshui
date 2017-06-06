<?php
class Service_Admafterpayuser extends Service 
{
    public function getList($page, $pageSize, $whereStr)
    {
        if(count($whereStr['warehouse_id']) > 1){
            $wIds = array_column($whereStr['warehouse_id'], 'id');
            $whereStr['warehouse_id'] = implode(',', $wIds);
            $whereStr['warehouse_id'] = "u.cid in ({$whereStr['warehouse_id']})";
        }elseif(count($whereStr['warehouse_id']) == 1){
            $whereStr['warehouse_id'] = $whereStr['warehouse_id'][0]['id'];
            if ($whereStr['warehouse_id'] == 0) {
                unset($whereStr['warehouse_id']);
            } else {
                $whereStr['warehouse_id'] = "u.cid={$whereStr['warehouse_id']}";
            }
        }else{
            unset($whereStr['warehouse_id']);
        }
        if($whereStr['query_id']){
            $whereStr['warehouse_id'] = "u.cid={$whereStr['warehouse_id']}";
        }
        if ($whereStr['status']) {
            $whereStr['status'] = "ua.status = {$whereStr['status']}";
        }
        $whsql = '';
        if ($whereStr) {
            $whsql = ' where '.implode(' and ',$whereStr);
        }

        $sql = "SELECT ua.*,u.userName,u.account,u.cid,u.refundTime,u.refundRules FROM `user_account` as ua inner join users as u on ua.user_id=u.id $whsql order by ua.status asc limit $page, $pageSize";
        $list = $this->db->fetchAll($sql);
        if ($list) {
            foreach ($list as $k => $v) {
                $list[$k]['warehousename'] = $this->db->fetchOne("select warehousename from warehouse where id={$v['cid']}");
                unset($list[$k]['cid']);
            }
            return array($list, $this->db->fetchOne("select count(*) from user_account as ua inner join users as u on ua.user_id=u.id $whsql"));
        } else {
            return array(array(),0);
        }
    }
    
    public function accountList($arr,$first, $pageSize)
    {
        $orderAll = $this->db->fetchOne("SELECT orderAll FROM `user_account` as ua where ua.id={$arr['id']}");
        $where = '';
        if(trim($arr['ktime']) != '' || trim($arr['gtime']) != ''){
            $where[] = (!empty($arr['ktime']) ? ' wa.create_time>='.strtotime($arr['ktime']) : '').((!empty($arr['ktime']) && !empty($arr['gtime']))?' AND ':'').(!empty($arr['gtime'])?' wa.create_time<='.strtotime($arr['gtime']).' ':'');
        }
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
        $orderlist = $this->db->fetchAll($sql);
        $data = array();
        if ($orderlist) {
            foreach ($orderlist as $k => $v) {
                $orderlist[$k]['warehousename'] = $this->db->fetchOne("select warehousename from warehouse where id={$v['warehouse_id']}");
                $status = Util::getOrderInfoStatus($v['order_status'],$v['shipping_status'],$v['shipping_type'],$v['pay_status'],$v['sign_status'],$v['pay_id']);
                $orderlist[$k]['status'] = $status[0];
                $data['list'] = $orderlist;
            }
        }
        $data['pay_money'] = ($orderAll)?$this->db->fetchOne("SELECT SUM(pay_money) FROM `order_info` as oi where oi.`id` IN ($orderAll)"):0;
        $data['countOrderNumber'] = ($orderAll)?$this->db->fetchOne("SELECT count(*) FROM `order_info` as oi where oi.`id` IN ($orderAll)"):0;
        
        return array('list'=>$data, 'count' => $this->db->fetchOne($sql = "SELECT count(*) FROM `order_info` AS oi $wheres "));
    }
    
    /**
     * 修改账单状态
     * @param unknown $id
     * @param unknown $table
     */
    public function updateStatus($id)
    {
        $time = time();
        if ($this->db->update('user_account', array('status' => 1, 'refund_time' => $time), 'id='.$id)) {
//             return date('Y-m-d H:i:s', $time);
        } else {
            return false;
        }
    }
}