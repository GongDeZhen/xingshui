<?php
class Service_Timing extends Service
{
    /**
     * 推送账单到期消息
     * @param string $type
     */
    public function pushOrderMessage($type='')
    {
        $sql = 'SELECT * FROM `user_account` AS ua WHERE FROM_UNIXTIME(UNIX_TIMESTAMP(NOW()),"%Y%m%d") = FROM_UNIXTIME(ua.`end_time`-259200, "%Y%m%d")';
        $list = $this->db->fetchAll($sql);
        return $list;
    }
    /**
     * 自动取消
     */
    public function cancelOrder()
    {
        $sql = 'SELECT 
          oi.`id`,
          oi.`add_time` 
        FROM
          `order_info` AS oi 
        WHERE oi.`order_status` = 1 
          AND oi.`shipping_status` = 1 
          AND oi.`sign_status` = 1 
          AND oi.`pay_status` = 1 ORDER BY oi.`add_time` DESC';
        $orderList = $this->db->fetchAll($sql);
        $time = time();
        foreach ($orderList as $k => $v) {
            if ($v['add_time'] < $time-172800) {
                if ($this->db->update('order_info', array('order_status'=>2), 'id='.$v['id']) ) {
                    Service::getInstance('order')->rebackGoodsNumber($v['id']); // 把商品退回到库存
                }
            }
        }
        return true;
    }
    
    /**
     * 自动签收
     */
    public function receiveOrder()
    {
        $sql = 'SELECT 
          oi.`id`,
          oi.`shipments_time`,
          oi.`sign_status` 
        FROM
          `order_info` AS oi 
        WHERE oi.`order_status` = 3 
          AND oi.`shipping_status` = 2 
          AND oi.`sign_status` = 1 
          AND oi.`pay_status` = 2 
        ORDER BY oi.`add_time` ';
        $orderList = $this->db->fetchAll($sql);
        $time = time();
        foreach ($orderList as $k => $v) {
            if ($v['shipments_time'] < $time-86400*2) {
                $this->db->update('order_info', array('sign_status'=>2), 'id='.$v['id']);
            }
        }
        return true;
    }
    
}