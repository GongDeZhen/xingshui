<?php

class Service_Developers extends Service
{
    public function getRegisterCodeByEmail($email, $name = '')
    {
        $row = $this->db->fetchRow('SELECT * FROM developers WHERE email=?', $email);
        if ($row) {
            return $row['code'];
        }

        $code = md5(uniqid() . '' . rand(111111, 999999));
        $salt = substr($code, 0, 6);
        $row = array('email' => $email, 'code' => $code, 'salt' => $salt);
        if ($name) {
            $row['name'] = $name;
        }

        $this->db->insert('developers', $row);

        return $code;
    }
    
    public function getRegisterInfoByEmail($email, $name = '')
    {
        $row = $this->db2->fetchRow('SELECT * FROM developers WHERE email=?', $email);
        if ($row) {
            return $row['code'];
        }
    
        $code = md5(uniqid() . '' . rand(111111, 999999));
        $salt = substr($code, 0, 6);
        $row = array('email' => $email, 'code' => $code, 'salt' => $salt);
        if ($name) {
            $row['name'] = $name;
        }
    
        $this->db->insert('developers', $row);
    
        $row['lastId'] = $this->db->lastInsertId();
        return $row;
    }
    
    public function developerlist($page, $perpage,$keyword)
	{
	    $map = '';
	    if($keyword!='')
	    {
	        $map = " WHERE name like '%{$keyword}%'";
	    }
	    $sql = "SELECT * FROM developers $map ORDER BY id desc".$this->db->buildLimit($page, $perpage);
	   
	    $data['list'] = $this->db2->fetchAll($sql);
	    $sql = "SELECT count(*) FROM developers $map";
	    $data['total'] = $this->db2->fetchOne($sql);
	    return $data;
	}

    public function getDeveloperByEmail($email)
    {
        $developers = $this->db2->fetchRow('SELECT * FROM developers WHERE email = ?', $email);
        if ($developers) {
            $warehouse = $this->db2->fetchRow("SELECT w.* FROM `users` AS u INNER JOIN `warehouse` AS w ON u.`cid`=w.`id` WHERE u.`id`={$developers['id']}");
            $developers['wid'] = $warehouse['company_id'];
            return $developers;
        } else {
            return '';
        }
    }

    public function getDeveloperByCode($code)
    {
        return $this->db2->fetchRow('SELECT * FROM developers WHERE code = ?', $code);
    }

//     public function updatePassword($id, $pwd)
//     {
//         $this->db->update('users', array('pwd' => $pwd), ' id=' . $id);
//     }
    public function updatePassword($id, $pwd)
    {
        $this->db->update('developers', array('pwd' => $pwd), 'id=' . $id);
    }

    /**
     * 后台用户管理
     * @param unknown $first
     * @param unknown $pageSize
     * @param unknown $search_name
     * @return multitype:unknown
     */
    public function adminList($first, $pageSize, $search_name)
    {
        $whsql = '';
        $and = '';
    
        $whsql = $search_name? implode(' and ',$search_name):'';
        if (!empty($whsql)) {
            $where = 'where';
        }

        $sql = "select tad.*,r.`name` rolename from developers as tad  LEFT JOIN `role` AS r ON tad.`roleId`=r.`id` $where $whsql $and order by tad.create_time desc limit $first,$pageSize";

        $whsql = str_replace('tad.', '', $whsql);
        $count = $this->db2->fetchOne("select count(*) from developers $where $whsql");

        $ret = $this->db2->fetchAll($sql);

        foreach($ret as $key => $value){
            $companyIds = $value['company_id'];
            $sql = "SELECT companyname FROM company WHERE id in ($companyIds)";
            $companys = $this->db2->fetchAll($sql);
            $companys = str_replace(',', '<br/>', implode(',', array_column($companys, 'companyname')));
            $ret[$key]['companyname'] = $companys;
        }


        return array($ret, $count);
    }
    
    public function info($id)
    {
        return $this->db2->fetchRow("select * from developers where id=$id");
    }
    
    public function update($id, $data)
    {
        return $this->db->update('developers', $data, 'id=' . $id);
    }
    
    public function getDeveloperByAdd($data)
    {
        $result = $this->db->insert('developers', $data);
        $result = $this->db->lastInsertId();
        return $result;
    }
    
    /**
     * 全部角色
     */
    public function getRoleAll()
    {
        return $this->db2->fetchAll("SELECT * FROM `role` AS tar " );
    }
    
    public function delete($id)
    {
        return $this->db->delete('developers', "id=".$id);
    }
    /**
     * 应用管理
     * @param unknown $pag
     * @param unknown $pageSize
     */
    public function rbc_adm_menu($pag,$pageSize){
        $sql="select id,name,controller,method from menu order by controller ASC  limit $pag,$pageSize";
        return array($this->db2->fetchAll($sql), $this->db->fetchOne('select count(*) from menu'));
    }
    
    // 获取应用
    public function inMenu($controller,$action)
    {
        $sql="SELECT COUNT(*) FROM menu WHERE controller='$controller' AND method='$action'";
        return $this->db2->fetchOne($sql);
    }
    
    public function add_adm_menu($arr){
        $this->db->insert('menu',$arr);
        return $this->db->lastInsertId();
    }
    
    public function getOne_menu($id){
        $sql="select id,name,controller,method from menu where id='$id'";
        return $this->db2->fetchRow($sql);
    }
    
    public function update_menu($id,$data){
        return $this->db->update('menu', $data, 'id=' . $id);
    }
    
    public function dele_adm_menu($id){
        return $this->db->delete('menu',"id=".$id);
    }
    
    /**
     * 角色列表
     * @param number $first
     * @param number $pageSize
     * @param unknown $name
     * @return multitype:
     */
    public function getRoleList($first=0,$pageSize=10, $name)
    {
        $whsql = '';
        $whereStr[] = ($name)?" name LIKE '%$name%' ":'';
        $whsql = $whereStr? implode(' and ',$whereStr):'';
        if (!empty($whsql)) {
            $where = 'where';
        }
        $list = $this->db2->fetchAll("SELECT * FROM `role` AS tar $where $whsql limit $first,$pageSize" );
        $count = $this->db2->fetchOne("SELECT count(*) FROM `role` AS tar $where $whsql" );
        return array($list, $count);
    }
    
    /**
     * 角色详情
     * @param unknown $id
     * @return multitype:unknown Ambigous <string, unknown> multitype:
     */
    public function roleInfo($id)
    {
        $tar = $this->db2->fetchRow("SELECT tar.name,tar.permission FROM `role` AS tar WHERE tar.`id`=$id");
        $tamAll = $this->menuList();
        $tam = '';
        if (!empty($tar['permission'])) {
            $tamArr = $this->db2->fetchAll("select id from `menu` where id IN ({$tar['permission']})");
            foreach ($tamArr as $k => $v) {
                $tam[] = $v['id'];
            }
        }
        
        return array($tar['name'],$tam,$tamAll);
    }
    
    /**
     * 应用列表
     */
    public function menuList()
    {
        return $this->db2->fetchAll("select id,name from `menu` order by controller DESC");
    }
    /**
     * 编辑角色
     * @param unknown $id
     * @param unknown $data
     * @return number
     */
    public function customRole($id, $data)
    {
        return $this->db->update('role', $data, "id=$id");
    }

    /**
     * 添加角色
     * @param unknown $data
     * @return number
     */
    public function roleAdd($data)
    {
        $this->db->insert('role', $data);
        return $this->db->lastInsertId();
    }
    /**
     * 删除角色
     */
    public function roleDelete($id)
    {
        return $this->db->delete('role', 'id='.$id);
    }
}