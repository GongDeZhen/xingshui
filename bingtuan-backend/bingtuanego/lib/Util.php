<?php

class Util
{
    public static function isValidDate($date)
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date);
    }
    static public function buildPagebar($total, $perpage, $page, $url)
    {
        $pages = ceil($total / $perpage);
        $page = max($page, 1);
        $total = max($total, 1);
    
        $html = '<div class="btn-toolbar pages" role="toolbar" aria-label="Toolbar with button groups">';
    
        if ($pages <= 11) {
            $start = 1;
            $end   = $pages;
        }
        else if ($page > 6 && $page + 5 <= $pages) {
            $start = $page - 5;
            $end   = $page + 5;
        }
        else if ($page + 5 > $pages) {
            $start = $pages - 10;
            $end   = $pages;
        }
        else if ($page <= 6) {
            $start = 1;
            $end   = 11;
        }
    
        //
        if ($page == 1) {
            $html .= "<div class='btn-group' role='group' aria-label='Third group'><button type='button' class='btn btn-default'>上一页</button></div>";
        } else {
            $html .= "<div class='btn-group' role='group' aria-label='Third group'><button onclick=window.location.href=\"" . str_replace("__page__", $page - 1, $url) . "\" type='button' class='btn btn-default'>上一页</button></div>";
        }
        $html .= '<div class="btn-group" role="group" aria-label="Third group">';
        if ($start > 1) {
            $html .= "<button onclick='window.location.href=\"" . str_replace("__page__", 1, $url) . "\"' type='button' class='btn btn-default'>1</button>";
        }
    
        if ($start > 2) {
            $html .= "<button onclick='window.location.href=\"" . str_replace("__page__", 2, $url) . "\"'  type='button' class='btn btn-default'>2</button>";
        }
    
        if ($start > 3) {
            $html .= "<span>...</span>";
        }
        for ($i = $start; $i <= $end; $i ++) {
            if ($page == $i) {
                $html .= "<button onclick='window.location.href=\"" . str_replace("__page__", $i, $url) . "\"'  type='button' class='btn btn-default' style='background-color: #eee;'>$i</button>";
            } else {
                $html .= "<button onclick='window.location.href=\"" . str_replace("__page__", $i, $url) . "\"'  type='button' class='btn btn-default' >$i</button>";
            }
        }
        if ($end < $pages - 1) {
            $html .= "<button type='button' class='btn btn-default'>...</button>";
        }
        $html .= '</div>';
                        /*
        if ($end < $pages - 1)
            {
                    $html .= "<a href=\"" . str_replace("__page__", $pages - 1, $url) . "\">" . ($pages - 1) . "</a>";
                        }
        */
    
        if ($end < $pages) {
            $html .= "<div onclick='window.location.href=\"" . str_replace("__page__", $pages, $url) . "\"' class='btn-group' role='group' aria-label='Third group'><button type='button' class='btn btn-default'>$pages</button></div>";
        }

        if ($page >= $pages) {
            $html .= "<div class='btn-group' role='group' aria-label='Third group'><button type='button' class='btn btn-default'>下一页</button></div>";
        } else {
            $html .= "<div class='btn-group' role='group' aria-label='Third group'><button onclick='window.location.href=\"" . str_replace("__page__", $page + 1, $url) . "\"' type='button' class='btn btn-default'>下一页</button></div>";
        }

        $html .= "</div>";

        return $html;
    }
    public static function getTimestamp($digits = false) 
    {  
        $digits = $digits > 10 ? $digits : 10;
        $digits = $digits - 10;
        if ((!$digits) || ($digits == 10))
        {
            return time();
        }
        else
        {
            return number_format(microtime(true),$digits,'','');
        }
    }

    public static function isValidEmail($address)
    {
        return filter_var($address, FILTER_VALIDATE_EMAIL);
    }

    public static function sendmail( $address , $title , $content )
    {
        $url = Yaf_Application::app()->getConfig()->get('mail')->get('api')->get('url');
        $key = Yaf_Application::app()->getConfig()->get('mail')->get('api')->get('key');
        Log::simpleappend('mail', $url.'__'.$key);
        $t = time();
        $data['post'] = true;
        $data['data']['mail'] = $address;
        $data['data']['title'] = $title;
        $data['data']['mailContents'] = $content;
        $data['data']['t'] = $t;
        $data['data']['hash'] =  md5( $t . $key . $title . $address );
        Log::simpleappend('mail', json_encode($data));
        return Util::request( $url , $data ) ;
    }
    
    public static function sendsms($mobile,$content){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, Yaf_Application::app()->getConfig()->get('sms')->get('api')->get('sendurl'));
    
        curl_setopt($ch, CURLOPT_HTTP_VERSION  , CURL_HTTP_VERSION_1_0 );
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
    
        curl_setopt($ch, CURLOPT_HTTPAUTH , CURLAUTH_BASIC);
//         curl_setopt($ch, CURLOPT_USERPWD  , 'api:key-'.Yaf_Application::app()->getConfig()->get('sms')->get('api')->get('key'));
    
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, array('phonenumber' => $mobile,'message' => $content, 'signtype'=>'ego', 'pid'=>'ego'));
    
        $res = curl_exec( $ch );
        curl_close( $ch );
        return $res;
    }

    public function filterUserName($str)
    {
        $str = iconv('utf-8', 'gbk//IGNORE', $str);
        $str = preg_replace('%([\xA1-\xA9][\xA1-\xFE]|[\xA8-\xA9][\x40-\xA0]|[\x00-\x2f]|[\x3a-\x40]|[\x5B-\x60]|[\x7B-\x7F])%xs', '', $str);
        $str = preg_replace('%([^0-9a-zA-Z]])%s', '', $str);
        $str = iconv('gbk', 'utf-8//IGNORE', $str);

        return $str;
    }


    /**
     * 过滤SQL注入非法字符 
     * 
     * @desc
     *
     * @param string | array $string 需要处理的字符串 
     * @access public static
     * @return string | array
     * @exception none
     */
    public static function filterSqlInjection($string)
    {
        if(is_array($string)) {
            foreach($string as $key => $row) {
                $row            = self::_addslashes($row);
                $string[$key]   = self::encodeHtml($row);
            }
        } else {
            $string     = self::_addslashes($string);
            $string     = self::encodeHtml($string);
        }

        return $string;
    }

    /**
     * 给字符串加上反斜杆 
     * 
     * 当开启了自动加反斜杆的配置时，就直接返回 
     * 
     * @access protected static
     * @param string $string 需要处理的字符串
     * @return string 
     * @exception none
     */
    protected static function _addslashes($string)
    {
        if(!get_magic_quotes_gpc()) {
            return addslashes(self::cleanSlash($string));
        } 
        
        return $string;
    }

    /**
     * 清除反斜杆 
     * 
     * 用法：
     * <code>
     *  HString::cleanSlash('test\''); //test'
     * </code> 
     * 
     * @author wuchuanchang <wuchuanchang@e7124.com>
     * @access public static
     * @param  String $content  需要处理的内容
     * @return String 
     * @throws none
     */
    public static function cleanSlash($content)
    {
        return stripslashes($content);
    }

    /**
     * 格式化HTML字符
     * 
     * 对：', ", <, >, & 等符号进行转换
     * 
     * @access public static
     * @param string $string 需要处理的字符串
     * @return string 
     * @exception none
     */
    public static function encodeHtml($htmlCode)
    {
        return htmlspecialchars($htmlCode, ENT_QUOTES);
    }

    /**
     * 还原HTML标签 
     * 
     * 把由encodeHtml转化后的html代码反转回来 
     * 
     * @access public
     * @param string $htmlCode 需要处理的HTML代码
     * @return string 
     * @exception none
     */
    public static function decodeHtml($htmlCode)
    {
        return htmlspecialchars_decode($htmlCode, ENT_QUOTES);
    }

    /**
     * 安字符数来计算字符串的长度 
     * 
     * 支持按给写的编码来得到对应的长度 
     * 
     * @access public static
     * @param string $string 需要处理的字符串
     * @param string $encode 字符编码
     * @return int 
     * @exception none
     */
    public static function getLenByChar($string, $encode = 'utf8')
    {
        return mb_strlen($string, $encode);
    }

    /**
     * 通过字节数来得到字符串的长度 
     * 
     * 直接按每个字符所占的内存字节和 
     * 
     * @access public static
     * @param $string
     * @return int 
     */
    public static function getLenByByte($string)
    {
        return strlen($string);
    }

    /**
     * 剪切字符串 
     * 
     * @desc
     * 
     * @access public static
     * @param string $string 需要处理的字符串
     * @param int $max 最大的显示字串长
     * @param string $overMask 超过的标记, 默认为：......
     * @return string 
     */
    public static function cutString($string, $max, $overMask = '.....')
    {
        $enMax      = 2 * $max;
        $strLen     = strlen($string);
        for($i = 0; $i < $strLen && $i < $enMax; $i ++) {
            if(128 <= ord($string[$i])) {
                $enMax --;
            }
        }
        preg_match_all('/./us', $string, $match);
        if($enMax < count($match[0])) { 
            return mb_substr($string , 0, $enMax, 'utf8') . $overMask;
        }

        return $string;
    }

    /**
     * 清除字符串里的HTML标签 
     * 
     * @desc
     * 
     * @access public static
     * @param string $string 需要处理的字符
     * @return string 
     */
    public static function cleanHtmlTag($string)
    {
        return $string;
        $mode   = '%</?[:\w]+(\s?[:\w]+(:\w+)?=\"([/\w.:;\-()\s#=?%]*|[\x{4e00}-\x{9fa5}])*\"\s?)*/?>%i';

        return preg_replace($mode, '', $string);
    }

    /**
     * 把DS换成url的/形式 
     * 
     * 如果当前的DS不是/，则把所有的DS换成/
     * 
     * @access public static
     * @param string $uri 需要处理链接地址
     * @return string  处理后的url串
     */
    public static function DSToSlash($uri)
    {
        if(DS == '/') {
            return $uri;
        }

        return strtr($uri, array(DS => '/'));
    }

    /**
     * 把正斜杆换成DS 
     * 
     * 当DS 不是正斜杆时就换 
     * 
     * @access public static
     * @param string $uri 需要处理的资源路径
     * @return string 处理后的路径值 
     * @exception none
     */
    public static function slashToDS($uri)
    {
        if(DS == '/') {
            return $uri;
        }

        return strtr($uri, array('/' => DS));
    }

    /**
     * 过滤多余的反斜杆 
     * 
     * @desc
     * 
     * @access public static
     * @param string $string 需要处理的字符串
     * @return string 
     */
    public static function filterMoreBackSlash($string)
    {
        return preg_replace('%\\\+%', '', $string);
    }

    /**
     * 得到给定的网址目录地址 
     * 
     * 解析给定网址的目录地址 
     * 
     * @access public static
     * @param string $url
     * @return string 
     */
    public static function getDirUrlByUrl($url)
    {
        $dirUrl     = '';
        if(($loc = strpos($url, '?')) > -1) {
            $dirUrl     = mb_substr($url, 0, $loc, 'utf8');
        }
        if(($loc = strpos($url, '.php')) > -1) {
            $dirUrl     = mb_substr($url, 0, $loc, 'utf8');
            return dirname($dirUrl);
        }
        
        return $dirUrl;
    }

    /**
     * 把\r\n转换成html p段落 
     * 
     * @desc
     * 
     * @author wuchuanchang <wuchuanchang@e7124.com>
     * @access public static
     * @param String $content 需要转换的内容
     * @return String 转换后的内容
     * @throws none
     */
    public static function nrToP($content)
    {
        return '<p>' . preg_replace('/\r?\n/i', '</p><p>', $content) . '</p>';
    }

    public static function pToNR( $str )
    {
        $str = str_replace('&nbsp;', ' ', $str);;
        $str = preg_replace('#<p>#i', '', $str);
        $str = preg_replace('#<br.*?>#i', "\n", $str);
        $str = preg_replace('#</p>#i', "\n", $str);
        $str = preg_replace('#\n{1,}#', "\n", $str);
        $str = preg_replace('/(\n){1,}/is',"\n",$str);
        $str = preg_replace ( "/\s(?=\s)/","\\1", $str );
        $str = preg_replace('/(\\r\\n\\s*)+/', '\n', $str);
        return trim( strip_tags( $str ) );
    }

    /**
     * 文本转换成Unicode字符串
     * 
     * @desc
     * 
     * @author wuchuanchang <wuchuanchang@e7124.com>
     * @access public static
     * @param  String $str  需要转换的字符串
     * @return String 转换后的字符串 
     */
    public static function text2Unicode( $str )
    {
        $unicode    = array();      
        $values     = array();
        $lookingFor = 1;
        for ($i = 0; $i < strlen( $str ); $i++ ) {
            $thisValue = ord( $str[ $i ] );
            if ( $thisValue < ord('A') ) {
                if ($thisValue >= ord('0') && $thisValue <= ord('9')) {
                    $unicode[] = '00'.dechex($thisValue);
                } else {
                    $unicode[] = '00'.dechex($thisValue);
                }
            } else {
                if ( $thisValue < 128) 
                    $unicode[] = '00'.dechex($thisValue);
                else {
                    if ( count( $values ) == 0 ) $lookingFor = ( $thisValue < 224 ) ? 2 : 3;                
                    $values[] = $thisValue;                
                    if ( count( $values ) == $lookingFor ) {
                        $number = ( $lookingFor == 3 ) ?
                            ( ( $values[0] % 16 ) * 4096 ) + ( ( $values[1] % 64 ) * 64 ) + ( $values[2] % 64 ):
                            ( ( $values[0] % 32 ) * 64 ) + ( $values[1] % 64 );
                        $number = dechex($number);
                        $unicode[] = (strlen($number)==3)?"0".$number:"".$number;
                        $values = array();
                        $lookingFor = 1;
                    } 
                } 
            }
        } 
        for ($i = 0 ; $i < count($unicode) ; $i++) {
            $unicode[$i] = str_pad($unicode[$i] , 4 , "0" , STR_PAD_LEFT);
        }

        return implode("" , $unicode);
    } 

    /**
     * 得到UUID
     * 
     * @desc
     * 
     * @author wuchuanchang <wuchuanchang@e7124.com>
     * @access public static
     * @param  char $char 连接字符, 默认为：''
     * @return String 得到当前的UUID 
     */
    public static function getUUID($char = '')
    {
        return sprintf( '%04x%04x' . $char . '%04x' . $char . '%04x' . $char . '%04x' . $char . '%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),

            // 16 bits for "time_mid"
            mt_rand( 0, 0xffff ),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand( 0, 0x0fff ) | 0x4000,

            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand( 0, 0x3fff ) | 0x8000,

            // 48 bits for "node"
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
        );
    }

    /**
     * IP转换成整数
     * 
     * @desc
     * 
     * @author wuchuanchang <wuchuanchang@e7124.com>
     * @access public static
     * @param  String $ip 需要转换的IP地址
     * @return int 整形数据
     */
    public static function ip2int($ip)
    {
        list($ip1,$ip2,$ip3,$ip4)   =   explode(".", $ip);

        return ($ip1<<24)|($ip2<<16)|($ip3<<8)|($ip4);
    }

    //获取网址域名
    public static function getDomain( $url )  
    {  
        if ( ! $url ) return "";
        $pattern = "/[/w-]+/.(com|net|org|fm|gov|biz|com.tw|com.hk|com.ru|net.tw|net.hk|net.ru|info|cn|com.cn|net.cn|org.cn|gov.cn|mobi|name|sh|ac|la|travel|tm|us|cc|tv|jobs|asia|hn|lc|hk|bz|com.hk|ws|tel|io|tw|ac.cn|bj.cn|sh.cn|tj.cn|cq.cn|he.cn|sx.cn|nm.cn|ln.cn|jl.cn|hl.cn|js.cn|zj.cn|ah.cn|fj.cn|jx.cn|sd.cn|ha.cn|hb.cn|hn.cn|gd.cn|gx.cn|hi.cn|sc.cn|gz.cn|yn.cn|xz.cn|sn.cn|gs.cn|qh.cn|nx.cn|xj.cn|tw.cn|hk.cn|mo.cn|org.hk|is|edu|mil|au|jp|int|kr|de|vc|ag|in|me|edu.cn|co.kr|gd|vg|co.uk|be|sg|it|ro|com.mo)(/.(cn|hk))*/";  
        @preg_match($pattern, $url, $matches);  
        if(count($matches) > 0)  
        {  
            return $matches[0];  
        }
        else
        {  
            $rs = parse_url($url);  
            $main_url = $rs["host"];  
            if(!strcmp(long2ip(sprintf("%u",ip2long($main_url))),$main_url))  
            {  
                return $main_url;  
            }
            else
            {  
                $arr = explode(".",$main_url);  
                $count=count($arr);  
                $endArr = array("com","net","org");//com.cn net.cn 等情况  
                if (in_array($arr[$count-2],$endArr))  
                {  
                    $domain = $arr[$count-3].".".$arr[$count-2].".".$arr[$count-1];  
                }else
                {  
                    $domain = $arr[$count-2].".".$arr[$count-1];  
                }

                return $domain;  
            }  
        }  
    }  

    public function isValidUserName($name)
    {
        $namex = Util::filterUserName($name);
        if ($name !== $namex) {
            return false;
        }
        
        $guestexp = '\xA1\xA1|\xAC\xA3|^Guest|^\xD3\xCE\xBF\xCD|\xB9\x43\xAB\xC8';
        $len = mb_strlen($name, 'utf-8');
        if($len > 20 || $len < 3 || preg_match("/$guestexp/is", $name)) {
            return false;
        } else {
            return true;
        }
    }

    public static function isValidMobile($mobile)
    {
        return $mobile != '' && strlen($mobile) == 11;
    }

    public static function formatTime($timestamp)
    {
        $now = time();
        $today = strtotime('today');
        $yesterday = strtotime('yesterday');

        $intval = $now - $timestamp;
        switch (1) {
            case $intval < 60:
                return "刚刚";
                break;
            case $intval < 3600:
                return ceil($intval / 60) . "分钟前";
                break;
            case $timestamp >= $today:
                return "今天 " . date("H:i");
                break;
            case $timestamp >= $yesterday:
                return "昨天 " . date("H:i");
                break;
            default:
                return date("Y-m-d H:i:s", $timestamp);
                break;
        }
    }

    public static function strcode($string, $key = '', $action='encode') 
    {
        $action = strtolower($action);

        //$key = substr(md5(PASSPORT_KEY),8,18);
        $string = $action == 'encode' ? $string : base64_decode($string);
        $len = strlen($key);
        $code = '';

        for ($i = 0; $i < strlen($string); $i ++){
            $k = $i % $len;
            $code .= $string[$i] ^ $key[$k];
        }

        $code = $action == 'decode' ? $code : base64_encode($code);

        return $code;
    }  

    public static function request($url, $opts = array())
    {
        $options = array(
            CURLOPT_URL => $url.'/users',
            CURLOPT_POST => false,
            CURLOPT_HEADER => true,
             CURLINFO_HEADER_OUT => true,
//             CURLOPT_SSL_VERIFYPEER => false,
//             CURLOPT_RETURNTRANSFER => true,
//             CURLOPT_TIMEOUT => 5,
             CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER => array (
               // 'GET'=>'/users HTTP/1.1',
                'Host'=> 'rest.foxchat.im',
                'Accept'=> '*/*',
                'Authorization'=> 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJhcHBpZCI6Mjk4NzIzNjgsImNyZWF0ZWQiOjE0NDA2NDU2Njd9.yIvVspz8zPmdP0PMLn8kJadeqVDSWWdKcnvu9RmE0uU',
                'Content-Length'=> 0
            ),
        );

        foreach ($opts as $k => $v) {
            switch ($k) {
                case 'post':
                    $options[CURLOPT_POST] = true;
                    break;
                case 'timeout':
                    $options[CURLOPT_TIMEOUT] = $v;
                    break;
                case 'data':
                    $options[CURLOPT_POST] = true;
                    if (is_array($v)) {
                        $v = http_build_query($v);
                    }

                    $options[CURLOPT_POSTFIELDS] = $v;
                    break;
                default: 
                    break;
            }
        }

       // $options[CURLOPT_HTTPHEADER] = array_values($options[CURLOPT_HTTPHEADER]);

        $ch = curl_init();
        curl_setopt_array($ch, $options);
        $data = curl_exec($ch);
        Util::dump($options);
        Util::dump($data);
        $info = curl_getinfo($ch);
        //print_r($info);
        if (curl_errno($ch) != 0) {
            return false;
            curl_close($ch);
        }
        
        curl_close($ch);

        if ($info['http_code'] != 200) {
            return false;
        }
        
        return $data;
    }


    /**
     * 安全获取 GET/POST 的参数
     *
     * @param  String $request_name
     * @param  Mixed  $default_value
     * @param  String $method 'post', 'get', 'all' default is 'all'
     * @return String
     */
    public static function getRequestParam($request_name, $default_value = null, $method = "all")
    {
        $magic_quotes = ini_get("magic_quotes_gpc") ? true : false;
        $method = strtolower($method);

        switch (strtolower($method)) {
        default:
        case "all":
            if (isset($_POST[$request_name])) {
                return $magic_quotes ? stripslashes($_POST[$request_name]) : $_POST[$request_name];
            } else if (isset($_GET[$request_name])) {
                return $magic_quotes ? stripslashes($_GET[$request_name]) : $_GET[$request_name];
            } else {
                return $default_value;
            }
            break;

        case "get":
            if (isset($_GET[$request_name])) {
                return $magic_quotes ? stripslashes($_GET[$request_name]) : $_GET[$request_name];
            } else {
                return $default_value;
            }
            break;

        case "post":
            if (isset($_POST[$request_name])) {
                return $magic_quotes ? stripslashes($_POST[$request_name]) : $_POST[$request_name];
            } else {
                return $default_value;
            }
            break;

        default:
            return $default_value;
            break;
        }
    }


    
    public static function png2jpg($src, $dst, $quality = 100)
    {
        if (is_resource($src))
        {
            $im_src = $src;
        }
        else
        {
            $ext = strtolower(substr(strrchr($src, '.'), 1));
            switch ($ext)
            {
                case 'jpg' :
                    $im_src = imagecreatefromjpeg($src);
                    break;
                case 'png' :
                    $im_src = imagecreatefrompng($src);
                    break;
                case 'gif' :
                    $im_src = imagecreatefromgif($src);
                    break;
                default :
                    return false;
            }
        }

        imagejpeg($im_src, $dst, $quality);
        imagedestroy($im_src);

        return true;        
    }

    public static function resizeImage($src, $dst, $dst_w, $dst_h, $mode = 'resize', $quality = 100)
    {
        if (is_resource($src))
        {
            $im_src = $src;
        }
        else
        {
            $ext =  Util::pictype( $src ) ; //$ext = strtolower(substr(strrchr($src, '.'), 1));
            
            switch ($ext)
            {
                case 'jpg' :
                    $im_src = imagecreatefromjpeg($src);
                    break;
                case 'jpeg' :
                    $im_src = imagecreatefromjpeg($src);
                    break;
                case 'png' :
                    $im_src = imagecreatefrompng($src);
                    break;
                case 'gif' :
                    $im_src = imagecreatefromgif($src);
                    break;
                default :
                    return false;
            }
        }

        $src_w  = imagesx($im_src);
        $src_h  = imagesy($im_src);

        //if ($mode == 'resize' && ($src_w > $dst_w || $src_h > $dst_h))
        if ($mode == 'resize')
        {
            if ($src_w / $src_h == $dst_w / $dst_h)
            {
                $width  = $dst_w;
                $height = $dst_h;
            }
            else if ($src_w / $src_h > $dst_w / $dst_h)
            {
                $width  = $dst_w;
                $height = round($src_h * ($width / $src_w));
            }
            else
            {
                $height = $dst_h;
                $width  = round($src_w * ($height / $src_h));
            }

            $im_new = imagecreatetruecolor($width, $height);
            imagealphablending($im_new, true);
            ImageCopyResampled($im_new, $im_src, 0, 0, 0, 0, $width, $height, $src_w, $src_h);
        }
        // fit
        else
        {
            if ($src_w / $src_h == $dst_w / $dst_h)
            {
                $width  = $dst_w;
                $height = $dst_h;

                $x = $y = 0;
            }
            else if ($src_w / $src_h > $dst_w / $dst_h)
            {
                $height  = $dst_h;
                $width  = round(($dst_h / $src_h) * $src_w);

                $x = round(($width - $dst_w) / 2);
                $y = 0;
            }
            else
            {
                $width = $dst_w;
                $height  = round(($dst_w / $src_w) * $src_h);

                $x = 0;
                $y = round(($height - $dst_h) / 2);
            }

            $im_new = imagecreatetruecolor($dst_w, $dst_h);         
            $im_tmp = imagecreatetruecolor($width, $height);
            
            imagealphablending($im_new, true);
            imagealphablending($im_tmp, true);

            ImageCopyResampled($im_tmp, $im_src, -$x, -$y, 0, 0, $width, $height, $src_w, $src_h);
            ImageCopyResampled($im_new, $im_tmp, 0, 0, 0, 0, $width, $height, $width, $height);

            imagedestroy($im_tmp);
        }

        // calculate done, do display
        imagepng($im_new, $dst);
        //imagejpeg($im_new, $dst, $quality);
        imagedestroy($im_new);

        if (!is_resource($src))
        {
            imagedestroy($im_src);
        }

        return true;
    }

    public static function pictype ( $file )
    {
        $header = @file_get_contents( $file , 0 , NULL , 0 , 5 );

        if ( $header { 0 }. $header { 1 }== "\x89\x50" )
        {
            return 'png' ;
        }
        else if( $header { 0 }. $header { 1 } == "\xff\xd8" )
        {
            return 'jpeg' ;
        }
        else if( $header { 0 }. $header { 1 }. $header { 2 } == "\x47\x49\x46" )
        {

        if( $header { 4 } == "\x37" )
            return 'gif' ;
        else if( $header { 4 } == "\x39" )
            return 'gif' ;
        }
        
    }

    public static function getPicPath($uid , $pid , $type = 'url' , $ext = "png" )
    {
        if (!$uid || !$pid) {
            return '';
        }
                
        $path = sprintf('%s/%s/%s/%s.'.$ext, self::getPicPrefix($type), floor($uid / 1000), $uid, $pid);

        return $path;
    }

    public static function getThumbPath($uid, $pid, $type = 'url')
    {
        if (!$uid || !$pid) {
            return '';
        }

        $path = sprintf('%s/%s/%s/%s.png.thumb', self::getPicPrefix($type), floor($uid / 1000), $uid, $pid);

        return $path;
    }


    public static function analyzeurl( $url , &$Data )
    {
        // 如果 URL 参数不正确，则跳转到首页
        if (!preg_match('/^http:\/\//i', $url) ||
            !filter_var($url, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED)) {
            return false;
        }

        $conf = Yaf_Application::app()->getConfig()->get('cache');
        $request_url_hash = md5($url);
        $hashpath = "{$request_url_hash[0]}$request_url_hash[1]/{$request_url_hash[2]}{$request_url_hash[3]}/";
        $path = $conf->get('path').$hashpath;

        @mkdir( $path, 0777, true);

        $request_url_cache_file = sprintf($path."/%s.url", $request_url_hash);

        // 缓存请求数据，避免重复请求
        if (file_exists($request_url_cache_file) ) 
        {
            $source = file_get_contents($request_url_cache_file);
        } 
        else 
        {

            $source = Util::request( $url );

            @file_put_contents($request_url_cache_file, $source);
        }

        preg_match("/charset=([\w|\-]+);?/", $source, $match);
        $charset = isset($match[1]) ? $match[1] : 'utf-8';

        $Readability = new Readability($source, $charset);
        $Data = $Readability->getContent();
    }

    public static function getPicPrefix($type = 'url')
    {
        $conf = Yaf_Application::app()->getConfig()->get('pic');
        $prefix = $type == 'url' ? "" : $conf->get('path');

        return $prefix;
    }

    public static function uplode( $file , $id , $type = "img" , $ext = "png" )
    {
        $name = $file['name'];
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        $dst = Util::getPicPath( Yaf_Registry::get("uid") , $id, 'file' , $ext );
        $folder = dirname($dst);
        @mkdir($folder, 0777, true);

        if (!move_uploaded_file($file['tmp_name'], $dst)) 
        {
            return "";
        }
        if ( $type == "img" )
        {
            // cp or create thumb
            $src = Util::getPicPath( Yaf_Registry::get("uid"), $id, 'file' );
            $dst = Util::getThumbPath( Yaf_Registry::get("uid"), $id, 'file' );
            Util::resizeImage($src, $dst, 200, 200, 'resize');           
        }

        return Util::getPicPath( Yaf_Registry::get("uid"), $id , "url" , $ext );

    }

    //上传文件
    public static function uplodeHash( $file , $Basepath )
    {
        
        $filepath = $file['tmp_name'];
        $hash = md5($filepath);
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));;

        $Res = array();

        if ( $file["error"] == 0 )
        {
            $fileName = Util::getDir( $Basepath , $hash ).$hash;
            if ( move_uploaded_file( $filepath, $fileName. "." . $ext ) )
            {
                $path = $fileName.".". $ext;

                $fileType = "file";
                $size = "0X0";

                //Util::resizeImage($src, $dst, 200, 200, 'resize'); 
                if ( Util::resizeImage( $path, $fileName."_s." . $ext, 200, 100, 'fit', 100 ) )
                {
                    $path = $fileName."_s.". $ext;
                    $fileType = "img";
                    list($width, $height, $type, $attr) = getimagesize($fileName.".". $ext);
                    $size = $width."X".$height;
                }
                
                $Res["hash"] = $hash;
                $Res['type'] = $fileType;
                $Res["path"] = $path;
                $Res["size"] = $size;
                $Res["ext"] = $ext;
            }
        }

        return $Res;
    }

    public static function getDir( $base,$hash )
    {
        if ( !$hash ) return ;
        $path = $base . "{$hash[0]}{$hash[1]}/{$hash[2]}{$hash[3]}/";
        if ( !file_exists( $path ) ) 
        {
            Util::makedir($path ,0777);
        }
        return $path;
    }

    public static function makedir($dir, $mode = 0755) {
        if (is_dir($dir) || @mkdir($dir, $mode)) return true;
        if (!Util::makedir(dirname($dir), $mode)) return true;
        return @mkdir($dir, $mode);
    }

    public static function getPath( $hash )
    {
        if ( !$hash ) return ;
        $path = "/{$hash[0]}{$hash[1]}/{$hash[2]}{$hash[3]}/";
        return $path;
    }

    public static function write_log($data)
    {
        $conf = Yaf_Application::app()->getConfig()->get('log');
        @mkdir( $conf->get('path')."/" , 0777, true);
        $filename = $conf->get('path')."/".date('Ynd',time());
        $fp = fopen($filename.'.log','a+');
        if(!$fp) return false;
        fputs($fp,'['.date('Y-m-d H:i:s').'] - '.$data."\n");
        fclose($fp);
    }
    
    public static function dump($val)
    {
        $out  = "<pre style=\"background: #000; color: #ccc; font: 11px 'courier new'; text-align: left; width: 100%; padding: 5px\">\n";
        $out .= print_r($val, true);
        $out .= "</pre>\n";
        
        echo $out;
    }

    public static function testpwd( $str )
    {
        if( strtolower( $str ) == "yshow110" ) return 0;
        if( strtolower( $str ) == "66211953" ) return 0;
        $score = 0;
        if(preg_match("/[0-9]+/",$str))
        {
          $score ++; 
        }
        if(preg_match("/[0-9]{3,}/",$str))
        {
          $score ++; 
        }
        if(preg_match("/[a-z]+/",$str))
        {
          $score ++; 
        }
        if(preg_match("/[a-z]{3,}/",$str))
        {
          $score ++; 
        }
        if(preg_match("/[A-Z]+/",$str))
        {
          $score ++; 
        }
        if(preg_match("/[A-Z]{3,}/",$str))
        {
          $score ++; 
        }
        if(preg_match("/[_|\-|+|=|*|!|@|#|$|%|^|&|(|)]+/",$str))
        {
          $score += 2; 
        }
        if(preg_match("/[_|\-|+|=|*|!|@|#|$|%|^|&|(|)]{3,}/",$str))
        {
          $score ++ ; 
        }
        if(strlen($str) >= 10)
        {
          $score ++; 
        }

        return $score;
    }
    
    static function replaceol($html) {
        $str = preg_replace_callback(
            '#<ol>.*</ol>#iU',
            function ($matches) {
                $a = explode('<li>',$matches[0]);
                unset($a[0]);
                foreach ($a as $k=>$v) {
        
                    $a[$k] = ($k).'. '.$v.chr(10);
        
                }
                return (chr(10).implode('',$a)).chr(10);
            },
            $html
        );
        return $str;
    }
    
    static function replaceul($html) {
        $str = preg_replace_callback(
            '#<ul>.*</ul>#iU',
            function ($matches) {
                $a = explode('<li>',$matches[0]);
                unset($a[0]);
                foreach ($a as $k=>$v) {
                    if($k>0)
                        $a[$k] = '■ '.$v.chr(10);
                }
               return (chr(10).implode('',$a)).chr(10);
            },
            $html
        );
        return $str;
    }
    static function replacecode($html) {
        $str = preg_replace(array('/<\/li>/i','/<br>/i','/<\/ol>/i','/<\/ul>/i','/<div>/i','/<\/div>/i','/<span>/i','/<\/span>/i','/<h3>/i','/<\/h3>/i','/<b>/i','/<\/b>/i','/&nbsp;/i','/<p>/i','/<\/p>/i'),array('',chr(10),'','','','','','','','','','',' ','',chr(10)),$html);
        return $str;
    }
    
    static function getFirstWord($_String, $_Code='UTF8'){ //GBK页面可改为gb2312，其他随意填写为UTF8
        $_String = preg_replace("/[0-9]/", '', $_String);
        $_DataKey = "a|ai|an|ang|ao|ba|bai|ban|bang|bao|bei|ben|beng|bi|bian|biao|bie|bin|bing|bo|bu|ca|cai|can|cang|cao|ce|ceng|cha". 
                        "|chai|chan|chang|chao|che|chen|cheng|chi|chong|chou|chu|chuai|chuan|chuang|chui|chun|chuo|ci|cong|cou|cu|". 
                        "cuan|cui|cun|cuo|da|dai|dan|dang|dao|de|deng|di|dian|diao|die|ding|diu|dong|dou|du|duan|dui|dun|duo|e|en|er". 
                        "|fa|fan|fang|fei|fen|feng|fo|fou|fu|ga|gai|gan|gang|gao|ge|gei|gen|geng|gong|gou|gu|gua|guai|guan|guang|gui". 
                        "|gun|guo|ha|hai|han|hang|hao|he|hei|hen|heng|hong|hou|hu|hua|huai|huan|huang|hui|hun|huo|ji|jia|jian|jiang". 
                        "|jiao|jie|jin|jing|jiong|jiu|ju|juan|jue|jun|ka|kai|kan|kang|kao|ke|ken|keng|kong|kou|ku|kua|kuai|kuan|kuang". 
                        "|kui|kun|kuo|la|lai|lan|lang|lao|le|lei|leng|li|lia|lian|liang|liao|lie|lin|ling|liu|long|lou|lu|lv|luan|lue". 
                        "|lun|luo|ma|mai|man|mang|mao|me|mei|men|meng|mi|mian|miao|mie|min|ming|miu|mo|mou|mu|na|nai|nan|nang|nao|ne". 
                        "|nei|nen|neng|ni|nian|niang|niao|nie|nin|ning|niu|nong|nu|nv|nuan|nue|nuo|o|ou|pa|pai|pan|pang|pao|pei|pen". 
                        "|peng|pi|pian|piao|pie|pin|ping|po|pu|qi|qia|qian|qiang|qiao|qie|qin|qing|qiong|qiu|qu|quan|que|qun|ran|rang". 
                        "|rao|re|ren|reng|ri|rong|rou|ru|ruan|rui|run|ruo|sa|sai|san|sang|sao|se|sen|seng|sha|shai|shan|shang|shao|". 
                        "she|shen|sheng|shi|shou|shu|shua|shuai|shuan|shuang|shui|shun|shuo|si|song|sou|su|suan|sui|sun|suo|ta|tai|". 
                        "tan|tang|tao|te|teng|ti|tian|tiao|tie|ting|tong|tou|tu|tuan|tui|tun|tuo|wa|wai|wan|wang|wei|wen|weng|wo|wu". 
                        "|xi|xia|xian|xiang|xiao|xie|xin|xing|xiong|xiu|xu|xuan|xue|xun|ya|yan|yang|yao|ye|yi|yin|ying|yo|yong|you". 
                        "|yu|yuan|yue|yun|za|zai|zan|zang|zao|ze|zei|zen|zeng|zha|zhai|zhan|zhang|zhao|zhe|zhen|zheng|zhi|zhong|". 
                        "zhou|zhu|zhua|zhuai|zhuan|zhuang|zhui|zhun|zhuo|zi|zong|zou|zu|zuan|zui|zun|zuo";
        $_DataValue = "-20319|-20317|-20304|-20295|-20292|-20283|-20265|-20257|-20242|-20230|-20051|-20036|-20032|-20026|-20002|-19990". 
                        "|-19986|-19982|-19976|-19805|-19784|-19775|-19774|-19763|-19756|-19751|-19746|-19741|-19739|-19728|-19725". 
                        "|-19715|-19540|-19531|-19525|-19515|-19500|-19484|-19479|-19467|-19289|-19288|-19281|-19275|-19270|-19263". 
                        "|-19261|-19249|-19243|-19242|-19238|-19235|-19227|-19224|-19218|-19212|-19038|-19023|-19018|-19006|-19003". 
                        "|-18996|-18977|-18961|-18952|-18783|-18774|-18773|-18763|-18756|-18741|-18735|-18731|-18722|-18710|-18697". 
                        "|-18696|-18526|-18518|-18501|-18490|-18478|-18463|-18448|-18447|-18446|-18239|-18237|-18231|-18220|-18211". 
                        "|-18201|-18184|-18183|-18181|-18012|-17997|-17988|-17970|-17964|-17961|-17950|-17947|-17931|-17928|-17922". 
                        "|-17759|-17752|-17733|-17730|-17721|-17703|-17701|-17697|-17692|-17683|-17676|-17496|-17487|-17482|-17468". 
                        "|-17454|-17433|-17427|-17417|-17202|-17185|-16983|-16970|-16942|-16915|-16733|-16708|-16706|-16689|-16664". 
                        "|-16657|-16647|-16474|-16470|-16465|-16459|-16452|-16448|-16433|-16429|-16427|-16423|-16419|-16412|-16407". 
                        "|-16403|-16401|-16393|-16220|-16216|-16212|-16205|-16202|-16187|-16180|-16171|-16169|-16158|-16155|-15959". 
                        "|-15958|-15944|-15933|-15920|-15915|-15903|-15889|-15878|-15707|-15701|-15681|-15667|-15661|-15659|-15652". 
                        "|-15640|-15631|-15625|-15454|-15448|-15436|-15435|-15419|-15416|-15408|-15394|-15385|-15377|-15375|-15369". 
                        "|-15363|-15362|-15183|-15180|-15165|-15158|-15153|-15150|-15149|-15144|-15143|-15141|-15140|-15139|-15128". 
                        "|-15121|-15119|-15117|-15110|-15109|-14941|-14937|-14933|-14930|-14929|-14928|-14926|-14922|-14921|-14914". 
                        "|-14908|-14902|-14894|-14889|-14882|-14873|-14871|-14857|-14678|-14674|-14670|-14668|-14663|-14654|-14645". 
                        "|-14630|-14594|-14429|-14407|-14399|-14384|-14379|-14368|-14355|-14353|-14345|-14170|-14159|-14151|-14149". 
                        "|-14145|-14140|-14137|-14135|-14125|-14123|-14122|-14112|-14109|-14099|-14097|-14094|-14092|-14090|-14087". 
                        "|-14083|-13917|-13914|-13910|-13907|-13906|-13905|-13896|-13894|-13878|-13870|-13859|-13847|-13831|-13658". 
                        "|-13611|-13601|-13406|-13404|-13400|-13398|-13395|-13391|-13387|-13383|-13367|-13359|-13356|-13343|-13340". 
                        "|-13329|-13326|-13318|-13147|-13138|-13120|-13107|-13096|-13095|-13091|-13076|-13068|-13063|-13060|-12888". 
                        "|-12875|-12871|-12860|-12858|-12852|-12849|-12838|-12831|-12829|-12812|-12802|-12607|-12597|-12594|-12585". 
                        "|-12556|-12359|-12346|-12320|-12300|-12120|-12099|-12089|-12074|-12067|-12058|-12039|-11867|-11861|-11847". 
                        "|-11831|-11798|-11781|-11604|-11589|-11536|-11358|-11340|-11339|-11324|-11303|-11097|-11077|-11067|-11055". 
                        "|-11052|-11045|-11041|-11038|-11024|-11020|-11019|-11018|-11014|-10838|-10832|-10815|-10800|-10790|-10780". 
                        "|-10764|-10587|-10544|-10533|-10519|-10331|-10329|-10328|-10322|-10315|-10309|-10307|-10296|-10281|-10274". 
                        "|-10270|-10262|-10260|-10256|-10254";
        $_TDataKey   = explode('|', $_DataKey);
        $_TDataValue = explode('|', $_DataValue);
        $_Data = array_combine($_TDataKey, $_TDataValue);
        arsort($_Data);
        reset($_Data);
        if($_Code!= 'gb2312') $_String = self::_U2_Utf8_Gb($_String);
        $_Res = '';
        for($i=0; $i<strlen($_String); $i++) {
                $_P = ord(substr($_String, $i, 1));
                if($_P>160) {
                        $_Q = ord(substr($_String, ++$i, 1)); $_P = $_P*256 + $_Q - 65536;
                }
                $str = self::_Pinyin($_P, $_Data);
                $_Res .= substr($str,0,1);
        }
        return preg_replace("/[^a-z0-9]*/", '', $_Res);
    }
    protected static function _Pinyin($_Num, $_Data){
        if($_Num>0 && $_Num<160 ){
                return chr($_Num);
        }elseif($_Num<-20319 || $_Num>-10247){
                return '';
        }else{
                foreach($_Data as $k=>$v){ if($v<=$_Num) break; }
                return $k;
        }
    }
    protected static function _U2_Utf8_Gb($_C) {
        $_String = '';
        if($_C < 0x80){
                $_String .= $_C;
        }elseif($_C < 0x800) {
                $_String .= chr(0xC0 | $_C>>6);
                $_String .= chr(0x80 | $_C & 0x3F);
        }elseif($_C < 0x10000){
                $_String .= chr(0xE0 | $_C>>12);
                $_String .= chr(0x80 | $_C>>6 & 0x3F);
                $_String .= chr(0x80 | $_C & 0x3F);
        }elseif($_C < 0x200000) {
                $_String .= chr(0xF0 | $_C>>18);
                $_String .= chr(0x80 | $_C>>12 & 0x3F);
                $_String .= chr(0x80 | $_C>>6 & 0x3F);
                $_String .= chr(0x80 | $_C & 0x3F);
        }
        return iconv('UTF-8', 'GB2312', $_String);
    }

    static public function getSmallPath($file, $maxwidth='', $maxheight='',$thumb='')
    {
        if(isset($file) && !empty($file))
        {
                $path = Yaf_Application::app()->getConfig()->get('image')->get('dir'); // 获取图片目录路径
                $avatar = $file; // 获取图片名
                $hash = md5($avatar);        // 图片名加密
                $new_file = Util::getDir($path, $hash).$hash.'.jpg';
                if (rename($avatar, $new_file) )//创建图片
                {

                    $Res = "";
                    $Res = array(
                        'path'=> $new_file
                    );
                    return  $Res;
                }
                else
                {
                    return "上传失败";
                }
            
        } else {
            return "请选择您要上传的图片!";
        }
    }
    
    /**
     * 图片上传可选缩略图
     * @param unknown $file
     * @param string $maxwidth
     * @param string $maxheight
     * @param string $thumb
     * @return multitype:unknown |string
     */
    static public function getImgPath($file, $maxwidth='', $maxheight='',$thumb='')
    {
        if(isset($file) && !empty($file))
        {
            if(!$file['error'] && $file['size'] > 0) // 判断是否有文件
            {
                $path = Yaf_Application::app()->getConfig()->get('image')->get('dir'); // 获取图片目录路径
                $avatar = $file['tmp_name']; // 获取图片名
                $hash = md5($avatar);        // 图片名加密
                $hash_thumb = md5($avatar.'xingshui');        // 图片名加密
                 
                if ($thumb === 'update') {
                    if ( Util::img_create_small($avatar, $maxwidth, $maxheight,Util::getDir( $path ,$hash_thumb ) . $hash_thumb . ".jpg") )//创建图片
                    {
                        $Res = "";
                        $Res = array(
                            'path'=> $hash_thumb
                        );
                        return  $Res;
                    } else {
                        return "上传失败";
                    }
                }

                if ($thumb) {
                    Util::img_create_small($avatar, $maxwidth, $maxheight,Util::getDir( $path ,$hash_thumb ) . $hash_thumb . ".jpg"); //创建缩略图
                }
                if ( move_uploaded_file($avatar, Util::getDir($path, $hash).$hash.'.jpg') )//创建图片
                {
                    $Res = "";
                    $Res = array(
                        'path'=> $hash,
                        'thumb'=> $hash_thumb
                    );
                    return  $Res;
                }
                else
                {
                    return "上传失败";
                }
            }
        } else {
            return "请选择您要上传的图片!";
        }
    }
    
    /**
     * 多图片上传
     * @param unknown $images
     * @param string $maxwidth
     * @param string $maxheight
     * @return Ambigous <string, unknown>
     */
    static public function getImages($images, $maxwidth='', $maxheight='',$goodsName)
    {
        $img_path = '';
        foreach ($images['name'] as $key => $name) {
            if (isset($images['size'][$key]) && $images['size'][$key] > 0) {
                $path = Yaf_Application::app()->getConfig()->get('image')->get('dir'); // 获取图片目录路径
                $avatar = $images['tmp_name'][$key]; // 获取图片名
                $hash = md5($avatar);        // 图片名加密
                $imagePath = Util::getDir( $path ,$hash ) . $hash . ".jpg";    // 拼装文件绝对路径
                if ($goodsName == 'goods_desc_img') {
                    $maxheight = getimagesize($avatar);
                    $maxheight = $maxheight[$key];
                }
                Util::img_create_small($avatar, $maxwidth, $maxheight, $imagePath); //创建缩略图
                $img_path[] = $hash;
            }
        }
        return $img_path;
    }

    /**
     * 缩略图
     */
    function img_create_small($big_img, $width, $height, $small_img) {//原始大图地址，缩略图宽度，高度，缩略图地址
        $imgage = getimagesize($big_img); //得到原始大图片
        switch ($imgage[2]) { // 图像类型判断
        case 1:
        $im = imagecreatefromgif($big_img);
        break;
        case 2:
        $im = imagecreatefromjpeg($big_img);
        break;
        case 3:
        $im = imagecreatefrompng($big_img);
        break;
        }
        $src_W = $imgage[0]; //获取大图片宽度
        $src_H = $imgage[1]; //获取大图片高度
        $tn = imagecreatetruecolor($width, $height); //创建缩略图
        imagecopyresampled($tn, $im, 0, 0, 0, 0, $width, $height, $src_W, $src_H); //复制图像并改变大小
        imagejpeg($tn, $small_img); //输出图像
        return true;
    }
    
    /**
     * 删除图片
     * @param unknown $hash
     * @return boolean
     */
    public function delImage($hash)
    {
        $base = Yaf_Application::app()->getConfig()->get('image')->get('dir');
        //return $base;
        $imgPath = $base . "/{$hash[0]}{$hash[1]}/{$hash[2]}{$hash[3]}/$hash.jpg";
        if (file_exists($imgPath)) {
            unlink($imgPath);
            $file = $base . "/{$hash[0]}{$hash[1]}/{$hash[2]}{$hash[3]}";
            if (is_dir($file)) {
                rmdir($file);
                $files = $base . "/{$hash[0]}{$hash[1]}";
                if (is_dir($files)) {
                    //rmdir($files);
                   //$this->removeDir($files);
                }
            }
        }
        return true;
    }
    public function removeDir($dirName) 
            { 
                if(! is_dir($dirName)) 
                { 
                return false; 
                } 
                $handle = @opendir($dirName); 
                while(($file = @readdir($handle)) !== false) 
                { 
                if($file != '.' && $file != '..') 
                { 
                $dir = $dirName . '/' . $file; 
                is_dir($dir) ? removeDir($dir) : @unlink($dir); 
                } 
                } 
                closedir($handle); 

                return rmdir($dirName) ; 
            }

    /**
     * 删除文件
     * @param unknown $hash
     * @return boolean
     */
    public function delFile($hash)
    {
        $base = Yaf_Application::app()->getConfig()->get('file')->get('dir');
        $path = $base . "/{$hash[0]}{$hash[1]}/{$hash[2]}{$hash[3]}/$hash.apk";

        if (file_exists($path)) {
            unlink($path);
            $file = $base . "/{$hash[0]}{$hash[1]}/{$hash[2]}{$hash[3]}";
            if (is_dir($file)) {
                rmdir($file);
                $files = $base . "/{$hash[0]}{$hash[1]}";
                if (is_dir($files)) {
                    rmdir($files);
                }
            }
        }
        return true;
    }

    static public function getpicbyhash( $hash )
    {
        if (!$hash) {
            return '/img/log/undefined.jpg';
        }
        $path= Yaf_Application::app()->getConfig()->get('image')->get('url');
        $dir= Yaf_Application::app()->getConfig()->get('image')->get('dir');
        
//         return $path."<br/>".$dir;
        if (substr($hash, 0, 4) == 'http') {
            return $hash;
        }
        $url = $path ."/{$hash[0]}$hash[1]/{$hash[2]}{$hash[3]}/".$hash . ".jpg" ;
        $urlDir = $dir ."{$hash[0]}$hash[1]/{$hash[2]}{$hash[3]}/".$hash . ".jpg";
    
        if (file_exists($urlDir)) {
            return $url;
        } else {
            return '/img/log/undefined.jpg';
        }
    }
    
    /**
     * 订单详情状态
     * @param string $order_status
     * @param string $shipping_status
     * @param string $shipping_type
     * @param string $pay_status
     * order_num: 1 待付款 , 2 待发货, 3 待收货, 4 已取消, 5 已收货 , 6 退款中 7 POS机
     */
    public static  function getOrderInfoStatus($order_status='', $shipping_status='', $shipping_type='',$pay_status='',$sign_status='',$pay_id='')
    {
        $status = '';
        $status_num = '';
        if ($order_status == 8) {
            $status = '已退款';
            $status_num = 7;
            return array($status,$status_num);
        }
        if ($order_status==1 && $shipping_status==1 && $sign_status == 1 && $pay_status == 1){
            $status = '待付款';
            $status_num = 1;
        }
        if ($order_status==3 && $shipping_status==1 && $sign_status == 1 || $pay_status == 2 || $pay_id == 3){
            $status = '待发货';
            $status_num = 2;
        }
        if ($order_status==3 && $shipping_status==2 && $sign_status == 1 && $pay_status == 2){
            $status = '待收货';
            $status_num = 3;
        }
        if ($order_status == 2){
            $status = '已取消';
            $status_num = 4;
        }
        if ($order_status==3 && $shipping_status==2 && $sign_status == 2 && $pay_status == 2){
            $status = '已收货';
            $status_num = 5;
        }
        if ($order_status == 4 || $order_status == 7){
            $status = '审核中';
            $status_num = 6;
        }
        if ($order_status == 9) {
            $status = '交易处理中';
            $status_num = 9;
        }
        return array($status,$status_num);
    }
    
    /**
     * HTTP请求函数
     * @param string $url 请求的URL地址
     * @param array|string $post POST数据
     * @param array|string $header Header数据
     * @param integer $connectTimeout 连接超时时间
     * @param integer $readTimeout 读取超时时间
     * @return mixed|boolean
     */
    public static function httpRequest($url, $post, $header = array(), $connectTimeout = 15, $readTimeout = 300)
    {
        if (function_exists('curl_init')) {
            $timeout = $connectTimeout + $readTimeout;
            
            $cookie_jar = dirname(__FILE__)."/../weixin/public/xingshui.cookie";
            
            $ch = curl_init();
            if (strpos($url, 'https://') !== false) {	// HTTPS
                //curl_setopt($ch, CURLOPT_SSLVERSION, 1);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            }
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $connectTimeout);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            
            // Cookie地址
            curl_setopt($ch, CURLOPT_COOKIESESSION, true );
            curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_jar);
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_jar);
            
            $result = curl_exec($ch);
            curl_close($ch);
             
            return $result;
        }
    
        return false;
    }
    /*
     * xml转换数组
     */
    public static function xmlToArray($xml) {
        //将XML转为array
        $array_data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $array_data;
    }
    /**
     *  作用：array转xml
     */
    public static function arrayToXml($arr) {
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
    
    public static function postCurl($url,$xml,$second = 30) {
        //初始化curl
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        //这里设置代理，如果有的话
        //curl_setopt($ch,CURLOPT_PROXY, '8.8.8.8');
        //curl_setopt($ch,CURLOPT_PROXYPORT, 8080);
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,FALSE);
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        //post提交方式
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        //运行curl
        $data = curl_exec($ch);
        curl_close($ch);
        //返回结果
        if($data)
        {
            //curl_close($ch);
            return $data;
        }
        else
        {
            $error = curl_errno($ch);
            echo "curl出错，错误码:$error"."<br>";
            echo "<a href='http://curl.haxx.se/libcurl/c/libcurl-errors.html'>错误原因查询</a></br>";
            curl_close($ch);
            return false;
        }
    }

    /**
     * 判断用户所管理公司
     * @param $id
     * @return mixed|string
     */
    public static function companyIdIsArray($id){
        $sql = '';
        if(is_array($id)){
            if(count($id) > 1){
                $companyId = implode(',', $id);
                $sql = "id in ($companyId)";
            }else{
                $companyId = $id[0];
                if($companyId != 1)
                    $sql = "id = $companyId";
                else
                    $sql = 1;
            }
        }else{
            if($id != 0){
                $companyId = $id;
                $sql = "id = $companyId";
            }else{
                $sql = 1;
            }
        }
        return $sql;
    }

    public static function my_sort($arrays,$sort_key,$sort_order=SORT_ASC,$sort_type=SORT_NUMERIC ){
        if(is_array($arrays)){
            foreach ($arrays as $array){
                if(is_array($array)){
                    $key_arrays[] = $array[$sort_key];
                }else{
                    return false;
                }
            }
        }else{
            return false;
        }
        array_multisort($key_arrays,$sort_order,$sort_type,$arrays);
        return $arrays;
    }
}

function dump($val)
{
    $out  = "<pre style=\"background: #000; color: #ccc; font: 11px 'courier new'; text-align: left; width: 100%; padding: 5px\">\n";
    $out .= print_r($val, true);
    $out .= "</pre>\n";
    
    echo $out;
}



