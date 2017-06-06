<?php
class StateController extends BaseController
{
    /*
     * 全部订单状态
     */
    public function qstateAction(){
       $jssdk = new Jssdk();
       $jssdk->getInstance("wxe6e1cc76af59d8a1", "6c9c754baa9e00310abdc5f6c538a8e1");
       $signPackage = $jssdk->GetSignPackage();
       $this->_view->jsdata = $signPackage;
        $url = $this->_view->weixinapi."/order/getOrderList";
        $type= (int)$type=isset($_GET['status'])?$_GET['status']:0;
        if($type>5 || is_string($type)){
            $type=0;
        }
        $uid = Yaf_Registry::get('uid');
        $arr=array(
            'uid'=>$uid,
            'status'=>$type
        );
        $result = Util::httpRequest($url,$arr);
        $result = json_decode($result,true);
        $this->_view->title ="订单列表";
        if(!empty($result['data']) && $result["success"]==1){
            $this->_view->info = $result['data'];
            $this->_view->type = $type;
        }else{
            $this->_view->info =0;
            $this->_view->type = $type;
        }
    }
    /*
     *取消订单 提醒订单
     *@param $type int 支付1 取消订单2 提醒3 签收4 删除 5
     */
    public function qorderAction(){         
     $id=isset($_POST['id'])?$_POST['id']:'';
     $type=(int)$type=isset($_POST['type'])?$_POST['type']:'';
     if($type>7 || is_string($type) || $type===''){
         exit(json_encode(array('code'=>0,'message'=>'异常错误')));
     }
     if($type==1){
        exit(json_encode(array('type'=>$type,'url'=>'/cart/paymoney')));   
     }else if($type==2){
         $url = $this->_view->weixinapi."/order/cancel";
     }else if($type==3){
         $url = $this->_view->weixinapi."/order/remindSend";
     }else if($type==4){
         $url = $this->_view->weixinapi."/order/receiving";
     }else if($type==5){
         $url = $this->_view->weixinapi."/order/closeDeal";
     }
     $uid = Yaf_Registry::get('uid');
      $arr=array(
          'uid'=>$uid,
           'order_id'=>$id
       );
       $result = Util::httpRequest($url,$arr);
       $result = json_decode($result,true);
       exit(json_encode(array('type'=>$type,'message'=>$result)));
    }
    /*
     * 订单详情页
     */
    public function infoAction(){
        $jssdk = new Jssdk();
        $jssdk->getInstance("wxe6e1cc76af59d8a1", "6c9c754baa9e00310abdc5f6c538a8e1");
        $signPackage = $jssdk->GetSignPackage();
        $this->_view->jsdata = $signPackage;
        
        $id=isset($_GET["mid"])?$_GET["mid"]:'';//消息id
        $associated_id=isset($_GET["associated_id"])?$_GET["associated_id"]:'';//关联id
        $type=isset($_GET["type"])?$_GET["type"]:'';//消息类型
        //更新消息状态
        if($id && $type && $associated_id){
            $this->updata($id,$associated_id,$type);
        }
        $url = $this->_view->weixinapi."/order/orderInfo";
        $id=(int)$type=isset($_GET['id'])?$_GET['id']:'';
         if(is_string($id)){
             exit;
         }
         setcookie('order_info','',time()-3600,'/');
         $uid = Yaf_Registry::get('uid');
         $arr=array('order_id'=>$id,'uid'=>$uid );
         $result = Util::httpRequest($url,$arr);
         $result = json_decode($result,true);
         if(!empty($result['data'])){
             $this->_view->info = $result['data'];
         }
         $this->_view->title ="订单详细列表";
         if(!empty($_GET['associated_id'])){
             $this->_view->url ="/message/qmessage";
         }else{
             $this->_view->url ="/state/qstate";
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