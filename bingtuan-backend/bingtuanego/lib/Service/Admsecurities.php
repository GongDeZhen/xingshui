<?php
/**
 * 后台代金券管理数据模型
 */
class Service_Admsecurities extends Service
{
    /**
     * 列表
     */
    public function getSecuritiesList($first,$pageSize,$data)
    {
        $sql = Util::companyIdIsArray($data['company_id']);
        if ($sql != 1) {
            if($data['query_id']){
                $data['company_id'] = "c.cid = {$data['query_id']}";
                unset($data['query_id']);
            }else{
                $data['company_id'] = "c.c{$sql}";
            }
        }
        
        $wheres = '';
        if ($data) {
            $wheres = ' AND '.implode(' AND ', $data);
        }
        $sql = "SELECT * FROM `coupons` AS c WHERE c.type = 3 AND c.`is_del`=0 $wheres order by c.createdTime desc limit $first,$pageSize";
        $list       = $this->db->fetchAll($sql);
        $count = $this->db->fetchOne("SELECT count(*) FROM `coupons` AS c WHERE c.type = 3 AND c.`is_del`=0 $wheres");
        return array($list,$count);
    }
    
    public function add($data)
    {
        unset($data['id']);
        $data['start_time'] = strtotime($data['start_time']);
        $data['end_time'] = strtotime($data['end_time']);
        $data['createdTime'] = time();
        $data['coupons_number']   = strftime("%Y%m%d%H%M%S") . mt_rand(100000,999999);
        $data['type'] = 3;
        $this->db->insert('coupons', $data);
        return $this->db->lastInsertId();
    }
    
    public function update($id, $data)
    {
        $data['start_time'] = strtotime($data['start_time']);
        $data['end_time'] = strtotime($data['end_time']);
        $data['update_time'] = date('Y-m-d H:i:s', time());
        try {
            $this->db->update('coupons', $data, "id=$id");
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * 删除
     * @param unknown $id
     * @return number
     */
    public function delete($id)
    {
        return $this->db->update('coupons', array('is_del'=>1), 'id='.$id);
    }
    
    public function getSecuritiesInfo($id)
    {
        return $this->db->fetchRow("SELECT * FROM `coupons` AS c WHERE c.`is_del`=0 AND id=$id");
    }
    
    /**
     * 发送优惠券
     * @param unknown $id
     * @return boolean
     */
    public function resend($id,$user_type)
    {
        try {
            $where = '';
            if ($user_type != 3) {
                $where = " WHERE u.user_type=$user_type";
            }
            $online = ($user_type == 1 ? 0 :($user_type == 2 ? 1 : 0));
            $userList = $this->db->fetchAll("SELECT u.id FROM `users` AS u $where");
            foreach ($userList as $k => $v) {
                $this->db->insert('coupons_associated', array('coupons_id'=>$id, 'user_id'=>$v['id'], 'online' => $online));
            }
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}