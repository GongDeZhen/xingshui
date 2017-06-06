<?php
class MessageController extends BaseController
{
    /*
     * 获取消息
     */
    public function getMessage($receiver,$type){
        $url = $this->_view->weixinapi."message/getMessageList";
        if($type>5 || is_string($type)){
            $type=0;
        }
        if(is_string($receiver) || $receiver>3){
            return false;
        }
        $uid = Yaf_Registry::get('uid');
        $arr=array(
            'id'=>0,
            'uid'=>$uid,
            'receiver'=>$receiver,
            'page'=>0,
            'pageSize'=>1000,
            'type'=>$type
        );
        $result=Util::httpRequest($url,$arr);
        $result=json_decode($result,true);
        if(!empty($result['data'])){
            return $result['data'];
        }else{
            return array();
        }
    }
    /*
     * 全部消息
     */
    public function qmessageAction()
    {        
        $receiver=(int)$receiver= isset($_COOKIE['receiver'])?$_COOKIE['receiver']:1;
        $type= (int)$type=isset($_GET['type'])?$_GET['type']:0;
        $result=$this->getMessage($receiver,$type);
        if(!empty($result)){
            $this->_view->info = $result;
            $this->_view->type = $type;
            $this->_view->receiver = $receiver;
        }else{
            $this->_view->info =0;
            $this->_view->type = $type;
            $this->_view->receiver = 1;
        }
        $this->_view->title ="消息";
    }
    /*
     * 编辑消息
     */
    public function editmessAction()
    {
        $url = $this->_view->weixinapi."message/getMessageList";
        $receiver= (int)$receiver=isset($_GET['receiver'])?$_GET['receiver']:'';
        $type= (int)$type=isset($_GET['type'])?$_GET['type']:0;
        if($type>5 || is_string($type)){
            $type=0;
        }
        if(is_string($receiver) || $receiver>3){
            return false;
        }
        $uid = Yaf_Registry::get('uid');
        $arr=array(
            'id'=>0,
            'uid'=>$uid,
            'receiver'=>$receiver,
            'page'=>1,
            'pageSize'=>1000,
            'type'=>$type
        );
        $result=Util::httpRequest($url,$arr);
        $result=json_decode($result,true);
        if($result['data']){
            $this->_view->info = 1;
        }else{
            $this->_view->info = 0;
        }
        $this->_view->result = $result['data'];
        $this->_view->receiver = $receiver;
        $this->_view->type =$type;
        $this->_view->title ="消息";
    }
    /*
     * 消息  删除  标记
     */
    public function readAction(){
        if(!empty($_POST['info'])){
            $info=json_encode($_POST['info']);
        }else{
            exit(json_encode(array('code'=>0,'msg'=>'异常错误')));
        }
        $urltype=isset($_GET['type'])?$_GET['type']:'';
        if($urltype=='read'){
            $url = $this->_view->weixinapi."/message/signReadStatus";
        }else if($urltype=='remove'){
            $url = $this->_view->weixinapi."/message/delMessage";
        }else{
            exit(json_encode(array('code'=>0,'msg'=>'操作失误')));
        }
        $uid = Yaf_Registry::get('uid');
        $arr=array(
            'uid'=>$uid,
            'messageIDList'=>$info
        );
        $result=Util::httpRequest($url,$arr);
        $result=json_decode($result,true);
        if($result['success']==1){
            exit(json_encode(array('code'=>1,'msg'=>'成功')));
        }else{
            exit(json_encode(array('code'=>0,'msg'=>'失败')));
        }
    }
    /*
     * 消息详情
     */
    public function infoAction()
    {
        if($_POST["title"]!="" && $_POST['connect']!=""){
            $title=isset($_POST["title"])?$_POST["title"]:'';//消息标题
            $connect=isset($_POST["connect"])?$_POST["connect"]:'';//消息内容
            $time=$this->getPost('time');//消息时间
            $id=isset($_POST['id'])?$_POST['id']:0;//消息id
            $associated_id=isset($_POST['associated_id'])?$_POST['associated_id']:0;//关联Id
            $type=1;//消息类型
            $res=$this->updata($id,$associated_id,$type);
            exit(json_encode(array('code'=>1,'msg'=>'成功','title'=>$title,'connect'=>$connect,'time'=>$time)));
        }else{
            exit(json_encode(array('code'=>0,'msg'=>'查看失败')));
        }
    }

    public function infomessageAction()
    {
        $title=isset($_GET['title'])?$_GET['title']:'';
        $connect=isset($_GET['connect'])?$_GET['connect']:'';
        $time=$this->getQuery('time');
        if($title=='' && $connect=''){
            return false;
        }
        $this->_view->titles = $title;
        $this->_view->times = $time;
        $this->_view->connect = $connect;
        $this->_view->title ="消息详情";
    }
    /*
     * 账单详情
     */
    public function billmessAction(){
         $url=$url = $this->_view->weixinapi."/myaccount/accountInfo";
         $associated_id=(int)$associated_id=isset($_GET['associated_id'])?$_GET['associated_id']:'';
         if($associated_id==0){
             $result=array();
         }else{
             $arr=array('associated_id'=>$associated_id,'uid'=>$_COOKIE['uid']);
             $result=Util::httpRequest($url,$arr);
             $result=json_decode($result,true);
         }
    }
    /*
     * 更新消息状态
     */
    public function updata($id,$associated_id,$type){
        $arr=array();
        $arr[]=array('id'=>$id,'associated_id'=>$associated_id,'type'=>$type);
        $arr=json_encode($arr);
        $url=$url = $this->_view->weixinapi."/message/signReadStatus";
        $arr=array('messageIDList'=>$arr,'uid'=>$_COOKIE['uid']);
        $result=Util::httpRequest($url,$arr);
        return json_decode($result,true);
    }
    
}