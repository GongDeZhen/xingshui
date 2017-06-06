<?php
class DiscountController extends BaseController
{
    /*
     *券列表
     */
    public function syhquanAction()
    {
       $type=isset($_GET['type'])?$_GET['type']:0;
        if($type==3){
           $url = $this->_view->weixinapi."myaccount/vouchers";
        }else{
            $url = $this->_view->weixinapi."myaccount/coupons";
        }
        $uid = Yaf_Registry::get('uid');
        $arr=array(
            'online'=>$type,
            'uid'=>$uid
        );
        $result = Util::httpRequest($url,$arr);
        $result = json_decode($result,true);
        if($result['success']==1){
        	$this->_view->info = $result['data'];
        }
        $this->_view->type = $type;
    }
}