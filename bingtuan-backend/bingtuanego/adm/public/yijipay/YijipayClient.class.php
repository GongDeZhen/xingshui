<?php

if (version_compare("5.5", PHP_VERSION, ">")) {
	die("PHP 5.5 or greater is required!!!");
}

class YijipayClient {

	//网关
	public $gatewayUrl = "https://api.yiji.com/gateway.html";

	private $fileCharset = "UTF-8";
	private $postCharset = "UTF-8";

	private $isRedirect;

	private $config;

	public $debug = false;

	const SERVICES_JSON_SLICE = 1;
	const SERVICES_JSON_IN_STR = 2;
	const SERVICES_JSON_IN_ARR = 3;
	const SERVICES_JSON_IN_OBJ = 4;
	const SERVICES_JSON_IN_CMT = 5;
	const SERVICES_JSON_LOOSE_TYPE = 16;

	function __construct($config)
	{
		$this->config = $config;
		$this->gatewayUrl = $config["gatewayUrl"];
	}

	/**
	 * @return mixed
	 */
	public function isRedirect()
	{
		return $this->isRedirect;
	}

	/**
	 * @param BaseRequest $request 跳转类接口的request
	 * @param string $httpMethod 两个值可选：post、get
	 * @return string GET_QUERY_STRING | FORM_HTML 构建好的、签名后的最终跳转URL（GET）或String形式的form（POST）
	 * @throws Exception
	 */
	public function pageExecute($request,$httpMethod = "POST") {

//		if ($this->checkEmpty($this->postCharset)) {
//			$this->postCharset = "UTF-8";
//		}

		//$this->fileCharset = mb_detect_encoding($this->appId,"UTF-8,GBK");

		if (strcasecmp($this->fileCharset, $this->postCharset)) {
			// writeLog("本地文件字符集编码与表单提交编码不一致，请务必设置成一样，属性名分别为postCharset!");
			throw new Exception("文件编码：[" . $this->fileCharset . "] 与表单提交编码：[" . $this->postCharset . "]两者不一致!");
		}
		//待签名字符串
		$preSignStr = $request->getPreSignStr();
		$sign = $request->genSign($preSignStr, $this->config);

		$this->writeLog("原始请求(未加密)：". $preSignStr);
		$this->writeLog("原始请求sign：". $sign);

		if ("GET" == strtoupper($httpMethod)) {
			//拼接GET请求串
			//$requestUrl = $this->gatewayUrl."?".$preSignStr."&sign=".urlencode($totalParams["sign"]);
			$requestUrl = $this->gatewayUrl."?".$preSignStr."&sign=".$sign;
			return $requestUrl;
		} else {
			//参数
			$params = $request->getArrContent();
			foreach($params as $k => $v){
				if(is_array($v)) $params[$k] = json_encode($v, JSON_UNESCAPED_UNICODE);
			}
			$params["sign"] = $sign;
			//拼接表单字符串
            return $this->buildRequestForm($params);
		}
	}

	/**
	 * 建立请求，以表单HTML形式构造（默认）
	 * @param $params array 请求参数数组
	 * @return string 提交表单HTML文本
	 */
	protected function buildRequestForm($params) {
		//$sHtml = "<form id='yijiForm' name='yijiForm' action='".$this->gatewayUrl."?charset=".trim($this->postCharset)."' method='POST'>";
		$sHtml = "<form id='yijiForm' name='yijiForm' action='".$this->gatewayUrl."' method='POST'>";
		while (list ($key, $val) = each ($params)) {
			if (false === $this->checkEmpty($val)) {
				//$val = $this->characet($val, $this->postCharset);
				$val = str_replace("'","&apos;",$val);
				//$val = str_replace("\"","&quot;",$val);
				$sHtml.= "<input type='hidden' name='".$key."' value='".$val."'/>\n";
			}
		}
		//submit按钮控件请不要含有name属性
		$sHtml = $sHtml."<input type='submit' value='ok' style='display:none;''></form>";
		$sHtml = $sHtml."<script>document.forms['yijiForm'].submit();</script>";
		return $sHtml;
	}

	/**
	 * @param $request \yijipay\message\BaseRequest
	 * @return bool|mixed
	 * @throws Exception
	 */
	public function execute($request) {
		// 如果两者编码不一致，会出现签名验签或者乱码
		if (strcasecmp($this->fileCharset, $this->postCharset)) {
			$this->writeLog("本地文件字符集编码与表单提交编码不一致，请务必设置成一样，属性名分别为postCharset!");
			throw new Exception("文件编码：[" . $this->fileCharset . "] 与表单提交编码：[" . $this->postCharset . "]两者不一致!");
		}

		//计算签名
		$preSignStr = $request->getPreSignStr();
		$sign = $request->genSign($preSignStr, $this->config);
		$signStr = $preSignStr . "&sign=" . $sign;

		$this->writeLog("原始请求(未加密)：". $preSignStr);
		$this->writeLog("原始请求sign：". $sign);

		//系统参数放入GET请求串
		$requestUrl = $this->gatewayUrl . "?" . $signStr;

		//发起HTTP请求
		try {
			$resp = $this->curl($requestUrl);
		} catch (Exception $e) {
			$this->logCommunicationError($request, $requestUrl, "HTTP_ERROR_" . $e->getCode(), $e->getMessage());
			throw $e;
		}

		// 将返回结果转换本地文件编码
		if($this->postCharset != $this->fileCharset){
			$resp = iconv($this->postCharset, $this->fileCharset . "//IGNORE", $resp);
		}

		$this->writeLog("原始响应：" . $resp);

		return $resp;
	}

	protected function curl($url) {

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_FAILONERROR, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		//POST 请求
		curl_setopt($ch, CURLOPT_POST, true);
		//设置header
		$postMultipart = false;
		if ($postMultipart) {
			$headers = array('content-type: multipart/form-data;charset=' . $this->postCharset . ';boundary=' . $this->getMillisecond());
		} else {
			$headers = array('content-type: application/x-www-form-urlencoded;charset=' . $this->postCharset);
		}
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		$response = curl_exec($ch);

		if (curl_errno($ch)) {
			throw new Exception(curl_error($ch), 0);
		} else {
			$httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			if (302 === $httpStatusCode){
				$headers302 = curl_getinfo($ch);
				$response = $headers302['redirect_url'];
				$this->isRedirect = true;
			}else if (200 !== $httpStatusCode) {
				throw new Exception($response, $httpStatusCode);
			}
		}
		curl_close($ch);
		return $response;
	}

	/**
	 * @param $resp string|array
	 * @return bool
	 */
	public function verify($resp){

		if(!is_array($resp)){
			$resp = $this->yiji_json_decode($resp);
		}

		if(!array_key_exists("sign",$resp)) return false;

		$sign = $resp['sign'];

		$preStr = $this->getPreSignStr($resp);
		$mysign = $this->genSign($preStr, $resp["signType"]);

		$this->writeLog("响应结果(未加密):".$preStr);
		$this->writeLog("响应结果sign: ". $sign);

		return $mysign === $sign;
	}

	/**
	 * 获取sign
	 * @param $preStr
	 * @param $signType
	 * @return null|string
	 */
	public function genSign($preStr, $signType="MD5"){

		$sign = null;

		if("RSA" == $signType){
			if($this->checkEmpty($this->config['rsaPrivateKey'])){
				$priKey = $this->config['rsaPrivateKey'];
				$res = "-----BEGIN RSA PRIVATE KEY-----\n" .
					wordwrap($priKey, 64, "\n", true) .
					"\n-----END RSA PRIVATE KEY-----";
			}
//            else {
//                $priKey = file_get_contents($config['rsaPrivateKey']);
//                $res = openssl_get_privatekey($priKey);
//            }

			($res) or die('您使用的私钥格式错误，请检查RSA私钥配置');

			openssl_sign($preStr, $sign, $res);

//            if(!$this->checkEmpty($this->rsaPrivateKeyFilePath)){
//                openssl_free_key($res);
//            }
			$sign = base64_encode($sign);

		}else{
			//MD5签名
			$data = $preStr . $this->config['md5Key'];
			$sign = md5($data);
		}

//        echo "<br/>================sign：<br/>" . $sign ;
		return $sign;

	}

	/**
	 * 获取请求参数字符串：
	 * 1) 去除空置和sign
	 * 2) 完成排序， 增加签名
	 * 3) 签名
	 * @return string
	 */
	public function getPreSignStr($json){
		$arr = $this->paramsFilter($json);
		$str = $this->createLinkString($arr);
		return $str;
	}

	/**
	 * 除去数组中的空值和签名参数
	 * @param $params array 签名参数组
	 * @return array 去掉空值与签名参数后的新签名参数组
	 */
	private function paramsFilter($params) {
		$para_filter = array();
		while (list ($key, $val) = each ($params)) {
			if($key == "sign" || $val === "") continue;
			else	$para_filter[$key] = $params[$key];
		}
		ksort($para_filter);
		reset($para_filter);
		return $para_filter;
	}

	/**
	 * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
	 * @param $params array 需要拼接的数组
	 * @return string 拼接完成以后的字符串
	 */
	private function createLinkString($params) {
		$strParams = "";
		foreach ($params as $key => $val) {
			if(is_array($val)){
				$val = json_encode($val, JSON_UNESCAPED_UNICODE);
			}else if(is_bool($val)){
				if($val) $val="true";else $val="false";
			}
			$strParams .= "$key=" . ($val) . "&";
		}
		$strParams = substr($strParams, 0, -1);

		//如果存在转义字符，那么去掉转义
		if(get_magic_quotes_gpc()){$strParams = stripslashes($strParams);}
		return $strParams;
	}

	/**
	 * 校验$value是否非空
	 *  if not set ,return true;
	 *    if is null , return true;
	 * @param string $value
	 * @return bool
	 */
	protected function checkEmpty($value) {
		if (!isset($value))
			return true;
		if ($value === null)
			return true;
		if (trim($value) === "")
			return true;

		return false;
	}

	/**
	 * 记录日志
	 * @param $text
	 */
	function writeLog($text) {
		if($this->debug){
			echo "<br/>$text";
		}
		file_put_contents ( "log/log.txt", date ( "Y-m-d H:i:s" ) . "  " . $text . "<br/>\n", FILE_APPEND );
	}

	/**
	 * 记录网络请求错误日志
	 * @param $request BaseRequest
	 * @param $requestUrl string
	 * @param $errorCode string
	 * @param $responseTxt string
	 */
	protected function logCommunicationError($request, $requestUrl, $errorCode, $responseTxt) {
		$logData = array(
			date("Y-m-d H:i:s"),
			$request->getService().'_'.$request->getVersion(),
			$requestUrl,
			$errorCode,
			str_replace("\n", "", $responseTxt)
		);
		$text = implode("\t", $logData);
		file_put_contents ( "log/comm_err.log", $text . "\n", FILE_APPEND );
	}

	/**
	 * 自定义json_decode字符串，主要为了兼容易极付验签
	 * @param $str
	 * @return array|bool|null
	 */
	private function yiji_json_decode($str){
		switch (strtolower($str)) {
			case 'true':
				return true;
			case 'false':
				return false;
			case 'null':
				return null;
			default:
				$m = array();
				if (preg_match('/^\[.*\]$/s', $str) || preg_match('/^\{.*\}$/s', $str)) {
					// array, or object notation
					if ($str{0} == '[') {
						$stk = array(self::SERVICES_JSON_IN_ARR);
						$arr = array();
					} else {
						if (self::SERVICES_JSON_LOOSE_TYPE) {
							$stk = array(self::SERVICES_JSON_IN_OBJ);
							$obj = array();
						} else {
							$stk = array(self::SERVICES_JSON_IN_OBJ);
							$obj = new stdClass();
						}
					}

					array_push($stk, array('what'  => self::SERVICES_JSON_SLICE,
						'where' => 0,
						'delim' => false));

					$chrs = substr($str, 1, -1);

					if ($chrs == '') {
						if (reset($stk) == self::SERVICES_JSON_IN_ARR) {
							return $arr;

						} else {
							return $obj;

						}
					}

					//print("\nparsing {$chrs}\n");

					$strlen_chrs = strlen($chrs);

					for ($c = 0; $c <= $strlen_chrs; ++$c) {

						$top = end($stk);
						$substr_chrs_c_2 = substr($chrs, $c, 2);

						if (($c == $strlen_chrs) || (($chrs{$c} == ',') && ($top['what'] == self::SERVICES_JSON_SLICE))) {
							// found a comma that is not inside a string, array, etc.,
							// OR we've reached the end of the character list
							$slice = substr($chrs, $top['where'], ($c - $top['where']));
							array_push($stk, array('what' => self::SERVICES_JSON_SLICE, 'where' => ($c + 1), 'delim' => false));
							//print("Found split at {$c}: ".substr($chrs, $top['where'], (1 + $c - $top['where']))."\n");

							if (reset($stk) == self::SERVICES_JSON_IN_ARR) {
								// we are in an array, so just push an element onto the stack
								array_push($arr, decode($slice));

							} elseif (reset($stk) == self::SERVICES_JSON_IN_OBJ) {
								// we are in an object, so figure
								// out the property name and set an
								// element in an associative array,
								// for now
								$parts = array();

								if (preg_match('/^\s*(["\'].*[^\\\]["\'])\s*:\s*(\S.*),?$/Uis', $slice, $parts)) {
									// "name":value pair
									$key = preg_replace('/^"(.*)"$/', '\1', $parts[1]);
									$val = preg_replace('/^"(.*)"$/', '\1', $parts[2]);
									$obj[$key] = $val;
								}
							}
						} elseif ((($chrs{$c} == '"') || ($chrs{$c} == "'")) && ($top['what'] != self::SERVICES_JSON_IN_STR)) {
							// found a quote, and we are not inside a string
							array_push($stk, array('what' => self::SERVICES_JSON_IN_STR, 'where' => $c, 'delim' => $chrs{$c}));
							//print("Found start of string at {$c}\n");

						} elseif (($chrs{$c} == $top['delim']) &&
							($top['what'] == self::SERVICES_JSON_IN_STR) &&
							((strlen(substr($chrs, 0, $c)) - strlen(rtrim(substr($chrs, 0, $c), '\\'))) % 2 != 1)) {
							// found a quote, we're in a string, and it's not escaped
							// we know that it's not escaped becase there is _not_ an
							// odd number of backslashes at the end of the string so far
							array_pop($stk);
							//print("Found end of string at {$c}: ".substr($chrs, $top['where'], (1 + 1 + $c - $top['where']))."\n");

						} elseif (($chrs{$c} == '[') &&
							in_array($top['what'], array(self::SERVICES_JSON_SLICE, self::SERVICES_JSON_IN_ARR, self::SERVICES_JSON_IN_OBJ))) {
							// found a left-bracket, and we are in an array, object, or slice
							array_push($stk, array('what' => self::SERVICES_JSON_IN_ARR, 'where' => $c, 'delim' => false));
							//print("Found start of array at {$c}\n");

						} elseif (($chrs{$c} == ']') && ($top['what'] == self::SERVICES_JSON_IN_ARR)) {
							// found a right-bracket, and we're in an array
							array_pop($stk);
							//print("Found end of array at {$c}: ".substr($chrs, $top['where'], (1 + $c - $top['where']))."\n");

						} elseif (($chrs{$c} == '{') &&
							in_array($top['what'], array(self::SERVICES_JSON_SLICE, self::SERVICES_JSON_IN_ARR, self::SERVICES_JSON_IN_OBJ))) {
							// found a left-brace, and we are in an array, object, or slice
							array_push($stk, array('what' => self::SERVICES_JSON_IN_OBJ, 'where' => $c, 'delim' => false));
							//print("Found start of object at {$c}\n");

						} elseif (($chrs{$c} == '}') && ($top['what'] == self::SERVICES_JSON_IN_OBJ)) {
							// found a right-brace, and we're in an object
							array_pop($stk);
							//print("Found end of object at {$c}: ".substr($chrs, $top['where'], (1 + $c - $top['where']))."\n");

						} elseif (($substr_chrs_c_2 == '/*') &&
							in_array($top['what'], array(self::SERVICES_JSON_SLICE, self::SERVICES_JSON_IN_ARR, self::SERVICES_JSON_IN_OBJ))) {
							// found a comment start, and we are in an array, object, or slice
							array_push($stk, array('what' => self::SERVICES_JSON_IN_CMT, 'where' => $c, 'delim' => false));
							$c++;
							//print("Found start of comment at {$c}\n");

						} elseif (($substr_chrs_c_2 == '*/') && ($top['what'] == self::SERVICES_JSON_IN_CMT)) {
							// found a comment end, and we're in one now
							array_pop($stk);
							$c++;

							for ($i = $top['where']; $i <= $c; ++$i)
								$chrs = substr_replace($chrs, ' ', $i, 1);

							//print("Found end of comment at {$c}: ".substr($chrs, $top['where'], (1 + $c - $top['where']))."\n");

						}

					}
					if (reset($stk) == self::SERVICES_JSON_IN_ARR) {
						return $arr;
					} elseif (reset($stk) == self::SERVICES_JSON_IN_OBJ) {
						return $obj;
					}
				}
		}
	}

}