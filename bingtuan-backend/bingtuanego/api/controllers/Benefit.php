<?php
class BenefitController extends BaseController
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
     * 查询所有可用的满赠满减
     * post请求,参数为:
     * order_goods: {\"id\":\"86\",\"count\":1,\"price\":\"27.00\",\"activityId\":\"0\"},{\"id\":\"51880\",\"count\":1,\"price\":\"60.00\",\"activityId\":\"0\"}]
     * amount: 1000
     * total_count: 100
     */
    public function BenefitsAction(){
        $order_goods = $this->getPost('order_goods',[]);
        $amount = $this->getPost('amount',0);
        $total_count = $this->getPost('total_count',0);
        if($order_goods == []) $this->respon( 0 , '请将订单信息(order_goods,amount,total_count)post请求发送至服务器');
        // var_dump($order_goods);
        $goods_info = json_decode(stripslashes($order_goods),true);
        // $goods_info['uid'] = $this->_login_uid;
        Util::write_log('本次提交的订单信息为: '.json_encode($goods_info));
        $list = Service::getInstance('benefit')->getBenefits(['goods' => $goods_info,'uid'=>$this->_login_uid]);
        $this->respon(1,$list);
    }
}