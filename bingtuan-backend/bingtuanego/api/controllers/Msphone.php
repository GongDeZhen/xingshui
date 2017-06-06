<?php

class MsphoneController extends BaseController {

    public function init()
    {
        parent::init();
    
        if (  Yaf_Registry::get( 'isLogin' ) <> true )
        {
            $this->respon( 0 , "登录超时!"  );
        }
    }

	// @/notif/
	public function indexAction()
	{
        $phone = empty($_REQUEST["phone"]) ? "" : $_REQUEST["phone"];
        $ms = empty($_REQUEST["ms"]) ? "" : $_REQUEST["ms"];
        $t = empty($_REQUEST["t"]) ? 0 : intval( $_REQUEST["t"] );
        $hash = empty($_REQUEST["hash"]) ? "" : $_REQUEST["hash"];
        $type = empty($_REQUEST["type"]) ? 0 : intval( $_REQUEST["type"] );
        
        $key = "sernyoobukipqyty";
        
        if ( $hash != md5( $t . $key . $phone . $ms ) )
        {
                exit("Access forbidden ");
        }
        
        if ( !intval( $phone ) ) exit("err ");
        if ( !intval( $ms ) ) exit("err ");
        
        $mss = '亲爱的用户，您的验证码';
        if ( $type ) $mss = '亲爱的用户，您的新密码';
        
        
        $post_fields['corp_id'] = '7f2s002';
        $post_fields['corp_pwd'] = '5637wh';
        $post_fields['corp_service'] = '10690116yd';
        $post_fields['mobile'] = $phone;
        $post_fields['msg_content'] =  iconv('UTF-8', 'GB2312' , $mss.'是:'.$ms);
        $post_fields['corp_msg_id'] = '111';
        
        $url = 'http://service2.baiwutong.com:8080/sms_send2.do';
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_fields));
        $contents = curl_exec($ch);
        curl_close($ch);
        $Res = array(
            'phone' => $phone,
            'mss'=> $ms,
            'updatetime' => time()
        );
        $this->db->insert("Apimss",$Res);
        if($contents == '0#1') {
            exit('ok');
        } else {
            exit($contents);
        }
	}
	


}