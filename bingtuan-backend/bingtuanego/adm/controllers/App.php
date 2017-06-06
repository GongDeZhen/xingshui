<?php
class AppController extends BaseController
{
    public function init()
    {
        parent::init();
    }
    /**
     * 版本更新
     */
    public function indexAction()
    {
        $data = Service::getInstance('App')->index();
        $this->_view->info = $data;
        if ($this->isPost()) {
            $id = $this->getPost('id');
            $name = $this->getPost('name');
            $code = $this->getPost('code');
            $details = $this->getPost('details');
            if ($_FILES['hash']['size'] > 0) {
                if ($_FILES['hash']['type'] != 'application/octet-stream') {
                    echo 500;die;
                }
            }
            
            $return = Service::getInstance('App')->update($id, $name, $code, $details);
            //添加登陆日志id
            $userinfo = Yaf_Registry::get('developer');
            $log_system['log_type'] = 16;
            $log_system['action'] = '更新APP';
            $log_system['uid'] = $userinfo['id'];
            $log_system['create_time'] = time();
            $log_system['action_id'] = $id;
            $log_system['action_json'] = json_encode($name);
            $log_system['ip'] = $_SERVER["REMOTE_ADDR"];
            Service::getInstance('systemlog')->addLog($log_system);
            //添加结束
            echo $return;die;
        }
    }
}