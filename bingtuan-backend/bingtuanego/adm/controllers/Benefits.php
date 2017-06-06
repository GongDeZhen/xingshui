<?php

class BenefitsController extends BaseController
{
    public function init()
    {
        parent::init();
    }
    
    /**
     * 满减满赠规则列表
     */
    public function benefitListAction()
    {
        $userinfo = Yaf_Registry::get('developer');
        
        if($this->_view->rbac == '*'){
            $company_id = 0;
        }else{
            $company_id = $this->companyIds;
        }
        $where = array(
        );
        //分页
        $pag = $this->getQuery('page','')?$this->getQuery('page',''):'1';
        $pageSize = 10;
        $pags = ($pag*$pageSize)-$pageSize;
        
        $benefit_list = Service::getInstance('benefit')->benefitList($pags,$pageSize,$where);
        $this->_view->benefit_list = $benefit_list;
        $url = '';
        if('80' ==  $_SERVER['SERVER_PORT']){
            $url = "http://".$_SERVER['SERVER_NAME']."/benefits/benefitList?page=__page__&".http_build_query($where);
        }else{
            $url = "http://".$_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT']."/benefits/benefitList?page=__page__&".http_build_query($where);
        }
        //获取总数量的SQL
        $page_sql = Service::getInstance('benefit')->benefitListPageSql($where,1);
        $listInfos = Service::getInstance('benefit')->benefitListPags($page_sql,$pageSize,$pag,$url);
        $this->_view->paglist = $listInfos;
    }
}