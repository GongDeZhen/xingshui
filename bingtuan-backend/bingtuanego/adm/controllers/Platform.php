<?php

/**
 * 
 * @author 石晓凯
 * 
 * 后台统计 -- 数据插入
 *  */
class PlatformController extends BaseController{
    public function init()
    {
        parent::init();
    }
    /*  品牌统计 */
    public function brandlistAction()
    {
       //导入数据之前先清空表内数据
       date_default_timezone_set('Asia/Shanghai');
       $table_name = 'platform_brand';
       Service::getInstance('Platform')->giblets($table_name);
       $start_time = strtotime('2016-4-20');
       $end_time = strtotime(date('Y-m-d',time()));
       $end_time = $end_time+(3600*24);
       $num = ($end_time-$start_time)/(3600*24);
       $ksi=$start_time-(24*3600);
       $jie=$ksi+24*3600;
       //所有公司
       $arr = Service::getInstance('Platform')->getcompany();
       $list = array();
       echo '开始'.date('Y-m-d H:i:s',time());
       for ($x=1; $x<=$num; $x++) {
            $ksi=$jie;
            $jie= $ksi+24*3600;
            foreach ($arr as $k=>$v)
            {
                $clist = Service::getInstance('Platform')->getbrand($ksi,$jie,$v['id']);
                if($clist)
                {
                    foreach ($clist as $kk=>$vv)
                        {
                             $list['companyname'] =  $v['name'];
                             $list['goods_type'] = $vv['goods_type'];
                             $list['brand_id'] = $vv['brand_id'];
                             $list['company_id'] = $v['id'];
                             $list['tname'] = $vv['tname'];
                             $list['bname'] = $vv['bname'];
                             $list['sales'] = $vv['total_money'];
                             $list['sales_count'] = $vv['bcount'];
                             $list['start_time'] = $ksi;
                             $list['end_time'] = $jie;
                             $re = Service::getInstance('Platform')->insertbrand($list);
                        }
                }
               
//             if($arr['0'])
//             {
//                 foreach ($arr as $k=>$v)
//                 {
//                     foreach ($v as $vv) {
//                      $list['goods_type'] = $vv['goods_type'];
//                      $list['brand_id'] = $vv['brand_id'];
//                      $list['company_id'] = $vv['cid'];
//                      $list['tname'] = $vv['tname'];
//                      $list['bname'] = $vv['bname'];
//                      $list['sales'] = $vv['total_money'];
//                      $list['sales_count'] = $vv['bcount'];
//                      $list['start_time'] = $ksi;
//                      $list['end_time'] = $jie;
//                      $re = Service::getInstance('Platform')->insertbrand($list);
//                     }
//                   echo '1';
//                 }
//             }else{
//                 echo '无数据';
//             }
//             if($arr)
//             {
//                 foreach ($arr as $k=>$v)
//                 {
//                     $list['goods_type'] = $v['goods_type'];
//                     $list['brand_id'] = $v['brand_id'];
//                     $list['company_id'] = $v['cid'];
//                     $list['tname'] = $v['tname'];
//                     $list['bname'] = $v['bname'];
//                     $list['sales'] = $v['total_money'];
//                     $list['sales_count'] = $v['bcount'];
//                     $list['start_time'] = $ksi;
//                     $list['end_time'] = $jie;
//                     $re = Service::getInstance('Platform')->insertbrand($list);
//                 }
//                 echo '1';
//             }else{
//                 echo '没数据';
//             }
            
       }
       echo 'day_'.$x;
      }
      echo '结束'.date('Y-m-d H:i:s',time());
      exit();
      
    }
    /* 销售统计  */
    public function salelistAction() {
       //导入数据之前先清空表内数据
       date_default_timezone_set('Asia/Shanghai');
       $table_name = 'platfrom_sale_day';
       Service::getInstance('Platform')->giblets($table_name);
       $start_time = strtotime('2016-4-20');
        $start = $start_time;
       /*$end_time = strtotime(date('Y-m-d',time()));
       $end_time = $end_time+(3600*24);*/
       $currentTime = strtotime(date('Y-m-d'));
       $num = ($currentTime-$start_time)/(3600*24);
       /*$ksi=$start_time-(24*3600);
       $jie=$ksi+24*3600;*/
       $list = array();
       echo '开始'.date('Y-m-d H:i:s',time());
        for ($x=1; $x<=$num; $x++) {
            $endTime = $start_time + 3600 * 24;
            $arr = Service::getInstance('Platform')->getsale($start_time,$endTime);
            if($arr){
                foreach ($arr as $k=>$v)
                {
                    $list['unum'] = $v['unum']; 
                    $list['wname'] = $v['wname'];
                    $list['operate_days'] = $v['uptime'] > $start ? $v['uptime'] : $start;
                    $list['day_sale'] = $v['day_sale'] ? $v['day_sale'] : 0.00;
                    $list['day_salecount'] = $v['day_salecount'];
                    $list['company_id'] = $v['company_id'];
                    $list['cid'] = $v['cid'];
                    $list['ucount'] = $v['ucount'];
                    $list['ucount_s'] = $v['ucount1'];
                    $list['start_time'] = $start_time;
                    $list['end_time'] = $endTime;
                    $re = Service::getInstance('Platform')->insertinfo($table_name,$list);
                }
            }else{
                echo '没数据';
            }
            echo 'day_'.$x;
            $start_time = $start_time + 3600 * 24;
        }
        echo '结束'.date('Y-m-d H:i:s',time());
        exit();
    }
    /* 单品统计 */
    public function singlelistAction()
    {
        //导入数据之前先清空表内数据
        date_default_timezone_set('Asia/Shanghai');
        $time_start = $this->getmicrotime();
        $table_name = 'platfrom_single_day';
        Service::getInstance('Platform')->giblets($table_name);
        $start_time = strtotime('2016-4-20');
        $end_time = strtotime(date('Y-m-d',time()));
        $end_time = $end_time+(3600*24);
        $num = ($end_time-$start_time)/(3600*24);
        $ksi=$start_time-(24*3600);
        $jie=$ksi+24*3600;
        //查询所有商品
        $goods = Service::getInstance('Platform')->goodslist();
        $time_end = $this->getmicrotime();
        $time = $time_end - $time_start;
        echo "执行时间 $time seconds";
        $list = array();
        echo '开始'.date('Y-m-d H:i:s',time());
        for ($x=1; $x<=$num; $x++) {
            $ksi=$jie;
            $jie= $ksi+24*3600;

            /*
            foreach ($goods as $k=>$v){
                //$arr = Service::getInstance('Platform')->getsingle($ksi,$jie,$v['goods_id']);
                //$goods[$k]['day_total'] = $arr['goods_total'];
                //$goods[$k]['day_count'] = $arr['goods_count'];
                $ids[] = $v['goods_id'];
            }
            */
            $arr = Service::getInstance('Platform')->getsingleAll($ksi,$jie);
            if($arr){
                $new_arr = array();
                foreach($arr as $v){
                    $new_arr[$v['goods_id']] = $v;
                }
            }
            foreach ($goods as $k=>$v){
                //$arr = Service::getInstance('Platform')->getsingle($ksi,$jie,$v['goods_id']);
                $goods[$k]['day_total'] = $new_arr[$v['goods_id']]['goods_total'];
                $goods[$k]['day_count'] = $new_arr[$v['goods_id']]['goods_count'];

            }
            $time_end = $this->getmicrotime();
            $time = $time_end - $time_start;
            echo "执行时间 $time seconds";
            /*
            var_dump($goods);
            exit;
            */
            $tmp_arr = array();
            foreach ($goods as $k=>$v) {
                if($v['day_count']>0){
                    $list['id'] = '';
                    $list['goods_name'] = $v['goods_name'];
                    $list['type_name'] = $v['type_name'];
                    $list['bname'] = $v['bname'];
                    $list['tid'] = $v['goods_type'];
                    $list['bid'] = $v['brand_id'];
                    $list['goods_id'] = $v['goods_id'];
                    $list['day_total'] = $v['day_total'];
                    $list['day_count'] = $v['day_count'];
                    $list['cid'] = $v['cid'];
                    $list['start_time'] = $ksi;
                    $list['end_time'] = $jie;

                    $tmp_arr[] = $list;
                }

                }

            $re = Service::getInstance('Platform')->insertDayInfo($table_name,$tmp_arr);
            $time_end = $this->getmicrotime();
            $time = $time_end - $time_start;
            echo "执行时间 $time seconds";
            echo 'day_'.$x;
           }
        echo '结束'.date('Y-m-d H:i:s',time());
        exit();
    }



    function getmicrotime()
    {
        list($usec, $sec) = explode(" ",microtime());
        return ((float)$usec + (float)$sec);
    }
    /* 终端店统计 */
    public function storelistAction(){
        date_default_timezone_set('Asia/Shanghai');
        $table_name = 'platform_store';
        Service::getInstance('Platform')->giblets($table_name);
        $kadd_time = strtotime('2016-4-20');
        $today = strtotime(date('Y-m-d', time()));
        $end = $today + 24 * 60 * 60;
        $gadd_time = $end;
        $num = ($gadd_time-$kadd_time)/(3600*24);
        $ksi=$kadd_time-(24*3600);
        $jie=$ksi+24*3600;
        for ($x=1; $x<=$num; $x++) {
            $ksi=$jie;
            $jie= $ksi+24*3600;
            $list = Service::getInstance('Platform')->getstoreList(date("Y-m-d",$jie),date("Y-m-d",$ksi));
            foreach ($list as &$v){
                $arr=array(
                    'companyname'=>$v['cname'],
                    'warehousename'=>$v['wname'],
                    'user_total'=>intval($v['ucount']),
                    'start_time'=>$ksi,
                    'end_time'=>$jie,
                    'cid'=>$v['cid'],
                    'order_money'=>$v['order_money'],
                    'order_num'=>intval($v['ordernum']),
                    'wid'=>intval($v['wid'])
                );
                $addID = Service::getInstance('Platform')->insertinfo($table_name,$arr);
                if($addID){
                    echo '1';
                }else{
                    echo '2';
                }
            }
        }
        exit();
    }

    /**
     * 经销商账单
     */
    public function dealerBillAction()
    {
        date_default_timezone_set('Asia/Shanghai');
        $startTime = mktime(0,0,0,7, 31, 2016);
        $endTime = mktime(23,59,59,7, 31, 2016);
        $result = Service::getInstance('Platform')->getDealerBillList($startTime, $endTime);
        if($result){
            foreach($result as $key => $value){
                $addID = Service::getInstance('Platform')->insertinfo('platform_dealer_bill',$value);
                if($addID){
                    echo 1;
                }else{
                    echo 2;
                }
            }
        }
    }

    /**
     * 以往经销商订单跑批
     */
    public function allDealerBillsAction(){
        date_default_timezone_set('Asia/Shanghai');
        Service::getInstance('Platform')->giblets('platform_dealer_bill');
        $startTime = strtotime('2016-4-20');
        $today = strtotime(date('Y-m-d', time()));
        $num = ($today-$startTime)/(3600*24);
        for ($x=1; $x<=$num; $x++) {
            $endTime = $startTime + (24 * 3600) - 1;
            $result = Service::getInstance('Platform')->getDealerBillList($startTime, $endTime);
            if($result){
                foreach($result as $key => $value){
                    $addID = Service::getInstance('Platform')->insertinfo('platform_dealer_bill',$value);
                    if($addID){
                        echo 1;
                    }else{
                        echo 2;
                    }
                }
            }
            $startTime = $startTime + (3600*24);
        }
        echo '跑批结束';
        exit;
    }

}