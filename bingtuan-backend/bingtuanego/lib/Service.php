<?php

class Service
{
    public $db;
    public $db2;
    protected static $_instances = array();
  
    public function __construct()
    {
        $this->db = Db::getInstance();
        $config = Yaf_Application::app()->getConfig()->get('sdb');
        $this->db2 = Db::getInstance($config, 'slave1');
      
        /*$redis = new Redis(); 
        $host = Yaf_Application::app()->getConfig()->get('redis')->get( 'host' );
        $port = Yaf_Application::app()->getConfig()->get('redis')->get( 'port' );
        
        $redis->connect($host,$port); 
        $this->redis = $redis;*/
    }

    public static function getInstance($name)
    {

        $name = strtolower($name);
        if (!isset(self::$_instances[$name])) {
            $serviceName = 'Service_' . ucfirst($name);
            self::$_instances[$name] = new $serviceName();
        }

        return self::$_instances[$name];
    }

    private function getKey()
    {
        $key = Yaf_Application::app()->getConfig()->get('cookie')->get( 'key' );
        return $key;
    }

    public function setData( $key , $v )
    {
        return ;
        $key = md5( $key . $this->getKey( ) );
        $this->redis->set( $key , json_encode ( $v ) );
    }

    public function getData( $key )
    {
        return ;
        $key = md5( $key . $this->getKey( ) );
        $data = $this->redis->get( $key );
        if ( $data ) $data = json_decode( $data , true );
        return $data;
    }

    public function setAppinfo( $appid , $data )
    {
        $key = md5( $appid . "Oauth2.0");
        $this->redis->set( $key , json_encode ( $data ) );
    }

    public function getAppinfo( $appid )
    {
        $key = md5( $appid . "Oauth2.0" );
        $data = $this->redis->get( $key );
        if ( $data ) $data = json_decode( $data , true );
        return $data;
    }
//     function getError(){
//     	return $this->error;
//     }
    public function error($error = '', $errno = 0)
    {
    	exit(json_encode(array('success'=>$errno,'data'=>$error)));
    }
    public function success($success = '', $no = 0)
    {
    	exit(json_encode(array('success'=>$no,'data'=>$success)));
    }
}