<?php
/**
 * 优惠券管理控制类
 */
class CouponsController extends BaseController
{
    /**
     * 类型Map
     *  array
     */
    public function init()
    {
         parent::init();
    }

    /**
     * 列表页面
     * @return [type] [description]
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
            $where['create_time'] = (!empty($ktime) ? ' c.create_time>='.strtotime($ktime) : '').((!empty($ktime) && !empty($gtime))?' and ':'').(!empty($gtime)?' c.create_time<='.strtotime($gtime).' ':'');
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
        $url = "http://".$_SERVER['SERVER_NAME']."/coupons/list?page=__page__&".http_build_query($search);
        $list = Service::getInstance('Admcoupons')->getCouponsList($first,$pageSize,$where);
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
     * 添加优惠券
     * @return [type] [description]
     */
    public function addAction()
    {
        $user = Yaf_Registry::get('developer');
        $companyID = ($this->_view->rbac == '*') ? $this->getPost('cid',0) : $this->companyIds;
        if($companyID){
            $this->_view->getCompanyList = Service::getInstance('region')->getCompanyList($companyID);
        }else{
            $this->_view->getCompanyList = Service::getInstance('region')->getCompanyList();
        }

        $company_id = $this->getPost('cid',0);
        $this->_view->info = array('cid'=>$company_id);
        if ($this->isPost()) {
            $post = $_POST;
            if (empty($post['name'])) $this->respon(0, '请填写活动名称');
            if (empty($post['type_num'])) $this->respon(0, '请填写折扣');
            if ((int)$post['type_num'] > 10) {
                $this->respon(0, '折扣必须0.01~9.99折');
            }
            if ($post['type_num'] > 0 || $post['type_num'] < 10) {
                $post['type_num'] = number_format($post['type_num'], 2);
            }
            if ($post['type_num'] == '0.00' || !preg_match('/^[0-9]+\\.[0-9][0-9]$/', $post['type_num'])) $this->respon(0, '折扣必须0.01~9.99折');
            if (empty($post['start_time'])) $this->respon(0, '请选择活动开始时间');
            if ( strtotime($post['start_time']) < strtotime(date('Ymd',time())) ) $this->respon(0, '活动开始时间必须大于当前时间');
            if (empty($post['end_time'])) $this->respon(0, '请选择活动结束时间');
            if (strtotime($post['start_time']) > strtotime($post['end_time'])) $this->respon(0, '活动开始时间不可大于结束时间');
            if (strtotime($post['end_time']) < strtotime($post['start_time'])) $this->respon(0, '活动结束时间不可小于开始时间');
            
            if (empty($post['description'])) $this->respon(0, '请填写活动描述');
            if (empty($post['goods_list'])) $this->respon(0, '请选择活动商品');
            $post['cid'] = $company_id;
            $this->_view->info = $post;
            $post['goods_list'] = explode('-', $post['goods_list']);
            
            $return = Service::getInstance('Admcoupons')->add($post);
            if ($return) {
                //添加登陆日志id
                $userinfo = Yaf_Registry::get('developer');
                $log_system['log_type'] = 15;
                $log_system['action'] = '添加优惠卷';
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
     * ajax获取商品列表数据
     * @return [type] [description]
     */
    protected function goodsSearchAction()
    {
        $data = array(
            'company_id' => $this->getPost('company_id'),
            'goodsName' => $this->getPost('goodsName'),
            'result' => $this->getPost('result'),
            'id'=>$this->getPost('id')
        );
        $result = explode('-', $data['result']);
        $arr = array();
        foreach ($result as $k=>$v) {
            $value = explode(':', $v);
            if (!empty($value[0])) {
                $arr[] = $value[0];
            }
        }
        $data['result'] = $arr;
        $result = Service::getInstance('Admcoupons')->getGoodsList($data);
        echo json_encode($result);
        exit();
    }

    public function updateAction()
    {
        $pid = $this->getPost('id');
        $gid = $this->getQuery('id');
        $id = ($gid)?$gid:$pid;
        $info = Service::getInstance('Admcoupons')->getCouponsInfo($id);
        $this->_view->info = $info;
        $couponsGoods = Service::getInstance('Admcoupons')->getCouponsGoods($id);
        $this->_view->getCouponsGoods = $couponsGoods[0];
        $this->_view->getCouponsVal = $couponsGoods[1];
        $this->_view->getCompanyList = Service::getInstance('region')->getCompanyList();
        if ($this->isPost()) {
            $post = $_POST;
            if (empty($post['name'])) $this->respon(0, '请填写优惠券名称');
            if (empty($post['start_time'])) $this->respon(0, '请选择优惠券开始时间');
            if ($post['type_num'] > 0 || $post['type_num'] < 10) {
                $post['type_num'] = number_format($post['type_num'], 2);
            }
            if (!((int)$post['type_num'] < 10) || $post['type_num'] == '0.00' || !preg_match('/^[0-9]+\\.[0-9][0-9]$/', $post['type_num'])) $this->respon(0, '折扣必须0.01~9.99折');
//             if ( strtotime($post['start_time']) < strtotime(date('Ymd',time())) ) $this->respon(0, '优惠券开始时间必须大于当前时间');
            if (empty($post['end_time'])) $this->respon(0, '请选择优惠券结束时间');
            if (strtotime($post['start_time']) > strtotime($post['end_time'])) $this->respon(0, '优惠券开始时间不可大于结束时间');
            if (strtotime($post['end_time']) < strtotime($post['start_time'])) $this->respon(0, '优惠券结束时间不可小于开始时间');
        
            if (empty($post['description'])) $this->respon(0, '请填写优惠券描述');
            if (empty($post['goods_list'])) $this->respon(0, '请选择活动商品');
            $post['cid'] = ($post['cid'])?$post['cid']:$info['cid'];
            $this->_view->info = $post;
            if ($post['goods_list'] != $couponsGoods[1]) {
                $post['goods_list'] = explode('-', $post['goods_list']);
            } else {
                unset($post['goods_list']);
            }
            unset($post['status']);
            unset($post['id']);
            $return = Service::getInstance('Admcoupons')->update($id,$post);
            if ($return) {
                //添加登陆日志id
                $userinfo = Yaf_Registry::get('developer');
                $log_system['log_type'] = 15;
                $log_system['action'] = '修改优惠卷';
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
        $return = Service::getInstance('Admcoupons')->delete($id);
        //添加登陆日志id
        $userinfo = Yaf_Registry::get('developer');
        $log_system['log_type'] = 15;
        $log_system['action'] = '删除优惠卷';
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
     * 发送优惠券
     */
    public function resendAction()
    {
        $id = $this->getPost('id');
        $user_type = $this->getPost('user_type');
        $ret = Service::getInstance('Admcoupons')->resend($id,$user_type,'yhq');
        //添加登陆日志id
        $userinfo = Yaf_Registry::get('developer');
        $log_system['log_type'] = 15;
        $log_system['action'] = '发送优惠卷';
        $log_system['uid'] = $userinfo['id'];
        $log_system['create_time'] = time();
        $log_system['action_id'] = $id;
        $log_system['action_json'] = json_encode($user_type);
        $log_system['ip'] = $_SERVER["REMOTE_ADDR"];
        Service::getInstance('systemlog')->addLog($log_system);
        //添加结束
        echo $ret;
        die;
    }
}