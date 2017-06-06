<?php
header("Content-type:text/html;charset=utf-8");
$url = "http://file.api.weixin.qq.com/cgi-bin/media/get?access_token=fU8-f3VbaHtpZBN6ypej2_OBs4NveuTtkoQdpD8MvupVGbnQZ4eLyCF4rycKwU3u9PotHEPOLakwqG5evic6uN8feiVs6EJ1aDncTmAu4j-QvJ241p9Pip_3g3gtKFhYGSCeADARDN&media_id=lSW_V3gYS_89EnSWayqiGG0sQSWVwZ0npTgH2bGmiHbz9IDGi-22aS-oYq8STrjk";
$ch = curl_init($url) ;
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true) ; // 获取数据返回
curl_setopt($ch, CURLOPT_BINARYTRANSFER, true) ; // 在启用 CURLOPT_RETURNTRANSFER 时候将获取数据返回
$output = curl_exec($ch) ;

var_dump($output);