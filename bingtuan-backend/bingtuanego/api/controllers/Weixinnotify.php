<?php

class WeixinnotifyController extends BaseController
{
    private $wxpayConfig;
    private $parameters;
    private $returnParameters;
    
    public function init(){
        $this->wxpayConfig['key'] = 'ae3c66a66a59281fc9b162cfe7eed50c';
        //YshowrxeE5NqrSazM9YIiUnaZ588Qm4c
    }
    /**
     *  服务器异步通知页面路径
     */
    public function PaynotifyAction() {
        /**
         * 通用通知接口demo
         * ====================================================
         * 支付完成后，微信会把相关支付和用户信息发送到商户设定的通知URL，
         * 商户接收回调信息后，根据需要设定相应的处理流程。
         *
         * 这里举例使用log文件形式记录回调信息。
         */
        
        // 存储微信的回调
        //$xml = $GLOBALS['HTTP_RAW_POST_DATA'];
        Log::simpleappend('notify_weixin_in', date('Y-m-d H:i:s').'进入回调了');
        $xml = file_get_contents("php://input");
        if(!$xml){
            Log::simpleappend('notify_weixin_null', date('Y-m-d H:i:s').'回调数据为空');
            exit;
        }
        $data = $this->xmlToArray($xml);
        
        // 验证签名,并回应微信。
        // 对后台通知交互时,如果微信收到商户的应答不是成功或超时,微信认为通知失败，
        // 微信会通过一定的策略（如30分钟共8次）定期重新发起通知
        // 尽可能提高通知的成功率,但微信不保证通知最终能成功。
        if($this->checkSign($data) == FALSE){
            $this->setReturnParameter("return_code", "FAIL");        // 返回状态码
            $this->setReturnParameter("return_msg", "签名失败"); // 返回信息
        } else {
            $this->setReturnParameter("return_code", "SUCCESS"); // 设置返回码
        }
        $returnXml = $this->returnXml();
        echo $returnXml;
        Log::simpleappend('notify_weixin_post', $returnXml);
        //==商户根据实际情况设置相应的处理流程，此处仅作举例=======
    
        // 以log文件形式记录回调信息
        $strMSG = date("Y-m-d H:i:s"). "【接收到的notify通知】:\n" . $xml . "\n";
       
        Log::simpleappend('notify_weixin', json_encode($strMSG));
        
        if($this->checkSign($data) == TRUE) {
            if ($data["return_code"] == "FAIL") {  
                //$log_->log_result($log_name, "【通信出错】:\n" . $xml . "\n");
                $strMSG = date("Y-m-d H:i:s")."【通信出错】:\n" . $xml . "\n";
                Log::simpleappend('notify_weixin', json_encode($strMSG));
            } elseif ($data["result_code"] == "FAIL"){
                // 此处应该更新一下订单状态,商户自行增删操作
                $strMSG = date("Y-m-d H:i:s")."【业务出错】:\n" . $xml . "\n";
                Log::simpleappend('notify_weixin', json_encode($strMSG));
            } else {
                // 此处应该更新一下订单状态,商户自行增删操作
                $orderid = $data["out_trade_no"];
                //$log_->log_result($log_name, "【支付成功】:\n" . $orderid . "\n");
                $strMSG = date("Y-m-d H:i:s")."【支付成功】:\n" . $orderid . "\n";
                Log::simpleappend('notify_weixin', json_encode($strMSG));
                /*$shop = A('Wap/Shop');
                $shop->EndPay($orderid, 'wxjssdk');
                $url = U('/Wap/Shop/orderList/type/2');
                header('Location:' . $url);*/
//                 $orderInfo = Service::getInstance("orders")->getOrderByOid($orderid);
//                 if($orderInfo['status']=='0'){
//                     $sql = Service::getInstance('user')->updateUser($orderInfo['points'],$orderInfo['paypoints'],$orderInfo['money'],$orderInfo['uid']);
//                     Log::simpleappend('notify_weixin_update_1', $sql."\n");
               $resData = array('pay_id'=>1,'trade_no'=>$data['transaction_id']);
//                // 循环获取ID，保证获取ID
//                for ($i = 0; $i < 3; $i++) {
//                    $orderID = Service::getInstance('order')->getOrderNumber($orderid);
//                    if ($orderID > 0) {
//                        break;
//                    }
//                }
               if (Service::getInstance("order")->updateOrderPayStatus($data['attach'],$resData)) { //微信支付成功
                   Service::getInstance("message")->addMessage($data['attach']);
               }
//                 }
            }
    
            //商户自行增加处理流程,
            //例如：更新订单状态
            //例如：数据库操作
            //例如：推送支付完成信息
        }
        exit;
    }
    private function checkSign($data) {
        $tmpData = $data;
        unset($tmpData['sign']);
         
        $sign = $this->getSign($tmpData);//本地签名
        if ($data['sign'] == $sign) {
            return TRUE;
        }
        return FALSE;
    }
    
    private function trimString($value) {
        $ret = null;
        if (null != $value) {
            $ret = $value;
            if (strlen($ret) == 0) {
                $ret = null;
            }
        }
        return $ret;
        
    }
    /**
     *  作用：生成签名
     */
    private function getSign($Obj) {
        foreach ($Obj as $k => $v) {
            if($k !='sign'){
            $Parameters[$k] = $v;
            }
        }
        //签名步骤一：按字典序排序参数
        ksort($Parameters);
        $String = $this->formatBizQueryParaMap($Parameters, false);
        //echo '【string1】'.$String.'</br>';
        //签名步骤二：在string后加入KEY
        $String = $String."&key=".$this->wxpayConfig['key'];
        //echo "【string2】".$String."</br>";
        //签名步骤三：MD5加密
        $String = md5($String);
        //echo "【string3】 ".$String."</br>";
        //签名步骤四：所有字符转为大写
        $result_ = strtoupper($String);
        //echo "【result】 ".$result_."</br>";
        return $result_;
    }
    
    /**
     *  作用：格式化参数，签名过程需要使用
     */
    private function formatBizQueryParaMap($paraMap, $urlencode) {
        $buff = "";
        ksort($paraMap);
        foreach ($paraMap as $k => $v)
        {
            if($urlencode)
            {
                $v = urlencode($v);
            }
            //$buff .= strtolower($k) . "=" . $v . "&";
            //if($k && $v){
                $buff .= $k . "=" . $v . "&";
            //}
        }
        $reqPar = '';
        if (strlen($buff) > 0) {
            $reqPar = substr($buff, 0, strlen($buff)-1);
        }
        return $reqPar;
    }
    /**
     *  作用：将xml转为array
     */
    private function xmlToArray($xml) {
        //将XML转为array
        $array_data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $array_data;
    }
    
 
    /**
     * 设置返回微信的xml数据
     */
    private function setReturnParameter($parameter, $parameterValue) {
        $this->returnParameters[$this->trimString($parameter)] = $this->trimString($parameterValue);
    }
 
    /**
     * 将xml数据返回微信
     */
    private function returnXml() {
        $returnXml = $this->arrayToXml($this->returnParameters);
        return $returnXml;
    }
    /**
     *  作用：array转xml
     */
    private function arrayToXml($arr) {
        $xml = "<xml>";
        foreach ($arr as $key=>$val) {
            if (is_numeric($val)) {
                $xml.="<".$key.">".$val."</".$key.">";
            } else {
                $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
            }
        }
        $xml.="</xml>";
        return $xml;
    }
    

    
    
   
}