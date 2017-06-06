<?php

class GoodsController extends BaseController
{
    public function init()
    {
        parent::init();
        if (  Yaf_Registry::get( 'isLogin' ) <> true )
        {
            $this->respon( 0 , "登录超时!"  );
        }
    }

    /**
     * 商品列表
     */
    public function goodsListAction()
    {
        $user = Yaf_Registry::get('user');
        if (!$user['company_id']) {
            $this->respon(0, '经销商错误!');
        }
        $search = $this->getPost('goods_name','');
        $activity = $this->getPost('activity',0);
        $brand = $this->getPost('brand',0);
        $goods_type = $this->getPost('goods_type',0);
        $pageNum = $this->getPost('pageNum') ? $this->getPost('pageNum') : 1;
        $pageSize = $this->getPost('pageSize') ? $this->getPost('pageSize') : 10;
        $list = Service::getInstance('goods')->getList($activity,$brand,$goods_type,$pageNum,$pageSize,$search);
        //将商品名称换成简称与规格
        if($list){
            foreach($list as $k=>$v){
                if($v['goods_abbreviation'])
                    $list[$k]['goods_name'] = $v['goods_abbreviation'].' '.$v['standard'];
            }
        }
        $this->respon(1,$list);
    }

    /**
     * 商品详情
     */
    public function goodsInfoAction()
    {
        $id = $this->getPost('goods_id','');
        $activity_id = $this->getPost('activity_id','');

        if($id=='') $this->respon( 0 , '商品id不能为空' );
        if($activity_id=='') $this->respon( 0 , '商品活动id不能为空' );
        if($id=='') $this->respon( 0 , '商品id不能为空' );

        $list = Service::getInstance('goods')->goodsInfo($id,$activity_id);
        $this->respon(1 , $list);
    }
    
    /**
     * 商品评价列表
     */
    public function commentListAction()
    {
        $goodsID = $this->getPost('goods_id');
        if ( ! $goodsID ) $this->respon(0,'商品ID不能为空');
        $list = Service::getInstance('goods')->commentList($goodsID);
        $this->respon(1 , $list);
    }

    /**
     * 品牌和品类列表
     */
    public function typelistAction()
    {
        $list = Service::getInstance('goods')->typelist();
        if ($list) {
            $this->respon(1,$list);
        }else{
            $this->respon(1,array());
        }
    }

    /**
     * 获取品类列表
     * @return json
     */
    public function goodTypesAction(){
        $list = Service::getInstance('goods')->getGoodsTypes();
        $list ? $this->respon(1, $list) : $this->respon(1, array());
    }

    /**
     * 通过品类id获取品牌信息
     * @return json
     */
    public function brandsAction(){
        $goodsTypeId = $this->getPost('typeId', '');
        if($goodsTypeId){
            $list = Service::getInstance('goods')->getBrandsByTypeId($goodsTypeId);
            $list ? $this->respon(1, $list) : $this->respon(1, array());
        }else{
            $this->respon(0, '缺少关键参数');
        }
    }
}