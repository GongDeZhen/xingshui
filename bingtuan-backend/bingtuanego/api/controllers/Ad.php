<?php
class AdController extends BaseController
{
    /**
     *  广告列表
     */
    public function adlistAction()
    {
        $type = $this->getPost('type');
         
        if ( $type == "" ) $this->respon( 0 , "广告位不能为空!" );
        $data = Service::getInstance('Admad')->adList($type);
    
        if ( ! $data )
        {
            $this->respon(1,array());
        }else{
            $this->respon(1,$data);
        }
    }
    
    
    /**
     * 广告商品列表
     */
    public function adGoodsListAction()
    {
        $id = $this->getPost('id');
        $data['pageNum'] = $this->getPost('pageNum',1);
        $data['pageSize'] = $this->getPost('pageSize',10);
        $cid = $this->getPost('cid');
        if($cid){
            $where = "AND tg.cid =".$cid."";
        }
        if ( $id == "" ) $this->respon( 0 , '广告ID不能为空!' );
        $data = Service::getInstance('Admad')->adGoodsList($id,$data,$where);
        if ( ! $data ){
            $this->respon(1,array());
        }else{
            $this->respon(1,$data);
        }
    }
}