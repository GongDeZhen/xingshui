<?php
/**
 * Created by PhpStorm.
 * User: huangtuo
 * Date: 16/6/20
 * Time: 下午7:45
 */
$dbh = new PDO('mysql:host=localhost;dbname=xingshui', 'xingshui', 'xingshui110');
$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$dbh->exec('set names utf8');
/*添加*/
//$sql = "INSERT INTO `user` SET `login`=:login AND `password`=:password";
/*
$sql = "INSERT INTO `user` (`login` ,`password`)VALUES (:login, :password)";  $stmt = $dbh->prepare($sql);  $stmt->execute(array(':login'=>'kevin2',':password'=>''));
echo $dbh->lastinsertid();

$sql = "UPDATE `user` SET `password`=:password WHERE `user_id`=:userId";
$stmt = $dbh->prepare($sql);
$stmt->execute(array(':userId'=>'7', ':password'=>'4607e782c4d86fd5364d7e4508bb10d9'));
echo $stmt->rowCount();

$sql = "DELETE FROM `user` WHERE `login` LIKE 'kevin_'"; //kevin%
$stmt = $dbh->prepare($sql);
$stmt->execute();
echo $stmt->rowCount();
*/
/*查询*/

$sql = "SELECT * FROM `user` WHERE `login` LIKE :login";

$arr = array();

$arr = getCityInfo($dbh);
//var_dump($arr);
file_put_contents('address',json_encode($arr));
function getCityInfo($dbh)
{

    $a = 0;
    $sql = "SELECT * FROM `city` WHERE `parent_id` = 0 ORDER BY `city`.`id` ASC";
    $stmt = $dbh->prepare($sql);
    $stmt->execute();

    while($rs = $stmt->fetch(PDO::FETCH_ASSOC)){
        $array = array();
        $a++;
        $array['id'] = $rs['id'];
        $array['name'] = $rs['name'];
        $sql1 = "SELECT * FROM `city` WHERE `parent_id` = {$rs['id']} ORDER BY `city`.`id` ASC";
        $stmt1 = $dbh->prepare($sql1);
        $stmt1->execute();
        while($rs1 = $stmt1->fetch(PDO::FETCH_ASSOC)){
            //var_dump($rs1);
            $array1 = array();
            $array1['id'] = $rs1['id'];
            $array1['name'] = $rs1['name'];
            $sql2 = "SELECT * FROM `city` WHERE `parent_id` = {$rs1['id']} ORDER BY `city`.`id` ASC";
            $stmt2 = $dbh->prepare($sql2);
            $stmt2->execute();
            while($rs2 = $stmt2->fetch(PDO::FETCH_ASSOC)){
                $array2 = array();
                $array2['id'] = $rs2['id'];
                $array2['name'] = $rs2['name'];
                $array1['child'][] = $array2;
                unset($array2);
            }
            $array['child'][] = $array1;
            //var_dump($array1);
            unset($array1);
        }
        $arr[] = $array;
        //if($a==2)
          //  return $arr;

    }

    return $arr;
    /*

    while($rs = $this->db->fetchRow($sql)){

        $array['id'] = $rs['id'];
        $array['name'] = $rs['name'];
        $array['child'] = $this->getCityInfo($rs['id'],$arr);
        $arr = $array;

    }
    */
}
?>
