<?php
/**
 * 广告管理控制类
 */
class AdController extends BaseController
{
    public function init()
    {
        parent::init();
    }

    public $location = array(1=>'首页', 2=>'限时抢购', 3=>'买就送', 4=>'品牌活动');
    
    /**
     * 广告列表
     * @return [type] [description]
     */
    public function listAction()
    {
        $userinfo = Yaf_Registry::get('developer');
        $page = $this->getQuery('page','')?$this->getQuery('page',''):'1';
        $pageSize = 10;//结束值
        $first = ($page-1)*$pageSize;//起始值
        //时间
        $location = $this->getQuery('location');
        $company_id = ($this->_view->rbac == '*') ? 0 : $this->companyIds ;
        $queryId = $this->getQuery('company_id',0);
        $where = '';
        //加入是否过期
        $flag = isset($_GET['flag']) ? $_GET['flag'] : 0;
        $time = time();
        if($flag){
            $where['end_time'] = "a.end_time<{$time}";
        }else{
            $where['end_time'] = "a.end_time>{$time}";
        }
        //结束
        $sql = Util::companyIdIsArray($company_id);
        if ($sql != 1) {
            if ($queryId) {
                $where['company_id'] = "a.cid = $queryId ";
            } else {
                $where['company_id'] = "a.c{$sql}";
            }
        }else{
            if($queryId){
                $where['company_id'] = "a.cid = $queryId ";
            }
        }
        if ($location) {
            $where['location'] = "a.location = $location";
        }
        
        $search = array(
            'company_id'=> $queryId,
            'location' => $location,
            'flag' => $flag
//             'ktime' => $ktime,
//             'gtime' => $gtime
        );
        //$list = Service::getInstance('admad')->getList($first,$pageSize,$where);
        //增加过期和未过期方法
        if($flag){
            $list = Service::getInstance('admad')->getBackDescList($first,$pageSize,$where);
            $url = "http://".$_SERVER['SERVER_NAME']."/ad/list?flag=1&page=__page__&".http_build_query($search);
        }else{
            $list = Service::getInstance('admad')->getBackAscList($first,$pageSize,$where);
            $url = "http://".$_SERVER['SERVER_NAME']."/ad/list?page=__page__&".http_build_query($search);
        }
        foreach ($list[0] as $k => $v) {
            $list[0][$k]['location'] = $this->location[$v['location']];
        }
        $this->_view->list = $list[0];
        $get = $_GET;
        $get['company_id'] = $queryId;
        $this->_view->get = $get;
        $this->_view->location = $this->location;
        if($company_id){
            $this->_view->getCompanyList = Service::getInstance('region')->getCompanyList($company_id);
        }else{
            $this->_view->getCompanyList = Service::getInstance('region')->getCompanyList();
        }
        $this->_view->paglist = Util::buildPagebar($list[1],$pageSize,$page,$url);
    }

    /**
     * 添加广告
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

        $this->_view->getBrand =  Service::getInstance('Admgoods')->getBrand();
        $this->_view->location = $this->location;
        $user = Yaf_Registry::get('developer');
        $this->_view->info = array('cid'=>$company_id);
        if ($this->isPost()) {
            $post = $_POST;
            $this->_view->info = $post;
            $post['goods_list'] = explode('-', $post['goods_list']);
            $post['cid'] = $company_id;
            $return = Service::getInstance('Admad')->add($post);
            if ($return) {
                if ($_FILES['goods_img']) {
                    $file = Util::getImgPath($_FILES['goods_img'],828,414);
                    $goods_img = $file['path'];
                }
                if ($_FILES['bg_image']) {
                    $file = Util::getImgPath($_FILES['bg_image'],828,414);
                    $bg_image = $file['path'];
                }
                Service::getInstance('Admad')->upGoodsImg($return, $goods_img, $bg_image);
                //添加登陆日志id
                $userinfo = Yaf_Registry::get('developer');
                $log_system['log_type'] = 13;
                $log_system['action'] = '添加广告';
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
        $result = Service::getInstance('Admad')->getGoodsList($data);
        echo json_encode($result);
        exit();
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

        $companyID = ($this->_view->rbac == '*') ? $this->getPost('cid',0) : $this->companyIds;
        if($companyID){
            $this->_view->getCompanyList = Service::getInstance('region')->getCompanyList($companyID);
        }else{
            $this->_view->getCompanyList = Service::getInstance('region')->getCompanyList();
        }
        $info = Service::getInstance('Admad')->getAdInfo($id);
        $image = $info['image'];
        $bg_image_1 = $info['bg_image'];
       // var_dump( $info);
        $info['image'] = Util::getpicbyhash($info['image']);
        $info['bg_image'] = Util::getpicbyhash($info['bg_image']);
        //var_dump($info);
        $this->_view->info = $info;
        $goodsList = Service::getInstance('Admad')->getAdGoods($id);
        $this->_view->getAdGoods = $goodsList[0];
        $this->_view->getAdGoodsValue = $goodsList[1];
        $this->_view->location = $this->location;
        
        if ($this->isPost()) {
            $post = $_POST;
            $this->_view->info = $post;
//             $this->respon(0, '请填写广告名称');die;
//             if (empty($post['name'])) $this->respon(0, '请填写广告名称');
//             if (empty($post['location'])) $this->respon(0, '请选择广告位置');
//             if (empty($post['type'])) $this->respon(0, '选择广告类型');
//             if (empty($post['start_time'])) $this->respon(0, '请选择活动开始时间');
//             if ( strtotime($post['start_time']) < strtotime(date('Ymd',time())) ) $this->respon(0, '活动开始时间必须大于当前时间');
//             if (empty($post['end_time'])) $this->respon(0, '请选择活动结束时间');
//             if (strtotime($post['start_time']) > strtotime($post['end_time'])) $this->respon(0, '活动开始时间不可大于结束时间');
//             if (strtotime($post['end_time']) < strtotime($post['start_time'])) $this->respon(0, '活动结束时间不可小于开始时间');
        
//             if (empty($post['description'])) $this->respon(0, '请填写活动描述');
//             if (empty($post['goods_list'])) $this->respon(0, '请选择活动商品');
            if ($post['goods_list'] != $goodsList[1]) {
                $post['goods_list'] = explode('-', $post['goods_list']);
            } else {
                unset($post['goods_list']);
            }
            $post['cid'] = ($post['cid']>0)?$post['cid']:$info['cid'];
            $post['`name`'] = $post['name'];
            unset($post['name']);
            $post['sort'] = $post['sort'];
            $post['start_time'] = strtotime($post['start_time']);
            $post['end_time'] = strtotime($post['end_time']);
            unset($post['id']);
            
            $return = Service::getInstance('Admad')->update($id, $post);
            if ($return) {
                if ($_FILES['goods_img']) {
                    $file = Util::getImgPath($_FILES['goods_img'],828,414);
                    $goods_img = $file['path'];
                    if ($goods_img) {
                        Util::delImage($image);
                    }
                }
                if ($_FILES['bg_image']) {

                    $file = Util::getImgPath($_FILES['bg_image'],828,414);
                    $bg_image = $file['path'];

                    if ($bg_image) {
                        Util::delImage($bg_image_1);
                    }
                  
                }
                Service::getInstance('Admad')->upGoodsImg($id, $goods_img, $bg_image);
                //添加登陆日志id
                $userinfo = Yaf_Registry::get('developer');
                $log_system['log_type'] = 13;
                $log_system['action'] = '修改广告';
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
        $return = Service::getInstance('Admad')->delete($id);
        //添加登陆日志id
        $userinfo = Yaf_Registry::get('developer');
        $log_system['log_type'] = 13;
        $log_system['action'] = '删除广告';
        $log_system['uid'] = $userinfo['id'];
        $log_system['create_time'] = time();
        $log_system['action_id'] = $id;
        $log_system['ip'] = $_SERVER["REMOTE_ADDR"];
        Service::getInstance('systemlog')->addLog($log_system);
        //添加结束
        echo $return;
        exit();
    }
}