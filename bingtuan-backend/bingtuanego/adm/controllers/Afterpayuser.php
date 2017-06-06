<?php
class AfterpayuserController extends BaseController
{
    public function getListAction()
    {
        //$userinfo = Yaf_Registry::get('developer');
        //var_dump($userinfo);
        //公司、经销商
        $company_id = $this->_view->rbac == '*' ? 0 :$this->companyIds;
        $queryId = $this->getQuery('company_id',0);
        $where['warehouse_id'] = $this->getQuery('warehouse_id',0);
        if($where['warehouse_id']==0 && !in_array('1', $company_id)){
            $warehouse_id = Service::getInstance('Distributor')->getWarehouseByCid($company_id);
            $where['warehouse_id'] = $warehouse_id;
        }
        //分页
        $pag = $this->getQuery('page','')?$this->getQuery('page',''):'1';
        $pageSize = 10;
        $first = ($pag*$pageSize)-$pageSize;
        
        $ktime = $this->getQuery('ktime','');
        $gtime = $this->getQuery('gtime','');
        
        if(trim($ktime) != '' || trim($gtime) != ''){
            $where['create_time'] = (!empty($ktime) ? ' ua.create_time>='.strtotime($ktime) : '').((!empty($ktime) && !empty($gtime))?' AND ':'').(!empty($gtime)?' ua.create_time<='.strtotime($gtime).' ':'');
        }
        $where['status'] = $this->getQuery('status');
        if ($where['status'] == '') {
            unset($where['status']);
        }
        /*if($userinfo['company_id']!=1){
            $where['cid'] = "cid={$userinfo['company_id']}";
        }*/
        $search = array(
            'status' => $where['status'],
            'ktime' => $ktime,
            'gtime' => $gtime,
            'company_id' => $where['company_id']
        );
        
        $list = Service::getInstance('Admafterpayuser')->getList($first,$pageSize,$where);
        $this->_view->list = $list[0];
        $this->_view->get = $_GET;
        $search['company_id'] = $queryId;
        $url = "http://".$_SERVER['SERVER_NAME']."/Afterpayuser/list?page=__page__&".http_build_query($search);
        $this->_view->paglist = Util::buildPagebar($list[1],$pageSize,$page,$url);
        $this->_view->companyDealersList = Service::getInstance('region')->getCompanyDealersList(array('company'=>$company_id, 'warehouse'=>$where['warehouse_id'],'query_id'=>$queryId));
    }
    
    public function accountListAction()
    {
        // 分页
        $page = $this->getQuery('page','')?$this->getQuery('page',''):'1';
        $pageSize = 10;//结束值
        $first = ($page-1)*$pageSize;//起始值
        
        $data = array(
            'id' => $this->getQuery('id'),
            'order_number' => $this->getQuery('order_number'),
            'consignee' => $this->getQuery('consignee'),
            'order_status' => $this->getQuery('order_status'),
            'ktime' => $this->getQuery('ktime',''),
            'gtime' => $this->getQuery('gtime','')
        );
        
        $url = "http://".$_SERVER['SERVER_NAME']."/afterpayuser/accountlist?page=__page__&".http_build_query($data);
        $this->_view->get = $_GET;
        $list = Service::getInstance('Admafterpayuser')->accountList($data,$first,$pageSize);
        $this->_view->paglist = Util::buildPagebar($list['count'],$pageSize,$page,$url);
        $this->_view->list = $list['list'];
    }
    
    /**
     * 修改账单状态
     */
    public function updateStatusAction()
    {

        $id = $this->getPost('id');
        //编辑账号
        $userinfo = Yaf_Registry::get('developer');
        $goods['log_type'] = 4;
        $goods['action'] = '确认订单';
        $goods['uid'] = $userinfo['id'];
        $goods['create_time'] = time();
        $goods['action_id'] = $id;
        $goods['ip'] = $_SERVER["REMOTE_ADDR"];
        Service::getInstance('systemlog')->addLog($goods);
        echo json_encode(Service::getInstance('Admafterpayuser')->updateStatus($id));
        die;
    }
}