<?php
class GoodsController extends BaseController
{
    protected $appid;

    public function init()
    {
        parent::init();
        
        $this->_view->_module = 'dev';        
        $this->_view->_moduleName = '后台管理';
    }

    /**
     * 后台商品列表
     */
    public function listAction()
    {
        $userinfo = Yaf_Registry::get('developer');
        $queryId = $this->getQuery('company_id','');

        // 公司、经销商
        $where['company_id'] = ($this->_view->rbac == '*') ? 0 : $this->companyIds ;
        if($queryId) {
            $where['pid'] = 0;
            $where['query_id'] = $queryId;
        }

        // 分页
        $pag = $this->getQuery('page','')?$this->getQuery('page',''):'1';
        $pageSize = 10;
        $pags = ($pag*$pageSize)-$pageSize;
        $sorank = $this->getQuery('sorank','');
        // 时间
        $ktime = $this->getQuery('ktime','');
        $gtime = $this->getQuery('gtime','');
        // 价格
        $minprice = $this->getQuery('minprice','');
        $maxprice = $this->getQuery('maxprice','');
        // 品牌
        $where['brand'] = $this->getQuery('brand','');
        // 品类
        $where['goods_type'] = $this->getQuery('goods_type','');
        // 优惠卷 / 活动
        $where['coupons'] = $this->getQuery('coupons','');
        $where['activity'] = $this->getQuery('activity','');
        // 商品名称
        $where['goods_name'] = $this->getQuery('goods_name','');
        //规格
        //$where['standard'] = $this->getQuery('standard', '');
        
        //排序
        if ($_GET['amount']) {
            $where['amount'] = $this->getQuery('amount','');
        }
        if ($_GET['is_show']) {
            $where['is_show'] = $this->getQuery('is_show','');
        }
        $user = Yaf_Registry::get('developer');
        //查询优惠券
        $this->_view->coupons = Service::getInstance('Admgoods')->getCoupons($this->companyIds);
        //查询品牌
        $this->_view->getBrand =  Service::getInstance('Admgoods')->getBrand();
        $this->_view->brands = array_column($this->_view->getBrand, 'name', 'id');
        $brandNames = [];
        foreach($this->_view->getBrand as $value){
            $brandNames[] = $value['name'];
        }
        $this->_view->brandNames = json_encode($brandNames);
        $this->_view->brandList = json_encode($this->_view->getBrand);
        //获取所有分类
        $this->_view->get_goods_type =  Service::getInstance('Admgoods')->get_goods_type();
        //获取所有活动
        $this->_view->activity = Service::getInstance('Admgoods')->getActivity($this->companyIds);
        /////////////
        $this->_view->user = $user;

        ////////////
        //获取价格区间
        if($minprice!='' || $maxprice!=''){
            $where['all_price'] = ($minprice!=''? " g.all_price>=$minprice":'') . ($minprice!='' && $maxprice!='' ? ' and ':'') . ($maxprice!='' ? " g.all_price<=$maxprice ": '' );
        }
        //创建的时间区间
        if(trim($ktime) != '' || trim($gtime) != ''){
           $where['create_time'] = (!empty($ktime) ? ' g.create_time>='.strtotime($ktime) : '').((!empty($ktime) && !empty($gtime))?' and ':'').(!empty($gtime)?' g.create_time<='.strtotime($gtime).' ':'');
        }

        $goods_list = Service::getInstance('admgoods')->getlist($pags,$pageSize,$where);
        foreach ($goods_list as $k => $v) {
            if(is_array($v[0])){
                foreach($v as $key => $value){
                    $goods_list[$k][$key]['goods_img'] = Util::getpicbyhash($value['goods_img']);
                    $goods_list[$k][$key]['goods_thumb'] = Util::getpicbyhash($value['goods_thumb']);
                }
            }else{
                $goods_list[$k]['goods_img'] = Util::getpicbyhash($v['goods_img']);
                $goods_list[$k]['goods_thumb'] = Util::getpicbyhash($v['goods_thumb']);
            }
        }

        if(is_array($goods_list[0][0])){
            $list = [];
            foreach($goods_list as $goods){
                foreach($goods as $good){
                    $list[] = $good;
                }
            }

            $this->_view->goods_list= $list;

        }else{
            $this->_view->goods_list= $goods_list;
        }

        $sql=Service::getInstance('admgoods')->getGoodslistsql($where, 1);

        $rank = explode('_',$sorank);
        $rank[1] = $rank[1]=='asc'?'desc':'asc';
        $sorank = implode('_',$rank);

        $soarray = array('time','money','brand','category','company_id');
        foreach($soarray as $v){
            if($rank[0]==$v){
                $$v = $sorank;
            }else{
                $$v = $v."_desc";
            }
        }
        $get = $_GET;

        $get['company_id'] = $_GET['company_id'];
        $this->_view->get = $get;
        if($where['company_id']){
            $this->_view->getCompanyList = Service::getInstance('region')->getCompanyList($where['company_id']);
        }else{
            $this->_view->getCompanyList = Service::getInstance('region')->getCompanyList();
        }

        $where['company_id'] = $queryId;
        $url = "http://".$_SERVER['SERVER_NAME']."/goods/list?page=__page__&".http_build_query($where);
        if($this->getQuery('time','')) $url.="&sorank=".$sorank;

        $this->_view->paglist =Service::getInstance('admgoods')->getGoodsPags($sql,$goods_list, $pageSize,$pag,$url);
    }
    
    /**
     * 后台查看商品
     */
    public function goodsInfoAction()
    {
        $id = $this->getQuery('id',0);
        $goods = Service::getInstance('admgoods')->getGoodsInfo($id);
        $this->_view->goods_info= $goods;
    }
    /**
     * 后台添加商品
     */
    public function addAction()
    {
        $user = Yaf_Registry::get('developer');
        $goods_name = $this->getPost('goods_name');
        $goods_abbreviation = $this->getPost('goods_abbreviation');
        $goods_type = $this->getPost('goods_type');
        $brand_id = $this->getPost('brand_id');
        $city_price = (float) $this->getPost('city_price');
        $all_price = (float) $this->getPost('all_price');
        $amount = 0;
        $goods_weight = (float)$this->getPost('goods_weight');
        $packaging = $this->getPost('packaging');
        $origin = $this->getPost('origin');
        $sort = $this->getPost('sort');
        $standard = $this->getPost('standard');
        $production_time = $this->getPost('production_time');
        $storage = $this->getPost('storage');
        $shelf_life = $this->getPost('shelf_life');
        
        $province = $this->getPost('province');
        $goods_desc = $this->getPost('goods_desc');
        $couponsArr = $this->getPost('coupons');
        //$companyID = ($this->_view->rbac == '*') ? $this->getPost('cid',0) : $user['company_id'];
        $unit = $this->getPost('unit');

        $goods = Service::getInstance('admgoods')->getGoods();
        //$company = Service::getInstance('company')->getCompany();
        $coupons = Service::getInstance('admgoods')->getCouponsList();
        $this->_view->getCompanyList = Service::getInstance('region')->getCompanyList();
        $this->_view->companyDealersList = $getCompanyDealersList;
        $this->_view->province_id = $address['province'];
        $this->_view->goods_type = $goods[0];

        //将品类转换成拼音
        //$goodsTypeEN = [];
        $goodsTypeCH = [];
        foreach($goods[0] as $value) {
            $type = $value['name'];
            //$toPinyin = Pinyin::utf8_to($type);
            //$goodsTypeEN[] = $toPinyin;
            $goodsTypeCH[] = $type;
        }
        //$this->_view->typePY = json_encode($goodsTypeEN);
        $this->_view->typeCH = json_encode($goodsTypeCH);
        $this->_view->goodList = json_encode($goods[0]);

        //品牌转拼音
        //$goodsBrandEN = [];
        $goodsBrandCH = [];
        foreach($goods[1] as $value) {
            $brand = $value['name'];
            //$toPinyin = Pinyin::utf8_to($type);
            //$goodsBrandEN[] = $toPinyin;
            $goodsBrandCH[] = $brand;
        }
        //$this->_view->brandPY = json_encode($goodsBrandEN);
        $this->_view->brandCH = json_encode($goodsBrandCH);
        $this->_view->brandList = json_encode($goods[1]);

        $this->_view->brand = $goods[1];
        $this->_view->coupons = $coupons;
        $this->_view->goods = array('cid'=>$companyID);
        if ($this->isPost()) {
            $post = $_POST;
            $post['cid'] = $companyID;
            $this->_view->goods = $post;
            if ($goods_name == '') return $this->_view->errors = '商品名不能为空！';
            if ($goods_abbreviation == '') return $this->_view->errors = '商品简称不能为空！';
            if ($goods_type == '') return $this->_view->errors = '品类不能为空！';
            if ($brand_id == '') return $this->_view->errors = '品牌不能为空！';
            if ($city_price == '') return $this->_view->errors = '市场价不能为空！';
            if ($all_price == '') return $this->_view->errors = '销售价不能为空！';
            if (!is_numeric($city_price) || !is_numeric($all_price)) return $this->_view->errors = '价格-必须数字类型';
            //if ($amount == '') return $this->_view->errors = '库存不能为空！';
            if (!is_numeric($amount)) return $this->_view->errors = '库存-必须数字类型';
//            if ($goods_weight == '') return $this->_view->errors = '商品重量不能为空！';
            if (!is_numeric($goods_weight)) return $this->_view->errors = '重量-必须数字类型';
            if ($unit == '') return $this->_view->errors = '单位不能为空';
            if ($standard == '') return $this->_view->errors = '规格不能为空';
//            if ($production_time == '') return $this->_view->errors = '生产时间不能为空';
//            if ($storage == '') return $this->_view->errors = '贮存条件不能为空';
//            if ($shelf_life == '') return $this->_view->errors = '保质期不能为空';
//            if ($packaging == '') return $this->_view->errors = '包装不能为空';
//            if ($origin == '') return $this->_view->errors = '产地不能为空';
//            if ($province == '') return $this->_view->errors = '省份不能为空';
//            if ($goods_desc == '') return $this->_view->errors = '详细描述不能为空';
            if ($_FILES['goods_img']['size'] == 0) return $this->_view->errors = '请选择商品列图';
//             if ($_FILES['original_img']['size'] == 0) return $this->_view->errors = '请选择商品详情图';
            $goods = array(
                'goods_name' => $goods_name,
                'goods_abbreviation'=>$goods_abbreviation,
                'goods_type' => $goods_type,
                'brand_id' => $brand_id,
                'goods_weight' => $goods_weight,
                'packaging' => $packaging,
                'origin' => $origin,
                //'cid' => $companyID,
                'cid' => '1',//添加商品都视为给总公司添加
                'pid' => '0',
                'standard' => $standard,
                'production_time' => strtotime($production_time),
                'shelf_life' => $shelf_life,
                'storage' => $storage,
                'province' => $province,
                'goods_desc' => $goods_desc,
                'create_time' => time(),
                'city_price' => $city_price,
                'all_price' => $all_price,
                'amount' => $amount,
                'sort' => $sort,
                'unit' => $unit
            );
            $addID='';
            $addID = Service::getInstance('admgoods')->addGoods($goods,$couponsArr);
            if ($addID) {
                $goods_desc_img = '';
                $goods_img = '';
                $original_img = '';
                if ($_FILES['goods_desc_img']) {
                    $file = Util::getImages($_FILES['goods_desc_img'],828,414,'goods_desc_img');
                    $goods_desc_img = implode(',', $file);
                }
                if ($_FILES['goods_img']) {
                    $goods_img = Util::getImgPath($_FILES['goods_img'],380,388,true);
                }
                if ($_FILES['original_img']) {
                    $file = Util::getImages($_FILES['original_img'],828,414);
                    $original_img = implode(',', $file);
                }
                Service::getInstance('admgoods')->upGoodsImg($addID,$goods_desc_img, $goods_img, $original_img);
            }
            //添加登陆日志id
            $userinfo = Yaf_Registry::get('developer');
            $log_system['log_type'] = 7;
            $log_system['action'] = '添加商品';
            $log_system['uid'] = $userinfo['id'];
            $log_system['create_time'] = time();
            $log_system['action_id'] = $addID;
            $log_system['action_json'] = json_encode($goods);
            $log_system['ip'] = $_SERVER["REMOTE_ADDR"];
            Service::getInstance('systemlog')->addLog($log_system);
            //添加结束
            $this->flash('list','添加成功');
            return false;
        }
    }
    
    /**
     * 后台编辑商品
     */
    public function updateAction()
    {
        $user = Yaf_Registry::get('developer');
        $pid = $this->getQuery('pid');
        $id = $this->getQuery('id');
        
        $goods_name = $this->getPost('goods_name');
        $goods_abbreviation = $this->getPost('goods_abbreviation');
        $goods_type = $this->getPost('goods_type');
        $brand_id = $this->getPost('brand_id');
        $city_price = (float) $this->getPost('city_price');
        $all_price = (float) $this->getPost('all_price');
        $city_price_all_company = $this->getPost('city_price_all_company'); //市场参考价是否同步到所有分公司
        $all_price_all_company = $this->getPost('all_price_all_company'); //销售价是否同步到所有分公司
        $all_amount = $this->getPost('all_amount'); //库存是否同步到所有分公司
        $amount = (int)$this->getPost('amount');
        $goods_weight = $this->getPost('goods_weight');
        $packaging = $this->getPost('packaging');
        $standard = $this->getPost('standard');
        $production_time = $this->getPost('production_time');
        $storage = $this->getPost('storage');
        $shelf_life = $this->getPost('shelf_life');
        $origin = $this->getPost('origin');
        $province = $this->getPost('province');
        $goods_desc = $this->getPost('goods_desc');
        $cid = $this->getPost('cid');
        $unit = $this->getPost('unit');
        $couponsArr = $this->getPost('coupons','0');
        $goods = Service::getInstance('admgoods')->getGoods();
        $couponsList = Service::getInstance('admgoods')->getCouponsList();
        $couponsArray = Service::getInstance('admgoods')->getCouponsArr($id);
        $couponsID = array();
        foreach ($couponsArray as $k => $v) {
            $couponsList[$v['id']] = $v['name'];
            $couponsID[] = $v['id'];
        }
        $coupons = Service::getInstance('admgoods')->getCoupons();
        $this->_view->pid = $pid;
        $this->_view->getCompanyList = Service::getInstance('region')->getCompanyList();
        $this->_view->goods = $goods[2];
        $this->_view->goods_type = $goods[0];
        $this->_view->brand = $goods[1];
        $this->_view->coupons = $couponsList;
        $this->_view->couponsArray = $couponsID;
        $this->_view->company = $company;
        $this->_view->companyDealersList = $getCompanyDealersList;
        if ($_POST) {
            $post = $_POST;
            $this->_view->goods = $post;
//             if ($goods_name == '') return $this->_view->errors = '商品名不能为空！';
//             if ($goods_type == '') return $this->_view->errors = '品类不能为空！';
//             if ($brand_id == '') return $this->_view->errors = '品牌不能为空！';
//             if ($city_price == '') return $this->_view->errors = '市场价不能为空！';
//             if ($all_price == '') return $this->_view->errors = '销售价不能为空！';
//             if (!is_numeric($city_price) || !is_numeric($all_price)) return $this->_view->errors = '价格-必须数字类型';
//             if ($amount == '') return $this->_view->errors = '库存不能为空！';
//             if (!is_numeric($amount)) return $this->_view->errors = '库存-必须数字类型';
//             if ($goods_weight == '') return $this->_view->errors = '商品重量不能为空！';
//             if ($packaging == '') return $this->_view->errors = '包装不能为空！';
//             if ($origin == '') return $this->_view->errors = '产地不能为空！';
//             if ($province == '') return $this->_view->errors = '省份不能为空！';
//             if ($goods_desc == '') return $this->_view->errors = '详细描述不能为空！';
            
            $data = array(
                'goods_name' => $goods_name,
                'goods_abbreviation'=>$goods_abbreviation,
                'goods_type' => $goods_type,
                'brand_id' => $brand_id,
                'goods_weight' => $goods_weight,
                'packaging' => $packaging,
                'origin' => $origin,
                'province' => $province,
                'goods_desc' => $goods_desc,
                'standard' => $standard,
                'production_time' => strtotime($production_time),
                'shelf_life' => $shelf_life,
                'storage' => $storage,
                'create_time' => time(),
                'city_price' => $city_price,
                'all_price' => $all_price,
                'amount' => $amount,
                // 'sort' => $sort,
                'cid' => ($cid)?$cid:$goods[2]['cid'],
                'unit' => $unit
            );

            //同步全部公司标示数组
            $condition = [
                'city_price_is_update_all' => $city_price_all_company,
                'all_price_is_update_all' => $all_price_all_company,
                'amount_is_update_all' => $all_amount
            ];

            if (in_array($couponsArr,$couponsID)) {
                $couponsArr = '';
            }
            // 执行修改
            if (Service::getInstance('admgoods')->updateGoods($id,$data,$couponsArr, $condition)) {
                if ($_FILES['goods_img']['size'] > 0) {
                    $goods_img = Util::getImgPath($_FILES['goods_img'],380,388,true);
                    Service::getInstance('admgoods')->upGoodsImg($id,'', $goods_img, '');
                }
                //添加登陆日志id
                $userinfo = Yaf_Registry::get('developer');
                $log_system['log_type'] = 7;
                $log_system['action'] = '添加商品';
                $log_system['uid'] = $userinfo['id'];
                $log_system['create_time'] = time();
                $log_system['action_id'] = $id;
                $log_system['action_json'] = json_encode($data);
                $log_system['ip'] = $_SERVER["REMOTE_ADDR"];
                Service::getInstance('systemlog')->addLog($log_system);
                //添加结束
                $this->flash('list','修改成功');
                return false;
            }
        }
    }
    /**
     * 修改上下架
     */
    public function upShowAction()
    {
        $id = $this->getPost('id');
        $is_show = $this->getPost('is_show');
        $user = Yaf_Registry::get('developer');
        $company_id = $user['company_id'];
        $cid = $this->getPost('cid');
        //子公司操作商品先检测是否存在商品
        if($is_show){
            $return = Service::getInstance('admgoods')->upShow($id, $is_show,$cid);
        }else{
            if($cid>'0' && $cid != '1')
            {
                $pid = Service::getInstance('admgoods')->getCompanyInfo($id,$cid);
                if($pid){
                    if($cid == '1'){
                        $cid = $this->getPost('cid');
                    }
                    $return = Service::getInstance('admgoods')->upShow($pid,$is_show,$cid);
                }else{
                    $this->respon('2','异常错误');
                }
            }else{
                $return = Service::getInstance('admgoods')->upShow($id, $is_show,$cid);
            }
        }
        //添加登陆日志id
        $userinfo = Yaf_Registry::get('developer');
        $log_system['log_type'] = 7;
        $log_system['action'] = '商品下架';
        $log_system['uid'] = $userinfo['id'];
        $log_system['create_time'] = time();
        $log_system['action_id'] = $id;
        $log_system['ip'] = $_SERVER["REMOTE_ADDR"];
        Service::getInstance('systemlog')->addLog($log_system);
        //添加结束
        //子公司操作商品先检测是否存在商品
        echo $return;
        exit();
    }
    /**
     * 删除
     */
    public function deleteAction()
    {
        $id = $this->getPost('id');
        $is_delete = $this->getPost('is_delete');
        $user = Yaf_Registry::get('developer');
        $cid = $this->getPost('cid') ? $this->getPost('cid') : $user['company_id'];
        $return = Service::getInstance('admgoods')->delete($id,$is_delete,$cid);
        //添加登陆日志id
        $userinfo = Yaf_Registry::get('developer');
        $log_system['log_type'] = 7;
        $log_system['action'] = '删除商品';
        $log_system['uid'] = $userinfo['id'];
        $log_system['create_time'] = time();
        $log_system['action_id'] = $id;
        $log_system['ip'] = $_SERVER["REMOTE_ADDR"];
        Service::getInstance('systemlog')->addLog($log_system);
        //添加结束
        echo $return;
        exit();
    }
    
    /**
     * 商品图片修改
     * @return boolean
     */
    public function uploadImagesAction()
    {
        $id = $this->getPost('id');
        $name = $this->getPost('name');
        if ( empty($_FILES) || $_FILES[$name]['error'][0] > 0 || $_FILES[$name]['size'][0] == 0) {
            echo json_encode('null');
            exit;
        }
        $imgPath = Util::getImages($_FILES[$name],828,414,$name);
        $data = Service::getInstance('admgoods')->uploadImg($id,implode(',', $imgPath),$name);
        //添加登陆日志id
        $userinfo = Yaf_Registry::get('developer');
        $log_system['log_type'] = 7;
        $log_system['action'] = '修改商品图片';
        $log_system['uid'] = $userinfo['id'];
        $log_system['create_time'] = time();
        $log_system['action_id'] = $id;
        $log_system['action_json'] = json_encode($data);
        $log_system['ip'] = $_SERVER["REMOTE_ADDR"];
        Service::getInstance('systemlog')->addLog($log_system);
        //添加结束
        echo json_encode($data);
        exit;
    }
    
    /**
     * 商品头图
     * @return boolean
     */
    public function upGoodsImgAction()
    {
        if (empty($_FILES) || $_FILES['goods_img']['error'] > 0 || $_FILES['goods_img']['size'] == 0) {
            echo json_encode('null');
            exit;
        }
        $id = $this->getPost('id');
        $imgPath = Util::getImgPath($_FILES['goods_img'],380,388,true);
        $data = Service::getInstance('admgoods')->uploadGoodsImg($id,$imgPath['path'],$imgPath['thumb']);
        //添加登陆日志id
        $userinfo = Yaf_Registry::get('developer');
        $log_system['log_type'] = 7;
        $log_system['action'] = '商品头图修改';
        $log_system['uid'] = $userinfo['id'];
        $log_system['create_time'] = time();
        $log_system['action_id'] = $id;
        $log_system['action_json'] = json_encode($data);
        $log_system['ip'] = $_SERVER["REMOTE_ADDR"];
        Service::getInstance('systemlog')->addLog($log_system);
        //添加结束
        echo json_encode($data);
        exit;
    }
    
    /**
     * 删除图文详情图片
     * @return boolean
     */
    public function delImgAction()
    {
        $id = $this->getPost('id');
        $img = $this->getPost('img');
        $name = $this->getPost('name');
        $data = Service::getInstance('admgoods')->delImg($id,$img,$name);
        //添加登陆日志id
        $userinfo = Yaf_Registry::get('developer');
        $log_system['log_type'] = 7;
        $log_system['action'] = '删除图文详情图片';
        $log_system['uid'] = $userinfo['id'];
        $log_system['create_time'] = time();
        $log_system['action_id'] = $id;
        $log_system['action_json'] = json_encode($data);
        $log_system['ip'] = $_SERVER["REMOTE_ADDR"];
        Service::getInstance('systemlog')->addLog($log_system);
        //添加结束
        echo json_encode($data);
        exit;
    }
    
    public function getCityAction()
    {
        $province_id = $this->getPost('province');
        $ret = Service::getInstance('admgoods')->getCity($province_id);
        if($ret) {
            $this->respon(1, $ret);
        } else {
            $this->respon(0, '没有数据');
        }
        return false;
    }
    public function getAreaAction()
    {
        $city = $this->getPost('city');
        $ret = Service::getInstance('admgoods')->getArea($city);
        if($ret) {
            $this->respon(1, $ret);
        } else {
            $this->respon(0, '没有数据');
        }
        
        return false;
    }
    /**
     * 移动图片
     */
    public function upImagePlaceAction()
    {
        $goodsName = $this->getPost('goods_name');
        $imgStr = $this->getPost('imgStr');
        $goodsID = $this->getPost('goods_id');

        if (Service::getInstance('admgoods')->upImagePlace($goodsName, $imgStr, $goodsID)) {
            //添加登陆日志id
            $userinfo = Yaf_Registry::get('developer');
            $log_system['log_type'] = 7;
            $log_system['action'] = '移动图片';
            $log_system['uid'] = $userinfo['id'];
            $log_system['create_time'] = time();
            $log_system['action_id'] = $goodsID;
            $log_system['ip'] = $_SERVER["REMOTE_ADDR"];
            Service::getInstance('systemlog')->addLog($log_system);
            //添加结束
            $this->respon(1, '图片移动成功');
        } else {
            $this->respon(1, '图片移动失败');
        }
    }
    /**
     * 获取优惠券与活动列表
     */
    public function selectListAction()
    {
        $cid = $this->getPost('cid');
        $coupons = Service::getInstance('admgoods')->getCoupons($cid);
        $couponsStr = '';
        foreach ($coupons as $k => $v) {
            $couponsStr .= "<option value='".$v['id']."'>".$v['name']."</option>";
        }
        $activity = Service::getInstance('admgoods')->getActivity($cid);
        $activityStr = '';
        foreach ($activity as $k => $v) {
            $activityStr .= "<option value='".$v['id']."'>".$v['name']."</option>";
        }
        $this->respon(1,array($couponsStr, $activityStr));
    }
    
    /**
     * 修改库存
     */
    public function upAmountAction()
    {
        $id = $this->getPost('id');
        $amount = $this->getPost('amount');
        $user = Yaf_Registry::get('developer');
        $cid = $user['company_id'];
        $cid = $this->getPost('cid');
        //子公司操作商品先检测是否存在商品
        if($cid>'0' && $cid != '1')
        {
            $pid = Service::getInstance('admgoods')->getCompanyInfo($id,$cid);
            if($pid){
                if($cid == '1'){
                    $cid = $this->getPost('cid');
                }
                $return = Service::getInstance('admgoods')->upAmount($pid, $amount,$cid);
            }else{
                $this->respon('2','异常错误');
                return false;
            }
        }else{
               $return = Service::getInstance('admgoods')->upAmount($id, $amount,$cid);
        }
        //子公司操作商品先检测是否存在商品
           //$return = Service::getInstance('admgoods')->upAmount($id, $amount,$cid);
        if ($return) {
            //添加登陆日志id
            $userinfo = Yaf_Registry::get('developer');
            $log_system['log_type'] = 7;
            $log_system['action'] = '修改库存';
            $log_system['uid'] = $userinfo['id'];
            $log_system['create_time'] = time();
            $log_system['action_id'] = $id;
            $log_system['action_json'] = json_encode($amount);
            $log_system['ip'] = $_SERVER["REMOTE_ADDR"];
            Service::getInstance('systemlog')->addLog($log_system);
            //添加结束
            $this->respon(1, '修改成功');
        } else {
            $this->respon(0, '修改失败');
        }
    }
    
    /**
     * 修改销售价
     */
    public function upAllPriceAction()
    {
        $id = explode('-', $this->getPost('id'));
        $price = $this->getPost('price');
        $user = Yaf_Registry::get('developer');
        $cid = $user['company_id'];
        $cid = $this->getPost('cid');
        //子公司操作商品先检测是否存在商品
        if($cid>'0' && $cid != '1')
        {
            $pid = Service::getInstance('admgoods')->getCompanyInfo($id[1],$cid);
            if($pid){
                if($cid == '1'){
                    $cid = $this->getPost('cid');
                }
               $return = Service::getInstance('admgoods')->upAllPrice($pid, $price,$cid);
                $return_id = $pid;
            }else{
                $this->respon('2','异常错误');
                return false;
            }
        }else{
            $return = Service::getInstance('admgoods')->upAllPrice($id[1], $price,$cid);
            $return_id = $id[1];
        }
        //子公司操作商品先检测是否存在商品
        //$return = Service::getInstance('admgoods')->upAllPrice($pid, $price,$cid);
        if ($return) {
            //添加登陆日志id
            $userinfo = Yaf_Registry::get('developer');
            $log_system['log_type'] = 7;
            $log_system['action'] = '修改销售价';
            $log_system['uid'] = $userinfo['id'];
            $log_system['create_time'] = time();
            $log_system['action_id'] = $return_id;
            $log_system['action_json'] = json_encode($price);
            $log_system['ip'] = $_SERVER["REMOTE_ADDR"];
            Service::getInstance('systemlog')->addLog($log_system);
            //添加结束
            $this->respon(1, '修改成功');
        } else {
            $this->respon(0, '修改失败');
        }
    }
    /**
     * 修改参考价
     */
    public function upCityPriceAction()
    {
        $id = explode('_', $this->getPost('id'));
        $price = $this->getPost('price');
        $user = Yaf_Registry::get('developer');
        $cid = $user['company_id'];
        $cid = $this->getPost('cid');
        //子公司操作商品先检测是否存在商品
        if($cid>'0' && $cid != '1')
        {
            $pid = Service::getInstance('admgoods')->getCompanyInfo($id[2],$cid);
            if($pid){
                if($cid == '1'){
                    $cid = $this->getPost('cid');
                }
                $return = Service::getInstance('admgoods')->upCityPrice($pid, $price,$cid);
                $return_id = $pid;
            }else{
                $this->respon('2','异常错误');
                return false;
            }
        }else{
            $return = Service::getInstance('admgoods')->upCityPrice($id[2], $price,$cid);
            $return_id = $id[2];
        }
        //子公司操作商品先检测是否存在商品
        if ($return) {
            //添加登陆日志id
            $userinfo = Yaf_Registry::get('developer');
            $log_system['log_type'] = 7;
            $log_system['action'] = '修改参考价';
            $log_system['uid'] = $userinfo['id'];
            $log_system['create_time'] = time();
            $log_system['action_id'] = $return_id;
            $log_system['action_json'] = json_encode($price);
            $log_system['ip'] = $_SERVER["REMOTE_ADDR"];
            Service::getInstance('systemlog')->addLog($log_system);
            //添加结束
            $this->respon(1, '修改成功');
        } else {
            $this->respon(0, '修改失败');
        }
    }

    /**
     * 按品牌价格限定
     */
    public function noChangeAction(){
        //获取当前登录id
        $companyId = ($this->_view->rbac == '*') ? 0 : $this->companyIds;
        //所有品牌
        $this->_view->getBrand =  Service::getInstance('Admgoods')->getBrand();
        //$this->_view->get_goods_type =  Service::getInstance('Admgoods')->get_goods_type();//所有品类
        //所有公司
        if($companyId){
            $this->_view->getCompanyList = Service::getInstance('region')->getCompanyList($companyId);
        }else{
            $this->_view->getCompanyList = Service::getInstance('region')->getCompanyList();
        }

        // 分页
        $page = $this->getQuery('page','')?$this->getQuery('page',''):'1';
        $pageSize = 10;

        $list = Service::getInstance('Admgoods')->getNochangeList($page, $pageSize);
        $this->_view->list = $list['list'];
        $count = $list['count'];
        $url = "http://".$_SERVER['SERVER_NAME']."/goods/nochange?page=__page__";
        $this->_view->paglist = Util::buildPagebar($count,$pageSize,$page,$url);

        $this->_view->get = $_POST;

        if($this->isPost()){
            //经销商id
            $cid = $this->getPost('company_id');
            //品牌id
            $bid = $this->getPost('brand');
            if(!$cid) return $this->_view->error='请选择公司!';

            if(!$bid) return $this->_view->error='请选择品牌!';
            //查重
            if(Service::getInstance('Admgoods')->recodeExists($cid, $bid)){
                return $this->_view->error='已存在该品牌的经销商价格限定，请不要重复添加!';
            }

            //添加
            if(Service::getInstance('Admgoods')->addNochange($cid, $bid)){
                $this->flash( '/goods/nochange','添加成功!');
                exit;
            }
        }
    }

    /**
     * 经销商列表
     */
    public function getwarehouseAction(){
        $id = $this->getPost('cid');
        $list = Service::getInstance('Admstatis')->getwarehouse1($id);
        exit(json_encode($list,true));
    }

    /**
     * 删除价格限定
     */
    public function delNochangeAction(){
        $id = $this->getGet('id', '');
        if($id){
            $result = Service::getInstance('Admgoods')->delNochange($id);
            if($result){
                $this->respon(1, '删除成功');
            }else{
                $this->respon(0, '删除失败');
            }
        }else{
            $this->respon(0, '缺少关键参数');
        }
    }
}