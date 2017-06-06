<?php

class UploadedController extends BaseController {

    public function init()
    {
        parent::init();

        if ( ! (bool)Yaf_Registry::get("isLogin") )
        {
            $this->respon( 0 , "Login Access Denied" );
        }
        
    }

    //@ /uploaded/
    public function indexAction( )
    {
        if ( !isset( $_FILES["file"] ) ) $this->respon( 0 , "参数错误!" );
        
        $files = $_FILES["file"];

        if ( $files["error"] == 0 )
        {
            $file = Service::getInstance('attach')->uploaded( $files  );
            $this->respon( 1 , $file );
        }

        $this->respon( 0 , "上传失败!" );

    }
}