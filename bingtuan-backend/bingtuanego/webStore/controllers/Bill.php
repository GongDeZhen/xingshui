<?php
class BillController extends BaseController
{
    /*
     *我的账单列表
     */
    public function listAction()
    {

        $url=$this->_view->weixinapi."/myaccount/myAccountOrder";
        $uid = Yaf_Registry::get('uid');
        $year=(int)$account_id=isset($_POST['year'])?$_POST['year']:0;
        if($year==0 || $year=='' || is_string($year)){
            $year=2016;
        }
        $arr=array('year'=>$year,'uid'=>$uid);
        $result=Util::httpRequest($url,$arr);
        $result= json_decode($result,true);
       if(!empty($result["data"])){
           $this->_view->binfo =$result["data"];
       }
    }
    /*
     *账单详细信息
     */
    public function infoAction()
    {
        $url=$this->_view->weixinapi."/myaccount/accountInfo";
        $account_id=(int)$account_id=isset($_GET['account_id'])?$_GET['account_id']:0;
        if($account_id==0 || $account_id=='' || is_string($account_id)){
            header('Location: /bill/list/');
        }
        $arr=array('account_id'=>$account_id,'uid'=>$_COOKIE['uid']);
        $result=Util::httpRequest($url,$arr);
        $result= json_decode($result,true);
        if(!empty($result["data"])){
            $this->_view->binfo =$result["data"];
        }
    }
}