<?php
/**
 * Created by PhpStorm.
 * User: huangtuo
 * Date: 16/9/12
 * Time: 下午5:11
 */
class NotifyController extends BaseController
{
    /**
     *
     * 支付的回调函数
     */
    public function notifyAction(){

        file_put_contents('/home/htdocs/bingtuanego/weixin/public/notify_url.log','aaa',FILE_APPEND);

        Yaf_Dispatcher::getInstance()->autoRender(FALSE);

        include_once("../../lib/wxpay/demo/log_.php");
        include_once("../../lib/wxpay/WxPayPubHelper/WxPayPubHelper.php");
        //var_dump($notify_url);
        //使用通用通知接口
        $notify = new Notify_pub();

        //存储微信的回调
        $xml = $GLOBALS['HTTP_RAW_POST_DATA'];
        $notify->saveData($xml);
        $res = $notify->xmlToArray($xml);
        file_put_contents('/home/htdocs/bingtuanego/weixin/public/notify_url.log',$xml,FILE_APPEND);
        $data = json_encode($res);
        exit;
        /*
        //验证签名，并回应微信。
        //对后台通知交互时，如果微信收到商户的应答不是成功或超时，微信认为通知失败，
        //微信会通过一定的策略（如30分钟共8次）定期重新发起通知，
        //尽可能提高通知的成功率，但微信不保证通知最终能成功。
        if($notify->checkSign() == FALSE){
            $notify->setReturnParameter("return_code","FAIL");//返回状态码
            $notify->setReturnParameter("return_msg","签名失败");//返回信息
        }else{
            $notify->setReturnParameter("return_code","SUCCESS");//设置返回码
        }
        $returnXml = $notify->returnXml();

        //==商户根据实际情况设置相应的处理流程，此处仅作举例=======

        //以log文件形式记录回调信息
        $log_ = new Log_();
        $log_name="/home/htdocs/bingtuanego/weixin/public/notify_url.log";//log文件路径
        $log_->log_result($log_name,"【接收到的notify通知】:\n".$xml."\n");

        if($notify->checkSign() == TRUE)
        {
            if ($notify->data["return_code"] == "FAIL") {
                //此处应该更新一下订单状态，商户自行增删操作
                $log_->log_result($log_name,"【通信出错】:\n".$xml."\n");
            }
            elseif($notify->data["result_code"] == "FAIL"){
                //此处应该更新一下订单状态，商户自行增删操作
                $log_->log_result($log_name,"【业务出错】:\n".$xml."\n");
            }else{

                $out_trade_no = $res['out_trade_no'];//订单号
                $transaction_id = $res['transaction_id'];//支付单号
//		    $payjson = $data;
                //此处应该更新一下订单状态，商户自行增删操作
                $url = "http://api.dev.bingtuanego.com/order/updateodersStatus";//修改订单状态





                $resData = array('pay_id'=>4,'attach'=>$res['attach'],'trade_no'=>$res['transaction_id']);
                $a=json_encode($resData);
                $ress=Util::httpRequest($url, $resData);
                $log_->log_result($log_name,"【支付成功】\n".$xml."\n".$url."\n".$ress."\n".$a.'\n');
                //Service::getInstance("order")->updateOrderPayStatus($res['attach'],$resData);
// 		    if (Service::getInstance("order")->updateOrderPayStatus($res['attach'],$resData)) { //微信支付成功
// 		        Service::getInstance("message")->addMessage($res['attach']);
// 		    }

            }
            //商户自行增加处理流程,
            //例如：更新订单状态
            //例如：数据库操作
            //例如：推送支付完成信息
        }
*/
    }
}
