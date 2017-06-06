<?php
class AddressofficeController extends BaseController
{
    public function init()
    {
        parent::init();
    }
    
    public function addressOfficeListAction(){
        $where = '';
        $page = $this->getQuery( 'page',1 );
        $perpage = 10 ;
        $province_id = $this->getQuery('province_id');
        $city_id = $this->getQuery('city_id');
        $area_id = $this->getQuery('area_id');
        $status = $this->getQuery('status');
        if( $province_id ){
            $where .= " and province_id = {$province_id} ";
        }
        if( $city_id ){
            $where .= " and city_id = {$city_id} ";
        }
        if( $area_id ){
            $where .= " and area_id = {$area_id} ";
        }
        if($status != ''){
            $where .=" and status = {$status} ";
        }
        $companyname = $this->getQuery('companyname');
        if($companyname){
            $where .= " and companyname like '%{$companyname}%' ";
        }
        $sql = Util::companyIdIsArray($this->companyIds);
        if(!$this->showAll){
            $where .= " and $sql";
        }
        $data = Service::getInstance('company')->getList( $page, $perpage, $where );
        $href = $_SERVER['REQUEST_URI'];
        //分页控制
        if(strrpos($href, '?') == false ){
            $url = $url = $uri .'?'. 'page=__page__';
        }else{
            $url = strrpos($_SERVER['REQUEST_URI'], '&page='.$page) == false ? str_replace('page='.$page, '', $href ).'&page=__page__' : str_replace('page='.$page, '', $href ).'page=__page__';
        }
        $pagebar = Util::buildPagebar( $data['total'], $perpage, $page, $url );
        $this->_view->list = $data['list'];
        $this->_view->info = $_GET;
        $this->_view->pagebar = $pagebar;
        $this->_view->provincelist =  Service::getInstance('region')->getProvinceList();
        $this->_view->jiliancaidan = Service::getInstance('region')->getSelect($province_id, $city_id, $area_id);
    }

    public function editAction(){
        $gid = $this->getQuery('id');
        $pid = $this->getPost('id');
        $id = ($gid)?$gid:$pid;
        $info = Service::getInstance('company')->companyInfo($id);
        $province_id = $this->getPost('province_id');
        $province_id = ($province_id) ? $province_id : $info['province_id'];
		$city_id = $this->getPost('city_id');
		$city_id = ($city_id) ? $city_id : $info['city_id'];
		$email = $this->getPost('email');
		$companyname = $this->getPost('companyname');
		$person = $this->getPost('person');
		$contact = $this->getPost('contact');
        $mobile = $this->getPost('mobile');
		$status = $this->getPost('status');
		$describe = $this->getPost('describe');
		$this->_view->ID = $id;
		$this->_view->info = ($_POST)?$_POST:$info;
        $this->_view->jiliancaidan = Service::getInstance('region')->getSelect($province_id, $city_id, $area_id);

		if ($this->isPost()) {
            if ($city_id == 0) return $this->_view->errors = '请选择省市'; 
            if ($companyname == '') return $this->_view->errors = '请填写公司名称';
            if ($person == '') return $this->_view->errors = '请填写法人';
            if ($contact == '') return $this->_view->errors = '请填写联系人';
            if ($mobile == '') return $this->_view->errors = '请填写手机号';
            if (strlen($mobile) != 11) return $this->_view->errors = '手机号必须11位数';
            if ($status == '') return $this->_view->errors = '请选择用户状态';

		    $data = array(
		        'province_id' => $province_id,
		        'city_id' => $city_id,
		        'area_id' => $area_id,
		        'companyname' => $companyname,
                'person' => $person,
                'contact' => $contact,
                'mobile' => $mobile,
                '`status`' => $status,
                '`describe`' => $describe,
                'create_time' => time()
		    );
            $ret = Service::getInstance('company')->updatecompany($id, $data);
            if ($ret) {
                if ($_FILES['license']['error'] == 0 && $_FILES['license']['size'] > 0) {
                    Util::delImage($info['license']);
                    $file = Util::getImgPath($_FILES['license'],414,175);
                    $license = implode(',', $file);
                    Service::getInstance('company')->upIicenseImg($id,$license);
                }
            }
            //添加成功编辑分公司日志
            $userinfo = Yaf_Registry::get('developer');
            $goods['log_type'] = 3;
            $goods['action'] = '编辑分公司';
            $goods['uid'] = $userinfo['id'];
            $goods['create_time'] = time();
            $goods['action_id'] = $ret;
            Service::getInstance('systemlog')->addLog($goods);
            $this->flash('addressOfficeList','编辑成功');
            return false;
		}
    }

    public function addAction()
    {
//         include('/phpqrcode.php');
        $province_id = $this->getPost('province_id');
		$city_id = $this->getPost('city_id');
		$email = $this->getPost('email');
		$companyname = $this->getPost('companyname');
		$person = $this->getPost('person');
		$contact = $this->getPost('contact');
        $mobile = $this->getPost('mobile');
		$status = $this->getPost('status');
		$describe = $this->getPost('describe');
        $this->_view->jiliancaidan = Service::getInstance('region')->getSelect($province_id, $city_id, $area_id);

		if ($this->isPost()) {
            $this->_view->info = $_POST;
            if ($city_id == '') return $this->_view->errors = '请选择地区';
            if ($companyname == '') return $this->_view->errors = '请填写公司名称';
            if ($person == '') return $this->_view->errors = '请填写负责人';
            if ($contact == '') return $this->_view->errors = '请填写联系人';
            if ($mobile == '') return $this->_view->errors = '请填写手机号';
            if (strlen($mobile) != 11) return $this->_view->errors = '手机号必须11位数';
            if ($status == '') return $this->_view->errors = '请选择用户状态';
		    $data = array(
		        'province_id' => $province_id,
		        'city_id' => $city_id,
		        'companyname' => $companyname,
                'person' => $person,
                'contact' => $contact,
                'mobile' => $mobile,
                '`status`' => $status,
                '`describe`' => $describe,
                'create_time' => time()
		    );
            $ret = Service::getInstance('company')->addcompany($data);
            
            if ($ret) {
                if ($_FILES['license']) {
                    $file = Util::getImgPath($_FILES['license'],414,175);
                    $license = implode(',', $file);
                }
                Service::getInstance('company')->upIicenseImg($ret,$license);
            }
            //添加成功编辑分公司日志

            $userinfo = Yaf_Registry::get('developer');
            $goods['log_type'] = 3;
            $goods['action'] = '添加分公司';
            $goods['uid'] = $userinfo['id'];
            $goods['create_time'] = time();
            $goods['action_id'] = $ret;
            Service::getInstance('systemlog')->addLog($goods);

            $this->flash('addressOfficeList','添加成功');
            return false;
		}
    }
    
    public function roleupdateAction()
    {
        $gid = $this->getQuery('id');
        $pid = $this->getPost('company_id');
        $id = ($gid)?$gid:$pid;
        
        $data = $permissionStr = Service::getInstance('company')->roleInfo($id);
        $data[0]['permission'] = explode(',', $data[0]['permission']);
        $data['company_id'] = $id;
        $this->_view->info = $data;
        if ($this->isPost()) {
            $permission = $this->getPost('permission');
            if ($permission == '') return $this->_view->errors = '请选择权限';
            $permission = ($permission) ? implode(',',$permission) :$permission;
            if (!empty($permissionStr[0]['permission'])) {
                $data = array(
                    'permission' => ($permission == $permissionStr[0]['permission'])?$permissionStr[0]['permission']:$permission
                );
                Service::getInstance('company')->roleUpdate($id,$data);
            } else {
                $data = array(
                    'company_id' => $id,
                    'permission' => $permission
                );
                Service::getInstance('company')->roleAdd($data);
            }
            
            $this->flash('/addressOffice/addressOfficeList','修改成功');
            return false;
        }
    }
    /**
     * 公司详情
     */
    public function companyInfoAction()
    {
        $info = Service::getInstance('company')->companyInfo($this->getQuery('id'));
        $info['license'] = Util::getpicbyhash($info['license']);
        $this->_view->jiliancaidan = Service::getInstance('region')->getSelect($info['province_id'], $info['city_id'], $info['area_id']);
        $this->_view->info = $info;
        $warehouse = Service::getInstance('admwarehouse')->companyWarehouseList($this->getQuery('id'));
        foreach ($warehouse as $k => $v) {
            $warehouse[$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
            $warehouse[$k]['status'] = ($v['status'] == 1) ? '正常' : '不正常';
        }
        $this->_view->warehouse = $warehouse;
    }
    /**
     * 经销商账单
     */
    public function warehouseAccountListAction()
    {
        $userinfo = Yaf_Registry::get('developer');
        // 公司
        if( $this->_view->rbac == '*'){
            $company_id = 0;
        }else{
            $company_id = explode(',', $userinfo['company_id']);
        }
        $queryId = $this->getQuery('company_id', 0);
        //$company_id = ($this->_view->rbac == '*') ? $this->getQuery('company_id',0) : $userinfo['company_id'] ;
        $warehouse_id = $this->getQuery('warehouse_id',0);
        // 时间
        $ktime = $this->getQuery('ktime','');
        $gtime = $this->getQuery('gtime','');
        if(trim($ktime) != '' || trim($gtime) != ''){
            $create_time = (!empty($ktime) ? ' wa.addTime>='.strtotime($ktime) : '').((!empty($ktime) && !empty($gtime))?' AND ':'').(!empty($gtime)?' wa.addTime<='.strtotime($gtime).' ':'');
        }
        // 分页
        $page = $this->getQuery('page','')?$this->getQuery('page',''):'1';
        $pageSize = 10;//结束值
        $first = ($page-1)*$pageSize;//起始值
        
        $search = array(
            'addTime' => $create_time,
            'ktime' => $ktime,
            'gtime' => $gtime,
            'company_id' => $company_id,
            'warehouse_id' => $warehouse_id,
            'query_id' => $queryId
        );
        $list = Service::getInstance('company')->warehouseAccountList($first, $pageSize, $search);
        $this->_view->list = $list[0];
        $this->_view->get = $_GET;
        $search['company_id'] = $queryId;
        unset($search['query_id']);
        unset($search['addTime']);
        $url = "http://".$_SERVER['SERVER_NAME']."/addressOffice/warehouseaccountlist?page=__page__&".http_build_query($search);
        $this->_view->paglist = Util::buildPagebar($list[1],$pageSize,$page,$url);
        $this->_view->companyDealersList = Service::getInstance('region')->getCompanyDealersList(array('company'=>$company_id, 'warehouse'=>$warehouse_id,'query_id'=>$queryId));
    }
    
    /**
     * 经销商账单列表
     */
    public function warehouseAccountInfoAction()
    {
        $id = $this->getQuery('id');
        $bill = Service::getInstance('company')->warehouseAccountInfo($id);
        $this->_view->list = $bill['list'];
        $this->_view->vlist = $bill['vOrderList'];
        $this->_view->rlist = $bill['rOrderList'];
    }
    
    /**
     * 分公司账单
     */
    public function companyAccountListAction()
    {
        $developer = Yaf_Registry::get('developer');
        // 公司
        if($this->_view->rbac == '*'){
            $company_id = 0;
        }else{
            $company_id = explode(',', $developer['company_id']);
        }

        $queryId = $this->getQuery('company_id', 0);

        //$company_id = ($this->_view->rbac == '*') ? $this->getQuery('company_id',0):$developer['company_ids'];
        // 时间
        $ktime = $this->getQuery('ktime','');
        $gtime = $this->getQuery('gtime','');
        if(trim($ktime) != '' || trim($gtime) != ''){
            $create_time = (!empty($ktime) ? ' ca.create_time>='.strtotime($ktime) : '').((!empty($ktime) && !empty($gtime))?' AND ':'').(!empty($gtime)?' ca.create_time<='.strtotime($gtime).' ':'');
        }
        // 分页
        $page = $this->getQuery('page','')?$this->getQuery('page',''):'1';
        $pageSize = 10;//结束值
        $first = ($page-1)*$pageSize;//起始值
        
        $search = array(
            'create_time' => $create_time,
            'company_id' => $company_id,
            'query_id' => $queryId
        );
        $list = Service::getInstance('company')->companyAccountList($first, $pageSize, $search);
        $this->_view->list = $list[0];
        $get = $_GET;
        $get['company_id'] = $this->getQuery('company_id', 0);
        $search['company_id'] = $get['company_id'];
        unset($search['query_id']);
        $url = "http://".$_SERVER['SERVER_NAME']."/addressOffice/companyaccountlist?page=__page__&".http_build_query($search);
        $this->_view->get = $get;
        $this->_view->paglist = Util::buildPagebar($list[1],$pageSize,$page,$url);
        if($company_id == 0)
            $this->_view->getCompanyList = Service::getInstance('region')->getCompanyList();
        else
            $this->_view->getCompanyList = Service::getInstance('region')->getCompanyList($this->companyIds);
    }
    /**
     * 账单列表
     */
    public function companyAccountInfoAction()
    {
        // 分页
        $page = $this->getQuery('page','')?$this->getQuery('page',''):'1';
        $pageSize = 5;//结束值
        $first = ($page-1)*$pageSize;//起始值
        
        $data = array(
            'id' => $this->getQuery('id'),
            'cid' => $this->getQuery('cid',0),
            'order_number' => $this->getQuery('order_number'),
            'consignee' => $this->getQuery('consignee'),
            'order_status' => $this->getQuery('order_status'),
            'warehouse_id' => $this->getQuery('warehouse_id')
        );
        
        $url = "http://".$_SERVER['SERVER_NAME']."/addressOffice/companyaccountinfo?page=__page__&".http_build_query($data);
        $this->_view->get = $_GET;
        $list = Service::getInstance('company')->companyAccountInfo($data,$first,$pageSize);
        $this->_view->paglist = Util::buildPagebar($list['count'],$pageSize,$page,$url);
        $this->_view->list = $list['list'];
        $this->_view->getWarehouseList = Service::getInstance('region')->getWarehouseList($data['cid']);
    }
    /**
     * 修改账单状态
     */
    public function updateStatusAction()
    {
        $id = $this->getPost('id');
        $table = $this->getPost('table');
        //添加成功编辑分公司日志
        $userinfo = Yaf_Registry::get('developer');
        $goods['log_type'] = 3;
        $goods['action'] = '确认结算';
        $goods['uid'] = $userinfo['id'];
        $goods['create_time'] = time();
        $goods['action_id'] = $id;
        Service::getInstance('systemlog')->addLog($goods);
        echo json_encode(Service::getInstance('company')->updateStatus($id, $table));
        die;
    }
}