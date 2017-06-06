<?php
class MessageController extends BaseController
{
    public function init()
    {
        parent::init();
        //数据模型对象
    }

    /**
     * 系统消息列表
     * @return [type] [description]
     */
    public function messagelistAction()
    {
        $ktime = $this->getQuery('ktime','');
        $gtime = $this->getQuery('gtime','');
        $type = $this->getQuery('type','');
        $page = $this->getQuery('page','')?$this->getQuery('page',''):'1';
        $pageSize = 10;//结束值
        $first = ($page-1)*$pageSize;//起始值
        
        // 时间
        $add_time = '';
        if(trim($ktime)!='' || trim($gtime)!=''){
            $add_time = ($ktime != '' ? ' tms.create_time>='.strtotime($ktime):'').($ktime != '' && $gtime != '' ? ' AND ':'').($gtime != '' ? ' tms.create_time<='.strtotime($gtime):'');
        }
        
        $search = array(
            'type'=> $type,
            'ktime' => $ktime,
            'gtime' => $gtime
        );
        $url = "http://".$_SERVER['SERVER_NAME']."/message/messagelist?page=__page__&".http_build_query($search);
        $list = Service::getInstance('admmessage')->getPageMessageList($first, $pageSize, $type, $add_time);
        $this->_view->get = $_GET;
        $this->_view->list = $list[0];
        $this->_view->paglist = Util::buildPagebar($list[1],$pageSize,$page,$url);
    }

    public function upShowAction()
    {
       $id = $this->getPost('id');
       $is_show = $this->getPost('is_show');
       $ret = Service::getInstance('admmessage')->getMessageList($id, $is_show);
       if ($ret) {
            $is_show = ($is_show == 1) ? 0 : ($is_show == 0 ? 1 : 0);
            echo $is_show;die;
       } else {
            echo 400;die;
       }
    }

    public function updateAction()
    {
        $id = ($this->getQuery('id'))?$this->getQuery('id'):$this->getPost('id');

        $title = $this->getPost('title');
        $content = $this->getPost('content');
        $receiver = $this->getPost('receiver','');
        $url = $this->getPost('url');
        $message = Service::getInstance('admmessage')->messageInfo($id);
        $this->_view->info = $message;
        if ($_POST) {
            $this->_view->info = $_POST;
            if ($title == '') return $this->_view->errors = '标题不能为空！';
            if ($content == '') return $this->_view->errors = '内容不能为空！';
            $data = array(
                'title' => $title,
                'content' => $content,
                'receiver' => $receiver
            );
            Service::getInstance('admmessage')->messageUpdate($id, $data);
            //添加登陆日志id
            $userinfo = Yaf_Registry::get('developer');
            $goods['log_type'] = 6;
            $goods['action'] = '编辑系统消息';
            $goods['uid'] = $userinfo['id'];
            $goods['create_time'] = time();
            $goods['action_id'] = $id;
            $goods['action_json'] = json_encode($data);
            $goods['ip'] = $_SERVER["REMOTE_ADDR"];
            Service::getInstance('systemlog')->addLog($goods);
            //添加结束
            $this->flash('messagelist','修改成功');
            return false;
        }
    }

    public function addAction()
    {
        $title = $this->getPost('title');
        $content = $this->getPost('content');
        $receiver = $this->getPost('receiver','');
        
        if ($_POST) {
            $this->_view->info = $_POST;
            if ($title == '') return $this->_view->errors = '标题不能为空！';
            if ($content == '') return $this->_view->errors = '内容不能为空！';
            $data = array(
                'title' => $title,
                'content' => $content,
                'receiver' => $receiver,
                'type' => 3,
                'create_time' => time()
            );

            $id = Service::getInstance('admmessage')->messageAdd($data);
            //添加登陆日志id
            $userinfo = Yaf_Registry::get('developer');
            $goods['log_type'] = 6;
            $goods['action'] = '发送系统消息';
            $goods['uid'] = $userinfo['id'];
            $goods['create_time'] = time();
            $goods['action_id'] = $id;
            $goods['action_json'] = json_encode($data);
            $goods['ip'] = $_SERVER["REMOTE_ADDR"];
            Service::getInstance('systemlog')->addLog($goods);
            //添加结束
            $this->flash('messagelist','添加成功');
            return false;
        }
    }

    public function delAction()
    {
        $id = $this->getPost('id');
        $type = $this->getPost('type');
        $return = Service::getInstance('admmessage')->delete($id,$type);
        if ($return) {
            //添加登陆日志id
            $userinfo = Yaf_Registry::get('developer');
            $goods['log_type'] = 6;
            $goods['action'] = '删除系统消息';
            $goods['uid'] = $userinfo['id'];
            $goods['create_time'] = time();
            $goods['action_id'] = $id;
            $goods['ip'] = $_SERVER["REMOTE_ADDR"];
            Service::getInstance('systemlog')->addLog($goods);
            //添加结束
            exit(json_encode(200));
        } else {
            exit(json_encode(400));
        }
    }
    
    /**
     * 订单消息列表
     * @return [type] [description]
     */
    public function orderListAction()
    {
        $search_name = $this->getQuery('search_name','');
        $page = $this->getQuery('page','')?$this->getQuery('page',''):'1';
        $ktime = $this->getQuery('ktime','');
        $gtime = $this->getQuery('gtime','');
        $order_status = $this->getQuery('order_status');
        $pageSize = 10;//结束值
        $first = ($page-1)*$pageSize;//起始值
        // 时间
        $add_time = '';
        if(trim($ktime)!='' || trim($gtime)!=''){
            $add_time = ($ktime != '' ? ' message.create_time>='.strtotime($ktime):'').($ktime != '' && $gtime != '' ? ' AND ':'').($gtime != '' ? ' message.create_time<='.strtotime($gtime):'');
        }
        $search = array(
            'search_name'=>$search_name,
            'ktime' => $ktime,
            'gtime' => $gtime
        );
        $url = "http://".$_SERVER['SERVER_NAME']."/message/orderList?page=__page__&".http_build_query($search);
        $list = Service::getInstance('admmessage')->getMessageList($first, $pageSize, $search_name,$add_time,$order_status);
        $this->_view->list = $list[0];
        $this->_view->paglist = Util::buildPagebar($list[1],$pageSize,$page,$url);
    }

    /**
     * 重新发送
     */
    public function resendAction()
    {
        $id = $this->getPost('id');
        //添加登陆日志id
        $userinfo = Yaf_Registry::get('developer');
        $goods['log_type'] = 6;
        $goods['action'] = '重新发送系统消息';
        $goods['uid'] = $userinfo['id'];
        $goods['create_time'] = time();
        $goods['action_id'] = $id;
        $goods['ip'] = $_SERVER["REMOTE_ADDR"];
        Service::getInstance('systemlog')->addLog($goods);
        //添加结束
        print_r(Service::getInstance('admmessage')->resend($id));die;
    }
}