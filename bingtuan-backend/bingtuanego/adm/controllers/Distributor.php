<?php

class DistributorController extends BaseController
{


    public function indexAction()
    {
        //当日时间
        $time = " add_time>".strtotime(date('Y-m-d'))." AND add_time<".strtotime(date('Y-m-d',strtotime('+1 day')));
        //$time = " add_time>1467820800 AND add_time<1467907200";
        $total=array();
        $company_id =$this->companyIds;
        //统计不同种商品的销量
        $arr = array('ice_cream'=>1,'frozen'=>3,'drink'=>4);
        foreach ($arr as $k=>$value){
            $data = Service::getInstance(Distributor)->getGoodsSales($company_id,$time,$value);
            $goods_sales[$k] = $data[0]['num'];
        }
        $total['goods_sales']=$goods_sales;
        //var_dump($total);exit;
        //统计商品的状态
        $goods_state = Service::getInstance(Distributor)->getGoodsState($company_id);
        $total['goods_state'] = $goods_state;
        //库存预警商品总数
        $goods_warning = Service::getInstance(Distributor)->getGoodsWarning($company_id);
        $total['goods_warning'] = $goods_warning['num'];
        //var_dump($total);exit;
        //计算上线时间
        $warehouse_time = Service::getInstance('Distributor')->getWarehouseTimeByCid($company_id);
        $online_time = intval((time()-$warehouse_time['online_time'])/(3600*24));
        $total['online_time']=$online_time;
        //当前的时间
        $total['date'] = date('Y-m-d',time());
        //通过公司获取经销商id
        $warehouse_id = Service::getInstance('Distributor')->getWarehouseByCid($company_id);
        $warehouse_id = array_column($warehouse_id,'id');
        $cid = $warehouse_id;
        //今日登陆的终端店
        $order_user_sign = Service::getInstance(Distributor)->getUserLogged($warehouse_id,$time);
        $total['order_user_sign'] = $order_user_sign;
        //计算经销商的用户
        $user_num = Service::getInstance('Distributor')->getUserNum($warehouse_id);
        $total['total_number'] =$user_num['total_number'];
        foreach($warehouse_id as $key=>$value){
            $warehouse_id[$key] = 'warehouse_id = '.$value;
        }
        $warehouse_id = implode(' or ',$warehouse_id);
        //print_r($total);exit;
        //订单总数
        $order_total = Service::getInstance('Distributor')->getOrderInfoNum($warehouse_id,$time);
        $total['order_total'] =$order_total['order_num'];
        //当日销售情况
        $order_info = Service::getInstance('Distributor')->getOrderInfo($warehouse_id,$time);
        $total['order_info']=$order_info;
        //当日成交终端店数量
        $deal_num = Service::getInstance(Distributor)->getOrderNum($warehouse_id,$time);
        $total['deal_num'] = $deal_num;
        //当日的客单价
        $total['per_price'] = round($total['order_info']['money']/$total['order_info']['order_num'],2);
        //var_dump($total);exit;
        //订单状态
        $order_state = Service::getInstance(Distributor)->getOderState($warehouse_id,$time);
        //查询代发货订单
        $order_state_dai = Service::getInstance(Distributor)->getOderStateDai($warehouse_id,$time);
        $total['order_state'] = $order_state;
        //查询待收货状态
        $order_state_shou = Service::getInstance(Distributor)->getOderStateShou($warehouse_id,$time);
        //查询待收货付款状态
        $order_state_notpay = Service::getInstance(Distributor)->getOderStateDaiPay($warehouse_id,$time);
        $total['order_state'][0]['receive']=$order_state_shou[0]['num'];
        $total['order_state'][0]['delivery']=$order_state_dai[0]['num'];
        $total['order_state'][0]['obligations']=$order_state_notpay[0]['num'];
        //今日下单的终端店
        $order_user_place = Service::getInstance(Distributor)->getUserOrder($warehouse_id,$cid,$time);
        //总下单终端数
        $order_info_total = Service::getInstance(Distributor)->getUserOrder($warehouse_id,$cid);
        $total['order_info_total'] = $order_info_total['num'];
        $total['order_user_place'] = $order_user_place;
        //终端转化率
        $total['conversion'] = round($order_info_total['num']/$total['total_number'],4)*100;
        $developer[] = $total;
        //var_dump($developer);exit;
        $this->_view->developer= $developer;
    }
    public function goodsStatisticsAction(){
        $search_name = $this->getQuery('goods_name','');
        $ktime = $this->getQuery('ktime','');
        $gtime = $this->getQuery('gtime','');
        // 品牌
        $brand = $this->getQuery('brand','');
        // 品类
        $goods_type = $this->getQuery('goods_type','');
        //排序
        $order = $this->getQuery('goods_number','');

        $page = $this->getQuery('page','')?$this->getQuery('page',''):'1';
        //结束值
        $pageSize = 10;
        //起始值
        $first = ($page-1)*$pageSize;
        $search = array(
            'goods_name'=>$search_name,
            'goods_number'=>$order,
            'brand'=>$brand,
            'goods_type'=>$goods_type,
            'ktime' => $ktime,
            'gtime' => $gtime
        );
        if(trim($ktime) != '' || trim($gtime) != ''){
            if ($ktime ==$gtime){
                $time = (!empty($ktime) ? ' gs.create_time>='.strtotime($ktime) : '').((!empty($ktime) && !empty($gtime))?' and ':'').(!empty($gtime)?' gs.create_time<='.(strtotime($gtime)+3600*24):'');
            }else{
                $time = (!empty($ktime) ? ' gs.create_time>='.strtotime($ktime) : '').((!empty($ktime) && !empty($gtime))?' and ':'').(!empty($gtime)?' gs.create_time<='.strtotime($gtime).' ':'');
            }
        }
        //var_dump($time);exit;
        $url = "http://".$_SERVER['SERVER_NAME']."/distributor/goodsstatistics?page=__page__&".http_build_query($search);

        $userinfo=Yaf_Registry::get('developer');
        $company_id =$this->companyIds;
        $basedata = Service::getInstance('Distributor')->getGoodsStatistics($company_id,$first,$pageSize,$brand,$goods_type,$search_name,$time,$order);
        /*foreach($basedata['data'] as &$v){
            $goods_id =$v['goods_id'];
            //判断是否在活动中
            $activity_id = Service::getInstance('Distributor')->getActivityId($goods_id);
            $v['is_activity'] = (empty($activity_id))?0:$activity_id['type'];
            $activity_gifts = Service::getInstance('Distributor')->getActivityGifts($goods_id);
            $v['is_activitygifts'] = ($activity_gifts['id'])?$activity_gifts['id']:0;
        }*/
        $get = $_GET;
        $this->_view->get = $get;
        //品牌
        $this->_view->getBrand =  Service::getInstance('Admgoods')->getBrand();
        //品类
        $this->_view->get_goods_type =  Service::getInstance('Admgoods')->get_goods_type();
        $this->_view->paglist = Util::buildPagebar($basedata['num'],$pageSize,$page,$url);
        $this->_view->developer= $basedata['data'];
    }

    public  function export($data,$arr,$filename,$setTitle){
        if ($data) {
            if ($data > 0) {
                $objPHPExcel = new PHPExcel();
                $objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
                foreach ($arr as $k=>$value){
                    $objPHPExcel->getActiveSheet()->setCellValue($k, $value);
                }
                $e = 0;
                foreach ($data as $i => $v) {
                    $e = $i+2;
                    foreach ($v as $kk=>$vv ){
                        if($kk == 'A'){
                            $objPHPExcel->getActiveSheet()->setCellValueExplicit($kk. $e,$vv);
                        }else{
                            $objPHPExcel->getActiveSheet()->setCellValue($kk . $e, $vv);
                        }
                    }
                }
                $FileName = $filename;
                // sheet名称
                $objPHPExcel->getActiveSheet()->setTitle($setTitle);
                $objPHPExcel->setActiveSheetIndex(0);
                //到文件
                // $objWriter->save($FileName);
                //到浏览器
                header("Content-Type: application/force-download");
                header("Content-Type: application/octet-stream");
                header("Content-Type: application/download");
                header('Content-Disposition:inline;filename="'.$FileName.'"');
                header("Content-Transfer-Encoding: binary");
                header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
                header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
                header("Pragma: no-cache");
                $objWriter->save('php://output');
                unset($data);die;
            }
        }
    }

    /**
     * 导出商品统计
     */
    public function goodsExportAction()
    {
        set_time_limit(1800);
        //品牌
        $this->_view->getBrand =  Service::getInstance('Admgoods')->getBrand();
        //品类
        $this->_view->get_goods_type =  Service::getInstance('Admgoods')->get_goods_type();
            $search_name = $this->getQuery('goods_name','');
            $ktime = $this->getQuery('ktime','');
            $gtime = $this->getQuery('gtime','');
            // 品牌
            $brand = $this->getQuery('brand','');
            // 品类
            $goods_type = $this->getQuery('goods_type','');
            if(trim($ktime) != '' || trim($gtime) != ''){
                if ($ktime ==$gtime){
                    $time = (!empty($ktime) ? ' gs.create_time>='.strtotime($ktime) : '').((!empty($ktime) && !empty($gtime))?' and ':'').(!empty($gtime)?' gs.create_time<='.(strtotime($gtime)+3600*24):'');
                }else{
                    $time = (!empty($ktime) ? ' gs.create_time>='.strtotime($ktime) : '').((!empty($ktime) && !empty($gtime))?' and ':'').(!empty($gtime)?' gs.create_time<='.strtotime($gtime).' ':'');
                }
            }
            $userinfo=Yaf_Registry::get('developer');
            $company_id =$this->companyIds;
            //查询要导出的数据
            //var_dump($brand);exit;
            $data = Service::getInstance('PlatformExcel')->getGoodsStatistics($company_id,$brand,$goods_type,$search_name,$time);
            //定义excel的内容，根据你查询出来的数据构造需要的字段
            foreach ($data['data'] as $value){
                $arrs['A']=$value['name'];
                $arrs['B']=$value['brand'];
                $arrs['C']=$value['goods_name'];
                $arrs['D']='无';
                $arrs['E']=$value['goods_number'];
                $arrs['F']='正常';
                $array[] =$arrs;
            }
            //定义excel头行的内容
            $arr = array(
                'A1'=> '品类',
                'B1'=> '品牌',
                'C1'=> '商品名称',
                'D1'=> '活动情况',
                'E1'=> '销售数量',
                'F1'=> '属性'
            );
            //定义文件名
            $FileName ='经销商商品统计.xlsx';
            //定义标题
            $setTitle = '商品统计';
            $this->export($array,$arr,$FileName,$setTitle);
    }
    /**
     * 导出终端统计
     */
    public function terminalsExportAction()
    {
            set_time_limit(1800);
            //搜索用户
            $search_name = $this->getQuery('comName','');
            //时间
            $ktime = $this->getQuery('ktime','');
            $gtime = $this->getQuery('gtime','');
            //范围
            $range = $this->getQuery('range','');
            //3-下单成交终端店 1-下单未成交终端店 4-从未下单终端店
            if($range==3){
                $ranges =  " AND oi.pay_status=2 AND oi.order_status=".$range." AND (u.comName like '%".$search_name."%' OR u.account like '%".$search_name."%')" ;
            }elseif($range==4){
                $judge =  " AND oi.user_id is null AND (u.comName like '%".$search_name."%' OR u.account like '%".$search_name."%')";
                $ranges = '';
            }else{
                $ranges = " AND (u.comName like '%".$search_name."%' OR u.account like '%".$search_name."%')";
            }
            if(trim($ktime) != '' || trim($gtime) != ''){
                if ($ktime==$gtime){
                    $time = (!empty($ktime) ? ' AND oi.add_time>='.strtotime($ktime) : '').((!empty($ktime) && !empty($gtime))?' and ':'').(!empty($gtime)?' oi.add_time<='.(strtotime($gtime)+3600*24):'');
                }else{
                    $time = (!empty($ktime) ? ' AND oi.add_time>='.strtotime($ktime) : '').((!empty($ktime) && !empty($gtime))?' and ':'').(!empty($gtime)?' oi.add_time<='.strtotime($gtime).' ':'');
                }
            }
            //var_dump($time);exit;
            $userinfo=Yaf_Registry::get('developer');
            //var_dump($userinfo);exit;
            $company_id =$this->companyIds;
            $warehouse_id = Service::getInstance('PlatformExcel')->getWarehouseByCid($company_id);
            $warehouse_id = array_column($warehouse_id,'id');
            foreach($warehouse_id as $key=>$value){
                $warehouse_id[$key] = 'u.cid = '.$value;
            }
            $warehouse_id = implode(' or ',$warehouse_id);
            if($range==1){
                $basedata= Service::getInstance('PlatformExcel')->getTerminalstatisticss($warehouse_id,$ranges,$time);
            }else{
                $basedata= Service::getInstance('PlatformExcel')->getTerminalstatistics($warehouse_id,$ranges,$judge,$time);
            }
            foreach ($basedata['data'] as &$v){
                $user_id = $v['id'];
                $add = Service::getInstance(PlatformExcel)->getAddressById($user_id);
                $updatetime = Service::getInstance(PlatformExcel)->getUpdateById($user_id);
                $v['updateTime']=$updatetime['updatetime'];
                $v['user_name'] = $add['user_name'];
                $v['phone'] = $add['phone'];
            }
            //var_dump($basedata['data']);exit;
            //定义excel头行的内容
            $arr = array(
                'A1'=> '用户名',
                'B1'=> '店名',
                'C1'=> '收货人姓名',
                'D1'=> '收货电话',
                'E1'=> '上次登录时间',
                'F1'=> '上次下单时间',
                'G1'=> '审核状态',
                'H1'=> '下单数量',
            );
            //定义excel的内容，根据你查询出来的数据构造需要的字段
            date_default_timezone_set("Asia/Shanghai");
            foreach ($basedata['data'] as $value){
                $arrs['A']=$value['account'];
                $arrs['B']=$value['comName'];
                $arrs['C']=$value['user_name'];
                $arrs['D']=$value['phone'];
                $arrs['E']=!empty($value['updateTime'])?date("Y-m-d H:i:s",$value['updateTime']):'';
                $arrs['F']=!empty($value['last_time'])?date("Y-m-d H:i:s",$value['last_time']):'';
                if($value['auditType']==0){
                    $arrs['G']='审核中';
                }elseif($value['auditType']==1){
                    $arrs['G']='不通过';
                }elseif($value['auditType']==2){
                    $arrs['G']='通过';
                }else{
                    $arrs['G']='拉黑';
                }
                $arrs['H']=(empty($value['oid']))?0:$value['num'];
                $array[] =$arrs;
            }
            //定义文件名
            $FileName ='经销商终端统计.xlsx';
            //定义标题
            $setTitle = '终端统计';
            $this->export($array,$arr,$FileName,$setTitle);
            exit;
    }




    public function terminalsSatisticsAction(){
        //搜索用户
        $search_name = $this->getQuery('comName','');
        //页数
        $page = $this->getQuery('page','')?$this->getQuery('page',''):'1';
        //结束值
        $pageSize = 10;
        //开始
        $first = ($page-1)*$pageSize;
        //时间
        $ktime = $this->getQuery('ktime','');
        $gtime = $this->getQuery('gtime','');
        //范围
        $range = $this->getQuery('range','');
        //排序
        $order = $this->getQuery('num','');
        $search = array(
            'range'=>$range,
            'ktime' => $ktime,
            'gtime' => $gtime,
            'num'=>$order,
            'comName'=>$search_name
        );
        if($range==3){
            $ranges =  " AND oi.pay_status=2 AND oi.order_status=".$range." AND (u.comName like '%".$search_name."%' OR u.account like '%".$search_name."%')" ;
        }elseif($range==4){
            $judge =  " AND oi.user_id is null AND (u.comName like '%".$search_name."%' OR u.account like '%".$search_name."%')";
            $ranges = '';
        }else{
            $ranges = " AND (u.comName like '%".$search_name."%' OR u.account like '%".$search_name."%')";
        }
        //var_dump($range);exit;
        if(trim($ktime) != '' || trim($gtime) != ''){
            if ($ktime==$gtime){
                $time = (!empty($ktime) ? ' AND oi.add_time>='.strtotime($ktime) : '').((!empty($ktime) && !empty($gtime))?' and ':'').(!empty($gtime)?' oi.add_time<='.(strtotime($gtime)+3600*24):'');
            }else{
                $time = (!empty($ktime) ? ' AND oi.add_time>='.strtotime($ktime) : '').((!empty($ktime) && !empty($gtime))?' and ':'').(!empty($gtime)?' oi.add_time<='.strtotime($gtime).' ':'');
            }

        }

        //var_dump($time);exit;
        $userinfo=Yaf_Registry::get('developer');
        //var_dump($userinfo);exit;
        $company_id =$this->companyIds;
        $warehouse_id = Service::getInstance('Distributor')->getWarehouseByCid($company_id);
        $warehouse_id = array_column($warehouse_id,'id');
        foreach($warehouse_id as $key=>$value){
            $warehouse_id[$key] = 'u.cid = '.$value;
        }
        $warehouse_id = implode(' or ',$warehouse_id);
        if($range==1){
            $basedata= Service::getInstance('Distributor')->getTerminalstatisticss($warehouse_id,$first,$pageSize,$ranges,$time,$order);
        }else{
            $basedata= Service::getInstance('Distributor')->getTerminalstatistics($warehouse_id,$first,$pageSize,$ranges,$judge,$time,$order);
        }
            foreach ($basedata['data'] as &$v){
                $user_id = $v['id'];
                $add = Service::getInstance(Distributor)->getAddressById($user_id);
                $updatetime = Service::getInstance(Distributor)->getUpdateById($user_id);
                $v['updateTime']=$updatetime['updatetime'];
                $v['user_name'] = $add['user_name'];
                $v['phone'] = $add['phone'];
            }
        $get = $_GET;
        $this->_view->get = $get;
        $url = "http://".$_SERVER['SERVER_NAME']."/distributor/terminalssatistics?page=__page__&".http_build_query($search);
        $this->_view->paglist = Util::buildPagebar($basedata['num'],$pageSize,$page,$url);
        $this->_view->developer= $basedata['data'];
    }


    public function testAction()
    {
        //cid 为经销商代码 order为要填写的前缀 直接更换相对应的
        $cid = '4';
        $order = '10004';
        $arr = Service::getInstance('Distributor')->add_order($cid,$order);
        exit;
    }
    //批量导入商品
    public function addGoodsAction(){
        $handle = fopen("test.txt","r");
        if ($handle) {
            while (!feof($handle)) {
                $buffer = fgets($handle, 4096);
                //用正则去掉不规则空格
                $data = preg_replace('/\s+/', ' ', $buffer);
                //去掉两侧的空字符串
                $data = trim($data);
                //$data = iconv("GBK","UTF-8", $data);
                //$data = mb_convert_encoding($data,'UTF-8','GBK');
                //分割成数组
                $datas = explode(' ',$data);
                $num = count($datas);

                if($num>=4 && $datas[0]!=''){

                    /*
                    if($num>4){
                        $datas[3] = $datas[3].$datas[4];
                    }
                    */
                    $goods_name = $datas[0];
                    if($datas[2] =='冰淇淋'){
                        $goods_type = 1;
                    }elseif ($datas[2] =='速冻类'){
                        $goods_type = 3;
                    }elseif($datas[2] =='饮品类'){
                        $goods_type = 4;
                    }elseif($datas[2] =='粮油类'){
                        $goods_type = 5;
                    }elseif($datas[2] =='啤酒'){
                        $goods_type = 6;
                    }elseif($datas[2] =='蔬菜类'){
                        $goods_type = 8;
                    }elseif($datas[2] =='白酒'){
                        $goods_type = 7;
                    }elseif($datas[2] =='赠品'){
                        $goods_type=9;
                    }
                    $brand_name = $datas[1];
                    //通过品牌名查询品牌的id
                    $brand_id = Service::getInstance('Distributor')->getBrandId($brand_name);
                    if(!$brand_id){
                        continue;
                    }
                    //拿新的名字去查询，如果存在说明已经插入过数据，不存在说明是新的数据
                    $id = Service::getInstance('Distributor')->getGood($datas[0]);
                    if (empty($id)){
                        $arr = Service::getInstance('Distributor')->getGoodInfo($datas[3]);
                        if(!$arr){
                            file_put_contents('notsearch.txt',$datas[3].',',FILE_APPEND);
                        }else{
                            unset($arr[0]['id']);
                            $arr[0]['goods_name'] = $goods_name;
                            $arr[0]['brand_id'] = $brand_id['id'];
                            $arr[0]['goods_type'] = $goods_type;
                            $arr[0]['cid'] =1;
                            $arr[0]['pid'] = 0;
                            $arr[0]['is_show']=0;
                            $arr[0]['is_delete']=0;
                            $arr[0]['goods_abbreviation']=$datas[4];
                            //var_dump($arr[0]);
                            $addID = Service::getInstance('Distributor')->addGoods($arr[0]);
                            if($addID){
                                echo '1';
                            }else{
                                echo '2';
                            }
                        }
                    }
                    /*if(empty($arr)){
                        //如果为空说明不存在原来的直接查找替换
                        $arr = Service::getInstance('Distributor')->getGoodInfo($datas[3]);
                        foreach($arr as $v){
                            $re = Service::getInstance('Distributor')->upGoodsInfo($v['id'],$v['cid'],$goods_name,$brand_id['id'],$goods_type);
                        }
                    }else{
                        foreach ($arr as $value){
                            if($value['brand_id']!=$brand_id['id']  || $value['goods_type']!=$goods_type){
                                $re = Service::getInstance('Distributor')->upGoodsInfos($value['id'],$brand_id['id'],$goods_type);
                            }
                        }
                    }
                        unset($arr[0]['id']);
                        $arr[0]['goods_name'] = $goods_name;
                        $arr[0]['brand_id'] = $brand_id['id'];
                        $arr[0]['goods_type'] = $goods_type;
                        $arr[0]['cid'] =1;
                        $arr[0]['pid'] = 0;
                        $arr[0]['is_show']=0;*/

                    //将商品插入到数据库中
                    //var_dump($arr[0]);exit;
                    /*$addID = Service::getInstance('Distributor')->addGoods($arr[0]);
                    if($addID){
                        echo '添加成功';
                    }else{
                        echo '添加失败';
                    }*/
                }
            }
            fclose($handle);
        }
        exit;
    }


    /**
 * 更新重复的商品
 */
    public function updateGoodsAction(){
        $handle = fopen("update.txt","r");
        if ($handle) {
            while (!feof($handle)) {
                $buffer = fgets($handle, 4096);
                //用正则去掉不规则空格
                $data = preg_replace('/\s+/', ' ', $buffer);
                //去掉两侧的空字符串
                $data = trim($data);
                //var_dump($data);
                //$data = iconv("GBK","UTF-8", $data);
                //$data = mb_convert_encoding($data,'UTF-8','GBK');
                //分割成数组
                $datas = explode(' ',$data);
                $num = count($datas);
                //var_dump($datas);
                if($num=2 && $datas[0]!='') {
                    if($datas[0]!=$datas[1]){
                        //echo $datas[0];
                        //首先修改对应商品的订单，然后删除该商品

                        $rs = Service::getInstance('Distributor')->uporderid($datas[0],$datas[1]);

                        Service::getInstance('Distributor')->deletegoods($datas[0]);


                    }
                }

            }
            fclose($handle);
        }
        exit;
    }

    /**
     * 更新重复的商品
     */
    public function updateGoodsnameAction(){
        $handle = fopen("newgoods.txt","r");
        if ($handle) {
            while (!feof($handle)) {
                $buffer = fgets($handle, 4096);
                //用正则去掉不规则空格
                $data = preg_replace('/\s+/', ' ', $buffer);
                //去掉两侧的空字符串
                $data = trim($data);

                $datas = explode(' ', $data);
                $num = count($datas);
                //var_dump($datas);
                if($num==7){
                    $array = array();
                    foreach($datas as $k=>$v){

                        if($k==1){
                            $array[] = '';
                            $array[] = $v;
                        }else{
                            $array[] = $v;
                        }
                    }

                    $datas = $array;
                    var_dump($datas);
                    echo $num = count($datas);
                }
                if ($num == 8 && $datas[0] != '') {
                    if ($datas[0].$datas[1] != $datas[3].$datas[2]) {
                        //echo $datas[0];
                        //首先修改对应商品的订单，然后删除该商品

                        $rs = Service::getInstance('Distributor')->getGoodsname($datas[0],$datas[1],$datas[3],$datas[2]);
                        var_dump($rs);

                    }
                }

            }
        }
        fclose($handle);
        exit;
    }

    public function updateerrorAction(){
        $handle = fopen("error.txt","r");
        if ($handle) {
            while (!feof($handle)) {
                $buffer = fgets($handle, 4096);
                //用正则去掉不规则空格
                $data = preg_replace('/\s+/', ' ', $buffer);
                //去掉两侧的空字符串
                $data = trim($data);

                $datas = explode(' ', $data);
                $num = count($datas);
                if($num==7){
                    $array = array();
                    foreach($datas as $k=>$v){

                        if($k==1){
                            $array[] = '';
                            $array[] = $v;
                        }else{
                            $array[] = $v;
                        }
                    }

                    $datas = $array;
                    var_dump($datas);
                    echo $num = count($datas);
                }

                if ($num == 8 && $datas[0] != '') {
                    if ($datas[0].$datas[1] != $datas[3].$datas[2]) {
                        //echo $datas[0];
                        //首先修改对应商品的订单，然后删除该商品

                        $rs = Service::getInstance('Distributor')->getGoodsname($datas[0],$datas[1],$datas[3],$datas[2]);
                        var_dump($rs);

                    }
                }

            }
        }
        fclose($handle);
        exit;
    }
    public function addAllgoodsAction(){
    $handle = fopen("newgoods.txt","r");
    if ($handle) {
        while (!feof($handle)) {
            $buffer = fgets($handle, 4096);
            //用正则去掉不规则空格
            $data = preg_replace('/\s+/', ' ', $buffer);
            //去掉两侧的空字符串
            $data = trim($data);

            $datas = explode(' ', $data);
            $num = count($datas);
            //var_dump($datas);
            if($num==7){
                $array = array();
                foreach($datas as $k=>$v){

                    if($k==1){
                        $array[] = '';
                        $array[] = $v;
                    }else{
                        $array[] = $v;
                    }
                }

                $datas = $array;
                var_dump($datas);
                $num = count($datas);
            }
            if ($num == 8 && $datas[0] != '') {
                $goods_name = $datas[3];
                if($datas[5] =='冰淇淋'){
                    $goods_type = 1;
                }elseif ($datas[5] =='速冻类'){
                    $goods_type = 3;
                }elseif($datas[5] =='饮品类'){
                    $goods_type = 4;
                }elseif($datas[5] =='粮油类'){
                    $goods_type = 5;
                }elseif($datas[5] =='啤酒'){
                    $goods_type = 6;
                }elseif($datas[5] =='蔬菜类'){
                    $goods_type = 8;
                }elseif($datas[5] =='白酒'){
                    $goods_type = 7;
                }elseif($datas[5] =='赠品'){
                    $goods_type=9;
                }
                $brand_name = $datas[6];
                //通过品牌名查询品牌的id
                $brand_id = Service::getInstance('Distributor')->getBrandId($brand_name);
                if(!$brand_id){
                    continue;
                }
                //拿新的名字去查询，如果存在说明已经插入过数据，不存在说明是新的数据
                $id = Service::getInstance('Distributor')->getGoodstandard($datas[3],$datas[2]);
                if (empty($id)) {
                    $arr = Service::getInstance('Distributor')->getGoodInfostandard($datas[3],$datas[2]);
                    if (!$arr) {
                        file_put_contents('notsearch.txt', $datas[3] . ',', FILE_APPEND);
                    } else {
                        unset($arr[0]['id']);
                        $arr[0]['goods_name'] = $goods_name;
                        $arr[0]['brand_id'] = $brand_id['id'];
                        $arr[0]['goods_type'] = $goods_type;
                        $arr[0]['cid'] = 1;
                        $arr[0]['pid'] = 0;
                        $arr[0]['is_show'] = 0;
                        $arr[0]['is_delete'] = 0;
                        $arr[0]['amount'] = 0;
                        $arr[0]['goods_abbreviation'] = $datas[4];
                        $arr[0]['standard'] = $datas[2];
                        //var_dump($arr[0]);
                        $addID = Service::getInstance('Distributor')->addGoods($arr[0]);
                        if ($addID) {
                            echo '1';
                        } else {
                            echo '2';
                        }
                    }
                }
            }

        }
    }
    fclose($handle);
    exit;
}

    public function addAllgoodserrAction(){
        $handle = fopen("goodserror.txt","r");
        if ($handle) {
            while (!feof($handle)) {
                $buffer = fgets($handle, 4096);
                //用正则去掉不规则空格
                $data = preg_replace('/\s+/', ' ', $buffer);
                //去掉两侧的空字符串
                $data = trim($data);

                $datas = explode(' ', $data);
                $num = count($datas);
                var_dump($datas);
                if($num==7){
                    $array = array();
                    foreach($datas as $k=>$v){

                        if($k==1){
                            $array[] = '';
                            $array[] = $v;
                        }else{
                            $array[] = $v;
                        }
                    }

                    $datas = $array;
                    var_dump($datas);
                    $num = count($datas);
                }
                if ($num == 8 && $datas[0] != '') {
                    $goods_name = $datas[3];
                    if($datas[5] =='冰淇淋'){
                        $goods_type = 1;
                    }elseif ($datas[5] =='速冻类'){
                        $goods_type = 3;
                    }elseif($datas[5] =='饮品类'){
                        $goods_type = 4;
                    }elseif($datas[5] =='粮油类'){
                        $goods_type = 5;
                    }elseif($datas[5] =='啤酒'){
                        $goods_type = 6;
                    }elseif($datas[5] =='蔬菜类'){
                        $goods_type = 8;
                    }elseif($datas[5] =='白酒'){
                        $goods_type = 7;
                    }elseif($datas[5] =='赠品'){
                        $goods_type=9;
                    }
                    echo $brand_name = $datas[6];
                    //通过品牌名查询品牌的id
                    $brand_id = Service::getInstance('Distributor')->getBrandId($brand_name);
                    if(!$brand_id){
                        continue;
                    }
                    //拿新的名字去查询，如果存在说明已经插入过数据，不存在说明是新的数据
                    $id = Service::getInstance('Distributor')->getGoodstandard($datas[3],$datas[2]);
                    if (empty($id)) {
                        $arr = Service::getInstance('Distributor')->getGoodInfostandard($datas[3],$datas[2]);
                        if (!$arr) {
                            file_put_contents('notsearch.txt', $datas[3] . ',', FILE_APPEND);
                        } else {
                            unset($arr[0]['id']);
                            $arr[0]['goods_name'] = $goods_name;
                            $arr[0]['brand_id'] = $brand_id['id'];
                            $arr[0]['goods_type'] = $goods_type;
                            $arr[0]['cid'] = 1;
                            $arr[0]['pid'] = 0;
                            $arr[0]['is_show'] = 0;
                            $arr[0]['is_delete'] = 0;
                            $arr[0]['amount'] = 0;
                            $arr[0]['goods_abbreviation'] = $datas[4];
                            $arr[0]['standard'] = $datas[2];
                            //var_dump($arr[0]);
                            $addID = Service::getInstance('Distributor')->addGoods($arr[0]);
                            if ($addID) {
                                echo '1';
                            } else {
                                echo '2';
                            }
                        }
                    }
                }

            }
        }
        fclose($handle);
        exit;
    }
}

