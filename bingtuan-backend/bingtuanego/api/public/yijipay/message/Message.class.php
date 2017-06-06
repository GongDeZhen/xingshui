<?php

/**
 * User: azhi
 * Date: 16/11/27
 * Time: 下午3:31
 */

abstract class Message{

    //服务名称("必填")
    protected $service;

    //请求流水号: 标识商户请求的唯一性
    protected $orderNo;

    //商户ID（必填)
    protected $partnerId;

    //交易订单号（在有交易的场景必须填写。具有交易唯一性)
    protected $merchOrderNo;

    //签名类型（必填)
    protected $signType = "MD5";

    //签名（必填)
    protected $sign;

    //服务协议（非必填)
    protected $protocol = "httpPost";

    //服务版本（非必填)
    protected $version;

    //会话参数（调用端的API调用会话参数，请求参数任何合法值，在响应时会回传给调用端)
    protected $context;

    //用于存放验签参数数组
    protected $arrContent;

    /**
     * @return mixed
     */
    public function getService()
    {
        return $this->service;
    }

    /**
     * @param mixed $service
     */
    public function setService($service)
    {
        $this->service = $service;
        $this->arrContent['service'] = $service;
    }


    /**
     * @return string
     */
    public function getOrderNo()
    {
        return $this->orderNo;
    }

    /**
     * @param string $orderNo
     */
    public function setOrderNo($orderNo)
    {
        $this->orderNo = $orderNo;
        $this->arrContent['orderNo'] = $orderNo;
    }

    /**
     * @return string
     */
    public function getPartnerId()
    {
        return $this->partnerId;
    }

    /**
     * @param string $partnerId
     */
    public function setPartnerId($partnerId)
    {
        $this->partnerId = $partnerId;
        $this->arrContent['partnerId'] = $partnerId;
    }

    /**
     * @return string
     */
    public function getMerchOrderNo()
    {
        return $this->merchOrderNo;
    }

    /**
     * @param string $merchOrderNo
     */
    public function setMerchOrderNo($merchOrderNo)
    {
        $this->merchOrderNo = $merchOrderNo;
        $this->arrContent['merchOrderNo'] = $merchOrderNo;
    }

    /**
     * @return string
     */
    public function getSignType()
    {
        return $this->signType;
    }

    /**
     * @param string $signType
     */
    public function setSignType($signType)
    {
        $this->signType = $signType;
        $this->arrContent['signType'] = $signType;
    }

    /**
     * @return string
     */
    public function getSign()
    {
        return $this->sign;
    }

    /**
     * @param string $sign
     */
    public function setSign($sign)
    {
        $this->sign = $sign;
        $this->arrContent['sign'] = $sign;
    }

    /**
     * @return string
     */
    public function getProtocol()
    {
        return $this->protocol;
    }

    /**
     * @param string $protocol
     */
    public function setProtocol($protocol)
    {
        $this->protocol = $protocol;
        $this->arrContent['protocol'] = $protocol;
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @param string $version
     */
    public function setVersion($version)
    {
        $this->version = $version;
        $this->arrContent['version'] = $version;
    }

    /**
     * @return string
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @param string $context
     */
    public function setContext($context)
    {
        $this->context = $context;
        $this->arrContent['context'] = $context;
    }

    /**
     * @return array
     */
    public function getArrContent(){
        return $this->arrContent;
    }


    /**
     * 获取请求参数字符串：
     * 1) 去除空置和sign
     * 2) 完成排序， 增加签名
     * 3) 签名
     * @return string
     */
    public function getPreSignStr(){
        $arr = $this->paramsFilter($this->arrContent);
        $str = $this->createLinkString($arr);
        return $str;
    }

    /**
     * 获取sign
     * @param $preStr
     * @param $config
     * @return null|string
     */
    public function genSign($preStr, $config){

        $sign = null;

        if("RSA" == $this->signType){
            if($this->checkEmpty($config['rsaPrivateKey'])){
                $priKey = $config['rsaPrivateKey'];
                $res = "-----BEGIN RSA PRIVATE KEY-----\n" .
                    wordwrap($priKey, 64, "\n", true) .
                    "\n-----END RSA PRIVATE KEY-----";
            }
//            else {
//                $priKey = file_get_contents($config['rsaPrivateKey']);
//                $res = openssl_get_privatekey($priKey);
//            }

            ($res) or die('您使用的私钥格式错误，请检查RSA私钥配置');

            openssl_sign($preStr, $sign, $res);

//            if(!$this->checkEmpty($this->rsaPrivateKeyFilePath)){
//                openssl_free_key($res);
//            }
            $sign = base64_encode($sign);

        }else{
            //MD5签名
            $data = $preStr . $config['md5Key'];
            $sign = md5($data);
        }

//        echo "<br/>================sign：<br/>" . $sign ;
        return $sign;

    }

    /**
     * 除去数组中的空值和签名参数
     * @param $params array 签名参数组
     * @return array 去掉空值与签名参数后的新签名参数组
     */
    private function paramsFilter($params) {
        $para_filter = array();
        while (list ($key, $val) = each ($params)) {
            if($key == "sign" || $val === "") continue;
            else	$para_filter[$key] = $params[$key];
        }
        ksort($para_filter);
        reset($para_filter);
        return $para_filter;
    }

    /**
     * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
     * @param $params array 需要拼接的数组
     * @return string 拼接完成以后的字符串
     */
    function createLinkString($params) {
        $strParams = "";
        foreach ($params as $key => $val) {
            if(is_array($val)){
                $val = json_encode($val, JSON_UNESCAPED_UNICODE);
            }else if(is_bool($val)){
                if($val) $val="true";else $val="false";
            }
            $strParams .= "$key=" . ($val) . "&";
        }
        $strParams = substr($strParams, 0, -1);

        //如果存在转义字符，那么去掉转义
        if(get_magic_quotes_gpc()){$strParams = stripslashes($strParams);}
        return $strParams;
    }

    /**
     * 校验$value是否非空
     *  if not set ,return true;
     *    if is null , return true;
     * @param string $value
     * @return bool
     */
    protected function checkEmpty($value) {
        if (!isset($value))
            return true;
        if ($value === null)
            return true;
        if (trim($value) === "")
            return true;

        return false;
    }

}