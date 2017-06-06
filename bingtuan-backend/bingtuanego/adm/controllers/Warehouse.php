<?php
class WarehouseController extends BaseController
{
    public function init()
    {
        parent::init();
    }
    
    public function warehouseListAction()
    {
        $where = '';
        $page = $this->getQuery( 'page',1 );
        $perpage = 10 ;
        $status = $this->getQuery('status');
        
        $userinfo = Yaf_Registry::get('developer');
        // 公司
        if($this->_view->rbac == '*'){
            $companyID = 0;
        }else{
            $companyID = $this->companyIds;
        }
        $queryId = $this->getQuery('company_id',0);
        if($queryId){
            $where['query_id'] = $queryId;
        }
        //$companyID = ($this->_view->rbac == '*') ? $this->getQuery('company_id',0) : $userinfo['company_id'] ;
        if ($companyID) {
            $where['company_id'] = $companyID;
        }
        if($status != ''){
            $where['status'] = $status;
        }
        $warehousename = $this->getQuery('warehousename');
        if($warehousename){
            $where['warehousename'] = $warehousename;
        }
        $data = Service::getInstance('admwarehouse')->warehouseList( $page, $perpage, $where );
        $href = $_SERVER['REQUEST_URI'];
        //分页控制
        if(strrpos($href, '?') == false ){
            $url = $url = $uri .'?'. 'page=__page__';
        }else{
            $url = strrpos($_SERVER['REQUEST_URI'], '&page='.$page) == false ? str_replace('page='.$page, '', $href ).'&page=__page__' : str_replace('page='.$page, '', $href ).'page=__page__';
        }
        $pagebar = Util::buildPagebar( $data['total'], $perpage, $page, $url );
        $this->_view->list = $data['list'];
        $get = $_GET;

        //添加登陆日志
        $goods['log_type'] = 2;
        $goods['action'] = '经销商列表';
        $goods['uid'] = $userinfo['id'];
        $goods['create_time'] = time();
        $goods['action_id'] = $userinfo['id'];
        Service::getInstance('systemlog')->addLog($goods);

        $get['company_id'] = $queryId;
        $this->_view->info = $get;
        $this->_view->pagebar = $pagebar;
        $this->_view->provincelist =  Service::getInstance('region')->getProvinceList();
        if($companyID){
            $this->_view->getCompanyList = Service::getInstance('region')->getCompanyList($companyID);
        }else{
            $this->_view->getCompanyList = Service::getInstance('region')->getCompanyList();
        }

    }

    public function editAction(){
        $userinfo = Yaf_Registry::get('developer');
        $gid = $this->getQuery('id');
        $pid = $this->getPost('id');
        $id = ($gid)?$gid:$pid;
        
        $info = Service::getInstance('warehouse')->warehouseInfo($id);

        $email = $this->getPost('email');
        $warehousename = $this->getPost('warehousename');
        $company_id = $this->getPost('company_id');
        $contact = $this->getPost('contact');
        $mobile = $this->getPost('mobile');
        $status = $this->getPost('status');
        $describe = $this->getPost('describe');
        $uptime = $this->getPost('uptime');
        $this->_view->ID = $id;
        $this->_view->info = ($_POST)?$_POST:$info;
        $this->_view->getCompanyList = Service::getInstance('region')->getCompanyList();

        if ($this->isPost()) {
            if ($company_id == 0) return $this->_view->errors = '请选择公司'; 
            if ($warehousename == '') return $this->_view->errors = '请填写经销商名称';
            if ($email == '') return $this->_view->errors = '请填写邮箱';
            if ($contact == '') return $this->_view->errors = '请填写联系人';
            if ($mobile == '') return $this->_view->errors = '请填写手机号';
            //if (strlen($mobile) != 11) return $this->_view->errors = '手机号必须11位数';
            if ($status == '') return $this->_view->errors = '请选择运营状态';
            if ($describe == '') return $this->_view->errors = '请填写经销商描述';
            if ($uptime == '') return $this->_view->errors = '请选择经销商上线时间';
            $uptime =strtotime($uptime);
            $data = array(
                'warehousename' => $warehousename,
                'email' => $email,
                'contact' => $contact,
                'mobile' => $mobile,
                '`status`' => $status,
                '`describe`' => $describe,
                'company_id' => $company_id,
                'create_time' => time(),
                'uptime'=>$uptime

            );
            
            $ret = Service::getInstance('admwarehouse')->updatewarehouse($id, $data);
            if ($ret) {
                if ($info['codeImg'] == '' || !file_exists($info['codeImg'])) {
                    include_once('./phpqrcode.php');
                    // 二维码数据
                    $codeImg = md5('xingshui_'.$id);
                    $key = Yaf_Application::app()->getConfig()->get('cookie')->get('key');
                    $data = Util::strcode('xingshui_'.$id, $key, 'encode');
                    // 生成的文件名
                    $path = Yaf_Application::app()->getConfig()->get('image')->get('dir').'codeImg/'; // 获取图片目录路径
                    $filename = $path.$codeImg.".png";
                    // 创建文件目录
                    if ( !file_exists( $path ) )
                    {
                        $rs = Util::makedir($path ,0777);
                    }
                    // 纠错级别：L、M、Q、H
                    $errorCorrectionLevel = 'L';
                    // 点的大小：1到10
                    $matrixPointSize = 5;
                    QRcode::png($data, $filename, $errorCorrectionLevel, $matrixPointSize, 2);
                    Service::getInstance('admwarehouse')->upIicenseImg($id,$codeImg);
                }

                //编辑用户
                $goods['log_type'] = 2;
                $goods['action'] = '编辑经销商';
                $goods['uid'] = $userinfo['id'];
                $goods['create_time'] = time();
                $goods['action_id'] = $id;
                $goods['action_json'] = json_encode($data);
                $goods['ip'] = $_SERVER["REMOTE_ADDR"];
                Service::getInstance('systemlog')->addLog($goods);
                $this->flash('warehouselist','编辑成功');
            } else {
                $this->flash("/warehouse/edit?id=$id",'编辑失败');
            }
            return false;
        }
    }

    public function addAction()
    {
        $userinfo = Yaf_Registry::get('developer');
        $email = $this->getPost('email');
        $warehousename = $this->getPost('warehousename');
        $company_id = $this->getPost('company_id');
        $contact = $this->getPost('contact');
        $mobile = $this->getPost('mobile');
        $status = $this->getPost('status');
        $describe = $this->getPost('describe','');
        $uptime = $this->getPost('uptime');
        $this->_view->getCompanyList = Service::getInstance('region')->getCompanyList();

        if ($this->isPost()) {
            $this->_view->info = $_POST;
            if ($company_id == '') return $this->_view->errors = '请选择公司';
            if ($warehousename == '') return $this->_view->errors = '请填写经销商名称';
            if ($contact == '') return $this->_view->errors = '请填写联系人';
            if ($email == '') return $this->_view->errors = '请填写正确邮箱';
            if ($mobile == '') return $this->_view->errors = '请输入正确手机号';
            //if (strlen($mobile) != 11) return $this->_view->errors = '请输入正确手机号';
            if ($status == '') return $this->_view->errors = '请选择用户状态';
            if ($uptime == '') return $this->_view->errors = '请选择经销商上线时间';
            $uptime =strtotime($uptime);
            $data = array(
                'company_id' => $company_id,
                'warehousename' => $warehousename,
                'email' => $email,
                'contact' => $contact,
                'mobile' => $mobile,
                '`status`' => $status,
                '`describe`' => $describe,
                'create_time' => time(),
                'uptime'=>$uptime
            );

            $ret = Service::getInstance('admwarehouse')->addwarehouse($data);
            $arr = $data;
            if ($ret) {
                include_once('./phpqrcode.php');
                // 二维码数据
                $codeImg = md5('xingshui_'.$ret);
                $key = Yaf_Application::app()->getConfig()->get('cookie')->get('key');
                $data = Util::strcode('xingshui_'.$ret, $key, 'encode');
                // 生成的文件名
                $path = Yaf_Application::app()->getConfig()->get('image')->get('dir').'codeImg/'; // 获取图片目录路径
                $filename = $path.$codeImg.".png";
                // 创建文件目录
                if ( !file_exists( $path ) )
                {
                    Util::makedir($path ,0777);
                }
                // 纠错级别：L、M、Q、H
                $errorCorrectionLevel = 'L';
                // 点的大小：1到10
                $matrixPointSize = 5;
                QRcode::png($data, $filename, $errorCorrectionLevel, $matrixPointSize, 2);
                Service::getInstance('admwarehouse')->upIicenseImg($ret,$codeImg);
            }
            //添加登陆日志id
            $goods['log_type'] = 2;
            $goods['action'] = '添加经销商';
            $goods['uid'] = $userinfo['id'];
            $goods['create_time'] = time();
            $goods['action_id'] = $ret;
            $goods['action_json'] = json_encode($arr);
            $goods['ip'] = $_SERVER["REMOTE_ADDR"];
            Service::getInstance('systemlog')->addLog($goods);
            //添加结束


            $this->flash('warehouselist','添加成功');
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
     * 经销商详情
     */
    public function warehouseInfoAction()
    {
        $info = Service::getInstance('warehouse')->warehouseInfo($this->getQuery('id'));
        $path = Yaf_Application::app()->getConfig()->get('image')->get('dir'); // 获取图片目录路径
        $codeImg = $path."codeImg/".$info['codeImg'].".png";
        if (!file_exists($codeImg)) {
            $info['codeImg'] ='/img/log/undefined.jpg';
        } else {
            $path = Yaf_Application::app()->getConfig()->get('image')->get('url'); // 获取图片目录路径
            $info['codeImg'] = $path."/codeImg/".$info['codeImg'].".png";
        }
        $this->_view->getCompanyList = Service::getInstance('region')->getCompanyList($info['company_id']);
        $this->_view->info = $info;
    }
}