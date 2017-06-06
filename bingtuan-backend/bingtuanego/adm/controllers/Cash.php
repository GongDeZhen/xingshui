<?php
class CashController extends BaseController
{
    public function indexAction(){
        $myaccount = Service::getInstance('myaccount');
        $this->_view->cash_list = $myaccount->Cash_list();
    }
    //拒绝打款
    public function refuseCashAction(){
        $cash_id = $this->getQuery('id','');
        $myaccount = Service::getInstance('myaccount');
        echo $myaccount->refuseCash($cash_id);exit;
    }
    //打款
    public function haveMoneyAction()
    {
        $cash_id = $this->getQuery('id','');
        $myaccount = Service::getInstance('myaccount');
        echo $myaccount->refuseCash($cash_id,2);exit;
    }
    //查看明细
    public function checkDetailsAction(){
        $cash_id = $this->getQuery('caid','');
        $myaccount = Service::getInstance('myaccount');
        $this->_view->cash_info = $cash_info = $myaccount->getCash($cash_id);

        $this->_view->ordre_info = $orderinfo = $myaccount->getOrder_All($cash_info['user_id'],$cash_info['start_cycle'],$cash_info['stop_cycle']);
//        echo '<pre>';
//        print_r($cash_info);
//        print_r($orderinfo);
//        echo '</pre>';
        //$this->_view->sell_order_info = $sell_order_info = $myaccount->getOrder_All($cash_info['user_id'],$cash_info['start_cycle'],$cash_info['stop_cycle'],2);

    }
}