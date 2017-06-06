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

    //用户名
    private $userName;

    //用户类型
    private $registerUserType = 'PERSONAL';

    //手机号
    private $mobile;

    //易极付交易号
    private $tradeNo;

    //交易动作
    private $executeAction;

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
    public function getExecuteAction()
    {
        return $this->executeAction;
    }

    /**
     * @param mixed $executeAction
     */
    public function setExecuteAction($executeAction)
    {
        $this->executeAction = $executeAction;
        $this->arrContent['executeAction'] = $executeAction;
    }

    /**
     * @return mixed
     */
    public function getUserName()
    {
        return $this->userName;
    }

    /**
     * @param mixed $userName
     */
    public function setUserName($userName)
    {
        $this->userName = $userName;
        $this->arrContent['userName'] = $userName;
    }

    /**
     * @return string
     */
    public function getRegisterUserType()
    {
        return $this->registerUserType;
    }

    /**
     * @param string $registerUserType
     */
    public function setRegisterUserType($registerUserType)
    {
        $this->registerUserType = $registerUserType;
        $this->arrContent['registerUserType'] = $registerUserType;
    }

    /**
     * @return mixed
     */
    public function getMobile()
    {
        return $this->mobile;
    }

    /**
     * @param mixed $mobile
     */
    public function setMobile($mobile)
    {
        $this->mobile = $mobile;
        $this->arrContent['mobile'] = $mobile;
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
}