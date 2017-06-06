<?php
/**
 * @author shixiaokai
 * 
 */
header("Content-type: text/html; charset=utf-8");
$pdo = new PDO("mysql:host=localhost;dbname=xingshui","root","");
$pdo->query('set names utf8');
//$result = $pdo -> query("SELECT * from goods GROUP BY goods_name");
$result = $pdo -> query("SELECT * from goods WHERE pid = 0 AND cid = 1");
$query = $result->fetchAll(PDO::FETCH_ASSOC);
// foreach ($query as $k=>$v) {
//     $arr = array_shift($v);
//     $arr = $v;
//     $arr['cid'] = '1';
//     $arr['pid']= 0;
//     $str = implode(',',array_keys($arr));//所有字段
//     $str1 = implode('\',\'',array_values($arr));//字段值
//     $str1 = "'".$str1."'";
//     if($pdo ->exec("insert into goods($str) values($str1)")){
//         $pid =  $pdo -> lastInsertId();
//         if($pid){
//             $sql  = "UPDATE `goods` SET `pid` = :pid WHERE `goods_name` = '{$arr['goods_name']}' AND `id` <> $pid";
//             $stmt = $pdo->prepare($sql);
//             $stmt->execute(array(':pid' => $pid));
//             if(!$stmt->rowCount()){
//                 echo '更新失败';
//                 break;
//             }
//         }else{
//             echo '插入失败1';
//             break;
//         }
//     }else {
//         echo '插入失败';
//         break;
//     }
// }
foreach ($query as $k=>$v) {
    $pid = $v['id'];
        if($pid){
             $sql  = "UPDATE `goods` SET `pid` = :pid WHERE `goods_name`= '{$v['goods_name']}' AND `id` <> $pid";
             $stmt = $pdo->prepare($sql);
             $stmt->execute(array(':pid' => $pid));
             if(!$stmt->rowCount()){
                 echo '更新失败';
                 continue;
             }else{
                 echo '1';
             }
        }
}
$pdo = null;