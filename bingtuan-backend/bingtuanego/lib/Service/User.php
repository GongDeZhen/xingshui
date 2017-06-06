<?php

class Service_User extends Service
{
    private $error;
    public function getError(){
        return $this->error;
    }
    public function doLogin( $account , $pwd , $token = '')
    {
        $sql = "SELECT * FROM users WHERE `account` = '".$account."'" ;

        $user = $this->db->fetchRow( $sql );

        if ( !$user ){
            return '用户名未注册' ;
        }
        Yaf_Registry::set('id', $user['id'] );
    
        if ( md5( $pwd ) != $user['password'] ){
            return '密码不正确';
        }
        if ($token) {
            $this->db->update('users', array('device_token'=>$token), "account=$account");
        }
        $address = $this->db->fetchRow( "SELECT id,user_name,phone,province_id,city_id,area_id,address from user_address where user_id={$user['id']} AND state=1 order by state desc" );
        if ($address) {
            $province_name = Service::getInstance('region')->provinceInfo($address['province_id']);
            $city_name = Service::getInstance('region')->provinceInfo($address['city_id']);
            $area_name = Service::getInstance('region')->provinceInfo($address['area_id']);
        }
        $user['add_id'] = $address['id']?$address['id']:null;
        $user['tel'] = '400-661-0288';
        $user['add_usern_ame'] = $address['user_name']?$address['user_name']:null;
        $user['add_phone'] = $address['phone']?$address['phone']:null;
        $user['add_province_id'] = $address['province_id']?$address['province_id']:null;
        $user['add_city_id'] = $address['city_id']?$address['city_id']:null;
        $user['add_area_id'] = $address['area_id']?$address['area_id']:null;
        $user['add_address'] = $address['address']?$province_name.$city_name.$area_name.$address['address']:null;
        $warehouse = Service::getInstance('warehouse')->warehouseInfo($user['cid']);
        $user['company_id'] = $warehouse['company_id'];
        if ( isset( $user['id'] ) ) $this->_setCookie( $user['id'] , $user['password'], true  );
        //if ( $user['Avatar'] ) $user['Avatar'] = $this->getAvata( $user['Avatar'] );
//         $cookieTime = time() + 86400 * 30;
//         setcookie('uid', $user['id'], $cookieTime);
//         setcookie('user', $user, $cookieTime);
        $this->setUser( $user );
        //return $user;
        return true;
    }
    
    public function sendsmsCode( $mobile,$con='注册')
    {
        if ( !Util::isValidMobile( $mobile ) ) return false;
    
        $num = rand( 1000 , 9999 );
    
        $res = array(
            'phone' => $mobile,
            'mss' => $num,
            'updatetime' => time()
        );
        if ( ! $this->db->insert('apimss', $res) ) return false;
        Util::sendsms( $mobile , "{$con}-验证码:$num");
//         $smsArr = json_decode($smsjson,true);
//         print_r($smsArr);die;
//         #TODO 短信接口未定义
//         if ( $smsArr['code'] == "200" )
//         {
//             return true;
//         }
    
        return true;
    }
    
    /**
     * 验证短信是否有效
     */
    public function checkCode($phone, $code)
    {
        $data = $this->db->fetchRow("select mss,phone from apimss where phone = {$phone} order by id desc limit 1");
        if ($code) {
            if ($code == $data['mss'] && $phone == $data['phone']) {
                return true;
            }
        }
        return false;
    }

    private function _setCookie($uid, $pwd, $keep = true)
    {
        $uid = $this->_packCookie($uid);

        $key = Yaf_Application::app()->getConfig()->get('cookie')->get('key');
        $pwd = $this->_packCookie( md5( $key . $pwd ) );

        $expired = $keep ? time() + 86400 * 30 : 0;
        setcookie('pwd', $pwd, $expired, '/');
        setcookie('uid', $uid, $expired, '/');
    }

    private function _packCookie($uid)
    {
        $key = Yaf_Application::app()->getConfig()->get('cookie')->get('key');
        return Util::strcode($uid, $key, 'encode');
    }

    public function getAccount(  )
    {
        if ( ! (bool)Yaf_Registry::get("isLogin") )
        {
            return false;
        }
    }

    //通过电话获获取用户id
    public function getUidBymobile( $mobile ){
        $uid = $this->db->fetchOne("select id from users where account = '".$mobile."'");
        return $uid;
    }
    
    //通过账号获得用户信息
    public function getUserByaccount( $account )
    {
           $sql = "SELECT * FROM users WHERE account = '" . $account . "'";
        $user = $this->db->fetchRow( $sql );
        return $user;
    }

    //注册
    public function register( $user , $Devtoken = "" , $platform = "iOS" )
    {
        $pwd = $user['password'];
        $user['`password`'] = md5( $user['password'] );
        unset($user['password']);
        $uid = '';
        if (isset($user['account'])) {
            $uid = $this->getUidBymobile($user['account']);
        }
        if (!empty($uid)) {
            return false;
        }
        if ( ! $this->db->insert('users', $user) ) return false;
        //注册成功!
//         $this->doLogin( $user['account'] , $pwd );
        return $this->db->lastInsertId();
        
    }

    //修改密码
    public function updatePassword( $phone , $pwd )
    {
        $uid = $this->getUidBymobile($phone);
        $profile['password'] = md5( $pwd );
        $res = $this->db->update('users', $profile, ' id =' . $uid );
//         if($res) $this->_setCookie($uid,md5($pwd),true);
        return $res;
    }

    
    //更新用户头像
    public function updateAvatar( $Avata )
    {
        if ( ! (bool)Yaf_Registry::get("isLogin") )
        {
            return false;
        }

        $profile = array();
        if ( $Avata ) $profile['Avatar'] = $Avata;
        return $this->updateProfile( $profile );
    }

    //获取用户头像
    public function getAvata( $hash )
    {
        $dir = Yaf_Application::app()->getConfig()->get('avatar')->get('dir');
    
        $url = Yaf_Application::app()->getConfig()->get('home')->get('url');
        $avatar_url = $url . "/default-avatar.png";

        if ( empty($hash) ) return $avatar_url;

        if ( file_exists( Util::getDir( $dir , $hash ).$hash."_avatar.jpg" ) )
        {
            $url = Yaf_Application::app()->getConfig()->get('avatar')->get('url');
            $avatar_url = $url . Util::getPath( $hash ) . $hash . "_avatar.jpg";
        }
        
        return $avatar_url;
    }

    //通过邮箱获取用户id
    public function getUidByemail( $email )
    {
        $uid = $this->db->fetchOne("select Id from Users where Email= '" . $email . "'"); 
        return $uid;
    }

    //通过邮箱查找用户
    public function getUserByemail( $email )
    {
        $user = $this->db->fetchRow("select * from Users where Email= '" . $email . "'"); 
        return $user;        
    }

    //获得用户信息
    public function getUserById( $uid = 0 )
    {
        if ( ! (bool)Yaf_Registry::get("isLogin") )
        {
            return false;
        }

        if ( $uid == 0 ) $uid = Yaf_Registry::get( 'uid' );

        if ( (int)$uid == 0 ) return array();

        if ( $uid !=  Yaf_Registry::get( 'uid' ) )
        {
            $this->visitorGetUserById( $uid );
        }

        $sql = 'SELECT * FROM users WHERE id = ' . (int) $uid ;

        $user = $this->db->fetchRow( $sql );

        if ( isset( $user['id'] ) ) 
        {
            if ( $user['avatar'] ) $user['avatar'] = $this->getAvata( $user['avatar'] );
        }

        return $user;
    }

    public function getUser($uid){
        $sql = 'SELECT * FROM users WHERE id = ' . (int) $uid ;

        return $this->db->fetchRow( $sql );
    }

    
    //更新用户信息
    public function updateProfile( $profile = array()  )
    {

        $key = Yaf_Application::app()->getConfig()->get('cookie')->get('key');
        $uid = Yaf_Registry::get('uid');

        $fields = array( 'userName', 'telphone' , 'password'  );

        foreach ( $profile as $field ) 
        {
            if ( !in_array( $field , $fields ) ) unset( $profile[$field] );
        }

        if ( isset( $profile['password'] ) ) $profile['password'] = md5( $profile['password'] );
        
        return $this->db->update('users', $profile, ' id =' . $uid );
    }
    //更新用户信息
    public function updateExam( $profile, $uid  )
    {
        return $this->db->update('users', $profile, ' id =' . $uid );
    }


    //验证用户信息
    public function checkUser()
    {
        $key = Yaf_Application::app()->getConfig()->get('cookie')->get('key');
        $uid = isset($_POST['uid'])?intval($_POST['uid']):Util::strcode($_COOKIE['uid'], $key, 'decode');
        
//         var_dump($uid);
//         exit;
//         $uid = $_COOKIE['uid'];
        if ( !intval( $uid ) ) return false;
        //$pwd = Util::strcode($_COOKIE['pwd'], $key, 'decode');
        $account = $this->db->fetchRow("SELECT * FROM users WHERE id = ".intval($uid));
        if ( !$account ) {
            return false;
        }
        $address = $this->db->fetchRow( "SELECT id,user_name,phone,province_id,city_id,area_id,address from user_address where user_id={$account['id']} AND state=1 order by state desc" );
        if ($address) {
            $province_name = Service::getInstance('region')->provinceInfo($address['province_id']);
            $city_name = Service::getInstance('region')->provinceInfo($address['city_id']);
            $area_name = Service::getInstance('region')->provinceInfo($address['area_id']);
        }
        $account['add_id'] = $address['id']?$address['id']:null;
        $account['add_usern_ame'] = $address['user_name']?$address['user_name']:null;
        $account['add_phone'] = $address['phone']?$address['phone']:null;
        $account['add_province_id'] = $address['province_id']?$address['province_id']:null;
        $account['add_city_id'] = $address['city_id']?$address['city_id']:null;
        $account['add_area_id'] = $address['area_id']?$address['area_id']:null;
        $account['add_address'] = $address['address']?$province_name.$city_name.$area_name.$address['address']:null;
        $warehouse = Service::getInstance('warehouse')->warehouseInfo($account['cid']);
        $account['company_id'] = $warehouse['company_id'];
        
        $key = Yaf_Application::app()->getConfig()->get('cookie')->get('key');

        /* if ( $pwd !== md5( $key  . $account['password']) ) {
            return false;
        } */

        unset( $account['password'] );

        $this->setUser( $account );

        return true;
    }

    //设置注册信息
    private function setUser( $user )
    {
        
        $u['id'] = $user['id'];
        $u['name'] = $user['userName'];
      
        Yaf_Registry::set('uid', $user['id'] );
        Yaf_Registry::set('user', $user );
        Yaf_Registry::set('PublicUser', $u );
        Yaf_Registry::set('auditType', $user['auditType']);
    }

    //添加用户
    public function add( $user  )
    {
        $user['pwd'] = md5($user['pwd']);
        if( $this->db->insert('users',$user) ){
            return $this->db->lastInsertId();
        }
        $this->error = '添加失败';
        return false;
    }
    
    //通过ID获得用户信息
    public function getUserInfoById($id) {
        $sql = "SELECT * FROM users WHERE id=".$id;
        $user = $this->db->fetchRow($sql);
        if ( isset( $user['id'] ) )
        {
            $user['avatar'] = $this->getAvata( $user['avatar'] );
        }
        return $user;
    }

    /**
     * 获取城市信息
     * @param $cid
     * @return mixed
     */
    public function getCityInfoById($cid){
        if($cid){
            $sql = "SELECT cy.`description`, c.contact 
                    FROM warehouse w 
                    JOIN company c 
                    ON w.company_id = c.id 
                    JOIN city cy 
                    ON c.city_id = cy.id
                    WHERE w.id = $cid";
            $city = $this->db->fetchRow($sql);
            return $city;
        }else{
            return '';
        }

    }
    
    public function getUserIdNameByAccount($account) {
        return $this->db->fetchRow('select id,name from users where account='.$account);
    }
    
    //编辑用户
    public function edit($info,$uid) {
        return $this->db->update('users', $info,' id='.$uid);
    }
    
    //删除用户
    public function del($id){
        return $this->db->update('users',array('status'=>1),' id='.$id);
    }
    
    public function updateHead($hash,$uid) {
        return $this->db->update('users', array('avata'=>$hash),' id='.$uid);
    }
    
    /**
     * 用户地址列表
     * @param unknown $uid
     * @return multitype:
     */
    public function getAddressList($uid)
    {
        $sql="SELECT 
          tua.id,
          tua.state,
          tua.`user_name`,
          tua.`phone`,
          tp.`name` tp_name,
          tc.`name` tc_name,
          ta.`name` ta_name,
          tua.address,
          tp.`id` tp_id,
          tc.`id` tc_id,
          ta.`id` ta_id
        FROM
          users AS u 
          LEFT JOIN `user_address` AS tua 
            ON tua.`user_id` = u.`id` 
            AND tua.`state` = 1 
          LEFT JOIN `city` AS tp 
            ON tua.`province_id` = tp.`id` 
          LEFT JOIN `city` AS tc 
            ON tua.`city_id` = tc.`id` 
            AND tc.`parent_id` = tua.`province_id` 
          LEFT JOIN `city` AS ta 
            ON tua.`area_id` = ta.`id` 
            AND ta.`parent_id` = tc.`id` 
        WHERE tua.user_id = $uid ";

        return $this->db->fetchAll($sql);
    }
    public function FixPassword($pwd) {
      $sql = "UPDATE users SET password = md5(visible) WHERE password = '$pwd'";
      return $this->db->query($sql);
    }
    public function addLoginLog($phone,$reIP){
        $data['user_id'] = $this->getUidBymobile($phone);
        $data['created_at'] = time();
        $data['updated_at'] = time();
        $data['ip'] = $reIP;
        $this->db->insert('login_log', $data);
        return $this->db->lastInsertId();
    }

    /**
     * 获取上次登录时间
     * @param $uid
     * @return mixed
     */
    public function getLoginTime($uid){
        $sql = "SELECT id, MAX(updated_at) time
                FROM login_log
                WHERE user_id = $uid";
        return $this->db->fetchRow($sql);
    }

    /**
     * 更新用户登录时间
     * @param $id
     * @param $ip
     * @return mixed
     */
    public function saveLoginTime($id, $ip){
        return $this->db->update('login_log', array('updated_at' => time(), 'ip' => $ip), "id=$id");
    }

    public function getCompanyInfo($uid){
        $sql="SELECT 
            p.`name` province,
            c.`name` city,
            comp.`companyname` warehouse
        FROM
          users AS u 
          LEFT JOIN `warehouse` AS w
            ON u.`cid` = w.`id`
          LEFT JOIN `company` AS comp
            ON w.`company_id` = comp.`id`
          LEFT JOIN `city` AS p 
            ON comp.`province_id` = p.`id` 
          LEFT JOIN `city` AS c 
            ON comp.`city_id` = c.`id` 
        WHERE u.id = $uid 
        limit 1";
        return $this->db->fetchRow($sql);
    }
}