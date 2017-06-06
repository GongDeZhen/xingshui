<?php
/**
 * 消息模型
 *  @luodao
 */
class Service_Message extends Service
{
    /**
     * 未读消息总数
     */
    public function getNoReadNumber($userID, $receiver)
    {
        $user = Yaf_Registry::get('user');
        $sql = "SELECT 
          COUNT(*) 
        FROM
          (SELECT 
              m.`id`,
              mo.`user_id`,
              IFNULL(mo.`status`, 0) `status`,
              IFNULL(mo.`is_del`, 0) `is_del` 
            FROM
              `message` AS m 
              INNER JOIN `message_order` AS mo 
                ON m.`id` = mo.`message_id` 
            WHERE mo.user_id = $userID 
              AND mo.`status` = 0 
              AND mo.`is_del` = 0 
            ) AS message";
        return $this->db->fetchOne($sql);
    }
    
    /**
     * 消息列表
     * 0全部 1.订单 2.账单 3.官方
     */
    public function getMessageList($data)
    {
        $where = '';
        switch ($data['type']) {
            case 0:
//                 $where .= " AND m.type = 3 OR m.type = 0 OR (m.type = 4 OR m.type = 1 OR m.type = 2) ";
                break;
            case 1:
                $where .= " AND m.type = 1 ";
                break;
            case 2:
                $where .= " AND m.type = 2 ";
                break;
            case 3:
                $where .= "  AND (m.type = 3 OR m.type = 4)";
                break;
            default:
                return array();
                break;
        }
        
        if ($data['id'] > 0) {
            $where .= " AND m.`id` < {$data['id']} ";
        }
        $sql = "select * from (SELECT 
            m.*,
            IFNULL(mo.`status`, 0) `status`,
            IFNULL(mo.`is_del`, 0) `is_del`,
            mo.`id` associated_id,
            mo.`message_id`,
            mo.`user_id`,
            CASE
              WHEN m.`type`=4 THEN mo.`coupons` 
              ELSE mo.parent_id 
            END AS parent_id
          FROM
            `message` AS m 
            LEFT JOIN `message_order` AS mo 
              ON m.`id` = mo.`message_id` 
          WHERE mo.user_id={$data['user_id']} AND mo.`is_del`=0 $where ORDER BY m.`id` DESC) as message 
        Limit {$data['page']}, {$data['limit']}";
        $messageList  = $this->db->fetchAll($sql);
        foreach ($messageList as $k => $v) {
            if ($v['type'] == 4) {
                if ($v['parent_id'] != '') {
                    $couponsID = explode(',', $v['parent_id']);
                    foreach ($couponsID as $key) {
                        $coupons = Service::getInstance('coupons')->getCouponInfo($key);
                        if ($coupons) {
                            $messageList[$k]['couponsList'][] = $coupons;
                        }
                    }
                }
            }
        }
        if(empty($messageList)) {
            return array();
        } else {
            return $messageList;
        }
    }

    /**
     * 得到已经删除官方消息的列表
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    private function _getSysDelAssociatedMessageList($data)
    {
        $where    = HjzSqlHelper::combineWhereByData(array(
            'receiver'  => $data['receiver'],
            'type'      => 1,
            'is_del'    => 1,
            'user_id'   => $data['user_id']
        ));
        $associatedSql      = HjzSqlHelper::getQuerySql(array('table' => 'tb_message_associated', 'where' => $where));
        
        return $this->db->fetchAll($associatedSql);
    }

    /**
     * 得到官方关联的消息列表
     * @param  [type] $list 官方消息
     * @return [type]       [description]
     */
    private function _getSysAssociatedMessageList($list)
    {
        if(!$list) {
            return array();
        }
        $sqlCfg = array('table' => 'tb_message_associated', 'where' => '`type` = 1 AND ' . HjzSqlHelper::whereInByListMap('message_id', 'id', $list));
        $sql    = HjzSqlHelper::getQuerySql($sqlCfg);
        $messageAssociatedList = $this->db->fetchAll($sql);
        $messageAssociatedList = HjzUtil::turnItemValueAsKey($messageAssociatedList, 'message_id');
        
        return $messageAssociatedList;        
    }

    /**
     * 标记为已读的消息列表
     * @return [type] [description]
     */
    public function signReadStatusList($data)
    {
        if(!$data['messageIDList']) {
            return false;
        }
        try {
            $messageIDList = json_decode($data['messageIDList'],true);
            foreach ($messageIDList as $k => $v) {
                $time = time();
                if ($v['type'] == 3) {
                    if ($this->db->fetchOne("SELECT COUNT(*) FROM message_order WHERE message_id={$v['id']} and user_id={$data['user_id']} and `status`=1") == 0) {
                        $this->db->insert('message_order', array('message_id'=>$v['id'], 'user_id'=> $data['user_id'], 'status' => 1, 'create_time'=>$time));
                    } else {
                        $this->db->update('message_order', array('status' => 1), "id={$v['associated_id']}");
                    }
                } else {
                    $this->db->update('message_order', array('status' => 1), "id={$v['associated_id']}");
                }
            }
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * 删除消息列表
     * @param  [type] $data 数据
     * @return [type]       [description]
     */
    public function delMessage($data)
    {
        if(!$data['messageIDList']) {
            return false;
        }
       
        try {
            $messageIDList = json_decode($data['messageIDList'],true);
            foreach ($messageIDList as $k => $v) {
                $time = time();
                if ($v['type'] == 3) {
                    $id = $this->db->fetchOne("SELECT id FROM message_order WHERE message_id={$v['id']} and user_id={$data['user_id']}" );
                    if (!$id) {
                        $this->db->insert('message_order', array('message_id'=>$v['id'], 'user_id'=> $data['user_id'], 'is_del' => 1, 'create_time'=>$time));
                    } else {
                        $this->db->update('message_order', array('is_del'=>1),"id={$v['associated_id']}");
                    }
                } else {
                    $this->db->delete('message_order', "id={$v['associated_id']}");
                }
            }
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * 添加消息
     * @param [type] $data [description]
     */
    public function addMessage($orderID='',$messateType='')
    {
        $push = new Push();
        $appkey = Yaf_Application::app()->getConfig()->get('umeng')->get('appkey');
        $appMasterSecret = Yaf_Application::app()->getConfig()->get('umeng')->get('appMasterSecret');
        
        if ($messateType == 'editPersonalPhone') {
            $data['title'] = '官方消息';
            $data['content'] = '密码已修改成功，为确保您账号安全，请勿向其他人透露！';
            $data['type'] = 3;
            $data['create_time'] = time();
            $data['receiver'] = $this->db->fetchOne("SELECT user_type FROM users where id={$orderID}");
            // 添加发送消息
            $this->db->insert('message', $data);
            $id = $this->db->lastInsertId();
            if ($id) {
                $associatedData = array('message_id'=>$id,'user_id'=>$orderID,'parent_id'=>0,'create_time'=>time());
                $this->_addMessageAssociated($associatedData);
                $token = $this->db->fetchOne("select device_token from users where id={$orderID}");
                if ($token) {
                    $push->sendAndroidUnicast($appkey, $appMasterSecret, $token, '官方消息', $data['title'], $data['content']);
                }
            }
            return true;
        }
        
        if ($messateType == 'editPersonalPhone') {
            $user = $this->db->fetchRow("SELECT account,user_type FROM users where id={$orderID}");
            $data['title'] = '官方消息';
            $data['content'] = '您于'.date('Y年m月d H:i:s').'将手机号成功变更为'.$user['account'];
            $data['type'] = 3;
            $data['create_time'] = time();
            $data['receiver'] = $user['user_type'];
            // 添加发送消息
            $this->db->insert('message', $data);
            $id = $this->db->lastInsertId();
            if ($id) {
                $associatedData = array('message_id'=>$id,'user_id'=>$orderID,'parent_id'=>0,'create_time'=>time());
                $this->_addMessageAssociated($associatedData);
                $token = $this->db->fetchOne("select device_token from users where id={$orderID}");
                if ($token) {
                    $push->sendAndroidUnicast($appkey, $appMasterSecret, $token, '官方消息', $data['title'], $data['content']);
                }
            }
            return true;
        }
        
        if ($messateType == 'editPersonalName') {
            $user = $this->db->fetchRow("SELECT userName,user_type FROM users where id={$orderID}");
            $data['title'] = '官方消息';
            $data['content'] = '您于'.date('Y年m月d H:i:s').'将联系人姓名成功变更为'.$user['userName'];
            $data['type'] = 3;
            $data['create_time'] = time();
            $data['receiver'] = $user['user_type'];
            // 添加发送消息
            $this->db->insert('message', $data);
            $id = $this->db->lastInsertId();
            if ($id) {
                $associatedData = array('message_id'=>$id,'user_id'=>$orderID,'parent_id'=>0,'create_time'=>time());
                $this->_addMessageAssociated($associatedData);
                $token = $this->db->fetchOne("select device_token from users where id={$orderID}");
                if ($token) {
                    $push->sendAndroidUnicast($appkey, $appMasterSecret, $token, '官方消息', $data['title'], $data['content']);
                }
            }
            return true;
        }
        
        if ($messateType == 'cancelStock') {
            $data['title'] = '官方消息';
            $data['content'] = '欢迎您使用冰团e购，我们致力于打造最便捷、最安心的冷食采购平台，快来体验吧，如您在使用过程中有任何问题或建议欢迎联系我们400-629-6379';
            $data['type'] = 3;
            $data['create_time'] = time();
            $data['receiver'] = $this->db->fetchOne("SELECT user_type FROM users where id={$orderID}");
            // 添加发送消息
            $this->db->insert('message', $data);
            $id = $this->db->lastInsertId();
            if ($id) {
                $associatedData = array('message_id'=>$id,'user_id'=>$orderID,'parent_id'=>0,'create_time'=>time());
                $this->_addMessageAssociated($associatedData);
                $token = $this->db->fetchOne("select device_token from users where id={$orderID}");
                if ($token) {
                    $push->sendAndroidUnicast($appkey, $appMasterSecret, $token, '官方消息', $data['title'], $data['content']);
                }
            }
            return true;
        }
        
        $orderInfo = $this->db->fetchRow("select id,order_number,user_id,tel,order_status,shipping_status,shipping_type,pay_status,pay_id,sign_status,pay_money,pay_time,add_time,seller_tel,refund_time from order_info where id=$orderID"); // 订单信息
        $userType = $this->db->fetchOne("SELECT user_type FROM users where id={$orderInfo['user_id']}");
        
        $status = Util::getOrderInfoStatus($orderInfo['order_status'],$orderInfo['shipping_status'],$orderInfo['shipping_type'],$orderInfo['pay_status'],$orderInfo['sign_status'],$orderInfo['pay_id']);
        $time = time();
        $data = array(
            'title'     => '订单消息',
            'type' => 1,
            'receiver' => $userType,
            'create_time' => $time
        );
        
        if ($userType == 1) {
            // 未付款
            if ($messateType == 'newOrder' && $orderInfo['pay_status'] == 1) {
                $data['content'] = '尊敬的用户，您在冰团e购已成功提交在线支付订单，请在1小时内完成支付，超时未支付订单将自动取消，活动订单将取消参与资格。客服热线：400-629-6379';
                if ($time-3600 > $orderInfo['add_time']) { // 过时取消订单
                    $this->db->update('order_info', array('order_status'=>2), 'id='.$orderID);
                    $data['content'] = '您的订单'.$orderInfo['order_number'].'超时未支付订单,已于'.date('Y-m-d H:i:s', $orderInfo['add_time']+3600).'自动取消';
                }
                // 添加发送消息
                $this->db->insert('message', $data);
                $id = $this->db->lastInsertId();
                if ($id) {
                    $associatedData = array('message_id'=>$id,'user_id'=>$orderInfo['user_id'],'parent_id'=>$orderInfo['id'],'create_time'=>time());
                    $this->_addMessageAssociated($associatedData);
                    $token = $this->db->fetchOne("select device_token from users where id={$orderInfo['user_id']}");
                    if ($token) {
                        $push->sendAndroidUnicast($appkey, $appMasterSecret, $token, '订单消息', $data['title'], $data['content']);
                    }
                }
                return true;
            }
            // 待发货
            if ($messateType == 'newOrder' && $orderInfo['pay_status'] == 2) {
                $data['content'] = '恭喜您下单成功啦！下单时间：'.date('Y-m-d H:i:s', $orderInfo['add_time']).', 查看该订单';
                // 添加发送消息
                $this->db->insert('message', $data);
                $id = $this->db->lastInsertId();
                if ($id) {
                    $associatedData = array('message_id'=>$id,'user_id'=>$orderInfo['user_id'],'parent_id'=>$orderInfo['id'],'create_time'=>time());
                    $this->_addMessageAssociated($associatedData);
                    $token = $this->db->fetchOne("select device_token from users where id={$orderInfo['user_id']}");
                    if ($token) {
                        $push->sendAndroidUnicast($appkey, $appMasterSecret, $token, '订单消息', $data['title'], $data['content']);
                    }
                }
                return true;
            }
            // 待收货
            if ($messateType == 'delivery' && $orderInfo['order_status'] == 3 && $orderInfo['shipping_status'] == 2 && $orderInfo['pay_status'] == 2) {
                $data['content'] = '您的订单'.$orderInfo['order_number'].'已发货。配送员电话：'.$orderInfo['seller_tel'].'。客服电话：400-629-6379, 点击查看详情';
                // 添加发送消息
                $this->db->insert('message', $data);
                $id = $this->db->lastInsertId();
                if ($id) {
                    $associatedData = array('message_id'=>$id,'user_id'=>$orderInfo['user_id'],'parent_id'=>$orderInfo['id'],'create_time'=>time());
                    $this->_addMessageAssociated($associatedData);
                    $token = $this->db->fetchOne("select device_token from users where id={$orderInfo['user_id']}");
                    if ($token) {
                        $push->sendAndroidUnicast($appkey, $appMasterSecret, $token, '订单消息', $data['title'], $data['content']);
                    }
                }
                return true;
            }
            // 已签收
            if ($messateType == 'receiving' && $orderInfo['order_status'] == 3 && $orderInfo['shipping_status'] == 2 && $orderInfo['sign_status'] == 2 && $orderInfo['pay_status'] == 2) {
                $data['content'] = '您的订单'.$orderInfo['order_number'].'已于'.date('Y-m-d H:i:s', $time).'签收, 查看该订单';
                // 添加发送消息
                $this->db->insert('message', $data);
                $id = $this->db->lastInsertId();
                if ($id) {
                    $associatedData = array('message_id'=>$id,'user_id'=>$orderInfo['user_id'],'parent_id'=>$orderInfo['id'],'create_time'=>time());
                    $this->_addMessageAssociated($associatedData);
                    $token = $this->db->fetchOne("select device_token from users where id={$orderInfo['user_id']}");
                    if ($token) {
                        $push->sendAndroidUnicast($appkey, $appMasterSecret, $token, '订单消息', $data['title'], $data['content']);
                    }
                }
                return true;
            }
        }
        if ($userType == 2) {
            // 待发货
            if ($messateType == 'newOrder' && $orderInfo['pay_status'] == 2) {
                $data['content'] = '恭喜您下单成功啦！下单时间：'.date('Y-m-d H:i:s', $orderInfo['add_time']).', 查看该订单';
                // 添加发送消息
                $this->db->insert('message', $data);
                $id = $this->db->lastInsertId();
                if ($id) {
                    $associatedData = array('message_id'=>$id,'user_id'=>$orderInfo['user_id'],'parent_id'=>$orderInfo['id'],'create_time'=>time());
                    $this->_addMessageAssociated($associatedData);
                    $token = $this->db->fetchOne("select device_token from users where id={$orderInfo['user_id']}");
                    if ($token) {
                        $push->sendAndroidUnicast($appkey, $appMasterSecret, $token, '订单消息', $data['title'], $data['content']);
                    }
                }
                return true;
            }
            // 待收货
            if ($messateType == 'delivery' && $orderInfo['order_status'] == 3 && $orderInfo['shipping_status'] == 2 && $orderInfo['pay_status'] == 2) {
                $data['content'] = '您的订单'.$orderInfo['order_number'].'已发货。配送员电话：'.$orderInfo['seller_tel'].'。客服电话：400-629-6379, 点击查看详情';
                // 添加发送消息
                $this->db->insert('message', $data);
                $id = $this->db->lastInsertId();
                if ($id) {
                    $associatedData = array('message_id'=>$id,'user_id'=>$orderInfo['user_id'],'parent_id'=>$orderInfo['id'],'create_time'=>time());
                    $this->_addMessageAssociated($associatedData);
                    $token = $this->db->fetchOne("select device_token from users where id={$orderInfo['user_id']}");
                    if ($token) {
                        $push->sendAndroidUnicast($appkey, $appMasterSecret, $token, '订单消息', $data['title'], $data['content']);
                    }
                }
                return true;
            }
            // 已签收
            if ($messateType == 'receiving' && $orderInfo['order_status'] == 3 && $orderInfo['shipping_status'] == 2 && $orderInfo['sign_status'] == 2 && $orderInfo['pay_status'] == 2) {
                $data['content'] = '您的订单'.$orderInfo['order_number'].'已于'.date('Y-m-d H:i:s', $time).'签收, 查看该订单';
                if ($time-172800 > $orderInfo['pay_time']) { // 过时自动签收
                    $this->db->update('order_info', array('order_status'=>2), 'id='.$orderID);
                    $data['content'] = '您的订单'.$orderInfo['order_number'].'超时未签收,已于'.date('Y-m-d H:i:s', $orderInfo['pay_time']+172800).'自动签收';
                }
                // 添加发送消息
                $this->db->insert('message', $data);
                $id = $this->db->lastInsertId();
                if ($id) {
                    $associatedData = array('message_id'=>$id,'user_id'=>$orderInfo['user_id'],'parent_id'=>$orderInfo['id'],'create_time'=>time());
                    $this->_addMessageAssociated($associatedData);
                    $token = $this->db->fetchOne("select device_token from users where id={$orderInfo['user_id']}");
                    if ($token) {
                        $push->sendAndroidUnicast($appkey, $appMasterSecret, $token, '订单消息', $data['title'], $data['content']);
                    }
                }
                return true;
            }
        }
        // 取消
        if ($messateType == 'cancel') {
            $data['content'] = '您的订单'.$orderInfo['order_number'].'已于'.date('Y-m-d H:i:s', time()).'被您取消, 查看退款进度';
            // 添加发送消息
            $this->db->insert('message', $data);
            $id = $this->db->lastInsertId();
            if ($id) {
                $associatedData = array('message_id'=>$id,'user_id'=>$orderInfo['user_id'],'parent_id'=>$orderInfo['id'],'create_time'=>time());
                $this->_addMessageAssociated($associatedData);
                $token = $this->db->fetchOne("select device_token from users where id={$orderInfo['user_id']}");
                if ($token) {
                    $push->sendAndroidUnicast($appkey, $appMasterSecret, $token, '订单消息', $data['title'], $data['content']);
                }
            }
            return true;
        }
        // 退款
        if ($messateType == 'refund') {
            $data['content'] = '您的订单'.$orderInfo['order_number'].'已退款成功，退款金额'.$orderInfo['pay_money'].'元，退款时间：'.date('Y-m-d H:i:s', $orderInfo['refund_time']).', 查看退款记录';
            // 添加发送消息
            $this->db->insert('message', $data);
            $id = $this->db->lastInsertId();
            if ($id) {
                $associatedData = array('message_id'=>$id,'user_id'=>$orderInfo['user_id'],'parent_id'=>$orderInfo['id'],'create_time'=>time());
                $this->_addMessageAssociated($associatedData);
                $token = $this->db->fetchOne("select device_token from users where id={$orderInfo['user_id']}");
                if ($token) {
                    $push->sendAndroidUnicast($appkey, $appMasterSecret, $token, '订单消息', $data['title'], $data['content']);
                }
            }
            return true;
        }
    }

    /**
     * 添加消息关联数据
     * @param [type] $data [description]
     */
    public function _addMessageAssociated($data)
    {
        $data['status']         = 0;
        $data['is_del']         = 0;
        $data['create_time']    = time();

        if(!$this->db->insert('message_order', $data)) {
            $this->error    = '插入关联消息数据失败';
            return false;
        } else {
            return true;
        }
    }
}