<?php
/**
 * 
 * @author waixiu
 * 
 * 修改商品
 *  */
class UpgoodsController extends BaseController{
    /*
     * 所有商品列表 
     *   */
    public function goodslistAction()
    {
        echo date('Y-m-d H:i:s',time());
        $handle = fopen("test.txt","r");
        $brandlist = Service::getInstance('Goodslist')->getbrand();
        $typelist = Service::getInstance('Goodslist')->getgoodstype();
        if ($handle) { 
            while (!feof($handle)) {
                $buffer = fgets($handle, 4096);
                //用正则去掉不规则空格
                $data = preg_replace('/\s+/', ' ', $buffer);
                //去掉两侧的空字符串
                $data = trim($data);
                //分割成数组
                $data = explode(' ',$data);
                $num = count($data);
                if($num==5 && $data[0]!=''){
                    //用新商品名查有没有正确存在的新商品
                    $brand = $data['1'];
                    $type = $data['2'];
                    foreach ($brandlist as $k=>$v)
                    {
                        if($brand == $v['name'])
                        {
                            $brand_id = $brandlist[$k]['id'];
                        }
                    }
                    foreach ($typelist as $k=>$v)
                    {
                        if($type == $v['name'])
                        {
                            $goods_type = $typelist[$k]['id'];
                        }
                    }
                    $info = Service::getInstance('Goodslist')->getListbyname($data['0']);
                    if($info)//有新商品存在查询每个分公司有没有重复的新商品
                    {
                        foreach($info as $k=>$v)
                        {
                            $list = Service::getInstance('Goodslist')->getgoodsbycid($v['cid'],$data['0']);
                            if(count($list)>1){//分公司存在新商品且不只一个
                                $id = $list['0']['id'];
                                foreach($list as $k=>$v)
                                {//修改订单id
                                    if($k+1 == count($list)){
                                        break;
                                    }
                                    $re = Service::getInstance('Goodslist')->uporder($list[$k+1]['id'],$id);
                                    if($re == 200)
                                    {
                                        $re = Service::getInstance('Goodslist')->delgoods($list[$k+1]['id']);
                                    }
                                }
                            }
                        }
                        //有新商品存在的时候也处理一下旧商品
                        $info = Service::getInstance('Goodslist')->getListbyname($data['3']);
                        foreach($info as $k=>$v)
                        {
                            
                            $info = Service::getInstance('Goodslist')->getgoodsbycid($v['cid'],$v['goods_name']);
                            if($info['0'])
                            {
                                $id = $info['0']['id'];
                                $cid = $info['0']['cid'];
                                if(count($info)>1)//分公司旧商品同名的不止一个
                                {
                                    foreach ($info as $k=>$v) {
                                        if($k+1 == count($info)){
                                            break;
                                        }
                                        $re = Service::getInstance('Goodslist')->uporder($info[$k+1]['id'],$id);
                                        if($re == 200)
                                        {
                                            $re = Service::getInstance('Goodslist')->delgoods($info[$k+1]['id']);
                                        }
                                    }
                                }
                                //删除完旧商品修改旧商品名为新商品名
                                $re = Service::getInstance('Goodslist')->upgoods($data['0'],$brand_id,$goods_type,$id,$cid);
                            }
                        }
                        echo 1;
                    }else{//没有的话用旧商品名查出所有商品修改商品名为最新商品
                        $info = Service::getInstance('Goodslist')->getListbyname($data['3']);
                        foreach($info as $k=>$v)
                        {
                            $info = Service::getInstance('Goodslist')->getgoodsbycid($v['cid'],$v['goods_name']);
                            if($info['0'])
                            {
                                $id = $info['0']['id'];
                                $cid = $info['0']['cid'];
                                if(count($info)>1)//分公司旧商品同名的不止一个
                                {
                                    foreach ($info as $k=>$v) {
                                        if($k+1 == count($info)){
                                            break;
                                        }
                                        $re = Service::getInstance('Goodslist')->uporder($info[$k+1]['id'],$id);
                                        if($re == 200)
                                        {
                                            $re = Service::getInstance('Goodslist')->delgoods($info[$k+1]['id']);
                                        }
                                    }
                                }
                                //删除完旧商品修改旧商品名为新商品名
                                $re = Service::getInstance('Goodslist')->upgoods($data['0'],$brand_id,$goods_type,$id,$cid);
                            }
                        }
                        echo 2;
                    }
                }
            }
            fclose($handle);
        }
        echo date('Y-m-d H:i:s',time());
        exit();
    }
    /* 商品统一完成，查询总公司所有商品，以商品名为条件，修改所有分公司商品的pid为总公司的商品id */
    public function unifiedgoodsAction()
    {
        $list = Service::getInstance('Goodslist')->getList();
        //echo count($list);exit;
        foreach ($list as $k=>$v){
            //var_dump($v);
            $re = Service::getInstance('Goodslist')->upgoodspid($v['goods_name'],$v['id'],$v['goods_abbreviation'],$v['standard']);
            if($re)
            {
                echo '200';
            }else{
                echo '100';
                continue;
            }
        }
        exit;
    }
}