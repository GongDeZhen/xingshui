<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/10/25 0025
 * Time: 上午 9:26
 */
class Service_Systemlog extends Service
{
    function addLog($log_arr){
        $return = $this->db->insert('system_log', $log_arr);
        $id = $this->db->lastInsertId();
        return $id;
    }
}