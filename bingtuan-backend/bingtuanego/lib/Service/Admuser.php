<?php
class Service_Admuser extends Service
{
    public function getError()
    {
        return $this->error;
    }

    /**
     * 用户列表
     * @param page
     * @param perpage
     * @param where
     */
    public function userlists($page=1 , $perpage=10,$where='')
    {
        $wheres = array();
        if ($where) {
            $sql = Util::companyIdIsArray($where['company_id']);
            if ($sql != 1 || $where['query_id']) {
                $join = " inner join warehouse as w on u.cid=w.id ";
            }
            if ($where['warehouse_id'] > 0) {
                if(!$where['query_id']){
                    $wheres[] = " u.cid = {$where['warehouse_id']} AND w.company_{$sql} ";
                }else{
                    $wheres[] = " u.cid = {$where['warehouse_id']} AND w.company_id = {$where['query_id']} ";
                }
            }
            if (($sql != 1 || $where['query_id'] > 1)  && $where['warehouse_id'] == 0) {
                if(!$where['query_id']){
                    $wheres[] = " w.company_{$sql} ";
                }else{
                    $wheres[] = " w.company_id = {$where['query_id']} ";
                }
                
            }
            if ($where['auditType'] >= 0 && $where['auditType'] != '') {
                $wheres[] = " u.auditType = {$where['auditType']} ";
            }
            if ($where['account']) {
                $wheres[] = " u.account LIKE '%{$where['account']}%' ";
            }
            if ($where['userName']) {
                $wheres[] = " u.userName LIKE '%{$where['userName']}%' ";
            }
            $wheres = implode(' AND ', $wheres);
            if ($wheres) {
                $wheres = " WHERE $wheres ";
            }
        }
        $sql = "SELECT u.* FROM `users` as u $join $wheres ORDER BY u.`auditType`=2 desc,u.`auditType`=0 desc, u.`auditType`=1 desc, u.createTime desc";
        if ($page && $perpage) {
            $query = $this->db->fetchAll($sql.$this->db->buildLimit($page, $perpage) );
        } else {
            $query = $this->db->fetchAll($sql);
        }
        
        foreach ($query as $k => $v) {
            if ($v['auditType']==3){
                $sql = "SELECT reason,remarks FROM blacklist WHERE user_id = {$v['id']} ORDER BY creat_time DESC LIMIT 1";
                $blicklist = $this->db->fetchAll($sql);
                $query[$k]['reason'] = $blicklist[0]['reason'];
                $query[$k]['remarks'] = $blicklist[0]['remarks'];
            }
            $address = $this->getUserAddress($v['id']);
            if ($address) {
                $province_name = Service::getInstance('region')->provinceInfo($address['province_id']);
                $city_name = Service::getInstance('region')->provinceInfo($address['city_id']);
                $area_name = Service::getInstance('region')->provinceInfo($address['area_id']);
                $query[$k]['user_address'] =  $province_name.$city_name.$area_name.$address['address'];
            } else {
                $query[$k]['user_address'] = '未设置';
            }
            
            $company = $this->db->fetchRow("SELECT c.id,c.`companyname` FROM `warehouse` AS w INNER JOIN `company` AS c ON w.`company_id`=c.`id` WHERE w.`id`={$v['cid']}");
            $query[$k]['companyName'] = ($company['companyname'])?$company['companyname']:'未设置';
            $query[$k]['company_id'] = ($company['id'])?$company['id']:0;
            $query[$k]['warehouseName'] = $this->db->fetchOne("select warehousename from warehouse where id={$v['cid']}");
        }
        $sql = "SELECT count(*) FROM `users` as u $join $wheres ORDER BY u.`auditType`=2 desc,u.`auditType`=0 desc, u.`auditType`=1 desc, u.createTime desc";
        return array('total' => $this->db->fetchOne($sql), 'list' => $query);
    }

    /**
     * 得到用户的地址
     * @param  [type] $userId   
     * @param  [type] $receiver [description]
     * @return [type]           [description]
     */
    public function getUserAddress($userID)
    {
        return $this->db->fetchRow("select * from `user_address` where user_id = $userID AND state = 1");
    }

    /**
     * 普通用户更新
     */
    public function updateUser($arr,$id){
        return $this->db->update('users', $arr,'id='.$id);
    }

    /**
     * 获取用户信息
     * @param unknown $id
     * @param string $db
     */
    public function userInfo($id)
    {
        $user = $this->db->fetchRow("select * from `users` where id = $id");
        $address = $this->getUserAddress($id);
        if ($address) {
            $province_name = Service::getInstance('region')->provinceInfo($address['province_id']);
            $city_name = Service::getInstance('region')->provinceInfo($address['city_id']);
            $area_name = Service::getInstance('region')->provinceInfo($address['area_id']);
            $user['user_address'] =  $province_name.$city_name.$area_name.$address['address'];
        } else {
            $user['user_address'] = '未设置';
        }
        if ($user) {
            $province_name = Service::getInstance('region')->provinceInfo($user['province']);
            $city_name = Service::getInstance('region')->provinceInfo($user['city']);
            $area_name = Service::getInstance('region')->provinceInfo($user['area']);
            $user['address'] =  $province_name.$city_name.$area_name.$user['address'];
        }
        
        $company = $this->db->fetchRow("SELECT c.id,c.`companyname` FROM `warehouse` AS w INNER JOIN `company` AS c ON w.`company_id`=c.`id` WHERE w.`id`={$user['cid']}");
        $user['companyName'] = ($company['companyname'])?$company['companyname']:'未设置';
        $user['company_id'] = ($company['id'])?$company['id']:0;
        return $user;
    }

    /**
     * 审核
     * @param unknown $data
     * @return boolean
     */
    public function cancelStock($data, $old)
    {
        $id = $data['id'];
        unset($data['id']);
        $array = array();
        if ($data['user_type'] == 1) {
            switch ($data['auditType']) {
                case 1:
                    $array = array(
                       'user_type' => $data['user_type'],
                       'auditType' => $data['auditType'],
                       'auditContent' => $data['auditContent'],
                       'refundTime' => 0,
                       'refundRules' => 0,
                       'refundMoney' => 0.00
                    );
                break;
                case 2:
                    $array = array(
                       'user_type' => $data['user_type'],
                       'auditType' => $data['auditType'],
                       'auditContent' => '',
                       'refundTime' => 0,
                       'refundRules' => 0,
                       'refundMoney' => 0.00
                    );
                break;
            }
        } elseif ($data['user_type'] == 2) {
            switch ($data['auditType']) {
                case 1:
                    $array = array(
                        'user_type' => $data['user_type'],
                        'auditType' => $data['auditType'],
                        'auditContent' => $data['auditContent'],
                        'refundTime' => 0,
                        'refundRules' => 0,
                        'refundMoney' => 0.00
                    );
                break;
                case 2:
                    $array = array(
                        'user_type' => $data['user_type'],
                        'auditType' => $data['auditType'],
                        'auditContent' => '',
                        'refundTime' => $data['refundTime'],
                        'refundRules' => $data['refundRules'],
                        'refundMoney' => $data['refundMoney']
                    );
                break;
            }
        }
        if (empty($array)) {
            return false;
        }
        $array['auditTime'] = time();
       
        $return = $this->db->update('users', $array, "id=$id");
        if ($return) {
            Service::getInstance("message")->addMessage($id, 'cancelStock');
        }

        return $return;
    } 
    
    /**
     * 拉黑普通用户
     */
    public function dodel($array){
        $id = $array['user_id'];
        $arrays = array(
            'auditType' => 3,
            'auditContent' => '',
            'refundTime' => 0,
            'refundRules' => 0,
            'refundMoney' => 0.00,
            'auditTime' => time()
        );
        try{
            $this->db->beginTransaction();
            $re = $this->db->update('users', $arrays,'id = '.$id );
            if (!$re)
                throw new PDOException('修改状态错误'); // 如出现异常提示信息或执行动作
            $res = $this->db->insert('blacklist',$array);
            if (!$res)
                throw new PDOException('写入拉黑错误'); // 如出现异常提示信息或执行动作
            $this->db->commit();
        }catch (PDOException $e){
            $this->db->rollback();
            return ($e->getMessage());
        }
        
    }
    /**
     * 修改用户密码
     */
    public function updatePassword($id, $pwd)
    {
        return $this->db->update('users', array('password'=> $pwd),'id = '.$id );
    }

    /**
     * 添加用户
     * @param unknown $data
     */
    public function adduser($data)
    {
        if ($data) {
            $uid = $data['account']?Service::getInstance("user")->getUidBymobile($data['account']):0;
            if ($uid > 0) {
                return true;
            } else {
                return $this->db->insert('users', $data );
            }
        }
    }
}