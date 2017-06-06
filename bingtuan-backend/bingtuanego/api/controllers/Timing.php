<?php
class TimingController extends BaseController
{
    /**
     * 推送账单到期消息
     */
    public function pushOrderMessageAction()
    {
        $data = Service::getInstance('timing')->pushOrderMessage();
        Util::dump($data);
        die;
    }
    
    /**
     * 自动取消
     */
    public function cancelOrderAction()
    {
        Service::getInstance('timing')->cancelOrder();
        $this->respon(1, 200);
    }
    
    /**
     * 自动签收
     */
    public function receiveOrderAction()
    {
        Service::getInstance('timing')->receiveOrder();
        $this->respon(1, 200);
    }
}