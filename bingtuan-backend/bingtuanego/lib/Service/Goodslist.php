<?php
class Service_Goodslist extends Service
{
    /* 总公司的商品 */
    public function getList()
    {
        $sql = "SELECT * FROM goods WHERE cid = 1";
        $list = $this->db->fetchAll($sql);
        return $list;
    }
    /* 根据商品名查出所有商品 */
    public function getListbyname($name)
    {
        $sql = "SELECT * FROM goods WHERE goods_name='{$name}' AND cid<>1 ORDER BY id DESC";
        $list = $this->db->fetchAll($sql);
        return $list;
    }
    /**
     * 查询分公司是否存在名字相同的商品
     * @param unknown $cid
     * @param unknown $name  */
    public function getgoodsbycid($cid,$name)
    {
        $sql = "SELECT * FROM goods WHERE goods_name = '{$name}' AND cid = {$cid} ORDER BY id DESC";
        $list = $this->db->fetchAll($sql);
        return $list;
    }
    /**
     * 根据商品id查询是否有商品出售
     * @param unknown $id  
     * @param unknown $gid //要改的id
     * */
    public function uporder($id,$gid)
    {
        $sql = "SELECT * FROM order_goods WHERE goods_id={$id}";
        $list = $this->db->fetchAll($sql);
        if($list)//如果有订单修改为对应的商品id
        {
            $return = $this->db->update('order_goods', array('goods_id' => $gid),'id='.$id);
            if($return == 0 || $return == 1)
            {
                return 200;
            }
        }else{//没有出售直接删除商品
             $this->db->delete('goods','id='.$id);
             return 100;
        }
    }
    /**
     * 删除商品
     * @param unknown $id  
     * */
    public function delgoods($id) {
        $this->db->delete('goods','id='.$id);
    }
    /**
     * 修改旧商品为最新商品
     * @param unknown $goods_name
     * @param unknown $id
     * @param unknown $cid  
     * */
    public function upgoods($goods_name,$brand_id,$goods_type,$id,$cid)
    {
        $return = $this->db->update('goods', array('goods_name' => $goods_name,'brand_id'=>$brand_id,'goods_type'=>$goods_type,'cid' => $cid,'id' => $id), "id = $id AND cid = $cid");
        if ($return == 0 || $return == 1) {
            return true;
        } else {
            return false;
        }
    }
    /**
     * 以商品名为条件，修改所有同名商品的pid为总公司的商品id
     * @param unknown $goods_name
     * @param unknown $pid 
     *  
     *  */
    public function upgoodspid($goods_name,$pid,$goods_abbreviation='',$standard)
    {

        echo $standard;
        if($goods_abbreviation){
            $return = $this->db->update('goods', array('pid' => $pid,'goods_abbreviation'=>$goods_abbreviation), 'goods_name='."'$goods_name'". ' and id<>'.$pid .' and standard="'.$standard.'"');

        }else{
            $return = $this->db->update('goods', array('pid' => $pid), 'goods_name='."'$goods_name'". ' and id<>'.$pid .' and standard="'.$standard.'"');

        }
        //return $return;
        if ($return == 0 || $return == 1) {
            return true;
        } else {
            return false;
        }
    }
    /**
     * 所有品牌
     * @param unknown $param  */
    public function getbrand() {
        $sql = "SELECT name,id FROM brand";
        $list = $this->db->fetchAll($sql);
        return $list;
    }
    /**
     * 所有品类
     *   */
    public function getgoodstype()
    {
        $sql = "SELECT name,id FROM goods_type";
        $list = $this->db->fetchAll($sql);
        return $list;
    }
    
    
    
    
}