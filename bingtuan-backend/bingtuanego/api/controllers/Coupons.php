<?php
class CouponsController extends BaseController
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
     * 查询所有可用的优惠卷
     */
    public function getCouponsAction(){
        $goodsArr = $this->getPost('goods_arr','');
        $goods_amount = $this->getPost('goods_amount', 0);
        
        if($goodsArr == '') $this->respon( 0 , '商品id不能为空');
        
        $list = Service::getInstance('coupons')->getCoupons($goodsArr,$goods_amount);
        $this->respon(1,$list);
    }
    
    /**
     * 删除优惠劵
     */
    public function delCouponAction()
    {
        $userID = Yaf_Registry::get('uid');
        $couponArr = $this->getPost('couponArr');
        if ( $couponArr == "" ) $this->respon( 0 , "请选择要删除的优惠劵" );
        $res = Service::getInstance('coupons')->delCoupon($userID, $couponArr);
        if($res) {
            $this->respon(1,'删除成功');
        } else {
            $this->respon(0,'删除失败');
        }
    }
}