<?php

class MsmailController extends BaseController {

    public function init()
    {
        parent::init();
    
        if (  Yaf_Registry::get( 'isLogin' ) <> true )
        {
            $this->respon( 0 , "登录超时!"  );
        }
    }

	public function indexAction()
	{
	    $mail = empty($_REQUEST["mail"]) ? "" : $_REQUEST["mail"];
        $title = empty($_REQUEST["title"]) ? "" : $_REQUEST["title"];
        $mailContents = empty($_REQUEST["mailContents"]) ? "" : $_REQUEST["mailContents"];
        $t = empty($_REQUEST["t"]) ? 0 : intval( $_REQUEST["t"] );
        $hash = empty($_REQUEST["hash"]) ? "" : $_REQUEST["hash"];
        
        $key = "sernyoobukipqyty";
        
        if ( $hash != md5( $t . $key . $title . $mail ) )
        {
                exit("Access forbidden ");
        }
        if( !preg_match('/^[\w\-\.]+@[\w\-]+(\.\w+)+$/', $mail) )
        {
                exit("mail err");
        }
        
        if ( $title == "" )
        {
                exit("title err");
        }
        
        if ( $mailContents == "" )
        {
                exit("mailContents err");
        }
        
        $Res = array(
            'mail' => $mail,
            'Title'=> $title,
            'MailContents' => $mailContents,
            'Updatetime' => time()
        );
        
        
        $redis = new Redis();
        $redis->connect('127.0.0.1',6379);
        
        try
        {
                $redis->LPUSH('yshow-sernyoobukipqyty',json_encode( $Res ) );
        }
        catch(Exception $e)
        {
                //echo $e->getMessage().'!!!!';
                echo 'redis error';
        }
        
        $this->db->insert("ApiMaiL",$Res);
        exit("ok");
	}
	


}