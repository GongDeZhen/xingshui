<?php

/**
 * 日志管理模型类
 * author: Nsinm
 * package: bingtuanego Admlog.php
 * Date: 2016/11/14
 * Time: 00:05
 */

class Service_Admlog extends Service
{
    /**
     * 获取日志列表
     * @param $index
     * @param $perPage
     * @return mixed
     */
    public function getList($index, $perPage){
        //其实索引
        $index = $index ? : 1;
        //查询数量
        $perPage = $perPage ? : 10;

        //条件预留
        $where = 1;

        $sql = "SELECT log.*, dev.email
                FROM system_log log
                JOIN developers dev
                ON log.uid = dev.id
                WHERE $where ORDER BY log.create_time DESC LIMIT $index, $perPage";
        $list = $this->db->fetchAll($sql);

        return $list;
    }

    /**
     * 获取日志数量
     * @return mixed
     */
    public function getLogCount(){
        $countSql = "SELECT count(id) FROM system_log";
        return $count = $this->db->fetchOne($countSql);
    }
}