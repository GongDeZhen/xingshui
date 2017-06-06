<?php
/* *
 * 配置文件
 * 版本：1.0
 * 日期：2016-06-06
 * 说明：
 * 以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己网站的需要，按照技术文档编写,并非一定要使用该代码。
 * 该代码仅供学习和研究支付宝接口使用，只是提供一个参考。
*/
 
//↓↓↓↓↓↓↓↓↓↓请在这里配置您的基本信息↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓
//合作身份者ID，签约账号，以2088开头由16位纯数字组成的字符串，查看地址：https://openhome.alipay.com/platform/keyManage.htm?keyType=partner
$alipay_config['partner']		= '2088912138458597';

//商户的私钥,此处填写原始私钥去头去尾，RSA公私钥生成：https://doc.open.alipay.com/doc2/detail.htm?spm=a219a.7629140.0.0.nBDxfy&treeId=58&articleId=103242&docType=1
$alipay_config['private_key']	= '-----BEGIN RSA PRIVATE KEY-----
MIICXQIBAAKBgQCzMHqE1Ty/LlvQ0UvJFZvlePyqOzlobJ756NoivueNiuOQJ29k
aKa8lNNCMfKx8TkyB9u/BlppsiZ3IUHmm0nys+g7sLhrYBDGP6bmGE4X8StgJWt+
yGGEFRu34/6dWbI7nYKNqBlg/QOxQUq/8Ra1OzjOjFjg2ySwTjFnab5D2wIDAQAB
AoGAAxIKl2ob/16o/hwIA2sWX8lB8xzOsAZkdv3RdxT0XM2LdrXmR78vVD3snM7E
TN5IT54o2s4eEH+X/ySXv37btoCTWr4K6mVNrskInq+itHzY2wt6A2Xpcqnicn5Y
3b4bdY0xV3LDwLUIHrUedOZEAGYRz5hWw3Tc23GTrvXYb0kCQQDtw3WNzua5OyiP
+AuhluvM3grVD9zrFL5Mlxg+tislM1NyX6pjFLwu3pCp30NuVMLqUDuuBwGk/eTN
bo4WsHlVAkEAwO7nGaTNUj460N95EPSxmdoCfQZnS6sEZY7LsF7+aDJjzXf/vZBG
tOF7fk6H+N0mgLuDj5dyVNrQa03IVbMIbwJBANqBqBY0EFwo3wU4e0/8yCXV5fSb
CHdO7/ipX1G6zvn4KndRLeCy3KLiTeaf1XOfGCaIp+AudjdwUgbt1E1Lz6UCQG3O
ixF5TrKkYG1JA2q3Ge3qusfVwSy75QC4SQ3Uww8ivtLOnWuSleuU+ehlYdYuaB+U
mcg79gJ9l2m7F25PO1kCQQCSbDRHWE2ofR6ID48IZQA8ixwQOc/TmzZ0QiIM21us
Rsl8lJyaIp6wUES3emEib/OAWsoroIQgtLFXA9GoIiFv
-----END RSA PRIVATE KEY-----';

//支付宝的公钥，查看地址：https://openhome.alipay.com/platform/keyManage.htm?keyType=partner
$alipay_config['alipay_public_key']= file_get_contents('key/rsa_public_key.pem');

//异步通知接口
$alipay_config['service']= 'mobile.securitypay.pay';
//↑↑↑↑↑↑↑↑↑↑请在这里配置您的基本信息↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑

//签名方式 不需修改
$alipay_config['sign_type']    = strtoupper('RSA');

//字符编码格式 目前支持 gbk 或 utf-8
$alipay_config['input_charset']= strtolower('utf-8');

//ca证书路径地址，用于curl中ssl校验
//请保证cacert.pem文件在当前文件夹目录中
$alipay_config['cacert']    = getcwd().'/cacert.pem';

//访问模式,根据自己的服务器是否支持ssl访问，若支持请选择https；若不支持请选择http
$alipay_config['transport']    = 'http';
?>