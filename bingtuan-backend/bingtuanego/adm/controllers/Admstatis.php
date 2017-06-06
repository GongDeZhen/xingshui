<?php

/**
 * 
 * @author 石晓凯
 * 
 * 后台统计
 *  */
class AdmstatisController extends BaseController{
    public function init()
    {
        parent::init();
    }

    /**
     *   
     *   销售统计列表
     *   */
    public function salelistAction()
    {
        // 分页
        $pag = $this->getQuery('page','')?$this->getQuery('page',''):'1';
        $queryId = $this->getQuery('company_id',0);
        $company_id = ($this->_view->rbac == '*') ? 0 : $this->companyIds;
        $pageSize = 10;
//         $pags = ($pag*$pageSize)-$pageSize;
        //公司
        if($company_id){
            $this->_view->getwareho = Service::getInstance('Admstatis')->getwareho($company_id);
        }else{
            $this->_view->getwareho = Service::getInstance('Admstatis')->getwareho();
        }
        $company_id = $this->getQuery('company_id',0) ? : $company_id;
        //统计
        $data = Service::getInstance('Admstatis')->getwarehouse($_GET,$company_id, $pag,$pageSize);
        $this->_view->getCompanyList = $data['0'];
        //var_dump($data);
        //$this->_view->getCompanyList = $data['list'];
        $get = $_GET;
        unset($get['page']);
        $url = "http://".$_SERVER['SERVER_NAME']."/Admstatis/salelist?page=__page__&".http_build_query($get);
        $this->_view->paglist = Util::buildPagebar($data['1']['cc'],$pageSize,$pag,$url);
        $this->_view->pag = $pag;
        //平台销售统计所有数据
        $this->_view->platform = Service::getInstance('Admstatis')->getplatform($get);
        $this->_view->get = $_GET;
 
    }
    /**
     *
     *   单品销量
     *   */
    public function singlelistAction()  
    { 
        $userinfo = Yaf_Registry::get('developer');
        // 公司、经销商
        $where['company_id'] = ($this->_view->rbac == '*') ? 0 : $this->companyIds ;
        $queryId = $this->getQuery('company_id',0);
        if($queryId){
            $where['query_id'] = $queryId;
        }
        // 分页
        $page = $this->getQuery('page','')?$this->getQuery('page',''):'1';
        $pageSize = 10;
        $get = $_GET;
        $pags = ($pag*$pageSize)-$pageSize;
        // 时间
        $where['ktime'] = $this->getQuery('ktime','');
        $where['gtime'] = $this->getQuery('gtime','');
        // 品牌
        $where['brand'] = $this->getQuery('brand','');
        // 品类
        $where['goods_type'] = $this->getQuery('goods_type','');
        //商品名称
        $where['goods_name'] = $this->getQuery('goods_name','');
        //排序
        $where['goods_total'] = $this->getQuery('goods_total','');
        $where['goods_count'] = $this->getQuery('goods_count','');
        $this->_view->getBrand =  Service::getInstance('Admgoods')->getBrand();//所有品牌
        $this->_view->get_goods_type =  Service::getInstance('Admgoods')->get_goods_type();//所有品类
        if($where['company_id']){
            $this->_view->getCompanyList = Service::getInstance('region')->getCompanyList($where['company_id']);//所有公司
        }else{
            $this->_view->getCompanyList = Service::getInstance('region')->getCompanyList();//所有公司
        }

        //$list = Service::getInstance('Admstatis')->getsingList($where['company_id'],$where['brand'],$where['goods_type'],$where['goods_name'],strtotime($where['gtime']),strtotime($where['ktime']),$page,$pageSize,$sort);
        $list = Service::getInstance('Admstatis')->getsingList($where,$page,$pageSize);
        $count = $list['1']['count'];
        //array_pop($list);
        $this->_view->list = $list['0'];
        $this->_view->total_money = $list['2']['total_money'];
        $this->_view->total_count = $list['2']['total_count'];
        $where['company_id'] = $queryId;
        $url = "http://".$_SERVER['SERVER_NAME']."/admstatis/singlelist?page=__page__&".http_build_query($where);
        $this->_view->get = $_GET;
        $this->_view->paglist = Util::buildPagebar($count,$pageSize,$page,$url);
    }
    /**
     *
     *   品牌销售额
     *   */
    public function brandlistAction()
    {
        $userinfo = Yaf_Registry::get('developer');
        // 公司、经销商
        $where['company_id'] = ($this->_view->rbac == '*') ? 0 : $this->companyIds ;
        $queryId = $this->getQuery('company_id',0);
        if($queryId){
            $where['query_id'] = $queryId;
        }
        // 分页
        $page = $this->getQuery('page','')?$this->getQuery('page',''):'1';
        $pageSize = 10;
        $get = $_GET;
        $pags = ($pag*$pageSize)-$pageSize;
        // 时间
        $where['ktime'] = $this->getQuery('ktime','');
        $where['gtime'] = $this->getQuery('gtime','');
        // 品牌
        $where['brand'] = $this->getQuery('brand','');
        //品类
        $where['goods_type'] = $this->getQuery('goods_type','');
        //排序
        $where['brand_total'] = $this->getQuery('brand_total','');
        $where['bcount'] = $this->getQuery('bcount','');

        $this->_view->getBrand =  Service::getInstance('Admgoods')->getBrand();//所有品牌
        $this->_view->get_goods_type =  Service::getInstance('Admgoods')->get_goods_type();//所有品类
        if($where['company_id']){
            $this->_view->getCompanyList = Service::getInstance('region')->getCompanyList($where['company_id']);//所有公司
        }else{
            $this->_view->getCompanyList = Service::getInstance('region')->getCompanyList();//所有公司
        }

        $list = Service::getInstance('Admstatis')->getbrandList($where,$page,$pageSize);
        $list1 = Service::getInstance('Admstatis')->getbrandInfo($where);
        $this->_view->p_total = $list1['total'];
        $this->_view->p_total_count = $list1['total_count'];
        $this->_view->list = $list['0'];
        $count = $list['1']['b_count'];
        $where['company_id'] = $queryId;
        $url = "http://".$_SERVER['SERVER_NAME']."/admstatis/brandlist?page=__page__&".http_build_query($where);
        $this->_view->paglist = Util::buildPagebar($count,$pageSize,$page,$url);
        $this->_view->get = $_GET;
        $this->_view->count = $list['1']['total_count'];
        $this->_view->total = $list['1']['total'];
        $this->_view->count1 = $list['2']['total_count']; //经销商总销量
        $this->_view->total1 = $list['2']['total'];//经销商总销售额
        //var_dump($list['2']['total']);
//         $list = Service::getInstance('Admstatis')->getbrandList($where,$page,$pageSize);
//         $b_count = $list['0']['b_count'];
//         $total =$list['1']['btotal_money'];
//         $count =$list['1']['brand_count'];
//         array_pop($list['0']);
//         $url = "http://".$_SERVER['SERVER_NAME']."/admstatis/brandlist?page=__page__&".http_build_query($where);
//         $this->_view->paglist = Util::buildPagebar($b_count,$pageSize,$page,$url);
//         $this->_view->list = $list['0'];
//         $this->_view->get = $_GET;
//         //平台品牌统计所有数据
//         $this->_view->platform = Service::getInstance('Admstatis')->getplatform_2($_GET);
//         $this->_view->count = $count;
//         $this->_view->total = $total;
    }
    /**
     * 单品牌销量
     *   
     *   */
    public function onebrandAction()
    {
        $userinfo = Yaf_Registry::get('developer');
        // 公司、经销商
        $where['company_id'] = ($this->_view->rbac == '*') ? 0 : $this->companyIds ;
        $queryId = $this->getQuery('company_id',0);
        if($queryId){
            $where['query_id'] = $queryId;
        }
        // 分页
        $page = $this->getQuery('page','')?$this->getQuery('page',''):'1';
        $pageSize = 10;
        $get = $_GET;
        //         var_dump($get);
        $pags = ($pag*$pageSize)-$pageSize;
        // 时间
        $where['ktime'] = $this->getQuery('ktime');
        $where['gtime'] = $this->getQuery('gtime');
        // 品牌
        $where['brand'] = $this->getQuery('brand','');
        //品类
        $where['goods_type'] = $this->getQuery('goods_type','');
        $where['goods_type'] = $this->getQuery('goods_type','');
        //排序
        $where['date'] = $this->getQuery('date','');
        $where['today_money'] = $this->getQuery('today_money','');
        $where['today_count'] = $this->getQuery('today_count','');
        
        $this->_view->getBrand =  Service::getInstance('Admgoods')->getBrand();//所有品牌
        $this->_view->get_goods_type =  Service::getInstance('Admgoods')->get_goods_type();//所有品类
        if($where['company_id']){
            $this->_view->getCompanyList = Service::getInstance('region')->getCompanyList($where['company_id']);//所有公司
        }else{
            $this->_view->getCompanyList = Service::getInstance('region')->getCompanyList();//所有公司
        }

        $this->_view->get = $get;
        $list = Service::getInstance('Admstatis')->getonebrand($where,$page,$pageSize);
//         var_dump($list);
        $this->_view->brand = $where['brand'];
        $this->_view->goods_type = $where['goods_type'];
        $where['company_id'] = $queryId;
        $url = "http://".$_SERVER['SERVER_NAME']."/admstatis/onebrand?page=__page__&".http_build_query($where);
        $this->_view->list = $list['0'];
        $this->_view->bcount = $list['1']['bcount'];
        $this->_view->total_money = $list['1']['total_money'];
        $this->_view->startTime = $list['1']['startTime'] ? $list['1']['startTime'] : false;
        $this->_view->endTime = $list['1']['endTime'] ? $list['1']['endTime'] : false;
        $count1 = $list['2']['count'];
        $this->_view->paglist = Util::buildPagebar($count1,$pageSize,$page,$url);
    }
    /**
     * 终端店统计
     *   */
    public function storelistAction()
    {
        /*$userinfo = Yaf_Registry::get('developer');
        // 公司、经销商
        $where['company_id'] = ($this->_view->rbac == '*') ? $this->getQuery('company_id',0) : $userinfo['company_id'] ;
        // 分页
        $page = $this->getQuery('page','')?$this->getQuery('page',''):'1';
        $pageSize = 10;
        $pags = ($pag*$pageSize)-$pageSize;
        // 时间
        $where['ktime'] = $this->getQuery('ktime','');
        $where['gtime'] = $this->getQuery('gtime','');
        //var_dump($where);
        $this->_view->getCompanyList = Service::getInstance('region')->getCompanyList();//所有公司
        $list = Service::getInstance('Admstatis')->getstoreList($where['company_id'],$where['gtime'],$where['ktime'],$page,$pageSize);
//        var_dump($list);
        $b_count = $list['count'];
        array_pop($list);
        //平台销售统计所有数据
        $this->_view->platform = Service::getInstance('Admstatis')->getplatform_1($_GET);
        $url = "http://".$_SERVER['SERVER_NAME']."/admstatis/storelist?page=__page__&".http_build_query($where);
        $get = $_GET;
        $this->_view->get = $_GET;
        $this->_view->paglist = Util::buildPagebar($b_count,$pageSize,$page,$url);
        $this->_view->list = $list;*/
        //获取公司ID
        $company_id = ($this->_view->rbac == '*') ? 0 : $this->companyIds ;
        $queryId = $this->getQuery('company_id','');
        if ($company_id){
            $sql = Util::companyIdIsArray($company_id);
            if($sql != 1){
                if($queryId){
                    $companyid = ' cid ='.$queryId;
                }else{
                    $companyid = ' c'.$sql;
                }
            }

        }else{
            if($queryId){
                $companyid = ' cid ='.$queryId;
            }else{
                $companyid = '';
            }
        }
        $page = $this->getQuery('page','')?$this->getQuery('page',''):'1';
        //结束值
        $pageSize = 10;
        $first = ($page-1)*$pageSize;
        //获取时间
        $ktime = $this->getQuery('ktime','');
        $gtime = $this->getQuery('gtime','');
        $search = array(
            'company_id'=>$queryId,
            'ktime' => $ktime,
            'gtime' => $gtime,
        );
        if(trim($ktime) != '' || trim($gtime) != ''){
            if ($ktime==$gtime){
                $time = (!empty($ktime) ? ' AND start_time>='.strtotime($ktime) : '').((!empty($ktime) && !empty($gtime))?' and ':'').(!empty($gtime)?' end_time<='.(strtotime($gtime)+3600*24):'');
            }else{
                $time = (!empty($ktime) ? ' AND start_time>='.strtotime($ktime) : '').((!empty($ktime) && !empty($gtime))?' and ':'').(!empty($gtime)?' end_time<='.(strtotime($gtime)+3600*24).' ':'');
            }
        }
        //平台销售统计所有数据
        $this->_view->platform = Service::getInstance('Admstatis')->getplatform_1($_GET);
        if($company_id){
            $this->_view->getCompanyList = Service::getInstance('region')->getCompanyList($company_id);//所有公司
        }else{
            $this->_view->getCompanyList = Service::getInstance('region')->getCompanyList();//所有公司
        }

        if(trim($ktime) == '' && trim($gtime)!='') $time = ' AND '.$time;
        $data = Service::getInstance('Admstatis')->getInfo($companyid,$time,$first,$pageSize);
        foreach ($data['data'] as &$v){
            $list = Service::getInstance('Admstatis')->getOrderNum($v['wid'],$time);
            $v['uorder_1']=$list;
            $lists = Service::getInstance('Admstatis')->getUserOrder($v['wid'],$time);
            $v['uorder'] = $lists;
        }
        $get = $_GET;
        $url = "http://".$_SERVER['SERVER_NAME']."/admstatis/storelist?page=__page__&".http_build_query($search);
        $this->_view->paglist = Util::buildPagebar($data['num'],$pageSize,$page,$url);
        $this->_view->get = $get;
        $this->_view->list = $data['data'];
    }
    public function testAction()
    {
        $cid = '4';
        $order = '10004';
        $arr = Service::getInstance('Admstatis')->add_order($cid,$order);
//         var_dump($arr);
//         exit;
    }
    /**
     *  首页
     *   */
    public function indexlistAction()
    {
        $cIds = $this->companyIds;
        $list = Service::getInstance('Admstatis')->indexList($cIds);
        $list1 = Service::getInstance('Admstatis')->indexList_1($cIds);
        $list2 = Service::getInstance('Admstatis')->indexList_2($cIds);
        $list3 = Service::getInstance('Admstatis')->indexList_3($cIds);
        $get = $_GET;
        $this->_view->get = $_GET;
        $this->_view->info = $list;
        $this->_view->info_1 = $list1;
        $this->_view->info_2 = $list2;
        $this->_view->info_3 = $list3;
      
    }
    public function getwarehouseAction(){
        $id = $this->getPost('cid');
        $list = Service::getInstance('Admstatis')->getwarehouse1($id);
        exit(json_encode($list,true));
    }


    /* 终端统计导出 */
    public function storelistExportAction(){
        set_time_limit(0);
        $company_id = ($this->_view->rbac == '*') ? $this->getQuery('company_id',0) : $this->companyIds ;
        $queryId = $this->getQuery('company_id','');
        if ($company_id){
            $sql = Util::companyIdIsArray($company_id);
            if($sql != 1){
                if($queryId){
                    $companyid = ' cid ='.$queryId;
                }else{
                    $companyid = ' c'.$sql;
                }
            }

        }else{
            $companyid = '';
        }
        $page = $this->getQuery('page','')?$this->getQuery('page',''):'1';
        //结束值
        $pageSize = 100000;
        $first = ($page-1)*$pageSize;
        //获取时间
        $ktime = $this->getQuery('ktime','');
        $gtime = $this->getQuery('gtime','');
        if(trim($ktime) != '' || trim($gtime) != ''){
            if ($ktime==$gtime){
                $time = (!empty($ktime) ? ' AND start_time>='.strtotime($ktime) : '').((!empty($ktime) && !empty($gtime))?' and ':'').(!empty($gtime)?' end_time<='.(strtotime($gtime)+3600*24):'');
            }else{
                $time = (!empty($ktime) ? ' AND start_time>='.strtotime($ktime) : '').((!empty($ktime) && !empty($gtime))?' and ':'').(!empty($gtime)?' end_time<='.(strtotime($gtime)+3600*24).' ':'');
            }
        }
        //平台销售统计所有数据
        $total_data = Service::getInstance('Admstatis')->getplatform_1($_GET);
        //构造平台的数据
        $total_data['cname'] = '平台';
        $total_data['wname'] = '平台';
        $total_data['ucount'] =$total_data['total_terminal'];
        $total_data['uorder'] =$total_data['zongorderwu'];
        $total_data['uorder_1'] =$total_data['total_terminal_1'];
        $total_data['uorder_1'] =$total_data['total_terminal_1'];
        $total_data['order_money'] =$total_data['zong_money'];
        $total_data['ordernum'] =$total_data['zong_effective'];
        $data = Service::getInstance('PlatformExcel')->getInfo($companyid,$time);
        foreach ($data['data'] as &$v){
            $list = Service::getInstance('PlatformExcel')->getOrderNum($v['wid'],$time);
            $v['uorder_1']=$list;
            $lists = Service::getInstance('PlatformExcel')->getUserOrder($v['wid'],$time);
            $v['uorder'] = $lists['num'];
        }
        //将平台的数据放到最前面
        array_unshift($data['data'],$total_data);
        //定义excel头行的内容
        $arr = array(
            'A1'=> '公司名称',
            'B1'=> '经销商',
            'C1'=> '终端店总数',
            'D1'=> '下单终端总数',
            'E1'=> '转化率',
            'F1'=> '活跃终端店总数',
            'G1'=> '活跃度',
            'H1'=> '客单价',
            'I1'=> '复购率',
            'J1'=> '上线时间',
            'K1'=> '状态',
            'L1'=> '公司id',
            'M1'=> '经销商id',
        );
        //定义excel的内容，根据你查询出来的数据构造需要的字段
        foreach ($data['data'] as $value){
            $arrs['A']=$value['cname'];
            $arrs['B']=$value['wname'];
            $arrs['C']=$value['ucount'];
            $arrs['D']=$value['uorder'];
            $arrs['E']=round((float)(($value["uorder"]/$value['ucount'])*100),2).'%';
            $arrs['F']=$value['uorder_1'];
            $arrs['G']=round((float)(($value["uorder_1"]/$value['ucount'])*100),2).'%';
            $arrs['H']=round($value['order_money']/$value['ordernum'],2);
            $arrs['I']=round($value["ordernum"]/$value['uorder_1'],2);
            $arrs['I']=round($value["ordernum"]/$value['uorder_1'],2);
            $arrs['J']=$value['uptime'];
            $arrs['K']=$value['status'];
            $arrs['L']=$value['wid'] ? $value['wid'] : '';
            $arrs['M']=$value['cid'] ? $value['cid'] : '';
            $array[] =$arrs;
        }
        //定义文件名
        $FileName ='平台终端店统计.xlsx';
        //定义标题
        $setTitle = '终端店统计';
        $this->export($array,$arr,$FileName,$setTitle);
    }

    /* 单品导出 */
    public function singleExportAction(){
      
        set_time_limit(0);
        $userinfo = Yaf_Registry::get('developer');
        // 公司、经销商
        $where['company_id'] = ($this->_view->rbac == '*') ? $this->getQuery('company_id',0) : $userinfo['company_id'] ;
        $queryId = $this->getQuery('company_id',0);
        if($queryId){
            $where['query_id'] = $queryId;
        }
        $get = $_GET;
        $pags = ($pag*$pageSize)-$pageSize;
        // 时间
        $where['ktime'] = $this->getQuery('ktime','');
        $where['gtime'] = $this->getQuery('gtime','');
        // 品牌
        $where['brand'] = $this->getQuery('brand','');
        // 品类
        $where['goods_type'] = $this->getQuery('goods_type','');
        //商品名称
        $where['goods_name'] = $this->getQuery('goods_name','');
        //查询要导出的数据
        $data = Service::getInstance('PlatformExcel')->getsingle($where);
        $info = $data['1'];
        //定义excel的内容，根据你查询出来的数据构造需要的字段
        foreach ($data['0'] as $value){
            $arr['A']=$value['type_name'];
            $arr['B']=$value['bname'];
            $arr['C']=$value['goods_id'];
            $arr['D']=$value['goods_name'];
            $arr['E']=$value['goods_total']?$value['goods_total']:0;
            $arr['F']=round((float)(($value["goods_total"]/$info['total_money'])*100),2).'%';
            $arr['G']=$value['goods_count']?$value['goods_count']:0;
            $arr['H']=round((float)(($value["goods_count"]/$info['total_count'])*100),2).'%';
            $arr['I']=$value['pid'];
            $arr['J']=$value['standard'];
            $array[] =$arr;
        }
//         //定义excel头行的内容
        $arr1 = array(
            'A1'=> '品类',
            'B1'=> '品牌',
            'C1'=> '商品ID',
            'D1'=> '商品名称',
            'E1'=> '销售额',
            'F1'=> '销售额占比',
            'G1'=> '数量',
            'H1'=> '数量占比',
            'I1'=> 'pid',
            'J1'=> '规格'
        );
        //定义文件名
        $FileName ='平台单品统计.xlsx';
        //定义标题
        $setTitle = '单品统计';
        $this->export($array,$arr1,$FileName,$setTitle);
    }
    /* 品牌导出 */
    public function brandExportAction(){
        set_time_limit(0);
        $userinfo = Yaf_Registry::get('developer');
        // 公司、经销商
        $where['company_id'] = ($this->_view->rbac == '*') ? $this->getQuery('company_id',0) : $this->companyIds ;
        $queryId = $this->getQuery('company_id',0);
        if($queryId){
            $where['query_id'] = $queryId;
        }
        $get = $_GET;
        $pags = ($pag*$pageSize)-$pageSize;
        // 时间
        $where['ktime'] = $this->getQuery('ktime','');
        $where['gtime'] = $this->getQuery('gtime','');
        // 品牌
        $where['brand'] = $this->getQuery('brand','');
        // 品类
        $where['goods_type'] = $this->getQuery('goods_type','');
        //商品名称
        $where['goods_name'] = $this->getQuery('goods_name','');
        //查询要导出的数据
        $data = Service::getInstance('PlatformExcel')->getbrand($where);
        $info = $data['1'];
        //定义excel的内容，根据你查询出来的数据构造需要的字段
        foreach ($data['0'] as $value){
            $arr['A']=$value['tname'];
            $arr['B']=$value['bname'];
            $arr['C']=$value['brand_total']?$value['brand_total']:0;
            $arr['D']=round((float)((($value["brand_total"]?$value["brand_total"]:0)/$info['total'])*100),2).'%';
            $arr['E']=$value['total_count']?$value['total_count']:0;
            $arr['F']=round((float)((($value["total_count"]?$value["total_count"]:0)/$info['total_count'])*100),2).'%';
            $array[] =$arr;
        }
        //定义excel头行的内容
        $arr1 = array(
            'A1'=> '品类',
            'B1'=> '品牌',
            'C1'=> '销售额 ',
            'D1'=> '销售额占比',
            'E1'=> '数量',
            'F1'=> '数量占比'
        );
        //定义文件名
        $FileName ='平台品牌统计.xlsx';
        //定义标题
        $setTitle = '品牌统计';
        $this->export($array,$arr1,$FileName,$setTitle);
    }
    /* 品牌导出 */
    public function onebrandExportAction(){
        set_time_limit(0);
        $userinfo = Yaf_Registry::get('developer');
        // 公司、经销商
        $where['company_id'] = ($this->_view->rbac == '*') ? $this->getQuery('company_id',0) : $userinfo['company_id'] ;
        $queryId = $this->getQuery('company_id',0);
        $where['query_id'] = $queryId;
        $get = $_GET;
        $pags = ($pag*$pageSize)-$pageSize;
        // 时间
        $where['ktime'] = $this->getQuery('ktime','');
        $where['gtime'] = $this->getQuery('gtime','');
        // 品牌
        $where['brand'] = $this->getQuery('brand','');
        // 品类
        $where['goods_type'] = $this->getQuery('goods_type','');
        //查询要导出的数据
        $data = Service::getInstance('Platformexcel')->getonebrand($where);
        $info = $data['1'];
        //定义excel头行的内容
        $arr1 = array(
            'A1'=> '开始日期',
            'B1'=> '结束日期',
            'C1'=> '品类',
            'D1'=> '品牌',
            'E1'=> '销售额',
            'F1'=> '销售额占比',
            'G1'=> '数量',
            'H1'=> '数量占比',
            'I1'=> '公司名称'
        );
        //定义excel的内容，根据你查询出来的数据构造需要的字段
        foreach ($data['0'] as $value){
            $arr['A'] = $value["k"]?date('Y-m-d',$value["k"]):'暂无';
            $arr['B'] = $value["j"]?date('Y-m-d',$value["j"]):'暂无';
            $arr['C'] = $value['tname'];
            $arr['D'] = $value['bname'];
            $arr['E'] = $value['today_money']?$value['today_money']:0;
            $arr['F'] = round((float)((($value["today_money"]?$value["today_money"]:0)/$info['total_money'])*100),2).'%';
            $arr['G'] = $value['today_count']?$value['today_count']:0;
            $arr['H'] = round((float)((($value["today_count"]?$value["today_count"]:0)/$info['bcount'])*100),2).'%';
            $arr['I'] = $value['companyname']?$value['companyname']:'暂无';
            $array[] =$arr;
        }
        //定义文件名
        $FileName ='平台单品牌统计.xlsx';
        //定义标题
        $setTitle = '单品牌统计';
        $this->export($array,$arr1,$FileName,$setTitle);
    }
    /* 销售统计导出 */
    public function saleExportAction()
    {
        set_time_limit(0);
        $userinfo = Yaf_Registry::get('developer');
        // 公司、经销商
        $where['company_id'] = ($this->_view->rbac == '*') ? $this->getQuery('company_id',0) : $this->companyIds ;
        $queryId = $this->getQuery('company_id',0);
        if($queryId){
            $where['query_id'] = $queryId;
        }
        // 时间
        $where['ktime'] = $this->getQuery('ktime','');
        $where['gtime'] = $this->getQuery('gtime','');
        //查询要导出的数据
        $data = Service::getInstance('PlatformExcel')->getsale($where);
        //var_dump($data);
        //定义excel头行的内容
        $arr1 = array(
            'A1'=> '经销商名称',
            'B1'=> '上线天数',
            'C1'=> '当日销售额',
            'D1'=> '当日有效订单数',
            'E1'=> '当月销售额',
            'F1'=> '当月有效订单数',
            'G1'=> '累积销售额',
            'H1'=> '累积有效订单数',
            'I1'=> '累积终端店数',
            'J1'=> '转化率',
            'K1'=> '活跃度',
            'L1'=> '日单量',
            'M1'=> '日新增终端'
        );
        foreach ($data as $value){
            $arr['A'] = $value['wname'];
            $arr['B'] = intval((time()-$value['operate_days'])/(60*60*24));
            $arr['C'] = (isset($value['day_amount']) && !empty($value['day_amount'])) ? $value['day_amount'] : 0 ;
            $arr['D'] = (isset($value['day_num']) && !empty($value['day_num'])) ? $value['day_num'] : 0 ;
            $arr['E'] = (isset($value['month_amount']) && !empty($value['month_amount'])) ? $value['month_amount'] : 0;
            $arr['F'] = (isset($value['month_num']) && !empty($value['month_num'])) ? $value['month_num'] : 0;
            $arr['G'] = (isset($value['total']) && !empty($value['total'])) ? $value['total'] : 0;
            $arr['H'] = (isset($value['count']) && !empty($value['count']))  ? $value['count'] : 0;
            $arr['I'] = $value['unum'];
            $arr['J'] = round((float)(empty($value['ucount_s'])?0:(empty($value['unum'])?0:($value['ucount_s']/$value['unum'])*100)),2).'%';
            $arr['K'] = round((float)(empty($value['ucount'])?0:(empty($value['unum'])?0:($value['ucount']/$value['unum'])*100)),2).'%';
            $arr['L'] = round(((isset($value['count']) && !empty($value['count'])) ? $value['count'] : 0) / intval((time()-$value['operate_days'])/(60*60*24)),1);
            $arr['M'] = round($value['unum'] / intval((time()-$value['operate_days'])/(60*60*24)),1);
            $array[] =$arr;
        }
        //定义文件名
        $FileName ='平台销售统计.xlsx';
        //定义标题
        $setTitle = '销售统计';
        $this->export($array,$arr1,$FileName,$setTitle);
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
                $objPHPExcel->getActiveSheet()->setTitle($setTitle);
                $objPHPExcel->setActiveSheetIndex(0);
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
}