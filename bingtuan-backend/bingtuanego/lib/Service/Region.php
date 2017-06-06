<?php
class Service_Region extends Service
{
    //省列表
    public function getProvinceList(){
        $sql = "SELECT * FROM `city`";
        return $this->db2->fetchAll($sql);
    }
    
    //省信息
    public function provinceInfo($id){
        $sql = "SELECT `name` FROM `city` WHERE id = ".$id;
        return $this->db2->fetchOne($sql);
    }
    
    /**
     * 权限级联菜单
     */
    public function getSelect($province_id, $city_id, $area_id){
        $adm = Yaf_Registry::get('user');
        $type = $adm['type'];
        $address=$this->getAddress();
        if ($adm['email'] != 'jason@suxin8.com') {
            switch ($type) {
                case 1:
                    $startAdm = "<fieldset>";
                    $endAdm = "</fieldset>";
                    break;
                case 2:
                    $start = "<fieldset disabled>";
                    $end = "</fieldset>";
                    break;
                case 3:
                    $startArea = "<fieldset disabled>";
                    $endArea = "</fieldset>";
                    break;
            }
        } else {
            $startAdm = "<fieldset>";
            $endAdm = "</fieldset>";
        }
    
        $tpl=$startArea.$start.$startAdm.'<div class="input-group"><div class="input-group-addon">省</div><select class="form-control" name=\'province_id\' id="province"><option value="">请选择</option>';
        foreach ($address as $key):
        $tpl.='<option value="'.$key['id'].'"'.($key['id'] == $province_id ? 'selected="selected"':'').'>';
        $tpl.=$key['name'].'</option>';
        endforeach;
        $tpl.='</select></div>';
        $citylist = $this->getCitylist($province_id);
        $tpl.='<div class="input-group"><div class="input-group-addon">市</div><select class="form-control"  name="city_id" id=\'city\'>';
        if ($province_id == 0) {
            $tpl .= '<option value="">请选择</option>';
        }
        //if ( $city_id > 0 ) {
        $tpl.='<option value="0">全部</option>';
            foreach ($citylist as $key):
            $tpl.='<option value="'.$key['id'].'"'.($key['id'] == $city_id ? 'selected="selected"':'').'>'.$key['name'].'</option>';
            endforeach;
        //}
        $tpl.='</select></div>'.$end;
//         $arealist = $this->getArealist($city_id);
//         $tpl.='<div class="input-group"><div class="input-group-addon">区</div><select class="form-control" name="area_id" id=\'area\'>';
//         if ($province_id == 0) {
//             $tpl .= '<option value="">请选择</option>';
//         } else {
//             $tpl .= '<option value="">请选择</option>';
//         }
//         if ( $city_id > 0 ) {
//             foreach ($arealist as $key):
//             $tpl.='<option value="'.$key['id'].'"'.($key['id'] == $area_id ? 'selected="selected"':'').'>'.$key['name'].'</option>';
//             endforeach;
//         }
//         $tpl.='</select></div>'.$endAdm.$endArea;
        return $tpl;
    }
    
    /**
     * 市
     */
    public function getCitylist($parent){
        $sql="select id,`name` from city where parent_id='$parent'";
        return $this->db2->fetchAll($sql);
    }
    /**
     * 区
     */
    public function getArealist($parent){
        $sql="select id,`name` from city where parent_id='$parent'";
        return $this->db2->fetchAll($sql);
    }
    /**
     * 获取地区
     */
    public function getAddress()
    {
        return $this->db2->fetchAll("select id,`name` from city where parent_id=0 order by id");
    }
    
    /**
     * 公司列表
     */
    public function getCompanyList($id)
    {
        $where = '';
        $sql = Util::companyIdIsArray($id);
        if($sql == "id = 1"){
            $where = '';
        }else{
            $where = " where $sql ";
        }
        $sql="select id,companyname `name` from company $where";
        return $this->db2->fetchAll($sql);
    }

    /**
     * 按城市区域展现公司列表
     * @return mixed
     */
    public function getCompanyListByCity(){
        $sql = "SELECT cct.company_id, cct.companyname, city.id province_id, city.`name` province_name, cct.city_id, cct.city_name 
                FROM (SELECT ct.id city_id, ct.parent_id, ct.`name` city_name, cp.id company_id, cp.companyname 
                      FROM city ct 
                      JOIN company cp 
                      ON ct.id = cp.city_id) cct 
                JOIN city 
                ON cct.parent_id = city.id";
        return $this->db2->fetchAll($sql);
    }
    
    /**
     * 经销商列表
     */
    public function getWarehouseList($id)
    {
        if(is_array($id)){
            if(count($id) > 1){
                $ids = implode(',', $id);
                $sql = "select id,warehousename `name` from warehouse where company_id in ($ids)";
            }else{
                $sql="select id,warehousename `name` from warehouse where company_id=$id[0]";
            }
        }else{
            $sql="select id,warehousename `name` from warehouse where company_id=$id";
        }
        return $this->db2->fetchAll($sql);
    }
    
    /**
     * 公司与经销商联菜单
     */
    public function getCompanyDealersList($data){
//         if($userinfo['type']==1 || $userinfo['username']== $this->getAdminName()){
            $company = $this->getCompanyList($data['company']);
            $tpl ='<div class="input-group"><div class="input-group-addon">公司</div><select style="padding: 0.2em;" class="form-control" name=\'company_id\' id="company">';
            $tpl .= '<option value="0">全部经销商</option>';
            foreach ($company as $key):
                $tpl .='<option value="'.$key['id'].'"';
                if($data['query_id'] == $key['id']):
                    $tpl .='selected="selected"';
                endif;
            $tpl.='>'.$key['name'].'</option>';
            endforeach;
            $tpl.='</select></div>';
//         }
//         if($userinfo['type']=='1' || $userinfo['type']=='2' || $userinfo['username']== $this->getAdminName()){
            $tpl.='<div class="input-group"><div class="input-group-addon">经销商</div>';
//             if($userinfo['type']!='2'){
                $tpl.='<select style="padding: 0.2em;" class="form-control" name="warehouse_id" id=\'warehouse\'><option value="0">请选择</option>';
                $warehouseList = $this->getWarehouseList($data['query_id']);
                foreach ($warehouseList as $key):
                $tpl.='<option value="'.$key['id'].'"';
                if($data['warehouse'] == $key['id']):
                $tpl.='selected="selected"';
                endif;
                $tpl.= '>'.$key['name'].'</option>';
                endforeach;
                $tpl.='</select></div>';
//             }else{
//                 $citylist = $this->getCitylist($userinfo['province_id']);
//                 $tpl.='<select style="padding: 0.2em;" class="form-control" name="city_id" id=\'city\'><option value="0">请选择</option>';
//                 foreach ($citylist as $key):
//                 if($userinfo['city_id']==$key['id']){
//                     $tpl.='<option value="'.$key['id'].'"';
//                     if($data['city'] == $key['id']):
//                     $tpl.='selected="selected"';
//                     endif;
//                     $tpl.= '>'.$key['name'].'</option>';
//                 }
//                 endforeach;
//                 $tpl.='</select></div>';
//             }
//         }
//         if($userinfo['type']!=3 || $userinfo['username']== $this->getAdminName()){
//             $tpl.='<div class="input-group"><div class="input-group-addon">区</div><select style="padding: 0.2em;" class="form-control" name="area_id" id=\'area\'><option value="0">请选择</option>';
//             $city = ($data['city'])?$data['city']:($userinfo['type']==3)?$userinfo['city_id']:0;
//             if ($city > 0) {
//                 $arealist = $this->getArealist($city);
//                 foreach ($arealist as $key):
//                 $tpl.='<option value="'.$key['id'].'"';
//                 if($data['area'] == $key['id']):
//                 $tpl.='selected="selected"';
//                 endif;
//                 $tpl.= '>'.$key['name'].'</option>';
//                 endforeach;
//             }
                
//             $tpl.='</select></div>';
//         }
        return $tpl;
    }
}