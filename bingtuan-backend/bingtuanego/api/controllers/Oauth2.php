<?php

class Oauth2Controller extends BaseController
{
    public function init()
    {
        parent::init();
    }

    public function indexAction()
    {
        //
    }


    public function getuserAction()
    {
        $token = $this->getQuery('token', '');
        if ( !$token ) $this->respon( 0 , "token 不能为空!" );  
        $user = $this->getUserBytoken( $token );
        if ( !isset( $user['Id'] ) ) $this->respon( 0 , "授权信息已失效!" );
        $user['uid'] = $user['Id'];
        Yaf_Registry::set("isLogin",true);
        $this->respon( 1 , $user );      
    }

    public function getProjctListAction(  )
    {
        $token = $this->getQuery('token', '');
        if ( !$token ) $this->respon( 0 , "token 不能为空!" );  
        $user = $this->getUserBytoken( $token );
        if ( !isset( $user['Id'] ) ) $this->respon( 0 , "授权信息已失效!" );
        Yaf_Registry::set( 'uid' , $user['Id'] );
        Yaf_Registry::set("isLogin",true);
        $list = Service::getInstance('projects')->getProjectList();
        $this->respon( 1 , $list ); 
    }

    private function getUserBytoken( $token )
    {
        $data = $this->redis->get( $token );
        if ( $data ) $data = json_decode( $data , true );
        if ( !isset( $data['Id'] ) ) return null;

        if ( time() > $data['expire'] ) return null;

        return $data;     
    }

}