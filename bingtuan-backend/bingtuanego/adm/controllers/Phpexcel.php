<?php
class PhpexcelController extends BaseController
{
    /**
     * 导出订单
     */
    public function exportAction()
    {
        set_time_limit(1800);
        $userinfo = Yaf_Registry::get('developer');
        $orderid = $this->getQuery('order_number','');
        $minprice = $this->getQuery('minprice','');
        $maxprice = $this->getQuery('maxprice','');
        $ktime = $this->getQuery('ktime','');
        $gtime = $this->getQuery('gtime','');
        $export_type = $this->getQuery('export_type','');
        
        $company_id = ($this->_view->rbac == '*') ? $this->getQuery('company_id','') : $this->companyIds ;
        $queryId = $this->getQuery('company_id','');
        $warehouse_id = $this->getQuery('warehouse_id', '');
        $order_status = $this->getQuery('order_status','');
        if($minprice!='' || $maxprice!=''){
            $amount = ($minprice!=''? " toi.goods_amount>=$minprice":'') . ($minprice!='' && $maxprice!='' ? ' and ':'') . ($maxprice!='' ? " toi.goods_amount<=$maxprice ": '' );
        }
        if(trim($ktime) != '' || trim($gtime) != ''){
            $add_time = (!empty($ktime) ? ' toi.add_time>='.strtotime($ktime) : '').((!empty($ktime) && !empty($gtime))?' and ':'').(!empty($gtime)?' toi.add_time<='.(strtotime($gtime)+86399).' ':'');
        }
        // 查询参数
        $where = array(
            'order_number' => $orderid,
            'goods_amount' => $amount,
            'add_time' => $add_time,
            'order_status' => ($order_status > 0)?$order_status:'',
            'company_id' => $company_id,
            'warehouse_id' => ($warehouse_id > 0)?$warehouse_id:'',
            'query_id' => $queryId
        );
        $where = array_filter($where);
        if ( empty($where) ) {
            $Url=$_SERVER['HTTP_REFERER'];
            header("Location:{$Url}");
            die;
        }
        $array = array();
        $orderlist = array();
        $order = Service::getInstance('admorder')->getBuyOrderList('', '', $where,'excel');
        foreach ($order as $k => $v) {
            $user = '';
            if ($v['user_id'] > 0) {
                $user = Service::getInstance('user')->getUserInfoById($v['user_id']);
            }
            if($user){
                $city = Service::getInstance('user')->getCityInfoById($user['cid']);
            }
            $cityInfo = $city ? explode('|', $city['description']) : '';
            $order[$k]['province'] = $cityInfo ? $cityInfo[0] : '';
            $order[$k]['city'] = $cityInfo ? $cityInfo[1] : '';
            $order[$k]['contact'] = $city ? $city['contact'] : '';
            $order[$k]['account'] = ($user)?$user['account']:'';
            $order[$k]['comName'] = ($user)?$user['comName']:'';
            unset($user);
            $status = Util::getOrderInfoStatus($v['order_status'],$v['shipping_status'],$v['shipping_type'],$v['pay_status'],$v['sign_status'],$v['pay_id']);
            $order[$k]['status'] = $status[0];
            $order[$k]['companyName'] = $this->db->fetchOne("SELECT c.`companyname` FROM `warehouse` AS w INNER JOIN `company` AS c ON w.`company_id`=c.`id` WHERE w.`id`={$v['warehouse_id']}");
            $order[$k]['company_id'] = $this->db->fetchOne("SELECT c.`id` FROM `warehouse` AS w INNER JOIN `company` AS c ON w.`company_id`=c.`id` WHERE w.`id`={$v['warehouse_id']}");
            $order[$k]['warehouseName'] = $this->db->fetchOne("select warehousename from warehouse where id={$v['warehouse_id']}");
            if ($export_type == 2) {
                $array[$v['id']] = Service::getInstance('admorder')->orderGoods($v['id']);
            }
            $orderlist[$v['id']] = $order[$k];
        }
        unset($order);
        $order = array();
        $i=0;
        if ($export_type == 2) {
            foreach ($orderlist as $keys => $vals) {
                foreach ($array as $key => $val) {
                    if ( $keys == $key) {
                        foreach ($val as $k => $v) {
                            $order[$i] = $vals;
                            $order[$i]['order_goods'] = $v;
                            $order[$i]['pay_money'] = $v['goods_money'] * $v['goods_number'];
                            $i++;
                        }
                        break;
                    }
                }
            }
        } else {
            foreach ($orderlist as $keys => $vals) {
                $order[$i] = $vals;
                $i++;
            }
        }
        // Util::dump($order);                
        $objPHPExcel = new PHPExcel();
        $objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
        $objPHPExcel->getActiveSheet()->setCellValue('A1', '订单号');
        $objPHPExcel->getActiveSheet()->setCellValue('B1', '订单状态');
        $objPHPExcel->getActiveSheet()->setCellValue('C1', '下单日期');
        $objPHPExcel->getActiveSheet()->setCellValue('D1', '下单时间');
        $objPHPExcel->getActiveSheet()->setCellValue('E1', '支付方式');
        $objPHPExcel->getActiveSheet()->setCellValue('F1', '付款时间');
        $objPHPExcel->getActiveSheet()->setCellValue('G1', '分公司');
        $objPHPExcel->getActiveSheet()->setCellValue('H1', '经销商');
        $objPHPExcel->getActiveSheet()->setCellValue('I1', '发货时间');
        $objPHPExcel->getActiveSheet()->setCellValue('J1', '用户名');
        $objPHPExcel->getActiveSheet()->setCellValue('K1', '店铺名');
        $objPHPExcel->getActiveSheet()->setCellValue('L1', '收货人');
        $objPHPExcel->getActiveSheet()->setCellValue('M1', '收货手机号');
        $objPHPExcel->getActiveSheet()->setCellValue('N1', '收货地址');
        $objPHPExcel->getActiveSheet()->setCellValue('O1', '订单备注');
        $objPHPExcel->getActiveSheet()->setCellValue('Q1', '省');
        $objPHPExcel->getActiveSheet()->setCellValue('R1', '市(区)');
        $objPHPExcel->getActiveSheet()->setCellValue('S1', '联系人');
        if ($export_type == 2) {
            $objPHPExcel->getActiveSheet()->setCellValue('P1', '商品品类');
            $objPHPExcel->getActiveSheet()->setCellValue('Q1', '商品品牌');
            $objPHPExcel->getActiveSheet()->setCellValue('R1', '商品编号');
            $objPHPExcel->getActiveSheet()->setCellValue('S1', '商品名称');
            $objPHPExcel->getActiveSheet()->setCellValue('T1', '数量');
            $objPHPExcel->getActiveSheet()->setCellValue('U1', '价格');
            $objPHPExcel->getActiveSheet()->setCellValue('V1', '活动');
            $objPHPExcel->getActiveSheet()->setCellValue('W1', '活动赠品');
            $objPHPExcel->getActiveSheet()->setCellValue('X1', '小计');
        } else {
            $objPHPExcel->getActiveSheet()->setCellValue('P1', '小计');
        }

        // 品牌、品类
        $list = Service::getInstance('goods')->typeAlllist();
        $brand = array();
        if (!empty($list['brand_list'])) {
            foreach ($list['brand_list'] as $k => $v) {
                $brand[$v['id']] = $v['name'];
            }
        }
        $goods_type = array();
        if (!empty($list['goods_type_list'])) {
            foreach ($list['goods_type_list'] as $k => $v) {
                $goods_type[$v['id']] = $v['name'];
            }
        }
        unset($list);
        // 循环导出excel
        $count = count($order);
        $e = 0;
        for ($i = 0; $i < $count; $i++) {
            $e = $i+2;
            $objPHPExcel->getActiveSheet()->setCellValueExplicit('A' . $e, $order[$i]['order_number'], PHPExcel_Cell_DataType::TYPE_STRING);
            $objPHPExcel->getActiveSheet()->setCellValue('B' . $e, $order[$i]['status']);
            $objPHPExcel->getActiveSheet()->setCellValue('C' . $e, ($order[$i]['add_time']?date('Y-m-d', $order[$i]['add_time']):'无'));
            $objPHPExcel->getActiveSheet()->setCellValue('D' . $e, ($order[$i]['add_time']?date('H:i', $order[$i]['add_time']):'无'));
            $objPHPExcel->getActiveSheet()->setCellValue('E' . $e, ($order[$i]['pay_id'] == 1)?'微信':($order[$i]['pay_id'] == 2 ? '支付宝' : ($order[$i]['pay_id'] == 4?"微信公众号支付":($order[$i]['pay_id'] == 5?'易极付':'无'))));
            $objPHPExcel->getActiveSheet()->setCellValue('F' . $e, ($order[$i]['pay_time']?date('Y-m-d H:i', $order[$i]['pay_time']):'无'));
            $objPHPExcel->getActiveSheet()->setCellValue('G' . $e, $order[$i]['companyName']);
            $objPHPExcel->getActiveSheet()->setCellValue('H' . $e, $order[$i]['warehouseName']);
            $objPHPExcel->getActiveSheet()->setCellValue('I' . $e, ($order[$i]['shipments_time']?date('Y-m-d H:i', $order[$i]['shipments_time']):'无'));
            $objPHPExcel->getActiveSheet()->setCellValue('J' . $e, $order[$i]['account']);
            $objPHPExcel->getActiveSheet()->setCellValue('K' . $e, $order[$i]['comName']);
            $objPHPExcel->getActiveSheet()->setCellValue('L' . $e, $order[$i]['consignee']);
            $objPHPExcel->getActiveSheet()->setCellValue('M' . $e, $order[$i]['tel']);
            $objPHPExcel->getActiveSheet()->setCellValue('N' . $e, $order[$i]['order_address']);
            $objPHPExcel->getActiveSheet()->setCellValue('O' . $e, ($order[$i]['postscript'])?$order[$i]['postscript']:'无');
            $objPHPExcel->getActiveSheet()->setCellValue('Q' . $e, ($order[$i]['province'])?$order[$i]['province']:'无');
            $objPHPExcel->getActiveSheet()->setCellValue('R' . $e, ($order[$i]['city'])?$order[$i]['city']:'无');
            $objPHPExcel->getActiveSheet()->setCellValue('S' . $e, ($order[$i]['contact'])?$order[$i]['contact']:'无');
            $objPHPExcel->getActiveSheet()->setCellValue('T' . $e, ($order[$i]['warehouse_id'])?$order[$i]['warehouse_id']:'');
            $objPHPExcel->getActiveSheet()->setCellValue('U' . $e, ($order[$i]['company_id'])?$order[$i]['company_id']:'');
            if (!empty($order[$i]['order_goods']) && $export_type == 2 ) { // 商品信息
                $order_goods = $order[$i]['order_goods'];
                $objPHPExcel->getActiveSheet()->setCellValue('P' . $e, ($goods_type[$order_goods['goods_type']]?$goods_type[$order_goods['goods_type']]:'无'));
                $objPHPExcel->getActiveSheet()->setCellValue('Q' . $e, ($brand[$order_goods['brand_id']]?$brand[$order_goods['brand_id']]:'无'));
                $objPHPExcel->getActiveSheet()->setCellValue('R' . $e, $order_goods['goods_id']);
                $objPHPExcel->getActiveSheet()->setCellValue('S' . $e, $order_goods['goods_name']);
                $objPHPExcel->getActiveSheet()->setCellValue('T' . $e, $order_goods['goods_number']);
                $objPHPExcel->getActiveSheet()->setCellValue('U' . $e, $order_goods['goods_money']);
                if (isset($order_goods['activity_info'])) { // 活动
                    $objPHPExcel->getActiveSheet()->setCellValue('V' . $e, ($order_goods['activity_info']['name'])?$order_goods['activity_info']['name']:'无活动信息');
                    if (isset($order_goods['activity_info']['gifts_list'])) {
                        $objPHPExcel->getActiveSheet()->setCellValue('W' . $e, $order_goods['activity_info']['gifts_list']['goods_name']);
                    } else {
                        $objPHPExcel->getActiveSheet()->setCellValue('W' . $e, '无赠品信息');
                    }
                } else {
                    $objPHPExcel->getActiveSheet()->setCellValue('V' . $e, '无活动信息');
                    $objPHPExcel->getActiveSheet()->setCellValue('W' . $e, '无赠品信息');
                }
                $objPHPExcel->getActiveSheet()->setCellValue('X' . $e, $order[$i]['pay_money']);
                $objPHPExcel->getActiveSheet()->setCellValue('Y' . $e, ($order[$i]['warehouse_id'])?$order[$i]['warehouse_id']:'');
                $objPHPExcel->getActiveSheet()->setCellValue('Z' . $e, ($order[$i]['company_id'])?$order[$i]['company_id']:'');
            } else {
                $objPHPExcel->getActiveSheet()->setCellValue('P' . $e, $order[$i]['pay_money']);
            }
        }

        $FileName = 'order.xlsx';
        // sheet名称
        $objPHPExcel->getActiveSheet()->setTitle('订单信息');
        $objPHPExcel->setActiveSheetIndex(0);
        //到文件
//         $objWriter->save($FileName);
        
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
        unset($order);die;
    }
    
    public function convertUTF8($str)
    {
        if(empty($str)) return '';
        return  iconv('gb2312', 'utf-8', $str);
    }
    
    public function orderArr($order, $order_goods) {
        return $order['order_goods'] = $order_goods;
    }
}