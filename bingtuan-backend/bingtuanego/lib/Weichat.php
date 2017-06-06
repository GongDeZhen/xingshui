<?php

class Weichat{
	
	private static $_config = null;

	private $redis;

	private $_appId;
	private $_secret;
	private $_token;

	//template_id:
	//xhz:IeR36-RIsXmaoyl5PftsMoDEUSjIVlZl_Ftr1up1Ahc
	//car:ZhC3omft7bve4DIRT3cJp_fZ3d08VSoU9Ummev0aNi4
	//car_openId:o4iyiuCl05Ri3oHh_vfXshKDA3ws
	//car_openId:o4iyiuOY9d-GAxOyQTgZw0LbeSYs
	private $_msg_template = array(
		'pre_goods' => "{\"touser\":\"%s\",\"template_id\":\"IeR36-RIsXmaoyl5PftsMoDEUSjIVlZl_Ftr1up1Ahc\",\"url\":\"%s\",\"topcolor\":\"#FF0000\",\"data\":{\"first\":{\"value\":\"商品预售提醒\",\"color\":\"#173177\"},\"keyword1\":{\"value\":\"%s\",\"color\":\"#173177\"},\"keyword2\":{\"value\":\"%s\",\"color\":\"#173177\"},\"keyword3\":{\"value\":\"%s\",\"color\":\"#173177\"},\"keyword4\":{\"value\":\"%s\",\"color\":\"#FF0000\"},\"keyword5\":{\"value\":\"%s\",\"color\":\"#173177\"},\"remark\":{\"value\":\"请及时上/下架处理\",\"color\":\"#173177\"}}}",
		);

	public function __construct( $config ){
		$this->_appId = $config->appid;
		$this->_secret = $config->secret;
		$this->_token = $config->token;

		$this->redis = Red::getInstance();
	}

	public static function getInstance( $config = null,$name= 'default' ){
		static $instance = array();

		if( !$config && !self::$_config ){
			die('no any config options ! ');
		}

		$config = $config ? $config : self::$_config;

		if( empty( $instance[$name] ) ){
			$instance[$name] = new Weichat( $config );
		}
		return $instance[$name];
	}

	public static function setDefaultConfig( $config ){
		self::$_config = $config;
	}

	/*{ ["id"]=> string(2) "75" ["channel"]=> string(1) "1" ["name"]=> string(5) "14321" ["gid"]=> string(4) "1202" ["goodsNo"]=> string(5) "12341" ["preId"]=> string(2) "15" ["startTime"]=> string(16) "2016-01-18 21:40" ["endTime"]=> string(16) "2016-01-18 21:45" ["sellType"]=> string(9) "一口价" ["cname"]=> array(1) { ["name"]=> string(6) "东家" } ["do"]=> string(9) "pre_goods" }*/
	public function doPushSend( $data ){

		$access_token = $this->getAccessToken();
		//var_dump($access_token);
		$url = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token={$access_token}";

		//$fail = array();
		foreach( $data['openIds'] as $v ){

			$template = $this->_msg_template[$data['do']];
			$uri = '';

			switch ($data['do']) {
				case 'pre_goods':
					$content = sprintf($template,$v,$uri,$data['name'],$data['goodsNo'],$data['cnames'],$data['action'],$data['startTime'].' 至 '.$data['endTime']);
					break;
				default:
					# code...
					break;
			}

			$result =json_decode( $this->_request( $url,'post',$content ) ,true );
			//var_dump($result);
			$record = array();
			if( $result['errcode'] != 0 ){
				//$file[] = $v;
				$record = array(
					'send_time'	   => time(),
					'send_content' => $content,
					'result' 	   => $result,
					);
				if( defined('ROOT_PATH') ){
					$file = ROOT_PATH.'/logs/time_list.weichat.error.log';
				}else{
					$file = './time_list.weichat.error.log';
				}
				if( file_exists($file) ){
					file_put_contents( $file ,PHP_EOL.'[ '.date('Y-m-d H:i:s').' ][start]'.json_encode($record).'[end]'.PHP_EOL,FILE_APPEND );
				}
			}
		}
		/*var_dump($fail);
		if(!empty($fail)) return false;*/
		//这里根据要求可以进行对错误或者失败的数据进行处理
		return true;
	}

	public function getAccessToken( ){

		$key = md5( $this->_appId );

		if( $this->redis->exists( $key ) ){
			$content = json_decode( $this->redis->get( $key ) ,true );
			if( time() < $content['get_time'] + $content['expires_in'] -120 ){
				return $content['access_token'];
			}
		}

		$url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$this->_appId}&secret={$this->_secret}";

		$result = json_decode( $this->_request( $url,'get' ) ,true );

		if( !empty( $result['access_token'] ) ){
			$result['get_time'] = time();
			$res = $this->redis->set( $key, json_encode( $result ) );
			if( !$res ) echo "error:[ ".date('Y-m-d H:i:s')." ] access_token存入redis出错！".PHP_EOL.json_encode($result).PHP_EOL;
			return $result['access_token'];
		}
	}

	private function _request($url,$type='post',$data='', $ssl=true) {
		// curl完成
		$curl = curl_init();
		//设置curl选项
		curl_setopt($curl, CURLOPT_URL, $url);//URL
		$user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '
Mozilla/5.0 (Windows NT 6.1; WOW64; rv:38.0) Gecko/20100101 Firefox/38.0 FirePHP/0.7.4';
		curl_setopt($curl, CURLOPT_USERAGENT, $user_agent);//user_agent，请求代理信息
		curl_setopt($curl, CURLOPT_AUTOREFERER, true);//referer头，请求来源
		curl_setopt($curl, CURLOPT_TIMEOUT, 30);//设置超时时间
		//SSL相关
		if ($ssl) {
			//禁用后cURL将终止从服务端进行验证
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			//检查服务器SSL证书中是否存在一个公用名(common name)
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
		}
		// 处理post相关选项
		if($type == 'post'){
			curl_setopt($curl, CURLOPT_POST, true);// 是否为POST请求
			curl_setopt($curl, CURLOPT_POSTFIELDS, $data);// 处理请求数据
		}
		// 处理响应结果
		curl_setopt($curl, CURLOPT_HEADER, false);//是否处理响应头
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);//curl_exec()是否返回响应结果
		// 发出请求
		$response = curl_exec($curl);
		if (false === $response) {
			echo '<br>', curl_error($curl), '<br>';
			return false;
		}
		curl_close($curl);
		return $response;
	}

}
/*$data = array(
	'do' => 'pre_goods',
	'name' => '女戒指',
	'goodsNo' => '18810168697',
	'cnames' => '东家 | Redone | RedTwo',
	'action' => '东家  锁定商品',
	'startTime' => '2016-01-08 00:00',
	'endTime' => '2016-01-08 02:10'
	);
$wei = new WeiChat();
$wei -> doPushSend($data,array('o-PQFj1NN-4gI7TfC_IN21vFUmKo','o-PQFj7jjy0nUl69hGeUK8ZJ1rmE'));*/

?>