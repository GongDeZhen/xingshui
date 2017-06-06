<?php
/**
 * 易极付配置信息
 * Created by PhpStorm.
 * User: nsinm
 * Date: 2017/1/19
 * Time: 15:03
 */

return [
    //商户ID stest
    'partnerId' => '20160802020011697041',
    //商户Key stest
    'md5Key' => '279cf642919e9efa80c0ba602507bda4',
    //service 快捷支付
    'service' => 'fastPayTradeRefund',
    //加密方式
    'signType' => 'MD5',
    //异步请求地址
    'notifyUrl' => 'http://api.bingtuanego.com/yijipay/refundNotify',
    //支付类型
    'paymentType' => 'PAYMENT_TYPE_UPMP',

    //商户ID snet
    //'partnerId' => '20160615020000091063',
    //商户Key snet
    //'md5Key' => '69a0f0bfec040cc3a0ed15f9b38e5130',

    //网关
//    'gatewayUrl' => "https://api.yiji.com/gateway.html",
//    'gatewayUrl' => "https://openapi.yijifu.net/gateway.html",
//    'gatewayUrl' => "http://192.168.46.3:8080/gateway.html",
    //测试地址
    'gatewayUrl' => "https://api.yiji.com/gateway.html",
    //正式地址
    //'gatewayUrl' => "http://api.yijifu.net/gateway.html",

//    'gatewayUrl' => "https://testapi.yiji.com/gateway.html",
];
