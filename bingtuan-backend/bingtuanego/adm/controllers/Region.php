<?php
/*
 * 省市区控制 
 * 作为前端ajax请求使用
 * return json
 */
class RegionController extends BaseController
{
    public function init()
    {
        parent::init();
    }
    
    public function getCitylistAction(){
        $id = $this->getPost('province',0);
        
        $citylist = Service::getInstance('region')->getCitylist($id);
        //var_dump($citylist);
        $arealist = Service::getInstance('region')->getArealist( $citylist[0]['id'] );
        $temp = array('id'=>0,'name'=>'全部');
        array_unshift($arealist,$temp);
        array_unshift($citylist,$temp);
        $list['city'] = $citylist ;
        //var_dump($arealist);

        $list['area'] = $arealist ;
        $data = array('success'=>1,'data'=>$list);
        $this->respon(1, $data);
    }
    
    public function getArealistAction(){
        $id = $this->getPost('city',0);
        $list = Service::getInstance('region')->getArealist($id);
        $data = array('success'=>1,'data'=>$list);
        $this->respon(1, $data);
    }
   
    public function getCompanyListAction()
    {
        $company_id = $this->getPost('company');
        $this->respon(1, Service::getInstance('region')->getCompanyList($company_id));
    }
    public function getWarehouseListAction()
    {
        $company_id = $this->getPost('company');
        $this->respon(1, Service::getInstance('region')->getWarehouseList($company_id));
    }
}