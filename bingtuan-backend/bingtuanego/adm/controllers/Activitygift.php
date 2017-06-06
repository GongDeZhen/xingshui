<?php

/**
 * 买就送
 */
class ActivitygiftController extends BaseController
{
    public function init()
    {
        parent::init();
    }

     /**
     * 买就送列表
     * @return [type] [description]
     */
    public function listAction()
    {
        $page = $this->getQuery('page','')?$this->getQuery('page',''):'1';
        $pageSize = 10;//结束值
        $first = ($page-1)*$pageSize;//起始值
        // 搜索商品
        $goods_name = $this->getQuery('goods_name','');
        
        //时间
        $ktime = $this->getQuery('ktime','');
        $gtime = $this->getQuery('gtime','');
        $user = Yaf_Registry::get('developer');
        $company_id = ($this->_view->rbac == '*') ? 0 : $this->companyIds;
        $queryId = $this->getQuery('company_id', 0);
        $where = '';
        if(trim($ktime) != '' || trim($gtime) != ''){
            $where['create_time'] = (!empty($ktime) ? ' a.create_time>='.strtotime($ktime) : '').((!empty($ktime) && !empty($gtime))?' and ':'').(!empty($gtime)?' a.create_time<='.strtotime($gtime).' ':'');
        }
        if ($company_id > 0) {
            $where['company_id'] = $company_id;
        }
        if ($goods_name) {
            $where['goods_name'] = $goods_name;
        }
        if($queryId){
            $where['query_id'] = $queryId;
        }
        $search = array(
            'company_id'=> $queryId,
            'ktime' => $ktime,
            'gtime' => $gtime,
            'goods_name' => $goods_name
        );
        $url = "http://".$_SERVER['SERVER_NAME']."/activitygift/list?page=__page__&".http_build_query($search);
        
        $list = Service::getInstance('Admactivitygift')->getList($first,$pageSize,$where);
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
     * 添加买就送
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
            if (
                    empty($post['receiving_num'])
                 || empty($post['free_num'])
                 || !is_numeric($post['receiving_num'])
                 || !is_numeric($post['free_num'])
            ) $this->respon(0, '请填写活动规则');
            if (empty($post['start_time'])) $this->respon(0, '请选择活动开始时间');
            if ( strtotime($post['start_time']) < strtotime(date('Ymd',time())) ) $this->respon(0, '活动开始时间必须大于当前时间');
            if (empty($post['end_time'])) $this->respon(0, '请选择活动结束时间');
            if (strtotime($post['start_time']) > strtotime($post['end_time'])) $this->respon(0, '活动开始时间不可大于结束时间');
            if (strtotime($post['end_time']) < strtotime($post['start_time'])) $this->respon(0, '活动结束时间不可小于开始时间');
            if (empty($post['description'])) $this->respon(0, '请填写活动描述');
            if (empty($post['goods_list'])) $this->respon(0, '请选择活动商品');
            if (empty($post['gift_goods_list'])) $this->respon(0, '请选择活动赠品');
            $post['free_num'] = (int)$post['free_num'];
            $post['receiving_num'] = (int)$post['receiving_num'];
            $post['cid'] = $company_id;
            $this->_view->info = $post;
            //$goods = explode('-', $post['goods_list']);
            //$status = array();
            //foreach($goods as $gid){
                //$post['goods_list'] = $gid;
                $return = Service::getInstance('Admactivitygift')->add($post);
                //if($return){
                    //array_push($status, '1');
                //}
            //}
            //if (count($goods) == count($status)) {
            if($return){
                //添加登陆日志id
                $userinfo = Yaf_Registry::get('developer');
                $log_system['log_type'] = 11;
                $log_system['action'] = '添加买就送';
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
            'id'=>$this->getPost('id'),
            'start_time' => strtotime($this->getPost('start_time')),
            'end_time' => strtotime($this->getPost('end_time'))
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
        $result = Service::getInstance('Admactivitygift')->getGoodsList($data);
        echo json_encode($result);
        exit();
    }
    
    /**
     * ajax获取商品列表数据
     * @return [type] [description]
     */
    protected function giftGoodsSearchAction()
    {
        $id = $this->getPost('id',0);
        $keyword = $this->getPost('keyword', '');
        $company_id = $this->getPost('company');
        $result = Service::getInstance('Admactivitygift')->getGiftGoodsList($id,$keyword,$company_id);
        echo json_encode($result);
        exit();
    }
    
    /**
     * 
     * @param  [type] $activityGoodsList [description]
     * @return [type]                    [description]
     */
    private function _getActivityGoodsJson($activityGoodsList)
    {
        if(empty($activityGoodsList)) {
            return;
        }
        foreach($activityGoodsList as $key => $item) {
            $data[$item['goods_id']]    = $item['goods_num'];
        }
        
        return json_encode($data);
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

        $this->_view->getCompanyList = Service::getInstance('region')->getCompanyList();
        $this->_view->info = Service::getInstance('Admactivitygift')->getActivityInfo($id);
        $goodsList = Service::getInstance('Admactivitygift')->getActivityGoods($id);
        $this->_view->getActivityGoods = $goodsList[0];
        $this->_view->getActivityGoodsValue = $goodsList[1];
        $giftList = Service::getInstance('Admactivitygift')->getActivityGoodsGift($id);
        $this->_view->getActivityGoodsGift = $giftList[0];
        $this->_view->getActivityGoodsGiftValue = $giftList[1];
        if ($this->isPost()) {
            $post = $_POST;
            $this->_view->info = $post;
            if (empty($post['name'])) $this->respon(0, '请填写活动名称');
            if (empty($post['start_time'])) $this->respon(0, '请选择活动开始时间');
            if (empty($post['end_time'])) $this->respon(0, '请选择活动结束时间');
            if (strtotime($post['start_time']) > strtotime($post['end_time'])) $this->respon(0, '活动开始时间不可大于结束时间');
            if (strtotime($post['end_time']) < strtotime($post['start_time'])) $this->respon(0, '活动结束时间不可小于开始时间');
        
            if (empty($post['description'])) $this->respon(0, '请填写活动描述');
            if (empty($post['goods_list'])) $this->respon(0, '请选择活动商品');

            $post['activity_goods_id'] = $goodsList[2];

            $post['`name`'] = $post['name'];
            unset($post['name']);
            $post['`order`'] = $post['order'];
            unset($post['order']);
            $post['update_time'] = date('Y-m-d H:i:d', time());
            $post['start_time'] = strtotime($post['start_time']);
            $post['end_time'] = strtotime($post['end_time']);
            unset($post['id']);
            $return = Service::getInstance('Admactivitygift')->update($id, $post);
            if ($return) {
                //添加登陆日志id
                $userinfo = Yaf_Registry::get('developer');
                $log_system['log_type'] = 11;
                $log_system['action'] = '修改买就送';
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
        $return = Service::getInstance('Admactivitygift')->delete($id);
        //添加登陆日志id
        $userinfo = Yaf_Registry::get('developer');
        $log_system['log_type'] = 11;
        $log_system['action'] = '删除买就送';
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
     * 排序
     */
    public function activityOrderAction()
    {
        $id = $this->getPost('id');
        $return = Service::getInstance('Admactivitygift')->activityOrder($id);
        echo $return;
        exit();
    }
    
    /**
     * 修改上下架
     */
    public function upShowAction()
    {
        $id = $this->getPost('id');
        $is_show = $this->getPost('is_show');
        $return = Service::getInstance('Admactivitylimittime')->upShow($id, $is_show);
        //添加登陆日志id
        $userinfo = Yaf_Registry::get('developer');
        $log_system['log_type'] = 11;
        $log_system['action'] = '买就送上下架';
        $log_system['uid'] = $userinfo['id'];
        $log_system['create_time'] = time();
        $log_system['action_id'] = $id;
        $log_system['action_json'] = json_encode($is_show);
        $log_system['ip'] = $_SERVER["REMOTE_ADDR"];
        Service::getInstance('systemlog')->addLog($log_system);
        //添加结束
        echo $return;
        exit();
    }
    
    /**
     * 批量修改上下架
     */
    public function updateCheckAction()
    {
        $check = $this->getPost('check');
        $return = Service::getInstance('Admactivitylimittime')->updateCheck($check);

        echo $return;
        exit();
    }

    /**
     * 判断活动时间是否冲突
     */
    public function isActivityTimeAction()
    {
        $id = $this->getPost('id');
        $name = $this->getPost('name');
        $time = $this->getPost('time');
        $return = Service::getInstance('Admactivitylimittime')->isActivityTime($id, $name, $time);
        $return[1] = ($return[1])?date('Y-m-d', $return[1]-86400):'';
        echo json_encode($return);
        exit();
    }
}