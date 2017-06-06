<?php
class Service_Warehouse extends Service
{
	public function getList($page, $perpage,$whereSql){
		$whereSql = substr($whereSql, 4);
		// echo $whereSql;
		if ($whereSql) {
			$where =  ' WHERE ';
		}
	    
	    $sql = "SELECT * FROM `warehouse` $where $whereSql ";
	    $query = $this->db->fetchAll( $sql.$this->db->buildLimit($page, $perpage) );
	    $sql = "SELECT count(*) FROM `warehouse` $where $whereSql ";
	    $total = $this->db->fetchOne($sql);
	    $data = array();
	    $list = array();
	    foreach ($query as $k => $row) {
	        $row['province'] = Service::getInstance('region')->provinceInfo( $row['province_id'] );
	        $row['city'] = Service::getInstance('region')->cityInfo( $row['city_id'] );
	        $row['area'] = Service::getInstance('region')->areaInfo( $row['area_id'] );
	        array_push($list, $row);
	    }
	    $data['total'] = $total;
	    $data['list'] = $list;
	    return $data;
	}
	
	public function warehouseInfo($id)
	{
	    $return = $this->db->fetchRow("select * from warehouse where id=$id");
	    return $return;
	}
	
	public function addwarehouse($arr)
	{
	    $this->db->insert('warehouse', $arr);
	    return $this->db->lastInsertId();
	}
	
	public function  updatewarehouse($id,$arr){
	    return $this->db->update( 'warehouse', $arr ,'id='.$id);
	}
	
	public function roleInfo($id)
	{
	    $tam = '';
	    $tar = ($id)?$this->db->fetchRow("select * from adm_role where company_id=$id"):'';
	    $tamAll = $this->db->fetchAll("select * from `adm_menu`");
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
        $tc = $this->db->fetchAll("SELECT * FROM `company` AS tc INNER JOIN `adm_developers` AS tad ON tc.`id`=tad.`roleId` WHERE tc.`id`=$id");
       
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
        $this->db->update('warehouse', array('license'=>$license), "id=$id");
    }
}