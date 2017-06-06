<?php
/**
 * 消息接口类 
 * @luodao
 */
class MessageController extends BaseController
{
    public function init()
    {
        parent::init();
    
        if (  Yaf_Registry::get( 'isLogin' ) <> true )
        {
            $this->respon( 0 , "登录超时!"  );
        }
    }
     /**
     * 数据库服务对象
     */
    private $_service;

    public function indexAction()
    {
        if (  !Service::getInstance('user')->checkUser()  )
        {
            $this->respon( 0 , "Login Access Denied" );
        }
    
        $this->respon( 1 , Yaf_Registry::get("User") );
    }

    /**
     * 未读消息数
     * 接口参数 用户Id
     * @return [type] [description]
     */
    public function messageUnknownNumberAction()
    {
        $userID = $this->getPost('uid')?$this->getPost('uid'):Yaf_Registry::get('uid');
        $receiver       = $this->getPost('receiver');
        if(!$receiver) $this->respon(0, '用户类型不能为空');
        
        $count = Service::getInstance('message')->getNoReadNumber($userID,$receiver);
        $this->respon(1, $count);
    }

    /**
     * 得到官方未读消息数
     * @return [type] [description]
     */
    public function getSysNoReadNumberAction()
    {
        $userId         = $this->getPost('uid')?$this->getPost('uid'):Yaf_Registry::get('uid');
        $receiver       = $this->getPost('receiver');
        if(1 == $receiver) {
            $areaId         = $this->getPost('area_id');
            if(!$areaId) {
                $this->respon(0, '买家必须传地区ID');
            }
        }else{
            $userAddress    = Service::getInstance('message')->getUserAddress($userId, $receiver);
            $areaId         = $userAddress['area_id'] ? $userAddress['area_id'] : 0;
        }
        $data           = array(
            'user_id' => $userId, 'area_id' => $areaId, 'receiver' => $receiver
        );
        $total          = Service::getInstance('message')->getSysNoReadNumber($data);

        $this->respon(1, $total ? $total : 0);
    }

    /**
     * 消息列表
     * @return [type] [description]
     */
    public function getMessageListAction()
    {   
        $id             = $this->getPost('id',0);
        $userId         = $this->getPost('uid')?$this->getPost('uid'):Yaf_Registry::get('uid');
        $page           = $this->getPost('page',0);
        $pageSize       = $this->getPost('pageSize', 10);
        $receiver       = $this->getPost('receiver');
        $type           = $this->getPost('type');
        
        if ($userId == '' || $receiver == '' || $type == '') {
            $this->respon(1, '请求参数错误，请检查参数是否完整');
        }
        
        $data           = array('id' => $id, 'user_id' => $userId, 'page' => $page, 'limit' => $pageSize, 'receiver' => $receiver, 'type'=>$type);
        $return         = Service::getInstance('message')->getMessageList($data);
        if(empty($return)) {
            $this->respon(1, array());
        } else {
            $this->respon(1, $return);
        }
    }
   
    /**
     * 标记已读
     * 接口参数: 
     * 用户ID
     * 消息ID: [{"id":消息ID, "associated_id":关联ID,"type":消息类型}]
     */
    public function signReadStatusAction()
    {
        $userID     = $this->getPost('uid')?$this->getPost('uid'):Yaf_Registry::get('uid');
        $messageIDList = $this->getPost('messageIDList');
        if(empty($messageIDList)) {
            $this->respon(0, '请选择消息');
        }
        $data   = array('user_id' => $userID, 'messageIDList' => $messageIDList);
        $result = Service::getInstance('message')->signReadStatusList($data);
        if($result) {
            $this->respon(1, '标记成功');
        } else {
            $this->respon(0, '标记失败');
        }
    }

    /**
     * 删除消息
     * 接口参数：
     * 用户ID
     * 消息ID列表
     * 类型 1、官方消息，2、订单
     * @return [type] [description]
     */
    public function delMessageAction()
    {
        $userID     = $this->getPost('uid')?$this->getPost('uid'):Yaf_Registry::get('uid');
        $messageIDList = $this->getPost('messageIDList');
        if(empty($messageIDList)) {
            $this->respon(0, '请选择消息');
        }
        $data = array('user_id' => $userID, 'messageIDList' => $messageIDList);
        $result = Service::getInstance('message')->delMessage($data);
        if($result) {
            $this->respon(1, '删除成功');
        } else {
            $this->respon(0, '删除失败');
        }
    }
}