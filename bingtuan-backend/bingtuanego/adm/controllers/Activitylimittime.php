<?php

/**
 * 促销活动限时限购控制类
 */
class ActivitylimittimeController extends BaseController
{
    public function init()
    {
        parent::init();
    }

     /**
     * 限时限购列表
     * @return [type] [description]
     */
    public function listAction()
    {
        $userinfo = Yaf_Registry::get('developer');
        $page = $this->getQuery('page','')?$this->getQuery('page',''):'1';
        $pageSize = 10;//结束值
        $first = ($page-1)*$pageSize;//起始值
        // 搜索商品
        $goods_name = $this->getQuery('goods_name','');
        
        // 时间
        $ktime = $this->getQuery('ktime','');
        $gtime = $this->getQuery('gtime','');
        $company_id = ($this->_view->rbac == '*') ? 0 : $this->companyIds ;
        $queryId = $this->getQuery('company_id',0);
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
            'company_id' => $queryId,
            'goods_name' => $goods_name,
            'ktime' => $ktime,
            'gtime' => $gtime,
        );

        $list = Service::getInstance('Admactivitylimittime')->getList($first,$pageSize,$where);
        $this->_view->list = $list[0];
        $get = $_GET;
        $get['company_id'] = $queryId;
        $url = "http://".$_SERVER['SERVER_NAME']."/activitylimittime/list?page=__page__&".http_build_query($search);
        $this->_view->get = $get;
        if($company_id){
            $this->_view->getCompanyList = Service::getInstance('region')->getCompanyList($company_id);
        }else{
            $this->_view->getCompanyList = Service::getInstance('region')->getCompanyList();
        }

        $this->_view->paglist = Util::buildPagebar($list[1],$pageSize,$page,$url);
    }

     /**
     * 添加限时抢购
     */
    public function addAction()
    {
        $user = Yaf_Registry::get('developer');
        $companyID = ($this->_view->rbac == '*') ? $this->getPost('cid',0) : $this->companyIds;
        $company_id = $this->getPost('cid',0);
        $this->_view->info = array('cid'=>$company_id);
        if($companyID){
            $this->_view->getCompanyList = Service::getInstance('region')->getCompanyList($companyID);
        }else{
            $this->_view->getCompanyList = Service::getInstance('region')->getCompanyList();
        }

        if ($this->isPost()) {
            $post = $_POST;
            if (empty($post['name'])) $this->respon(0, '请填写活动名称');
            if (empty($post['start_time'])) $this->respon(0, '请选择活动开始时间');
            if ( strtotime($post['start_time']) < strtotime(date('Ymd',time())) ) $this->respon(0, '活动开始时间必须大于当前时间');
            if (empty($post['end_time'])) $this->respon(0, '请选择活动结束时间');
            if (strtotime($post['start_time']) > strtotime($post['end_time'])) $this->respon(0, '活动开始时间不可大于结束时间');
            if (strtotime($post['end_time']) < strtotime($post['start_time'])) $this->respon(0, '活动结束时间不可小于开始时间');
            if($post['putaway']){
                if(empty($post['hour_start'])) $this->respon(0, '请选择日促销开始时间');
                if(empty($post['hour_end'])) $this->respon(0, '请选择日促销结束时间');
            }
            
            if (empty($post['description'])) $this->respon(0, '请填写活动描述');
            if (empty($post['goods_list'])) $this->respon(0, '请选择活动商品');
            $post['cid'] = $company_id;
            $this->_view->info = $post;
            $post['goods_list'] = explode('-', $post['goods_list']);
            $goodsList = array();
            foreach ($post['goods_list'] as $k => $v) {
                $goods = explode(':', $v);
                $goodsList[$goods[0]] = $goods[1];
            }
            $post['goods_list'] = $goodsList;
            
            $return = Service::getInstance('Admactivitylimittime')->add($post);
            if ($return) {
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
            'id' => $this->getPost('id'),
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
        $result = Service::getInstance('Admactivitylimittime')->getGoodsList($data);
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
        $this->_view->info = Service::getInstance('Admactivitylimittime')->getActivityInfo($id);
        $goodsList = Service::getInstance('Admactivitylimittime')->getActivityGoods($id);
        $this->_view->getActivityGoods = $goodsList[0];
        $this->_view->getActivityGoodsValue = $goodsList[1];
        if ($this->isPost()) {
            $post = $_POST;
            $this->_view->info = $post;
            if (empty($post['name'])) $this->respon(0, '请填写活动名称');
            if (empty($post['start_time'])) $this->respon(0, '请选择活动开始时间');
            if (empty($post['end_time'])) $this->respon(0, '请选择活动结束时间');
            if (strtotime($post['start_time']) > strtotime($post['end_time'])) $this->respon(0, '活动开始时间不可大于结束时间');
            if (strtotime($post['end_time']) < strtotime($post['start_time'])) $this->respon(0, '活动结束时间不可小于开始时间');
            if($post['putaway']){
                if(empty($post['hour_start'])) $this->respon(0, '请选择日促销开始时间');
                if(empty($post['hour_end'])) $this->respon(0, '请选择日促销结束时间');
            }
        
            if (empty($post['description'])) $this->respon(0, '请填写活动描述');
            if (empty($post['goods_list'])) $this->respon(0, '请选择活动商品');
            if ($post['goods_list'] != $goodsList[1]) {
                $post['goods_list'] = explode('-', $post['goods_list']);
                $goodsList = array();
                foreach ($post['goods_list'] as $k => $v) {
                    $goods = explode(':', $v);
                    $goodsList[$goods[0]] = $goods[1];
                }
                $post['goods_list'] = $goodsList;
            } else {
                unset($post['goods_list']);
            }
            
            $post['`name`'] = $post['name'];
            unset($post['name']);
            $post['`order`'] = $post['order'];
            unset($post['order']);
            $post['update_time'] = date('Y-m-d H:i:d', time());
            $post['start_time'] = strtotime($post['start_time']);
            $post['end_time'] = strtotime($post['end_time']);
            unset($post['id']);
            $return = Service::getInstance('Admactivitylimittime')->update($id, $post);
            if ($return) {
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
        $return = Service::getInstance('Admactivitylimittime')->delete($id);
        echo $return;
        exit();
    }
    /**
     * 排序
     */
    public function activityOrderAction()
    {
        $id = $this->getPost('id');
        echo Service::getInstance('Admactivitylimittime')->activityOrder($id);
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
        $return[1] = date('Y-m-d', $return[1]-86400);
        echo json_encode($return);
        exit();
    }
}
