<?php
/**
 * 活动商品自动上下架 -- 定时任务
 * Created by PhpStorm.
 * User: nsinm
 * Date: 2017/3/24
 * Time: 14:00
 */

//数据库对象实例化
$mysqli = new mysqli('rm-2zen57s89f8zy5k1d.mysql.rds.aliyuncs.com', 'bingtuanego', 'suxin8DB', 'xingshui');
//当前时间
$time = time();
//sql
$sql = "SELECT id, hour_start, hour_end FROM activity WHERE is_del = 0 AND putaway = 1 AND end_time >= $time";
//获取结果集
$result = $mysqli->query($sql);
//遍历结果集
if(!($result === false)){
    while($row = $result->fetch_assoc()){
        $hour = date('H');
        if($hour == '00'){
            $hour = '24';
        }
        //上架
        if($hour == $row['hour_start']){
            $updateSql = "UPDATE activity SET is_show = 1 WHERE id = {$row['id']}";
        }

        //下架
        if($hour == $row['hour_end']){
            $updateSql = "UPDATE activity SET is_show = 0 WHERE id = {$row['id']}";
        }
        $mysqli->query($updateSql);
        echo 'ID为'.$row['id']."活动自动上下架执行成功!\n";
    }
}