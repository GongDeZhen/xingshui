<?php

/**
 * User: azhi
 * Date: 16/11/27
 * Time: 下午3:31
 */

include "Message.class.php";

class BaseRequest extends Message {

    //通知地址(使用异步服务时必填)
    private $notifyUrl;

    //回调地址(需要回跳到商户时必填)
    private $returnUrl;

    //交易信息
    private $tradeInfo;

    //交易类型
    private $paymentType;

    //易极付交易号
    private $tradeNo;

    //退款金额
    private $refundAmount;

    //退款原因
    private $refundReason;

    //交易查询订单号
    private $tradeNos;

    /**
     * @return mixed
     */
    public function getTradeNos()
    {
        return $this->tradeNos;
    }

    /**
     * @param mixed $tradeNos
     */
    public function setTradeNos($tradeNos)
    {
        $this->tradeNos = $tradeNos;
        $this->arrContent['tradeNos'] = $tradeNos;
    }

    /**
     * @return mixed
     */
    public function getNotifyUrl()
    {
        return $this->notifyUrl;
    }

    /**
     * @param mixed $notifyUrl
     */
    public function setNotifyUrl($notifyUrl)
    {
        $this->notifyUrl = $notifyUrl;
        $this->arrContent['notifyUrl'] = $notifyUrl;
    }

    /**
     * @return mixed
     */
    public function getReturnUrl()
    {
        return $this->returnUrl;
    }

    /**
     * @param mixed $returnUrl
     */
    public function setReturnUrl($returnUrl)
    {
        $this->returnUrl = $returnUrl;
        $this->arrContent['returnUrl'] = $returnUrl;
    }

    /**
     * @return mixed
     */
    public function getTradeInfo()
    {
        return $this->tradeInfo;
    }

    /**
     * @param mixed $tradeInfo
     */
    public function setTradeInfo(Array $tradeInfo)
    {
        $this->tradeInfo = json_encode($tradeInfo, JSON_UNESCAPED_UNICODE);
        $this->arrContent['tradeInfo'] = json_encode($tradeInfo, JSON_UNESCAPED_UNICODE);
    }

    /**
     * @return mixed
     */
    public function getPaymentType()
    {
        return $this->paymentType;
    }

    /**
     * @param mixed $paymentType
     */
    public function setPaymentType($paymentType)
    {
        $this->paymentType = strtoupper($paymentType);
        $this->arrContent['paymentType'] = strtoupper($paymentType);
    }

    /**
     * @return mixed
     */
    public function getTradeNo()
    {
        return $this->tradeNo;
    }

    /**
     * @param mixed $tradeNo
     */
    public function setTradeNo($tradeNo)
    {
        $this->tradeNo = $tradeNo;
        $this->arrContent['tradeNo'] = $tradeNo;
    }

    /**
     * @return mixed
     */
    public function getRefundAmount()
    {
        return $this->refundAmount;
    }

    /**
     * @param mixed $refundAmount
     */
    public function setRefundAmount($refundAmount)
    {
        $this->refundAmount = $refundAmount;
        $this->arrContent['refundAmount'] = $refundAmount;
    }

    /**
     * @return mixed
     */
    public function getRefundReason()
    {
        return $this->refundReason;
    }

    /**
     * @param mixed $refundReason
     */
    public function setRefundReason($refundReason)
    {
        $this->refundReason = $refundReason;
        $this->arrContent['refundReason'] = $refundReason;
    }


}