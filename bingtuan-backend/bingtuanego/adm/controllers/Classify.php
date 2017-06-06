<?php
class ClassifyController extends BaseController
{
    public function init()
    {
        parent::init();
    }
    
    /**
     * 品类列表
     * @return [type] [description]
     */
    public function listAction()
    {
        $search_name = $this->getQuery('search_name','');
        $page = $this->getQuery('page','')?$this->getQuery('page',''):'1';
        $pageSize = 10;//结束值
        $first = ($page-1)*$pageSize;//起始值
        
        $search = array(
            'search_name'=>$search_name,
        );
        $url = "http://".$_SERVER['SERVER_NAME']."/classify/list?page=__page__&".http_build_query($search);
        $list = Service::getInstance('admclassify')->classifyList($first, $pageSize, $search_name);
        $this->_view->list = $list[0];
        $this->_view->get = $_GET;
        $this->_view->paglist = Util::buildPagebar($list[1],$pageSize,$page,$url);
    }
    
    public function upShowAction()
    {
        $id = $this->getPost('id');
        $is_show = $this->getPost('is_show');
        $ret = Service::getInstance('admclassify')->upShow($id, $is_show);
        if ($ret) {
            $is_show = ($is_show == 1) ? 1 : 0;
            echo $is_show;die;
        } else {
            echo 400;die;
        }
    }
    
    public function updateAction()
    {
        $id = $this->getPost('id');
        $ID = $this->getQuery('id');
        $name = $this->getPost('name');
        $order = $this->getPost('order');
        $describe = $this->getPost('describe');

        if ($_POST) {
            $this->_view->info = $_POST;
            if ($name == '') return $this->_view->errors = '品牌名不能为空！';
            if (!is_numeric($order) && $order != '') return $this->_view->errors = '排序-必须数字类型';
            if ($describe == '') return $this->_view->errors = '描述不能为空！';
    
            $data = array(
                'name' => $name,
                '`order`' => $order,
                '`describe`' => $describe
            );
            Service::getInstance('admclassify')->update($id,$data);
            //添加登陆日志id
            $userinfo = Yaf_Registry::get('developer');
            $log_system['log_type'] = 9;
            $log_system['action'] = '修改品类';
            $log_system['uid'] = $userinfo['id'];
            $log_system['create_time'] = time();
            $log_system['action_id'] = $id;
            $log_system['action_json'] = json_encode($data);
            $log_system['ip'] = $_SERVER["REMOTE_ADDR"];
            Service::getInstance('systemlog')->addLog($log_system);
            //添加结束
            $this->flash('list','修改成功');
            return false;
        }
        $ret = Service::getInstance('admclassify')->getGoodsType($ID);
        $this->_view->info = $ret;
    }
    
    public function addAction()
    {
        $name = $this->getPost('name');
        $order = $this->getPost('order');
        $describe = $this->getPost('describe');
        if ($_POST) {
            $this->_view->info = $_POST;
            if ($name == '') return $this->_view->errors = '品类名不能为空！';
            if (!is_numeric($order) && $order != '') return $this->_view->errors = '排序-必须数字类型';
            if ($describe == '') return $this->_view->errors = '描述不能为空！';
    
            $data = array(
                'name' => $name,
                '`order`' => $order,
                '`describe`' => $describe
            );
            $id = Service::getInstance('admclassify')->add($data);
            //添加登陆日志id
            $userinfo = Yaf_Registry::get('developer');
            $log_system['log_type'] = 9;
            $log_system['action'] = '添加品类';
            $log_system['uid'] = $userinfo['id'];
            $log_system['create_time'] = time();
            $log_system['action_id'] = $id;
            $log_system['action_json'] = json_encode($data);
            $log_system['ip'] = $_SERVER["REMOTE_ADDR"];
            Service::getInstance('systemlog')->addLog($log_system);
            //添加结束
            $this->flash('list','添加成功');
            return false;
        }
    }
    
    public function delAction()
    {
        $id = $this->getPost('id');
        $return = Service::getInstance('admclassify')->delete($id);
        if ($return) {
            //添加登陆日志id
            $userinfo = Yaf_Registry::get('developer');
            $log_system['log_type'] = 9;
            $log_system['action'] = '删除品类';
            $log_system['uid'] = $userinfo['id'];
            $log_system['create_time'] = time();
            $log_system['action_id'] = $id;
            $log_system['ip'] = $_SERVER["REMOTE_ADDR"];
            Service::getInstance('systemlog')->addLog($log_system);
            //添加结束
            exit(json_encode(200));
        } else {
            exit(json_encode(400));
        }
    }
}