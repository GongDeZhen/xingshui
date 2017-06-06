<?php
class Service_Admmessage extends Service
{
    /**
     * 系统消息详情
     * @param unknown $id
     * @return string
     */
    public function messageInfo($id)
    {
        return $this->db->fetchRow("SELECT * FROM `message` AS tms WHERE tms.id=$id ");
    }
    
    /**
     * 得到消息列表
     * @param  [type] $data 数据
     * @return [type]       [description]
     */
    public function getPageMessageList($page, $pageSize, $type, $time)
    {
        if ($type) {
            if ($type == 3) {
                $whereSearch[] = " tms.type = $type OR tms.type=4";
            } else {
                $whereSearch[] = " tms.type = $type";
            }
        }
        ($time) ? $whereSearch[] = $time :'';
        
        $where = $whereSearch? ' where '.implode(' and ',$whereSearch) :'';

//         $whsql = $whereStr? '('.implode(' and ',$whereStr).')' :'';
        
//         if (!empty($whsql)) {
//             $or = ' OR ';
//             $where = " where $whsql $or (tms.province_id = 0 AND tms.city_id = 0 AND tms.area_id = 0)";
//         }
        $sql = "SELECT * FROM `message` AS tms $where ORDER BY tms.create_time desc LIMIT $page, $pageSize ";
        return array($this->db->fetchAll($sql), $this->db->fetchOne("SELECT count(*) FROM `message` AS tms $where"));
    }
    /**
     * 添加发送系统消息
     * @param unknown $data
     * @return number
     */
    public function messageAdd($data)
    {
        try {
            $ret = $this->db->insert('message', $data);
            $id = $this->db->lastInsertId();
            if ($id) {
//                 $token = '';
                if ($data['receiver'] == 0) { // 全部
                    $token = $this->getToken($data['receiver']);
                } elseif ($data['receiver'] == 1) { // 普通人
                    $token = $this->getToken($data['receiver']);
                } elseif ($data['receiver'] == 2) { // 大客户
                    $token = $this->getToken($data['receiver']);
                }
//                 print_r($token);die;
//                 $push = new Push();
//                 $appkey = Yaf_Application::app()->getConfig()->get('umeng')->get('appkey');
//                 $appMasterSecret = Yaf_Application::app()->getConfig()->get('umeng')->get('appMasterSecret');
                foreach ($token as $k=>$v) {
//                     $push->sendIOSUnicast($appkey,$appMasterSecret,$v['device_token'],"{$data['title']},{$data['content']}");
                    $this->db->insert('message_order', array('message_id' => $id, 'user_id' => $v['id'], 'create_time'=>time()));
                }
                return $id;
            }
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * 修改系统消息
     * @param unknown $id
     * @param unknown $data
     * @return number
     */
    public function messageUpdate($id,$data)
    {
        $ret = $this->db->update('message', $data, "id=$id");
        return $ret;
    }
    /**
     * 重发消息
     * @param unknown $id
     * @return boolean
     */
    public function resend($id)
    {
        try {
            $data = $this->messageInfo($id);
            $token = '';
            $token = $this->getToken($data['receiver']);
            $this->db->delete('message_order', "message_id=$id");
//             $push = new Push();
            foreach ($token as $k=>$v) {
//                 $appkey = Yaf_Application::app()->getConfig()->get('umeng')->get('appkey');
//                 $appMasterSecret = Yaf_Application::app()->getConfig()->get('umeng')->get('appMasterSecret');
//                 $push->sendIOSUnicast($appkey,$appMasterSecret,$v['device_token'],"{$data['title']},{$data['content']}");
//                 $push->sendAndroidUnicast($appkey,$appMasterSecret,$v['device_token'],"{$data['title']},{$data['content']}");
                $this->db->insert('message_order', array('message_id' => $id, 'user_id' => $v['id'], 'create_time'=>time()));
            }
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * 获取用户的Token值
     * @param unknown $receiver
     * @return multitype:
     */
    public function getToken($receiver)
    {
        $token = '';
        $where == '';
        if ($receiver != 0) {
            $where = "u.`user_type` = $receiver AND ";
        }
        $token = $this->db->fetchAll("SELECT u.id, u.device_token FROM users AS u WHERE $where ");//u.`device_token` IS NOT NULL
        return $token;
    }
    /**
     * 删除系统消息
     * @param unknown $id
     * @return number
     */
    public function delete($id, $type)
    {
        $this->db->delete('message_order',"message_id=$id");
        return $this->db->delete('message',"id=$id AND type=$type");
    }
    
    /**
     * 得到订单消息列表
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    public function getMessageList($page, $pageSize, $name, $time,$receiver,$province_id,$city_id,$area_id)
    {
        $whsql = '';
        $and = '';
        ($name) ? $whereStr[] = " message.real_name LIKE '%$name%' ":'';
        ($time) ? $whereStr[] = $time :'';
        $left = '';
        
        $whereOne = '';
        $and = '';
        if ($province_id != 0 ) {
            $province_id = ($province_id)?$province_id:$userinfo['province_id'];
            $whereOne[] = " tms.province_id=$province_id";
        }
        if ($city_id != 0) {
            $city_id = ($city_id)?$city_id:$userinfo['city_id'];
            $whereOne[] = " tms.city_id=$city_id";
        }
        if ($area_id != 0) {
           $area_id = ($area_id)?$area_id:$userinfo['area_id'];
            $whereOne[] = " tms.area_id=$area_id";
        }
        $whsql = $whereOne? '('.implode(' and ',$whereOne).')' :'';
        if (!empty($whereOne)) {
            $and = ' AND ';
        }
        
        $whsql = $whereStr? implode(' AND ',$whereStr):'';
        if (!empty($whsql)) {
            $where = ' WHERE ';
        }
        $select = '';
        $from = '';
        $whereField = '';
        if ($receiver == 1) {
            $select = ' btu.real_name real_name ,';
            $from = ' buy_bingtuan.tb_users AS btu,';
            $whereField = " tm.user_id = btu.id AND tm.receiver=$receiver ";
        } else {
            $select = ' stu.real_name real_name ,';
            $from = ' sell_bingtuan.tb_users AS stu,';
            $whereField = " tm.user_id = stu.id AND tm.receiver=$receiver ";
        }
        
        $sql = "SELECT 
          * 
        FROM
          (SELECT 
            $select
            tm.*,
            toi.province_id,
            toi.city_id,
            toi.area_id
          FROM
            $from
            `tb_message` AS tm 
            INNER JOIN `tb_order_info` AS toi ON tm.order_id=toi.id
          WHERE  
            $whereField
            $and $whereOne 
          ORDER BY tm.create_time DESC) AS message 
            $where $whsql
        LIMIT $page, $pageSize ";
            
        $count = "SELECT 
          count(*) 
        FROM
          (SELECT 
            $select
            tm.*,
            toi.province_id,
            toi.city_id,
            toi.area_id
          FROM
            $from
            `tb_message` AS tm 
            INNER JOIN `tb_order_info` AS toi ON tm.order_id=toi.id
          WHERE  
            $whereField
            $and $whereOne 
          ORDER BY tm.create_time DESC) AS message 
            $where $whsql";
        return array($this->db->fetchAll($sql), $this->db->fetchOne($count));
    }
    
}