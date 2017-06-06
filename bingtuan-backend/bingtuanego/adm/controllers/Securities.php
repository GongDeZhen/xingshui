<?php
/**
 * 代金券管理控制类
 */
class SecuritiesController extends BaseController
{
    /**
     *数据模型对象
     */
    public function init()
    {
        parent::init();
    }
    
    /**
     * 代金券列表
     */
    public function listAction()
    {
        $userinfo = Yaf_Registry::get('developer');
        $page = $this->getQuery('page','')?$this->getQuery('page',''):'1';
        $pageSize = 10;//结束值
        $first = ($page-1)*$pageSize;//起始值
        //时间
        $ktime = $this->getQuery('ktime','');
        $gtime = $this->getQuery('gtime','');
        $company_id = ($this->_view->rbac == '*') ? $this->getQuery('company_id',0) : $this->companyIds ;
        $queryId = $this->getQuery('company_id',0);
        $where = '';
        if(trim($ktime) != '' || trim($gtime) != ''){
            $where['create_time'] = (!empty($ktime) ? ' c.createdTime>='.strtotime($ktime) : '').((!empty($ktime) && !empty($gtime))?' and ':'').(!empty($gtime)?' c.createdTime<='.strtotime($gtime).' ':'');
        }
        if ($company_id > 0) {
            $where['company_id'] = $company_id;
        }
        if($queryId){
            $where['query_id'] = $queryId;
        }
        $search = array(
            'company_id'=> $queryId,
            'ktime' => $ktime,
            'gtime' => $gtime
        );
        $url = "http://".$_SERVER['SERVER_NAME']."/securities/list?page=__page__&".http_build_query($search);
        $list = Service::getInstance('Admsecurities')->getSecuritiesList($first,$pageSize,$where);
        $this->_view->list = $list[0];
        $get = $_GET;
        $get['company_id'] = $queryId;
        $this->_view->get = $get;
        if($company_id){
            $this->_view->getCompanyList = Service::getInstance('region')->getCompanyList($company_id);
        }else{
            $this->_view->getCompanyList = Service::getInstance('region')->getCompanyList();
        }

        $this->_view->paglist = Util::buildPagebar($list[1],$pageSize,$page,$url);
    }
    
    /**
     * 添加代金券
     * @return [type] [description]
     */
    public function addAction()
    {
        $companyID = ($this->_view->rbac == '*') ? $this->getPost('cid',0) : $this->companyIds;
        $company_id = $this->getPost('cid',0);
        if($companyID){
            $this->_view->getCompanyList = Service::getInstance('region')->getCompanyList($companyID);
        }else{
            $this->_view->getCompanyList = Service::getInstance('region')->getCompanyList();
        }
        $user = Yaf_Registry::get('developer');

        $this->_view->info = array('cid'=>$company_id);
        if ($this->isPost()) {
            $post = $_POST;
            if (empty($post['name'])) $this->respon(0, '请填写活动名称');
            $post['type_num'] = (float)$post['type_num'];
            if ($post['type_num'] < 0.01) $this->respon(0, '代金券金额不能小于0.01元');
            if (empty($post['type_num']) || !is_numeric($post['type_num']) || $post['type_num'] <= 0) $this->respon(0, '请选填写代金券金额');
            if (empty($post['start_time'])) $this->respon(0, '请选择活动开始时间');
            if ( strtotime($post['start_time']) < strtotime(date('Ymd',time())) ) $this->respon(0, '活动开始时间必须大于当前时间');
            if (empty($post['end_time'])) $this->respon(0, '请选择活动结束时间');
            if (strtotime($post['start_time']) > strtotime($post['end_time'])) $this->respon(0, '活动开始时间不可大于结束时间');
            if (strtotime($post['end_time']) < strtotime($post['start_time'])) $this->respon(0, '活动结束时间不可小于开始时间');
            if (empty($post['description'])) $this->respon(0, '请填写代金券描述');
            $post['cid'] = ($post['cid'])?$post['cid']:$company_id;
            $this->_view->info = $post;
            $return = Service::getInstance('Admsecurities')->add($post);
            if ($return) {
                //添加登陆日志id
                $userinfo = Yaf_Registry::get('developer');
                $log_system['log_type'] = 14;
                $log_system['action'] = '添加代金券';
                $log_system['uid'] = $userinfo['id'];
                $log_system['create_time'] = time();
                $log_system['action_id'] = $return;
                $log_system['action_json'] = json_encode($post);
                $log_system['ip'] = $_SERVER["REMOTE_ADDR"];
                Service::getInstance('systemlog')->addLog($log_system);
                //添加结束
                echo 200;
                exit();
            } else {
                return '活动信息提交失败';
            }
        }
    }

    /**
     * 更新数据
     * @return [type] [description]
     */
    public function updateAction()
    {
        $pid = $this->getPost('id');
        $gid = $this->getQuery('id');
        $id = ($gid)?$gid:$pid;
        
        $this->_view->info = Service::getInstance('Admsecurities')->getSecuritiesInfo($id);
        $this->_view->getCompanyList = Service::getInstance('region')->getCompanyList();
        if ($this->isPost()) {
            $post = $_POST;
            if (empty($post['name'])) $this->respon(0, '请填写优惠券名称');
            if (empty($post['type_num']) || !is_numeric($post['type_num']) || $post['type_num'] <= 0) $this->respon(0, '请选填写代金券金额');
            if (empty($post['start_time'])) $this->respon(0, '请选择优惠券开始时间');
//             if ( strtotime($post['start_time']) < strtotime(date('Ymd',time())) ) $this->respon(0, '优惠券开始时间必须大于当前时间');
            if (empty($post['end_time'])) $this->respon(0, '请选择优惠券结束时间');
            if (strtotime($post['start_time']) > strtotime($post['end_time'])) $this->respon(0, '优惠券开始时间不可大于结束时间');
            if (strtotime($post['end_time']) < strtotime($post['start_time'])) $this->respon(0, '优惠券结束时间不可小于开始时间');
            if (empty($post['description'])) $this->respon(0, '请填写优惠券描述');
            $this->_view->info = $post;
            unset($post['cid']);
            unset($post['id']);
            $return = Service::getInstance('Admsecurities')->update($id,$post);
            if ($return) {
                //添加登陆日志id
                $userinfo = Yaf_Registry::get('developer');
                $log_system['log_type'] = 14;
                $log_system['action'] = '修改代金券';
                $log_system['uid'] = $userinfo['id'];
                $log_system['create_time'] = time();
                $log_system['action_id'] = $id;
                $log_system['action_json'] = json_encode($post);
                $log_system['ip'] = $_SERVER["REMOTE_ADDR"];
                Service::getInstance('systemlog')->addLog($log_system);
                //添加结束
                echo 200;
                exit();
            } else {
                return '活动信息提交失败';
            }
        }
    }
    
    /**
     * 删除
     */
    public function deleteAction()
    {
        $id = $this->getPost('id');
        $return = Service::getInstance('Admsecurities')->delete($id);
        //添加登陆日志id
        $userinfo = Yaf_Registry::get('developer');
        $log_system['log_type'] = 14;
        $log_system['action'] = '删除代金券';
        $log_system['uid'] = $userinfo['id'];
        $log_system['create_time'] = time();
        $log_system['action_id'] = $id;
        $log_system['ip'] = $_SERVER["REMOTE_ADDR"];
        Service::getInstance('systemlog')->addLog($log_system);
        //添加结束
        echo $return;
        exit();
    }
    
    /**
     * 发送代金券
     */
    public function resendAction()
    {
        $id = $this->getPost('id');
        $user_type = $this->getPost('user_type');
        $ret = Service::getInstance('Admcoupons')->resend($id,$user_type,'djq');
        //添加登陆日志id
        $userinfo = Yaf_Registry::get('developer');
        $log_system['log_type'] = 14;
        $log_system['action'] = '发送代金券';
        $log_system['uid'] = $userinfo['id'];
        $log_system['create_time'] = time();
        $log_system['action_id'] = $id;
        $log_system['action_json'] = json_encode($user_type);
        $log_system['ip'] = $_SERVER["REMOTE_ADDR"];
        Service::getInstance('systemlog')->addLog($log_system);
        //添加结束
        echo $ret;die;
    }
}