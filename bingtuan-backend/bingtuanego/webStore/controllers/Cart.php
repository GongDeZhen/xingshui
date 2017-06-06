<?php
class CartController extends BaseController
{ 

    /*
     *购物车列表
     */
    public function indexAction()
    {   


         $goods_info=empty($_SESSION['goods_info'])?'':$_SESSION['goods_info'];//商品信息
         $auditType=empty($_COOKIE['auditType'])?0:$_COOKIE['auditType'];//用户状态
         if ($auditType==2){
             $arr_ginfo=json_decode($goods_info,true);
             $this->_view->goods_info=$arr_ginfo;
             $this->_view->auditType=$auditType;
             $this->_view->title ="订单列表";
         }else{
             $_SESSION['goods_info']='';
         }

    }
    /*
     *编辑车列表
     */
    public function editAction()
    {
        $goods_info=empty($_SESSION['goods_info'])?'':$_SESSION['goods_info'];
        $arr_ginfo=json_decode($goods_info,true);
        $url = $this->_view->weixinapi."goods/goodsInfo";
        $this->_view->goods_info=$arr_ginfo;
        $this->_view->title ="订单列表";
    }

    /*
     * 获取商品的详情，分带活动的不带活动的
     * @param $goods_id
     * @param $activity_id
     * @return array
     */

    public function getgoodinfo($goods_id,$activity_id=0){
        $url = $this->_view->weixinapi."goods/goodsInfo";
        $arr=array(
            'goods_id'=>$goods_id,
            'activity_id'=>$activity_id
        );
        $json = Util::httpRequest($url,$arr);
        $result = json_decode($json,true);
        return $result;
    }

    /*
     *订单确认
     */
    public function billingAction(){

        //$this->getOpenId('billing');

            $price=0;//商品优惠价格
        $totalprice=0;//商品总价格
        $acmoney=0;//优惠金额
        $jssdk = new Jssdk();
        $appid = Yaf_Application::app()->getConfig()->get('weixin')->get('APPID');
        $AppSecret = Yaf_Application::app()->getConfig()->get('weixin')->get('AppSecret');
        $jssdk->getInstance($appid, $AppSecret);
        $signPackage = $jssdk->GetSignPackage();
        $this->_view->jsdata = $signPackage;
        $arr_ginfo=empty($_SESSION['goods_info'])?'':json_decode($_SESSION['goods_info'],true);//商品信息

        /*
         * 将购物车里面商品的价格 和 查询出来的价格进行对比
         */
        //var_dump($arr_ginfo);
        foreach ($arr_ginfo as $k=>&$v){
            $goods_id = $v['id'];
            $activity_id =isset($v['activityId'])?$v['activityId']:0;
            $result=$this->getgoodinfo($goods_id,$activity_id);
            //判断商品是否下架
            if ($result['data']['is_show']==1){
                //判断是否还在活动中
                if (!$result){
                    $result=$this->getgoodinfo($goods_id);
                    // 判断是否已经进行过验证
                    if ($v['price'] != $result['data']['all_price']){
                        $v['price'] = $result['data']['all_price'];
                        echo "<script>alert('{$v['names']}已不再活动中，现在将以原价购买');</script>";
                    }
                }else{
                    //加入购物车的价格
                    $present_price = $v['price'];
                    //修改的原价或者活动价
                    if (isset($result['data']['activity_goods']['activity_price']) && $result['data']['activity_goods']['activity_price']!=0){
                        $revised_price = $result['data']['activity_goods']['activity_price'];
                    }else{
                        $revised_price = $result['data']['all_price'];
                    }
                    if($present_price!=$revised_price){
                        $diff = $present_price-$revised_price;
                        if($diff>0){
                            //降价
                            $diff = abs($diff);
                            $v['price'] = $revised_price;
                            echo "<script>alert('{$result['data']['goods_name']}已降价{$diff}元');</script>";
                        }else{
                            //涨价
                            $diff = abs($diff);
                            $v['price'] = $revised_price;
                            echo "<script>alert('{$result['data']['goods_name']}已涨价{$diff}元');</script>";
                        }
                    }
                }
            }else{
                echo "<script>alert('{$v['names']}已经下架，现在不能购买');</script>";
                unset($arr_ginfo[$k]);
                $len = count($arr_ginfo);
                if (!$len){
                    echo "<script>alert('购物车为空');window.location.href='/cart/index';</script>";
                }
            }

        }
        //将session重写用于支付购买
        $_SESSION['goods_info']='';
        $_SESSION['goods_info'] = json_encode($arr_ginfo);
        //用户类型
        if(!empty($_COOKIE['receiver'])){
            $receiver=$_COOKIE['receiver'];
        }else{
            $receiver=1;
        }
        $c_name=$this->getPost('c_name');//优惠券活动名称
        $g_name=$this->getPost('g_name');//商品名称
        $g_id=$this->getPost('g_id');//商品ID
        //优惠券信息
        $activityinfo=empty($_COOKIE['ainfo'])?'':json_decode($_COOKIE['ainfo'],true);
        //代金券信息
        $voucher_id=empty($_COOKIE['voucher_id'])?0:json_decode($_COOKIE['voucher_id'],true);
        //商品总价格
        if($arr_ginfo){
            foreach($arr_ginfo as $key => $value){
                $totalprice+=$value["price"]*$value['count'];
            }
            $price=$totalprice;
        }
        //优惠后金额
        if($activityinfo){
            $str='';
            foreach($activityinfo as $a => $v){
                $str.=$v['activity_goods_id'];
            }
            $str=str_replace(array('}','{'),'',$str);
            $str='{'.rtrim($str,',').'}';
            $monery_info=json_decode($str,true);
            //商品遍历打折价格
            $price=0;
            foreach ($arr_ginfo as $k=>$v){
                //echo $monery_info[$v['id']];
                if(isset($monery_info[$v['id']])){
                    $price+=$v['price']*($monery_info[$v['id']])*$v['count']/10;
                }else{
                    $price+=$v['price']*$v['count'];
                }
            }
        }
        if(isset($voucher_id['money'])){
            $price_money= $totalprice-$price-$voucher_id['money'];
        }else{
            $price_money= $totalprice-$price;
        }
        /*//设置默认地址
        $url= $this->_view->weixinapi."myaccount/addressbyId";
        $uid = Yaf_Registry::get('uid');
        $arr=array('uid'=>$uid);
        $result=Util::httpRequest($url,$arr);
        $result=json_decode($result,true);
        if($result['success'] == '1')
        {
            $add_info=json_encode($result['data']);
            $_COOKIE['add_info'] = $add_info;
        }*/
       // $add_info=empty($_COOKIE['add_info'])?'请选择地址信息':json_decode($_COOKIE['add_info'],true);
        //如果cookie里没有存地址就在请求一次
        if (isset($_COOKIE['add_info']) && !empty($_COOKIE['add_info'])){
            $address = json_decode($_COOKIE['add_info'],true);
            /*$address_id = $address['address_id'];
            $url= $this->_view->weixinapi."myaccount/addressbyId";
            $uid = Yaf_Registry::get('uid');
            $arr=array('uid'=>$uid,'address_id'=>$address_id);
            $result=Util::httpRequest($url,$arr);
            $result=json_decode($result,true);*/
            $result = $add_info = $this->colladdress();
            if($result['success'] != 1){
                unset($_COOKIE['add_info']);
            }else{
                $add_info=$address;
            }
        }else{
            $add_info = $this->colladdress();



            if(!empty($add_info['data'])){
                $add_info =  $add_info['data'][0];
                $add_info['address_id']=$add_info['id'];
                unset($add_info['id']);
                $address_info=json_encode($add_info,true);
                $_COOKIE['add_info']=$address_info;
                setcookie('add_info',$address_info,time()+3600*7,'/');
            }
        }
        $postscript=$this->getPost('desc');//留言
        $address_id=empty($add_info['address_id'])?'':$add_info['address_id'];//地址ID
        $this->_view->goods_info=$arr_ginfo;//购买商品信息
        $this->_view->title ="订单商品";
        $this->_view->activityinfo=$activityinfo;//优惠券信息
        $this->_view->voucher_my=$voucher_id['money'];//代金券信息
        $this->_view->price=$price;//商品优惠后价格
        $this->_view->acmoney=$price_money;//商品优惠价格
        $this->_view->add_info =$add_info;//地址信息
        $this->_view->receiver=$receiver;//用户类型
       
    }

    /*
     *优惠券列表
     */
    public function yhquanAction()
    {
        $goods_info=empty($_SESSION['goods_info'])?'':json_decode($_SESSION['goods_info'],true);
        if(!$goods_info){
            header("Location:/goods/list");
        }
        $goods_id=array();//商品ID
        $money='';//商品金额
        foreach($goods_info as $k => $v){
          $goods_id[] =$v["id"];
          $money+=$v["all_price"]*$v['count'];
        }
        $goods_id=implode(',', $goods_id);
         $url=$this->_view->weixinapi."/coupons/getCoupons";
         $arr=array('goods_arr'=>$goods_id,'goods_amount'=>$money,'uid'=>$_COOKIE['uid']);
         $result=Util::httpRequest($url,$arr);
         $result= json_decode($result,true);
         if(!empty($result['data'])){
         	$this->_view->coupons=$result['data'];
         }
         $this->_view->title ="优惠券列表";
    }
    /*
     * 代金券列表
     */
    public function vouchersAction() {
    	 $url=$this->_view->weixinapi."/myaccount/vouchers";
    	 $uid = Yaf_Registry::get('uid');
    	 $arr=array('uid'=>$uid);
    	 $result=Util::httpRequest($url,$arr);
    	 $result= json_decode($result,true);
    	 if(isset($result['success'])&& !empty($result['data'])){
    	 	$this->_view->coupons=$result['data'];
    	 }
    }
    /*
     *生成订单
     */
    public function balanceAction()
    {
        $postscript=isset($_POST['desc'])?$_POST['desc']:'';
        //地址信息
        if(empty($_COOKIE['add_info']) && empty($_COOKIE['add_info']['address_id'])){
            exit(json_encode(array('code'=>2,'message'=>'请选择地址再购买')));
        }else{
            $add_info=json_decode($_COOKIE['add_info'],true);
            $add_info=$add_info['address_id'];
        }
        $voucher_id=isset($_POST['voucher_id'])?$_POST['voucher_id']:0; 
        $url=$this->_view->weixinapi."/order/balance";
        //商品信息
        $goods_info=empty($_SESSION['goods_info'])?'':$_SESSION['goods_info'];

        if(empty($goods_info)){
            exit(json_encode(array('code'=>0,'message'=>'请购买商品')));
        }
       //优惠券信息
       $couponId='';
       if(!empty($_COOKIE['ainfo'])){
           $activityinfo=json_decode($_COOKIE['ainfo'],true);
           foreach ($activityinfo as $k => $v){             
                 $couponId.=$v['activity_id'].',';
           }
           $couponId=rtrim($couponId,',');
       }
       $couponId=empty($couponId)?0:$couponId;
       //代金券信息
       if(!empty($_COOKIE['voucher_id'])){
           $voucher_id=json_decode($_COOKIE['voucher_id'],true);      
           $vo_id=$voucher_id['id'];
       }
       $vo_id=empty($voucher_id)?0:$vo_id;
       //用户类型
       if(!empty($_COOKIE['receiver']) && isset($_COOKIE['receiver'])){
           $receiver=$_COOKIE['receiver'];
       }else{
           $receiver=1;
       }   
       $uid = Yaf_Registry::get('uid');
        //这里是微信支付，把付款方式定义为4，为微信公众号支付  --by slash 2016.5.19
        $arr=array(
            'uid'=>$uid,
            'postscript'=>$postscript,
            'address_id'=>$add_info,
            'couponId'=>$couponId,
            'voucher_id'=>$vo_id,
            'pay_id'=>4,
            'goodsInfo'=>$goods_info
        );
       //修改结束
        $results=Util::httpRequest($url,$arr);
        $result= json_decode($results,true);
        if($result['success']==1 && !empty($result['data'])){
            $_COOKIE['order_info'] = $results;
            setcookie('order_info',$results,time()+3600,'/');
            setcookie('voucher_id','',time()-600,'/');//删除代金券信息
            setcookie('ainfo','',time()-600,'/');;//删除优惠券信息
            //setcookie('goods_info','',time()-600,'/');;//删除商品信息
            $_SESSION['goods_info'] = '';
            exit(json_encode(array('code'=>1,'receiver'=>$receiver,'message'=>$result)));
        }else{
            if(!empty($result["error"]['0']["content"])){
                $message=$result["error"]['0']["content"];
            }else{
                $message="下单失败";
            }
            exit(json_encode(array('code'=>0,'message'=>$message)));
        }
    }
    /*
     * 选择收货地址
     */
    public function  colladdressAction(){
        $url= $this->_view->weixinapi."myaccount/addressInfo";
        $uid = Yaf_Registry::get('uid');
        $arr=array('uid'=>$uid);
        $result=Util::httpRequest($url,$arr);
        $result= json_decode($result,true);
        $this->_view->info=$result['data'];
        $this->_view->title ="收货地址";
    }

    public function colladdress(){
        $url= $this->_view->weixinapi."myaccount/addressInfo";
        $uid = Yaf_Registry::get('uid');
        $arr=array('uid'=>$uid);
        $result=Util::httpRequest($url,$arr);
        $result= json_decode($result,true);
        return $result;
    }


    /*
     * 付款
     */
    public function paymoneyAction(){
        $appid = Yaf_Application::app()->getConfig()->get('weixin')->get('APPID');
        $mch_id = Yaf_Application::app()->getConfig()->get('weixin')->get('MCHID');
        $key = Yaf_Application::app()->getConfig()->get('weixin')->get('KEY');
        $notify_url = Yaf_Application::app()->getConfig()->get('weixin')->get('Notify_url');
        $state = 'STATE';
        $AppSecret = Yaf_Application::app()->getConfig()->get('weixin')->get('AppSecret');
        $res=array();
        $status = $this->getQuery('status','0');
        if(!empty($_COOKIE['openid'])){
            $openid=$_COOKIE['openid'];
        }else{
//             if ($status == 0){
//               $this->wxauthorize();
//             }else{
//             //静默授权获取用户openid
//                 if(isset($_GET['state']) && $_GET['state']==$state){
//                     $code = $_GET['code'];
//                     $uinfo=file_get_contents("https://api.weixin.qq.com/sns/oauth2/access_token?appid=".$appid."&secret=".$AppSecret."&code={$code}&grant_type=authorization_code");
//                     $uinfo=(array)json_decode($uinfo);
//                     $openid=$uinfo['openid'];
//                     $_COOKIE['openid']=$openid;
//                     setcookie('openid',$openid,time()+3600,'/');
//                 }
//             //静默授权获取用户openid

//             }
            $this->respon(101, '请重新授权登录');
        }
        if(!empty($_POST['order_number']) && !empty($_POST['pay_money'])){
                  $res['order_number']=$order_num=$this->getPost('order_number');
                  $res['pay_money']=$money=$this->getPost('pay_money');
                  $res['orid']=$money=$this->getPost('orid');
        }elseif(!empty($_COOKIE['order_info'])){
                $order_info=json_decode($_COOKIE['order_info'],true);
                $res['order_number']=$order_info['data']['order_number'];
                $res['pay_money']=(int)$res['money']=$order_info['data']['pay_money'];
                $res['orid']=$order_info['data']['order_id'];
        }else{
             $this->respon(102, '订单异常');
        } 
        $rand = md5(time() . mt_rand(0,1000));
        $param["appid"] = $appid;
        $param["openid"] = $openid;  
        $param["mch_id"] = $mch_id;
        $param["nonce_str"] = "$rand";
        $param["body"] = "冰团微信支付";
        $param["out_trade_no"] = "{$res['order_number']}"; //订单单号
        $param['attach']=$res['orid'];
        $param["total_fee"] = $res['pay_money']*100;//$price * 100;  总金额   
        $param["spbill_create_ip"] = $_SERVER["REMOTE_ADDR"];
        $param["notify_url"] = $notify_url.'cart/notify';//'http://wx.xingshui.todoo.im/notify_url.php'; //支付成功返回的数据处理//Yaf_Registry::get('weixinurl').'notify_url.php';
        $param["trade_type"] = "JSAPI";
        $signStr = 'appid='.$param["appid"]."&attach=".$param['attach']."&body=".$param["body"]."&mch_id=".$param["mch_id"]."&nonce_str=".$param["nonce_str"]."&notify_url=".$param["notify_url"]."&openid=".$param["openid"]."&out_trade_no=".$param["out_trade_no"]."&spbill_create_ip=".$param["spbill_create_ip"]."&total_fee=".$param["total_fee"]."&trade_type=".$param["trade_type"];
        $signStr = $signStr."&key=$key";
        $param["sign"] = strtoupper(MD5($signStr));
        $data = Util::arrayToXml($param);
        $postResult = Util::postCurl("https://api.mch.weixin.qq.com/pay/unifiedorder",$data);
        $postObj = Util::xmlToArray( $postResult );
        $json = json_encode($postObj);
        $msg = $postObj['return_code'];
        if($msg == "SUCCESS"){
            $result["timestamp"] = time();
            $result["nonceStr"] = $postObj['nonce_str'];  //不加""拿到的是一个json对象
            $result["package"] = "prepay_id=".$postObj['prepay_id'];
            $result["signType"] = "MD5";
            $paySignStr = 'appId='.$param["appid"].'&nonceStr='.$result["nonceStr"].'&package='.$result["package"].'&signType='.$result["signType"].'&timeStamp='.$result["timestamp"];
            $paySignStr = $paySignStr."&key=$key";
            $result["paySign"] = strtoupper(MD5($paySignStr));
            $result['appId'] = $appid;
            $result['order'] = $res; //订单id
            $this->respon( 1, $result );
        }else{
            $this->respon( 0, '支付失败' );
        }
    }

    /**
     *
     * 支付的回调函数
     */
    function notifyAction(){

        Yaf_Dispatcher::getInstance()->autoRender(FALSE);

        include_once("../../lib/wxpay/demo/log_.php");
        include_once("../../lib/wxpay/WxPayPubHelper/WxPayPubHelper.php");
        //var_dump($notify_url);
        //使用通用通知接口
        $notify = new Notify_pub();

        //存储微信的回调
        $xml = isset($GLOBALS['HTTP_RAW_POST_DATA'])?$GLOBALS['HTTP_RAW_POST_DATA']:'';
        $notify->saveData($xml);
        $res = $notify->xmlToArray($xml);
        $data = json_encode($res);

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
                $api_url = Yaf_Application::app()->getConfig()->get('weixinapi')->get('url');
                $url = $api_url."order/updateodersStatus";//修改订单状态





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
    }
    function wxauthorize($url){
        $appid = Yaf_Application::app()->getConfig()->get('weixin')->get('APPID');
        $redirect_uri=urlencode(Yaf_Registry::get('weixinurl').'cart/'.$url.'?status=1');
        $url="https://open.weixin.qq.com/connect/oauth2/authorize?appid=$appid&redirect_uri=$redirect_uri&response_type=code&scope=snsapi_base&state=STATE#wechat_redirect";
        header('Location:'.$url);
        exit;
    }

    function getOpenId($url)
    {
        if(empty($_COOKIE['openid'])){
            $appid = Yaf_Application::app()->getConfig()->get('weixin')->get('APPID');
            $state = 'STATE';
            $AppSecret = Yaf_Application::app()->getConfig()->get('weixin')->get('AppSecret');

            if(isset($_GET['state']) && $_GET['state']==$state){
                $code = $_GET['code'];
                $uinfo=file_get_contents("https://api.weixin.qq.com/sns/oauth2/access_token?appid=".$appid."&secret=".$AppSecret."&code={$code}&grant_type=authorization_code");
                $uinfo=(array)json_decode($uinfo);
                $openid=$uinfo['openid'];
                $_COOKIE['openid']=$openid;
                setcookie('openid',$openid,time()+3600,'/');
            }else {
                $this->wxauthorize($url);

            }
        }


    }
     /*
       *删除cookie商品
       */
     public function editgoodsAction(){
         $goods_info=json_decode($_SESSION['goods_info'],true);
         $goods_id=$this->getPost('goods_id');
         if($goods_id==''){
             echo "失败";
             exit;
         }
         $gid_arr=explode(",", $goods_id);
         foreach($goods_info as $k => $v){
             if(in_array($v['id'], $gid_arr) || $v['count']==0){
               unset($goods_info[$k]);
             }
         }
         $goods_info=json_encode($goods_info);
         //$_COOKIE["goods_info"]=$goods_info;
         $_SESSION['goods_info'] = $goods_info;
         //setcookie("goods_info",$goods_info,time()+3600,'/');
         exit(json_encode(array('code'=>200,"message"=>'删除成功','desc'=>"删除成功" )));
     }
     /*
      * 把地址(3) 代金券 (2)优惠券(1)存储到cookie
      */
     public function setcookieAction(){
        if(!empty($_POST['info'])){
             if($_POST['type']==1){
                 $activityinfo=json_encode($_POST['info']);
                 $_COOKIE['ainfo'] = $activityinfo;
                 setcookie('ainfo',$activityinfo,time()+600,'/');
                 if(!empty($_COOKIE['ainfo'])){
                     exit(json_encode(array('code'=>1,'message'=>'保存成功')));
                 }else{
                     exit(json_encode(array('code'=>0,'message'=>'选择优惠券失败')));
                 }
             }elseif($_POST['type']==3){
                 $add_info=json_encode($_POST['info']);
                 $_COOKIE['add_info'] = $add_info;
                 setcookie('add_info',$add_info,time()+7600,'/');
                 if(!empty($_COOKIE['add_info'])){
                     exit(json_encode(array('code'=>1,'message'=>'保存成功')));
                 }else{
                     exit(json_encode(array('code'=>0,'message'=>'选择地址失败')));
                 }
             }elseif($_POST['type']==2){
                 $voucher_id=json_encode($_POST['info']);;
                 $_COOKIE['voucher_id'] = $voucher_id;
                 setcookie('voucher_id',$voucher_id,time()+600,'/');
                 if(!empty($_COOKIE['voucher_id'])){
                     exit(json_encode(array('code'=>1,'message'=>'保存成功')));
                 }else{
                     exit(json_encode(array('code'=>0,'message'=>'选择代金券失败')));
                 }
             }

        }else{
            exit(json_encode(array('code'=>0,'message'=>'选择失败')));
        }
     }
     /*
      * 订单详情页
      */
     public function infoAction(){
         $jssdk = new Jssdk();
         $appid = Yaf_Application::app()->getConfig()->get('weixin')->get('APPID');
         $AppSecret = Yaf_Application::app()->getConfig()->get('weixin')->get('AppSecret');
         $jssdk->getInstance($appid, $AppSecret);
         $signPackage = $jssdk->GetSignPackage();
         $this->_view->jsdata = $signPackage;
     
         $id=isset($_GET["mid"])?$_GET["mid"]:'';//消息id
         $associated_id=isset($_GET["associated_id"])?$_GET["associated_id"]:'';//关联id
         $type=isset($_GET["type"])?$_GET["type"]:'';//消息类型
         //更新消息状态
         if($id && $type && $associated_id){
             $this->updata($id,$associated_id,$type);
         }
         $url = $this->_view->weixinapi."/order/orderInfo";
         $id=(int)$type=isset($_GET['id'])?$_GET['id']:'';
         if(is_string($id)){
             exit;
         }
         setcookie('order_info','',time()-3600,'/');
         $uid = Yaf_Registry::get('uid');
         $arr=array('order_id'=>$id,'uid'=>$uid );
         $result = Util::httpRequest($url,$arr);
         $result = json_decode($result,true);
         if(!empty($result['data'])){
             $this->_view->info = $result['data'];
         }
         $this->_view->title ="订单详细列表";
         if(!empty($_GET['associated_id'])){
             $this->_view->url ="/message/qmessage";
         }else{
             $this->_view->url ="/cart/qstate";
         }
     }
     /*
      * 全部订单状态
      */
     public function qstateAction(){

         //$this->getOpenId('qstate');
         $jssdk = new Jssdk();
         $appid = Yaf_Application::app()->getConfig()->get('weixin')->get('APPID');
         $AppSecret = Yaf_Application::app()->getConfig()->get('weixin')->get('AppSecret');
         $jssdk->getInstance($appid, $AppSecret); 
         $signPackage = $jssdk->GetSignPackage();
         $this->_view->jsdata = $signPackage;
         $url = $this->_view->weixinapi."/order/getOrderList";
         $type= (int)$type=isset($_GET['status'])?$_GET['status']:0;
         if($type>5 || is_string($type)){
             $type=0;
         }
         $uid = Yaf_Registry::get('uid');
         $arr=array(
             'uid'=>$uid,
             'status'=>$type
         );
         
         $result = Util::httpRequest($url,$arr);
         $result = json_decode($result,true);
         setcookie("order_info",'',time()-3600,'/');
         $this->_view->title ="订单列表";
         //用户类型
         if(!empty($_COOKIE['receiver']) && isset($_COOKIE['receiver'])){
             $receiver=$_COOKIE['receiver'];
         }else{
             $receiver=1;
         }
         if(!empty($result['data']) && $result["success"]==1){
             $this->_view->info = $result['data'];
             $this->_view->type = $type;
             $this->_view->receiver = $receiver;
         }else{
             $this->_view->info =0;
             $this->_view->type = $type;
             $this->_view->receiver = $receiver;
         }
     }
     /*
      * 更新消息状态
      */
     public function updata($id,$associated_id,$type){
         $arr=array();
         $arr[]=array('id'=>$id,'associated_id'=>$associated_id,'type'=>$type);
         $arr=json_encode($arr);
         $url=$url = $this->_view->weixinapi."/message/signReadStatus";
         $uid = Yaf_Registry::get('uid');
         $arr=array('messageIDList'=>$arr,'uid'=>$uid);
         $result=Util::httpRequest($url,$arr);
         return json_decode($result,true);
     }
     /*
      *取消订单 提醒订单
      *@param $type int 支付1 取消订单2 提醒3 签收4 删除 5
      */
     public function qorderAction(){
         $id=isset($_POST['id'])?$_POST['id']:'';
         $type=(int)$type=isset($_POST['type'])?$_POST['type']:'';
         if($type>7 || is_string($type) || $type===''){
             exit(json_encode(array('code'=>0,'message'=>'未知类型')));
         }
         if($type==1){
             exit(json_encode(array('type'=>$type,'url'=>'/cart/paymoney')));
         }else if($type==2){
             $url = $this->_view->weixinapi."/order/close";
         }else if($type==3){
             $url = $this->_view->weixinapi."/order/remindSend";
         }else if($type==4){
             $url = $this->_view->weixinapi."/order/receiving";
         }else if($type==5){
             $url = $this->_view->weixinapi."/order/closeDeal";
         }else if($type==7){
             $url = $this->_view->weixinapi."/order/cancel";
         }
         $uid = Yaf_Registry::get('uid');
         $arr=array(
             'uid'=>$uid,
             'order_id'=>$id
         );
         $result = Util::httpRequest($url,$arr);
         $result = json_decode($result,true);
         exit(json_encode(array('type'=>$type,'message'=>$result)));
     }
   
}














