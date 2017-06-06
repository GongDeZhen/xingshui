<?php
/*
 * 商户列表
 */
class UsersController extends BaseController
{
    public function init()
    {
        parent::init();
    }
    
    public function shoplistAction()
    {

      $perpage = 10 ;
      $page = $this->getQuery('page',1);
      $province_id = $this->getQuery('province_id', 0);
      $city_id = $this->getQuery('city_id', 0);
      $area_id = $this->getQuery('area_id', 0);
      $status = $this->getQuery('status','');
      $keyword = $this->getQuery('keyword','');
      $where = '';

      switch ($this->_developer['type']) {
        case'1':
          if($province_id) $where[] = " province_id = $province_id ";
          if($city_id) $where[] = " city_id = $city_id ";
          if($area_id) $where[] = " area_id = $area_id ";
          break;
        case'2':
          $city_id = ($city_id == 0) ? $this->_developer['city_id'] : $city_id;
          if($city_id) $where[] = " city_id = $city_id ";
          if($area_id) $where[] = " area_id = $area_id ";
          break;
        case'3':
          $where[] = " area_id = {$this->_developer['area_id']} ";
          break;
      }

      if($status === '0' || $status === '1' ){
          $where[] =" status = ".$status ;
      }
      if($keyword != ''){
          $where[] = " real_name like '%{$keyword}%' ";
      }
      $data = Service::getInstance('user')->shopuserlists($page,$perpage,$where );
      $href = $_SERVER['REQUEST_URI'] ;
      //分页控制
      if(strrpos($href, '?') === false ){ 
          $url = $url = $uri .'?'. 'page=__page__';
      }else{
          $url = strrpos($_SERVER['REQUEST_URI'], '&page='.$page) === false ? str_replace('page='.$page, '', $href ).'&page=__page__' : str_replace('page='.$page, '', $href ).'page=__page__';
      }
      
      $pagebar = Util::buildPagebar( $data['total'], $perpage, $page, $url );
      $this->_view->provincelist =  Service::getInstance('region')->getProvinceList();
      $this->_view->jiliancaidan = Service::getInstance('Admgoods')->getCAIDAN();
      $this->_view->list = $data['list'];
      $this->_view->pagebar = $pagebar;
    }
    
    //编辑详情
    public function shopdetailAction()
    {
       $id = $this->getQuery('id',0);
       if ( !$id ) return ;

       if( $this->isPost() ){
            $arr['status'] = $this->getPost('status');
            if(!$arr['status']) return $this->_view->errors='状态不能为空!';
            $arr['shop_name'] = $this->getPost('shop_name');
            if(!$arr['shop_name']) return $this->_view->errors='店铺名字不能为空!';
            $arr['province_id'] = $this->getPost('province_id');
            if(!$arr['province_id']) return $this->_view->errors='省不能为空!';
            $arr['city_id'] = $this->getPost('city_id');
            if(!$arr['city_id']) return $this->_view->errors='市不能为空!';
            $arr['area_id'] = $this->getPost('area_id');
            if(!$arr['area_id']) return $this->_view->errors='区不能为空!';
            $arr['address'] = $this->getPost('address');
            if(!$arr['address']) return $this->_view->errors='地址不能为空!';
            $arr['id_card_number'] = $this->getPost('id_card_number');
            if(!$arr['id_card_number']) return $this->_view->errors='身份证不能为空!';
            $id = $this->getPost('id');
            if(!$id) return $this->_view->errors='参数错误!';

            $userinfo = Service::getInstance('Users')->getUserInfo($id);
            if($_FILES['business_license']){
                $file = Util::getImgPath($_FILES['business_license'],300,200);
                if($file){
                    $arr['business_license']=$file['path'];
                    Util::delImage($userinfo['business_license']);
                }
            }

            if(Service::getInstance('user')->updateUser($arr,$id) ){
                $this->flash('/users/shoplist','操作成功');
                exit;
            }else{
                $this->flash( '/users/shoplist',Service::getInstance('user')->getError());
                exit;
            }
       }
       $user = Service::getInstance('user')->getUserInfo( $id );

       $user['bankcard'] = Service::getInstance('user')->bankcard($user['id'] );
       $user['business_license'] = Util::getpicbyhash($user['business_license']);
       $this->_view->jiliancaidan = Service::getInstance('developers')->getSelect(
           ($arr['province_id'] == '' ? $user['province_id'] : $arr['province_id']),
           ($arr['city_id'] == '' ? $user['city_id'] : $arr['city_id']),
           ($arr['area_id'] == '' ? $user['area_id'] : $arr['area_id'])
       );
       $this->_view->statusList = array('2'=>'身份证错误','3'=>'营业执照错误','1'=>'通过','4'=>'拉黑');
       $this->_view->userInfo = $user;

    }
    
    /**
     * 普通用户
     */
    public function userlistAction()
    {
        $where = '';
        $userinfo = Yaf_Registry::get('developer');
        // 公司、经销商
        if($this->_view->rbac == '*'){
            $companyID = 0;
        }else{
            $companyID = $this->companyIds;
        }
        $queryId = $this->getQuery('company_id',0);
        $where['query_id'] = $queryId;
        //$companyID = ($this->_view->rbac == '*') ? $this->getQuery('company_id',0) : $userinfo['company_id'] ;
        $where['company_id'] = $companyID;
        $warehouse = $this->getQuery('warehouse_id', 0);
        $where['warehouse_id'] = $warehouse;
        // 分页
        $perpage = 10 ;
        $page = $this->getQuery('page',1);
        // 审核状态
        $status = $this->getQuery('auditType','');
        if ($status >= 0) {
            $where['auditType'] = $status;
        }
        // 账号
        $account = $this->getQuery('account','');
        if ($account) {
            $where['account'] = $account;
        }
        // 搜索用户
        $userName = $this->getQuery('userName','');
        if ($userName) {
            $where['userName'] = $userName;
        }
        
        $data = Service::getInstance('admuser')->userlists($page,$perpage,$where);
        
        $href = $_SERVER['REQUEST_URI'] ;
        //分页控制
        if(strrpos($href, '?') === false ){
            $url = $url = $uri .'?'. 'page=__page__';
        }else{
            $url = strrpos($_SERVER['REQUEST_URI'], '&page='.$page) === false ? str_replace('page='.$page, '', $href ).'&page=__page__' : str_replace('page='.$page, '', $href ).'page=__page__';
        }
        $pagebar = Util::buildPagebar( $data['total'], $perpage, $page, $url );
        $this->_view->list =  $data['list'] ;
        $this->_view->get = $_GET;
        $this->_view->pagebar = $pagebar;
        $this->_view->companyDealersList = Service::getInstance('region')->getCompanyDealersList(array('company'=>$where['company_id'], 'warehouse'=>$where['warehouse_id'],'query_id'=>$queryId));
    }
    /**
     * 修改用户信息
     * @return void|boolean
     */
    public function userdetailAction()
    {
        $gid = $this->getQuery('id',0); 
        $pid = $this->getPost('id');
        $id = ($gid)?$gid:$pid;
        if ( !$id ) return ;
        
        $user = Service::getInstance('admuser')->userInfo( $id );
        $this->_view->warehouseList = Service::getInstance('region')->getWarehouseList($user['company_id']);
        $this->_view->userInfo = $user;
        $this->_view->userAssets = explode(',', $user['assets']);
        
        // 公司、经销商
        $companyID = $user['company_id'] ;
        $company_id = $this->companyIds;
        $warehouse = $this->getPost('warehouse_id', $user['cid']);
        $this->_view->companyDealersList = Service::getInstance('region')->getCompanyDealersList(array('company'=>$company_id, 'warehouse'=>$warehouse,'query_id'=> $companyID));
        if( $this->isPost() ){
            $arr['cid'] = $warehouse;
            if(!$arr['cid']) return $this->_view->errors = '请选择经销商';
            $arr['userName'] = $this->getPost('userName');
            $arr['telphone'] = $this->getPost('telphone');
            $arr['userName'] = $this->getPost('userName');
            $arr['comName'] = $this->getPost('comName');
            //$arr['userName'] = $this->getPost('address');
            $arr['email'] = $this->getPost('email');
            $arr['updateTime'] = date('Y-m-d H:i:s');
            $arr['assets'] = $this->getPost('asset') ? implode(',', $this->getPost('asset')) : '';

            if(!$id){
                $this->flash('/users/userlist','参数错误');
                return false;
            }
            if(Service::getInstance('admuser')->updateUser($arr,$id) ){
                $this->flash('/users/userlist','操作成功');
                return false;
            }else{
                $this->flash( '/users/userlist',Service::getInstance('admuser')->getError());
                return false;
            }
        }
    }

    /*
     * 拉黑用户
     */
    public function blacklistAction()
    {
        if($this->isPost()){
            $user_id = $this->getPost('id');
            $blackType = $this->getPost('blackType');
            $remarks = $this->getPost('remarks');
            if(!$blackType) return $this->_view->errors='原因不能为空!';
            $array = array(
                'user_id' => $user_id,
                'reason' => $blackType,
                'remarks' => $remarks,
                'creat_time'=>time()

            );
            $result = Service::getInstance('admuser')->dodel($array);
            //var_dump($result);exit;
            if (!$result){
                $this->flash('/users/userlist','操作成功');
                exit;
            }
            $this->flash('/users/userlist','操作不成功');
            exit;
        }
    }


    /**
     * 审核
     */
    public function cancelStockAction()
    {
        $gid = $this->getQuery('id',0);
        $pid = $this->getPost('id');
        $id = ($gid)?$gid:$pid;
        
        $user = Service::getInstance('admuser')->userInfo( $id );
        $this->_view->info = $user;

        if ($this->isPost()) {
            $post = $_POST;
            if($post['auditType'] == '2'){
                if(!$user['comName'] || !$user['userName'] || !$user['telphone']){
                    return $this->_view->errors = '用户信息不完整,不能完成审核!';
                }
            }
            $post['id'] = $id;
            $this->_view->info = $post;
            $array = array(
                'user_type' => $user['user_type'],
                'auditType' => $user['auditType'],
                'auditContent' => $user['auditContent'],
                'refundTime' => $user['refundTime'],
                'refundRules' => $user['refundRules'],
                'refundMoney' => $user['refundMoney']
            );
            
            if (Service::getInstance('admuser')->cancelStock($post,$array)) {
                if($post['auditType'] == '2' && $post['auditType'] != $user['auditType']){
                    $phone = $user['account'];
                    Util::sendsms($phone, '你的账户已通过验证!');
                    $push = new Push();
                    $push->sendIOSUnicast($user['device_token'],"你的账户已通过验证");
                }
                $this->flash('/users/userlist','审核成功');
                return false;
            } else {
                return $this->_view->errors = '审核失败';
            }
        }
    }
    /**
     * 拉黑
     * @return void|boolean
     */
    public function dodelAction()
    {
        $id = $this->getQuery('id',0);
        if(!$id) return;
        Service::getInstance('admuser')->dodel($id);
        $this->flash('/users/userlist','操作成功');
        return false;
    }
    
    // 重置密码
    public function updatePasswordAction()
    {
        if ($this->isPost()) {
            $password = $this->getPost('password', '');
            $confirm = $this->getPost('confirm', '');
            $current = $this->getPost('current', '');
            $id = $this->getPost('id', '');
            if ($password === '' || $password != $confirm) {
                $this->respon(0, '密码过短或两次输入不匹配');
            }
            $user = Service::getInstance('admuser')->userInfo( $id );
            if (md5($current) != $user['password']) {
                $this->respon(0, '原密码错误，请核实再提交');
            }
            $pwd = md5($password);
            if ($pwd == $user['password']) {
                $this->respon(0, '密码重置成功');
            }
            
            Service::getInstance('admuser')->updatePassword($id, $pwd);
            $this->respon(0, '密码重置成功');
        }
        return;
    }
    /**
     * 导入用户
     * @return boolean
     */
    public function batchRegAction()
    {
        $userinfo = Yaf_Registry::get('developer');
        // 公司、经销商
        $companyID = ($this->_view->rbac == '*') ? 0 : $this->companyIds;
        $queryId = $this->getPost('company_id',0);
        $warehouse = $this->getPost('warehouse_id', 0);
        $this->_view->companyDealersList = Service::getInstance('region')->getCompanyDealersList(array('company'=>$companyID, 'warehouse'=>$warehouse,'query_id'=> $queryId));

        if ($this->isPost()) {
            $warehouse_id = $this->getPost('warehouse_id');
            if ($warehouse_id <= 0) {
                return $this->_view->errors='请选择经销商';
            }
            if ($_FILES['excel']['error'] == 0 && $_FILES['excel']['size'] > 0) {
                $return = false;
                try {
                    $file = $_FILES['excel'];
                    $inputFileName = $file['tmp_name'];
                    $file_types = explode ( ".", $file['name'] );
                    $file_type = $file_types [count ( $file_types ) - 1];
                    
                    /*判别是不是.xls文件，判别是不是excel文件*/
                    if ( strtolower( $file_type ) != 'xlsx' )
                    {
                        return $this->_view->errors='不是Excel文件，重新上传';
                    }
                    
                    $inputFileType = 'Excel2007';//这个是计xlsx的
                    $objReader = PHPExcel_IOFactory::createReader($inputFileType);
                    $objPHPExcel = $objReader->load($inputFileName);
                    $objWorksheet = $objPHPExcel->getActiveSheet();//取得总行数
                    $highestRow = $objWorksheet->getHighestRow();//取得总列数
                    
                    $highestColumn = $objWorksheet->getHighestColumn();
                    $highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn);//总列数
                    $info = array();
                    for ($row = 2;$row <= $highestRow;$row++)
                    {
                        $data = array();
                        //注意highestColumnIndex的列数索引从0开始
                        for ($col = 0;$col < $highestColumnIndex;$col++)
                        {
                            $value = $objWorksheet->getCellByColumnAndRow($col, $row)->getValue();
                            if ($col == 6) {
                                $data[$col] = $value;
                            } else {
                                if (!empty($value)) {
                                    $data[$col] = $value;
                                }
                            }
                        }
                        if (count($data) == 8) {
                            $info[] = $data;
                            $pwd = mt_rand(100000,999999);
                            $user = array(
                                'account' => $data[1],
                                'password' => $pwd,
                                'visible' => $pwd,
                                'cid' => $warehouse,
                                'userName' => $data[4],
                                'email' => $data[6],
                                'comName' => $data[0],
                                'address' => $data[7],
                                'telphone' => $data[5],
                                'user_type' => 1,
                                'auditType' => 2,
                                'auditTime' => time(),
                                'createTime' => time()
                            );
                            Service::getInstance('user')->register( $user );
                        }
                    }
                    $return = true;
                } catch (Exception $e) {
                    return $this->_view->errors='导入失败';
                }
                
                if ($return) {
                    $this->flash('/users/userlist','导入成功');
                    return false;
                }
            }
        }
    }
    /**
     * 导出用户
     */
    public function exportAction()
    {
        set_time_limit(1800);
        $userinfo = Yaf_Registry::get('developer');

        // 公司、经销商
        $companyID = ($this->_view->rbac == '*') ? 0 : $this->companyIds ;
        $warehouse = $this->getPost('warehouse_id', '');

        //增加query_id 11.14
        $queryId = $this->getPost('company_id',0);

        $this->_view->companyDealersList = Service::getInstance('region')->getCompanyDealersList(array('company'=>$companyID, 'warehouse'=>$warehouse,'query_id'=>$queryId));
        if ($this->isPost()) {
            $where['company_id'] = $companyID;
            $where['warehouse_id'] = $warehouse;
            $where['query_id'] = $queryId;
            $data = Service::getInstance('admuser')->userlists('','',$where);
            if ($data['total'] > 0) {
                $objPHPExcel = new PHPExcel();
                $objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
                $objPHPExcel->getActiveSheet()->setCellValue('A1', '店铺名称');
                $objPHPExcel->getActiveSheet()->setCellValue('B1', '用户名');
                $objPHPExcel->getActiveSheet()->setCellValue('C1', '公司');
                $objPHPExcel->getActiveSheet()->setCellValue('D1', '经销商');
                $objPHPExcel->getActiveSheet()->setCellValue('E1', '联系人');
                $objPHPExcel->getActiveSheet()->setCellValue('F1', '联系电话');
                $objPHPExcel->getActiveSheet()->setCellValue('G1', '邮箱');
                $objPHPExcel->getActiveSheet()->setCellValue('H1', '地址');
                $objPHPExcel->getActiveSheet()->setCellValue('I1', '密码');
                $objPHPExcel->getActiveSheet()->setCellValue('J1', '注册时间');
                $objPHPExcel->getActiveSheet()->setCellValue('K1', '审核时间');
                $objPHPExcel->getActiveSheet()->setCellValue('L1', '审核状态');
                $objPHPExcel->getActiveSheet()->setCellValue('M1', '公司id');
                $objPHPExcel->getActiveSheet()->setCellValue('N1', '经销商id');
                $e = 0;
                foreach ($data['list'] as $i => $v) {
                    $e = $i+2;
                    $objPHPExcel->getActiveSheet()->setCellValueExplicit('A' . $e, $v['comName']);
                    $objPHPExcel->getActiveSheet()->setCellValue('B' . $e, $v['account']);
                    $objPHPExcel->getActiveSheet()->setCellValue('C' . $e, $v['companyName']);
                    $objPHPExcel->getActiveSheet()->setCellValue('D' . $e, $v['warehouseName']);
                    $objPHPExcel->getActiveSheet()->setCellValue('E' . $e, $v['userName']);
                    $objPHPExcel->getActiveSheet()->setCellValue('F' . $e, $v['telphone']);
                    $objPHPExcel->getActiveSheet()->setCellValue('G' . $e, $v['email']);
                    $objPHPExcel->getActiveSheet()->setCellValue('H' . $e, $v['address']);
                    $objPHPExcel->getActiveSheet()->setCellValue('I' . $e, $v['visible']);
                    $objPHPExcel->getActiveSheet()->setCellValue('J' . $e, date('Y-m-d',$v['createTime']));
                    $objPHPExcel->getActiveSheet()->setCellValue('K' . $e, date('Y-m-d',$v['auditTime']));
                    switch ($v['auditType']) {
                        case 0:
                            $status = '审核中';
                            break;
                        case 1:
                            $status = '审核不通过';
                            break;
                        case 2:
                            $status = '审核通过';
                            break;
                        case 3:
                            $status = '已拉黑';
                            break;
                    }
                    $objPHPExcel->getActiveSheet()->setCellValue('L' . $e, $status);
                    $objPHPExcel->getActiveSheet()->setCellValue('M' . $e, $v['company_id']);
                    $objPHPExcel->getActiveSheet()->setCellValue('N' . $e, $v['cid']);
                }
                
                $FileName = 'bing_tuan_users.xlsx';
                // sheet名称
                $objPHPExcel->getActiveSheet()->setTitle('用户信息');
                $objPHPExcel->setActiveSheetIndex(0);
                //到文件
                // $objWriter->save($FileName);
                
                //到浏览器
                header("Content-Type: application/force-download");
                header("Content-Type: application/octet-stream");
                header("Content-Type: application/download");
                header('Content-Disposition:inline;filename="'.$FileName.'"');
                header("Content-Transfer-Encoding: binary");
                header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
                header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
                header("Pragma: no-cache");
                $objWriter->save('php://output');
                unset($data);die;
            }
        }
    }

    /**
     * 导出用户资产
     */
    public function exportAssetsAction()
    {
        set_time_limit(1800);
        $userinfo = Yaf_Registry::get('developer');

        // 公司、经销商
        $companyID = ($this->_view->rbac == '*') ? $this->getPost('company_id',0) : $userinfo['company_id'] ;
        $warehouse = $this->getPost('warehouse_id', '');

        //增加query_id 11.14
        $queryId = $this->getQuery('company_id',0);

        $this->_view->companyDealersList = Service::getInstance('region')->getCompanyDealersList(array('company'=>$companyID, 'warehouse'=>$warehouse,'type'=>($this->_view->rbac == '*' ? '0' : '1'),'query_id'=>$queryId));
        if ($this->isPost()) {
            $where['company_id'] = $companyID;
            $where['warehouse_id'] = $warehouse;
            $data = Service::getInstance('admuser')->userlists('','',$where);
            if ($data['total'] > 0) {
                $objPHPExcel = new PHPExcel();
                $objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
                $objPHPExcel->getActiveSheet()->setCellValue('A1', '店铺名称');
                $objPHPExcel->getActiveSheet()->setCellValue('B1', '冰柜');
                $e = 2;
                foreach ($data['list'] as $i => $v) {
                    if($v['assets']){
                        $assets = explode(',', $v['assets']);
                        foreach($assets as $k => $value){
                            $e += $k;
                            $objPHPExcel->getActiveSheet()->setCellValueExplicit('A' . $e, $v['comName']);
                            $objPHPExcel->getActiveSheet()->setCellValue('B' . $e, $value);
                        }
                    }else{
                        $e += $i;
                        $objPHPExcel->getActiveSheet()->setCellValueExplicit('A' . $e, $v['comName']);
                        $objPHPExcel->getActiveSheet()->setCellValue('B' . $e, '未设置');
                    }

                }

                $FileName = 'bing_tuan_users_assets.xlsx';
                // sheet名称
                $objPHPExcel->getActiveSheet()->setTitle('用户资产信息');
                $objPHPExcel->setActiveSheetIndex(0);
                //到文件
                // $objWriter->save($FileName);

                //到浏览器
                header("Content-Type: application/force-download");
                header("Content-Type: application/octet-stream");
                header("Content-Type: application/download");
                header('Content-Disposition:inline;filename="'.$FileName.'"');
                header("Content-Transfer-Encoding: binary");
                header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
                header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
                header("Pragma: no-cache");
                $objWriter->save('php://output');
                unset($data);die;
            }
        }
    }

    /**
     * 添加用户
     */
    public function adduserAction()
    {
        $userinfo = Yaf_Registry::get('developer');
        // 公司、经销商
        if($this->_view->rbac == '*'){
            $companyID = 0;
        }else{
            $companyID = $this->companyIds;
        }
        $queryId = $this->getQuery('company_id',0);
        //$companyID = ($this->_view->rbac == '*') ? $this->getPost('company_id',0) : $userinfo['company_id'] ;
        $warehouse = $this->getPost('warehouse_id', '');
        $this->_view->companyDealersList = Service::getInstance('region')->getCompanyDealersList(array('company'=>$companyID, 'warehouse'=>$warehouse,'query_id'=>$queryId));
        
        if ($this->isPost()) {
            $data['comName'] = $this->getPost('comName');
            $data['account'] = $this->getPost('account');
            $data['cid'] = $this->getPost('warehouse_id');
            $data['userName'] = $this->getPost('userName');
            $data['telphone'] = $this->getPost('telphone');
            $data['address'] = $this->getPost('address');
            $data['email'] = $this->getPost('email');
            $data['password'] = md5($this->getPost('password'));
            $data['visible'] = $this->getPost('password');
            $data['user_type'] = 1;
            $data['auditTime'] = $data['createTime'] = time();
            $data['auditType'] = 2;
            
            if (!empty($_POST)) {
                if (Service::getInstance('admuser')->adduser($data)) {
                    $this->respon(1, '添加成功');
                } else {
                    $this->respon(0, '添加失败');
                }
            } else {
                $this->respon(0, '请求失败');
            }
        }
    }
}