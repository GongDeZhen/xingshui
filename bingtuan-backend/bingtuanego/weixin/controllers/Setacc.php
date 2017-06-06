<?php
class SetaccController extends BaseController
{
    public function IndexAction(){
       $count=0;
       $url= $this->_view->weixinapi."myaccount/index";

       $uid = Yaf_Registry::get('uid');
       $arr=array('uid'=>$uid);
       $info=Util::httpRequest($url, $arr);
       $info=json_decode($info,true);
       $goods_info=empty($_SESSION['goods_info'])?'':json_decode($_SESSION['goods_info'],true);
       $receiver=empty($_COOKIE['receiver'])?0:$_COOKIE['receiver'];
       if(!empty($goods_info)){
           foreach($goods_info as $k => $v){
               $count+=$v["count"];
           }
       }
       if(!empty($info['data']) && $info['success']==1){
         $this->_view->info=$info['data'];
         $this->_view->Num=$this->messNum($info["data"]["userInfo"]['user_type']);
       }else{
           $this->_view->info=0;
           $this->_view->Num=0;
       }
       $this->_view->receiver=$receiver;
       $this->_view->count =$count;
       $this->_view->title ="我的账户";
    }
    public function messNum($receiver){
        $messNumUrl= $this->_view->weixinapi."/message/messageUnknownNumber";
        $uid = Yaf_Registry::get('uid');
        $arr=array('receiver'=>$receiver,'uid'=>$uid);
        $info=Util::httpRequest($messNumUrl, $arr);
        $info=json_decode($info,true);
        if(!empty($info['success']) && $info['success']==1){
            return $info['data'];
        }else{
            return '';
        }
    }
}