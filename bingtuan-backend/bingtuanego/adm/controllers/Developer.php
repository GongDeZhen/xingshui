<?php

class DeveloperController extends BaseController
{
    public function init()
    {
        parent::init();
    }

    public function adminListAction()
    {
        $company_id = ($this->_view->rbac == '*') ? 0 : $this->companyIds;
        $queryId = $this->getQuery('company_id','');
        if($queryId){
            $search['queryId'] = "tad.company_id like '%$queryId%'";
        }
        $accountName = $this->getQuery('name', '');
        if($accountName){
            $search['name'] = "tad.name like '%$accountName%'";
        }
        $roleId = $this->getQuery('role', '');
        if($roleId){
            $search['roleId'] = "tad.roleId = $roleId";
        }
        $page = $this->getQuery('page','')?$this->getQuery('page',''):'1';
        unset($_GET['page']);
        $this->_view->get = $_GET;
        $pageSize = 20;//结束值
        $first = ($page-1)*$pageSize;//起始值
        //获取角色
        $this->_view->roleList = Service::getInstance('developers')->getRoleAll();
        $url = "http://".$_SERVER['SERVER_NAME']."/developer/adminlist?page=__page__&".http_build_query($_GET);
        $list = Service::getInstance('developers')->adminList($first, $pageSize, $search);
        if($company_id){
            $this->_view->getCompanyList = Service::getInstance('region')->getCompanyList($company_id);//所有公司
        }else{
            $this->_view->getCompanyList = Service::getInstance('region')->getCompanyList();//所有公司
        }
        $this->_view->adm = $list[0];
        $this->_view->paglist = Util::buildPagebar($list[1],$pageSize,$page,$url);
    }
    
    public function deleteAction()
    {
        $id = $this->getPost('id');
        if (Service::getInstance('developers')->delete($id)) {
            //添加删除账号
            $userinfo = Yaf_Registry::get('developer');
            $goods['log_type'] = 1;
            $goods['action'] = '删除账号';
            $goods['uid'] = $userinfo['id'];
            $goods['create_time'] = time();
            $goods['action_id'] = $id;
            $goods['ip'] = $_SERVER["REMOTE_ADDR"];
            Service::getInstance('systemlog')->addLog($goods);
            echo 200;die;
        }
        echo 400;die;
    }

    public function signinAction()
    {
    
        if ($this->isPost()) {
            $username = $this->getPost('username', '');
            $password = $this->getPost('password', '');

            if ($username === '' || $password === '') return $this->error('请输入账号和密码');
            
            $developer = Service::getInstance('developers')->getDeveloperByEmail($username);    //developer表
            
            if (!$developer) {
                $this->error('用户不存在');
                return;
            }
            
            $hash = md5($password.$developer['salt']);
           
            if ($hash !== $developer['pwd']) {
                $this->error('密码错误');
                return;
            }

            //添加登陆日志

            $goods['log_type'] = 1;
            $goods['action'] = '用户登录';
            $goods['uid'] = $developer['id'];
            $goods['create_time'] = time();
            $goods['action_id'] = $developer['id'];
            $goods['ip'] = $_SERVER["REMOTE_ADDR"];
            Service::getInstance('systemlog')->addLog($goods);

            $this->_setCookie($developer, true);
            $this->flash('/dev', '登录成功');
                        
            return false;
        }
    }

    // 账户设置
    public function updateAction()
    {
        $id = $this->getQuery('id');
        $info = Service::getInstance('developers')->info($id);

        $this->_view->companyIds = explode(',', $info['company_id']);
        $this->_view->info = $info;
        $companyList = Service::getInstance('region')->getCompanyListByCity();
        $provinceList = array_column($companyList, 'province_name', 'province_id');
        ksort($provinceList);
        $this->_view->provinceList = $provinceList;
        $cityList = array_column($companyList, 'city_name', 'city_id');
        $list = [];
        foreach($cityList as $k => $v){
            foreach($companyList as $value){
                if($value['city_id'] == $k){
                    $list[$k] = ['province_id' => $value['province_id'], 'city_name' => $v];
                }
            }
        }
        $this->_view->cityList = $list;
        $this->_view->companyList = $companyList;
        //var_dump($companyList);
        $this->_view->roleList = Service::getInstance('developers')->getRoleAll();
    }

    public function editdevAction(){
        $id = $this->getPost('id');
        $roleId = $this->getPost('roleId','');
        $name = $this->getPost('name','');
        $email = $this->getPost('email','');
        $current = $this->getPost('current', '');
        $password = $this->getPost('pwd', '');
        $confirm = $this->getPost('confirm', '');
        $company_id = $this->getPost('companyIds', '');

        $info = Service::getInstance('developers')->info($id);

        $result = ['errorCode' => 1, 'errorMsg' => ''];

        $data = array(
            'company_id' => implode(',', $company_id),
            'name' => $name,
            'roleId' => $roleId,
            'email' => $email
        );

        if ($password != '' || $current != '' || $confirm != '') {
            if (md5($current.$info['salt']) != $info['pwd']){
                $result['errorMsg'] = '原始密码不正确';
                exit(json_encode($result));
            }

            if (md5($password.$info['salt']) == $info['pwd']){
                $result['errorMsg'] = '当前密码与新密码重复，请重新输入';
                exit(json_encode($result));
            }
            if ($password == '' || $password != $confirm){
                $result['errorMsg'] = '密码过短或两次输入不匹配';
                exit(json_encode($result));
            }
            $data['pwd'] = md5($password.$info['salt']);
        }

        if(Service::getInstance('developers')->update($id, $data )){
            //编辑账号
            $userinfo = Yaf_Registry::get('developer');
            $goods['log_type'] = 1;
            $goods['action'] = '编辑账号';
            $goods['uid'] = $userinfo['id'];
            $goods['create_time'] = time();
            $goods['action_id'] = $id;
            $goods['action_json'] = json_encode($data);
            $goods['ip'] = $_SERVER["REMOTE_ADDR"];
            Service::getInstance('systemlog')->addLog($goods);
            $result['errorCode'] = 0;
        }else{
            $result['errorMsg'] = '修改失败!';
        }

        exit(json_encode($result));
    }

    // 重置密码
    public function passwordAction()
    {
        $code = $this->getQuery('code', '');
        if (!$code || !($developer = Service::getInstance('developers')->getDeveloperByCode($code))) {
            $this->fatal('链接不合法');
            return false;
        }

        if ($this->isPost()) {
            $password = $this->getPost('password', '');
            $confirm = $this->getPost('confirm', '');

            if ($password === '' || $password != $confirm) {
                $this->error('密码过短或两次输入不匹配');
                return;
            }

            $pwd = md5($password . $developer['salt']);
            Service::getInstance('developers')->updatePassword($developer['id'], $pwd);

            $message = $developer['pwd'] == '' ? '注册完成' : '密码重置成功';
            $message .= '，正在转向';
            $this->flash('/dev', $message);

            return false;
        }
    }

    public function signupAction()
    {
        $this->_view->roleList = Service::getInstance('developers')->getRoleAll();
        $companyList = Service::getInstance('region')->getCompanyListByCity();
        $provinceList = array_column($companyList, 'province_name', 'province_id');
        ksort($provinceList);
        $this->_view->provinceList = $provinceList;
        $cityList = array_column($companyList, 'city_name', 'city_id');
        $list = [];
        foreach($cityList as $k => $v){
            foreach($companyList as $value){
                if($value['city_id'] == $k){
                    $list[$k] = ['province_id' => $value['province_id'], 'city_name' => $v];
                }
            }
        }
        $this->_view->cityList = $list;
        $this->_view->companyList = $companyList;
    }

    public function addaccountAction(){
        $email = $this->getPost('email', '');
        $password = $this->getPost('pwd', '');
        $confirm = $this->getPost('confirm', '');
        $name = $this->getPost('name', '');
        $company_id = $this->getPost('companyIds', '');
        //var_dump($company_ids); die;
        $roleId = $this->getPost('roleId', '');

        $result = ['errorCode' => 1, 'errorMsg' => ''];
        //$this->_view->user = $_POST;
        $code = md5(uniqid() . '' . rand(111111, 999999));
        $salt = substr($code, 0, 6);

        $developer = Service::getInstance('developers')->getDeveloperByEmail($email);
        if($developer){
            $result['errorMsg'] = '账号已存在!';
            exit(json_encode($result));
        }

        $data = array(
            'name' => $name,
            'email' => $email,
            'pwd' => MD5($password.$salt),
            'salt' => $salt,
            'roleId' => $roleId,
            'company_id' => implode(',', $company_id),
            'create_time' => time()
        );

        $developer = Service::getInstance('developers')->getDeveloperByAdd($data);

        if ($developer) {
            //编辑账号
            $userinfo = Yaf_Registry::get('developer');
            $goods['log_type'] = 1;
            $goods['action'] = '添加账号';
            $goods['uid'] = $userinfo['id'];
            $goods['create_time'] = time();
            $goods['action_id'] = $developer;
            $goods['action_json'] = json_encode($data);
            $goods['ip'] = $_SERVER["REMOTE_ADDR"];
            Service::getInstance('systemlog')->addLog($goods);
            $result['errorCode'] = 0;
        } else {
            $result['errorMsg'] = "添加失败!";
            //return $this->_view->errors = "添加失败";
        }

        exit(json_encode($result));
    }

    public function lostAction()
    {
        if (!$this->isPost()) {
            return;
        }

        $email = $this->getPost('email', '');
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('电子邮箱地址格式不正确');
            return;
        }

        $code = Service::getInstance('developers')->getRegisterCodeByEmail($email);
        $vars = array(
            'email' => $email,
            'link' => sprintf('%s/developer/password?code=%s', Yaf_Application::app()->getConfig()->get('site.url'), $code)
        );
        $this->_view->disableLayout();
        $body = $this->_view->render('sys/mail_lost.phtml', $vars);
        $this->_view->enableLayout();

        $to = $email;
        $subject = '重置您在foxchat.im的密码';

        $conf = Yaf_Application::app()->getConfig();
        Mailer::setDefaultConfig($conf->get('mail'));
        $mail = Mailer::getInstance();
        $mail->From = $conf->get('mail.from');
        $mail->FromName = $conf->get('mail.name');
        $mail->AddAddress($to);
        $mail->IsHTML(true);
        $mail->CharSet = 'utf-8';

        $mail->Subject = $subject;
        $mail->Body    = $body;

        $this->message("一封包含重置密码链接邮件已发送到你的邮箱：{$email}，请点击邮件中的链接重置密码<hr>" . $body);
        //$this->forward('message', array('message' => "", 'body' => $body));
        return false;
    }

    public function signoutAction()
    {
        setcookie('token', false, 0, '/');
        setcookie('number', false, 0, '/');
        $this->flash('/', '已退出');
        return false;
    }

    protected function _setCookie($developer, $keep = true)
    {
        $key = Yaf_Application::app()->getConfig()->get('cookie')->get('key');
        $token = sprintf('%s|%s', $developer['email'], $developer['pwd']);
        $token = Util::strcode($token, $key, 'encode');
        $expired = $keep ? time() + 86400 * 30 : 0;
        setcookie('token', $token, $expired, '/');
    }
    
    public function zoneListAction()
    {
        $this->_view->listID = $this->getQuery('listID');
        $id = $this->getQuery('id');
        $search_name = $this->getQuery('search_name','');
        $page = $this->getQuery('page','')?$this->getQuery('page',''):'1';
        $pageSize = 10;//结束值
        $first = ($page-1)*$pageSize;//起始值
        
        $search = array(
            'search_name'=>$search_name,
        );
        $this->_view->ID = $id;
        $this->_view->adm = Service::getInstance('company')->zoneList($first, $pageSize, $search_name,$id);
    }
    
    public function zoneAction()
    {
        $gid = $this->getQuery('id');
        $pid = $this->getPost('id');
        $id = ($gid) ? $gid : $pid;
        
        $username = $this->getPost('username', '');
        $password = $this->getPost('pwd', '');
        $confirm = $this->getPost('confirm', '');
        $name = $this->getPost('name', '');
        $roleId = $this->getPost('roleId', '');
        $this->_view->ID = $id;
        
        if ($_POST) {
            $this->_view->user = $_POST;
            if ( $name == "" ) return $this->_view->errors = "管理员名称不能为空!";
            if (!preg_match("/^[a-z]+$/",strtolower($username))) return $this->error('账号必须是字母组合而成');
            if (Service::getInstance('developers')->getDeveloperByUserName($username)) return $this->_view->errors = "账号已存在！";
            if ( $username == "" ) return $this->_view->errors = "账号不能为空!";
            if ( $password == '' || $confirm == '') return $this->_view->errors = '密码不能为空!';
            if ( $password != $confirm ) return $this->_view->errors = '重复密码不正确!';
    
            $data = array(
                'name' => $name,
                'username' => $username,
                'pwd' => MD5($password),
                'create_time' => time(),
                'type' => 1
            );
            
            $developer = Service::getInstance('developers')->getDeveloperByAdd($data);
            if ($developer) {
                $this->flash('/developer/adminlist','添加成功');
                return false;
            } else {
                return $this->_view->errors = "添加失败";
            }
        }
    }
    
    #应用管理列表
    public function rbclistAction(){
        //分页
        $page = $this->getQuery('page','')?$this->getQuery('page',''):'1';
        $pageSize = 100;//结束值
        $first = ($page-1)*$pageSize;//起始值

        $url = "http://".$_SERVER['SERVER_NAME']."/developer/rbclist?page=__page__".http_build_query($search);
        $list = Service::getInstance('developers')->rbc_adm_menu($first,$pageSize);
        $this->_view->menulist = $list[0];
        $this->_view->paglist = Util::buildPagebar($list[1],$pageSize,$page,$url);
    }
    
    #添加应用
    public function addrbcAction(){
        if($this->isPost()){
            $this->_view->info=$_POST;
            $arr['name'] = $this->getPost('name');
            if(!$arr['name']) return $this->_view->error='名称不能为空!';
            $arr['controller'] = $this->getPost('controller');
            if(!$arr['controller']) return $this->_view->error='控制器不能为空!';
            $arr['method'] = $this->getPost('method');
            if(!$arr['method']) return $this->_view->error='模块不能为空!';
            if ( Service::getInstance('developers')->inMenu($arr['controller'],$arr['method']) ) {
                return $this->_view->error='此控制器下的模块已存在，请核实再提交！';
            }
            $id = Service::getInstance('developers')->add_adm_menu($arr);
            if($id){
                //编辑账号
                $userinfo = Yaf_Registry::get('developer');
                $goods['log_type'] = 1;
                $goods['action'] = '添加应用';
                $goods['uid'] = $userinfo['id'];
                $goods['create_time'] = time();
                $goods['action_id'] = $id;
                $goods['action_json'] = json_encode($arr);
                $goods['ip'] = $_SERVER["REMOTE_ADDR"];
                Service::getInstance('systemlog')->addLog($goods);
                $this->flash( '/developer/rbclist','添加应用成功!');
                exit;
            }
        }
    }
    
    #编辑应用
    public function updatemenuAction(){
        $id = $this->getQuery('id');
        $menuinfo = Service::getInstance('developers')->getOne_menu($id);
        $this->_view->menuinfo = $menuinfo;
        if($this->isPost()){
            $this->_view->menuinfo=$_POST;
            $arr['name'] = $this->getPost('name');
            if(!$arr['name']) return $this->_view->errors='名称不能为空!';
            $arr['controller'] = $this->getPost('controller');
            if(!$arr['controller']) return $this->_view->errors='控制器不能为空!';
            $arr['method'] = $this->getPost('method');
            if(!$arr['method']) return $this->_view->errors='模块不能为空!';
            $id = $this->getPost('id');
            $bool = true;
            if ($arr['controller'] == $menuinfo['controller'] && $arr['method'] == $menuinfo['method']) {
                $bool = false;
            }
            if ($bool == true) {
                if ( Service::getInstance('developers')->inMenu($arr['controller'],$arr['method']) ) {
                    return $this->_view->errors='此控制器下的模块已存在，请核实再提交！';
                }
            }
            $bool = Service::getInstance('developers')->update_menu($id,$arr);
            if($bool){
                //编辑账号
                $userinfo = Yaf_Registry::get('developer');
                $goods['log_type'] = 1;
                $goods['action'] = '添加应用';
                $goods['uid'] = $userinfo['id'];
                $goods['create_time'] = time();
                $goods['action_id'] = $id;
                $goods['action_json'] = json_encode($arr);
                $goods['ip'] = $_SERVER["REMOTE_ADDR"];
                Service::getInstance('systemlog')->addLog($goods);
                $this->flash( '/developer/rbclist','修改应用成功!');
                exit;
            }
        }
    }

    /**
     * 删除某一个应用
     */
    public function delerbcAction(){
        $id = $this->getPost('id');
        if(!$id) exit(json_encode(array('code'=>'300','msg'=>'删除失败!')));

        $bool = Service::getInstance('developers')->dele_adm_menu($id);
        if($bool) exit(json_encode(array('code'=>'200','msg'=>'删除失败!')));
    }
    /*
     * 角色管理
     */
    public function roleListAction()
    {
        //分页
        $page = $this->getQuery('page','')?$this->getQuery('page',''):'1';
        $name = $this->getQuery('name');
        $this->_view->search = $_GET;
        $pageSize = 10;//结束值
        $first = ($page-1)*$pageSize;//起始值
        $search = array(
            'name'=>$name,
        );
        $url="http://".$_SERVER['SERVER_NAME']."/developer/roleList?page=__page__".http_build_query($search);
        $list = Service::getInstance('developers')->getRoleList($first, $pageSize, $name);
        $this->_view->roleList = $list[0];
        $this->_view->paglist = Util::buildPagebar($list[1],$pageSize,$page,$url);
    }
    
    /**
     * 添加角色
     */
    public function roleAddAction()
    {
        $gid = $this->getQuery('id');
        $pid = $this->getPost('id');
        $id = ($gid) ? $gid : $pid;
        $this->_view->ID = $id;
        $permissionArr = $this->getPost('permission');
        $name = $this->getPost('name');
        $permission = implode(',',$permissionArr);
        $this->_view->roleinfo = Service::getInstance('developers')->menuList();
        $this->_view->default = (!empty($permissionArr) ? $permissionArr : array());
    
        if ($this->isPost()) {
            if ($name == '') return $this->_view->errors = '角色名不能为空';
            $data = array(
                'name' => $name,
                'permission' => ($permissionArr)?$permission:'',
                'create_time' => time()
            );
            if ($ret = Service::getInstance('developers')->roleAdd($data)) {
                //编辑账号
                $userinfo = Yaf_Registry::get('developer');
                $goods['log_type'] = 1;
                $goods['action'] = '添加角色';
                $goods['uid'] = $userinfo['id'];
                $goods['create_time'] = time();
                $goods['action_id'] = $ret;
                $goods['action_json'] = json_encode($data);
                $goods['ip'] = $_SERVER["REMOTE_ADDR"];
                Service::getInstance('systemlog')->addLog($goods);
                $this->flash('/developer/rolelist','添加成功');
                return false;
            } else {
                $this->flash('/developer/rolelist','添加失败');
                return false;
            }
        }
    }
    
    /*
     * 修改角色
     */
    public function roleupdateAction()
    {
        $name = $this->getPost('name');
        $gid = $this->getQuery('id');
        $pid = $this->getPost('id');
        $id = ($gid) ? $gid : $pid;
        $this->_view->ID = $id;
    
        $roleinfo = Service::getInstance('developers')->roleInfo($id);
        $this->_view->name = ($name ? $name:$roleinfo[0]);
        $this->_view->roleinfo = $roleinfo[2];
        $permissionArr = $this->getPost('permission');
        $this->_view->default = (!empty($roleinfo[1]) ? $roleinfo[1] : array());
        
        if ($this->isPost()) {
            if ($name == '') return $this->_view->errors = '角色名不能为空';
            
            sort($permissionArr);
            $permissionArr = implode(',',$permissionArr);
            $data = array(
                'name' => $name,
                'permission' => $permissionArr,
            );
            if (implode(',',$roleinfo[1]) == $data['permission']) {
                unset($data['permission']);
            }
            if ($roleinfo[0] == $name) {
                unset($data['name']);
            }
            
            if (empty($data)) {

                $this->flash('/developer/roleupdate?id='.$id,'角色修改成功');
                return false;
            }
            $ret = Service::getInstance('developers')->customRole($id,$data);
            if ($ret) {
                //编辑账号
                $userinfo = Yaf_Registry::get('developer');
                $goods['log_type'] = 1;
                $goods['action'] = '角色修改';
                $goods['uid'] = $userinfo['id'];
                $goods['create_time'] = time();
                $goods['action_id'] = $id;
                $goods['action_json'] = json_encode($data);
                $goods['ip'] = $_SERVER["REMOTE_ADDR"];
                Service::getInstance('systemlog')->addLog($goods);
                $this->flash('/developer/roleupdate?id='.$id,'角色修改成功');
                return false;
            } else {
                return $this->_view->errors = "角色修改失败";
            }
        }
    }
    
    public function roledeleteAction()
    {
        $gid = $this->getQuery('id');
        $pid = $this->getPost('id');
        $id = ($gid) ? $gid : $pid;
        $this->_view->ID = $id;
        
        $ret = Service::getInstance('developers')->roledelete($id);
        if ($ret) {
            //编辑账号
            $userinfo = Yaf_Registry::get('developer');
            $goods['log_type'] = 1;
            $goods['action'] = '删除角色';
            $goods['uid'] = $userinfo['id'];
            $goods['create_time'] = time();
            $goods['action_id'] = $id;
            $goods['ip'] = $_SERVER["REMOTE_ADDR"];
            Service::getInstance('systemlog')->addLog($goods);
            echo 200;die;
        } else {
            echo 400;die;
        }
    }
    
    #设置权限
//     public function permissionsAction(){
//         $id = $this->getQuery('id');
//         $userinfo = Service::getInstance('developers')->info($id);
//         $roleinfo = Service::getInstance('developers')->roleInfo($userinfo['roleId']);
//         $this->_view->roleinfo = $roleinfo[1];
//         $this->_view->sheng_roleinfo = Service::getInstance('developers')->get_adm_menu_list($roleinfo[0]['permission']);
//     }
}