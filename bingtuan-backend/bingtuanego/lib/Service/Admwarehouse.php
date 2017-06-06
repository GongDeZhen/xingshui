<?php
class Service_Admwarehouse extends Service
{
    public function warehouseList($page, $perpage,$whereSql)
    {
//         $whereSql = substr($whereSql, 4);
//         // echo $whereSql;
//         if ($whereSql) {
//             $where =  ' WHERE ';
//         }
//         $total = $this->db->fetchOne("SELECT COUNT(*) FROM `warehouse` $where $whereSql ");
//         $sql = "SELECT * FROM `warehouse` $where $whereSql ";
//         $query = $this->db->fetchAll( $sql.$this->db->buildLimit($page, $perpage) );
//         $data = array();
//         $list = array();
//         foreach ($query as $k => $row) {
//             $row['province'] = Service::getInstance('region')->provinceInfo( $row['province_id'] );
//             $row['city'] = Service::getInstance('region')->cityInfo( $row['city_id'] );
//             $row['area'] = Service::getInstance('region')->areaInfo( $row['area_id'] );
//             array_push($list, $row);
//         }
//         $data['total'] = $total;
//         $data['list'] = $list;
        if($whereSql['company_id']){
            $sql = Util::companyIdIsArray($whereSql['company_id']);
            unset($whereSql['company_id']);
        }

        if($whereSql['query_id']){
            $queryId = $whereSql['query_id'];
            unset($whereSql['query_id']);
        }

        $arr = array();
        foreach ($whereSql as $k => $v) {
            if ($k == 'warehousename') {
                $arr[] = " $k like '%$v%' OR contact LIKE '%$v%' ";
            } else {
                $arr[] = " $k = $v ";
            }
            
        }

        $where = '';
        if (!empty($arr)) {
            $where = ' WHERE ' . implode(' AND ', $arr);
        }
        if($where && $sql){
            $where .= " AND company_{$sql}";
        }elseif(!$where && $sql){
            $where = " WHERE company_{$sql}";
        }

        if($where && $queryId){
            $where .= " AND company_id = $queryId";
        }elseif(!$where && $queryId){
            $where = " WHERE company_id = $queryId";
        }

        $page = ($page-1) * $perpage;
        $sql = "SELECT * FROM `warehouse` AS w $where LIMIT $page, $perpage";
        $data = $this->db->fetchAll($sql);
        return array('list'=> $data, 'total' => $this->db->fetchOne("SELECT count(*) FROM `warehouse` AS w $where"));
    }
    
    public function warehouseInfo($id)
    {
        $return = $this->db->fetchRow("select * from warehouse where id=$id");
        $return['codeImg'] = Util::getpicbyhash($return['codeImg']);
        return $return;
    }
    
    public function addwarehouse($arr)
    {
        $this->db->insert('warehouse', $arr);
        return $this->db->lastInsertId();
    }
    
    public function  updatewarehouse($id,$data)
    {
        return $this->db->update( 'warehouse', $data ,"id=$id");
    }
    
    public function roleInfo($id)
    {
        $tam = '';
        $tar = ($id)?$this->db->fetchRow("select * from adm_role where company_id=$id"):'';
        $tamAll = $this->db->fetchAll("select * from `adm_menu`");
//         if (!empty($tar['permission'])) {
//             $tam = $this->db->fetchAll("select * from `adm_menu` where company_id IN ({$tar['permission']})");
//         }
    
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
        $tc = $this->db->fetchAll("SELECT * FROM `company` AS tc INNER JOIN `adm_developers` AS tad ON tc.`id`=tad.`roleId` WHERE tc.`id`=$id");
       
        return $tc;
    }
    
    /**
     * 保存二维码图片
     */
    public function upIicenseImg($id, $codeImg)
    {
        $this->db->update('warehouse', array('codeImg'=>$codeImg), "id=$id");
    }
    
    public function companyWarehouseList($id)
    {
        $sql = "SELECT * FROM `warehouse` AS w WHERE w.`company_id`=$id";
        $data = $this->db->fetchAll($sql);
        return $data;
    }
}