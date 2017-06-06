<?php
class GoodsController extends BaseController
{

    /*
    *   检测用户状态
    */
      public function init()
      {
       parent::init();
       $uinfo = parent::isshow();
       $auditType = $uinfo['data']['userInfo']['auditType'];
       $auditType1 = $_COOKIE['auditType'];
       if($auditType1 != $auditType && $auditType =='2')
         {
            foreach($_COOKIE as $key=>$value){
            if($key!='account'){
                if($key!='password'){
                    setCookie($key,"",time()-60,'/');
                }
            }
        }
            echo "<script>alert('审核通过，请重新登陆');</script>";
            header('Location: /login/login');
        }elseif($auditType == '3'){
             foreach($_COOKIE as $key=>$value){
            if($key!='account'){
                if($key!='password'){
                    setCookie($key,"",time()-60,'/');
                    }
               }
            }
            echo "<script>alert('您已被拉入黑名单');</script>";
            header('Location: /login/login');
        }  
            
      }
    /*
     * 商品列表
     */
    public function listAction(){
        //静默授权获取用户openid
        /*
        $state = 'STATE';
        $appid = Yaf_Application::app()->getConfig()->get('weixin')->get('APPID');
        $AppSecret = Yaf_Application::app()->getConfig()->get('weixin')->get('AppSecret');
        if(isset($_GET['state']) && $_GET['state']==$state){
            $code = $_GET['code'];
            $uinfo=file_get_contents("https://api.weixin.qq.com/sns/oauth2/access_token?appid=".$appid."&secret=".$AppSecret."&code={$code}&grant_type=authorization_code");
            $uinfo=(array)json_decode($uinfo);
            $openid=$uinfo['openid'];
            $_COOKIE['openid']=$openid;
            setcookie('openid',$openid,time()+3600,'/');
        }
        */
        //静默授权获取用户openid
        $url = $this->_view->weixinapi."/goods/goodsList";
        $bname=isset($_GET['bname'])?$_GET['bname']:'按品牌';
        $tname=isset($_GET['tname']) ? $_GET['tname']:'按品类';
        $stastu=isset($_GET['stastu']) ? $_GET['stastu']:0;
        $brand=isset($_GET['bid'])?$_GET['bid']:0;
        $type=isset($_GET['tid']) ? $_GET['tid']:0;
        $activity=isset($_GET['activity']) ? $_GET['activity']:0;
        $goods_name=isset($_POST['goods_name']) ? $_POST['goods_name']:'';
        $arr = array(
            'uid'=>$_COOKIE['uid'],
            'activity'=>$activity,
            'goods_name'=>$goods_name,
            'brand'=>$brand,
            'goods_type'=>$type,
            'pageSize'=>1,
            'pageSize'=>10
        );

        //商品
        $result = Util::httpRequest($url,$arr);
        $result = json_decode($result,true);
        //var_dump($result);exit;
        //品牌与品类
        $bool=$this->getbanner();
        if(!empty($bool)){
            $this->_view->brand = $bool['brand_list'];//品牌
            $this->_view->tplist = $bool['goods_type_list'];//品类
           
        }else{
            $this->_view->tplist='';
            $this->_view->brand ='';
        }
        //广告
        $abool=$this->activiey(1);
        if(!empty($abool['data'])){
            //进行排序
            foreach ($abool['data'] as $k=>$v){
                $flag[$k] = $v['sort'];
            }
            array_multisort($flag,SORT_ASC,$abool['data']);
            $this->_view->adgoods = $abool['data'];
        }else{
            $this->_view->adgoods ='';
        }
        //购物商品数量
        $count=0;
        $goods_info=empty($_SESSION['goods_info'])?'':json_decode($_SESSION['goods_info'],true);
        //var_dump($goods_info);
        //讲商品便利成已ID作为KEY
        $new_goods_info = array();
        if(!empty($goods_info)){
            foreach($goods_info as $k => $v){
                $count+=$v["count"];

                $new_goods_info[$v['id']] = $v;
            }
        }
        $uid = Yaf_Registry::get('uid');
        $this->_view->good_info = $new_goods_info;
        $this->_view->bname = $bname;//商品类型
        $this->_view->tname = $tname;//商品品牌
        $this->_view->tid = $type;//商品类型
        $this->_view->bid = $brand;//商品品牌
        $this->_view->activity = $activity;//活动
        $this->_view->count = $count;//商品数量
        if (isset($result['data'])){
            $this->_view->result = $result['data'];
        }
        $this->_view->auditType = $_COOKIE['auditType'];
        $this->_view->stastu =  $stastu;
    }
    /*
     * 商品信息
     */
    public function infoAction(){
        $url = $this->_view->weixinapi."goods/goodsInfo";
        $goods_id=isset($_GET['id'])?$_GET['id']:'';
        $activity_id=(int)$activity_id=isset($_GET['activity_id'])?$_GET['activity_id']:0;
        $arr=array(
            'uid'=>$_COOKIE['uid'],
            'goods_id'=>$goods_id,
            'activity_id'=>$activity_id
        );

        $json = Util::httpRequest($url,$arr);
        $result = json_decode($json,true);
        if($_COOKIE['cid']!=$result['data']['cid']){
            header('Location: /goods/list');
        }
        $this->_view->title ="商品详情";
        $this->_view->info = $result['data'];
    }
    /*
     * 搜索商品
     */
    public function searchAction(){
        $purl = $this->_view->weixinapi."/goods/typelist";
    }
    /*
     * 返回搜索商品列表
     */
    public function sechAction(){
        $url = $this->_view->weixinapi."/goods/goodsList";
        $goods_name=isset($_POST['value']) ? $_POST['value']:'';
        if($goods_name==''){
            exit(json_encode(array('code'=>0,'data'=>'商品信息错误')));
        }
        $arr = array(
            'uid'=>$_COOKIE['uid'],
            'activity'=>0,
            'goods_name'=>$goods_name,
            'brand'=>0,
            'goods_type'=>0,
            'pageSize'=>100000
        
        );
        $json = Util::httpRequest($url,$arr);
        $result = json_decode($json,true);
        if($result['success']==1 && !empty($result['data'])){
            exit(json_encode(array('code'=>1,'goods_info'=>$result['data'])));
        }else{
           exit(json_encode(array('code'=>0,'data'=>'')));
        }
    }
    /*
     * 限时抢购、买就送、品牌活动商品
     */
    public function scareAction(){
        $count=0;
        $url = $this->_view->weixinapi."/goods/goodsList";
        $this->_view->bid = $brand=(int)$brand=isset($_GET['bid'])?$_GET['bid']:0;
        $this->_view->tid = $type=(int)$type=isset($_GET['tid']) ? $_GET['tid']:0;
        $this->_view->activity = $activity=(int)$activity=isset($_GET['activity']) ? $_GET['activity']:1;
        $this->_view->activity_type = $activity_type=isset($_GET['activity_type']) ? $_GET['activity_type']:1;
        $this->_view->banme = $bname=isset($_GET['bname'])?$_GET['bname']:'按品牌';
        $this->_view->tanme = $tname=isset($_GET['tname']) ? $_GET['tname']:'按品类';
        //获取商品数量
        $goods_info=empty($_SESSION['goods_info'])?'':json_decode($_SESSION['goods_info'],true);
        $new_goods_info = array();
        if(!empty($goods_info)){
            foreach($goods_info as $k => $v){
                $count+=$v["count"];
                $new_goods_info[$v['id']] = $v;
            }
        }
        $this->_view->good_info = $new_goods_info;
        $arr = array(
            'uid'=>$_COOKIE['uid'],
            'activity'=>$activity,
            'goods_name'=>'',
            'brand'=>$brand,
            'goods_type'=>$type,
            'pageNum'=>'',
            'pageSize'=>'',);
        //商品
        $json = Util::httpRequest($url,$arr);
        //var_dump($json);
        $result = json_decode($json,true);
        //品牌与品类
        $bool=$this->getbanner();
        
        if(!empty($bool)){
            $this->_view->brand = $bool['brand_list'];//品牌
            $this->_view->tplist = $bool['goods_type_list'];//品类
        }else{
            $this->_view->tplist='';
            $this->_view->brand ='';
        }
        //广告商品
        if($activity==3) $activity=4;
        if($activity==1) $activity=3;
        $abool=$this->activiey($activity);
        if(!empty($abool['data'])){
            $this->_view->adgoods = $abool['data'];
        }else{
            $this->_view->adgoods ='';
        }

        $this->_view->title ="星水采购平台";
        $this->_view->bname = $bname;//商品类型名称
        $this->_view->tname = $tname;//商品品牌名称
        $this->_view->bid = $brand;//品牌
        $this->_view->tid = $type;//品类
        $this->_view->count = $count;
        $this->_view->type = $activity;
        //$this->_view->activity = $activity;
        $this->_view->result = $result['data'];

    } 
    /*
     * 广告商品
     */
    public function getactivieyAction(){
         $url = $this->_view->weixinapi."/ad/adgoodslist";
         $id=(int)$activity=isset($_GET['id']) ? $_GET['id']:0;
         $type = isset($_GET['type'])?$_GET['type']:1;
         $arr=array(
             'id'=>$id,
             'pageNum'=>1,
             'pageSize'=>1000,'uid'=>$_COOKIE['uid'],'cid'=>$_COOKIE['cid']
         );
         $json = Util::httpRequest($url,$arr);
         $result = json_decode($json,true);
         //购物商品数量
         $count=0;
         $goods_info=empty($_SESSION['goods_info'])?'':json_decode($_SESSION['goods_info'],true);
         //var_dump($goods_info);
         //讲商品便利成已ID作为KEY
         $new_goods_info = array();
         if(!empty($goods_info)){
             foreach($goods_info as $k => $v){
                 $count+=$v["count"];
         
                 $new_goods_info[$v['id']] = $v;
             }
         }
        $this->_view->count = $count;//商品数量
         //广告
         $abool=$this->activiey($type);
         if($abool){
             //拿出需要对应的广告
             foreach ($abool['data'] as $v){
                 if($v['id'] == $id){
                     $ad[]=$v;
                 }
             }
             $this->_view->adgoods = $ad;
         }else{
             $this->_view->adgoods ='';
         }
         if($result['data']){
             $this->_view->result = $result['data'];
         }else{
             $this->_view->result ='';
         }

    }
    /*
     * 平牌与品类
     */
    public function getbanner(){ 
       $url = $this->_view->weixinapi."/goods/typelist";
       $arr = array('uid'=>$_COOKIE['uid']);
       $result = Util::httpRequest($url,$arr);
       $result = json_decode($result,true);
       if(isset($result['data'])){
           return  $result['data'];
       }else{
           return false;
       }
    }
    /*
     * 广告
     */
    public function activiey($type=1){
        $goods_url=$this->_view->weixinapi."/ad/adlist";//广告商品
        $goods_id = array('id'=>1,'type'=>$type,'uid'=>$_COOKIE['uid']);
        $adgoods = Util::httpRequest($goods_url,$goods_id);
        $adgoods = json_decode($adgoods,true);
        if(isset($adgoods['data'])){
           return  $adgoods;
       }else{
           return false;
       }
    }
    /*
     * 保存选择的商品
     */
    public function getinfoAction(){
        $arr=array();
        $goods_info=$this->getPost("info");
         if(isset($_SESSION['goods_info']) && !empty($_SESSION['goods_info'])){
            $bool=false;
            $goods=json_decode($_SESSION['goods_info'],true);
            foreach($goods as $k => &$v){
                if($v['id']==$goods_info['id']){
                    if(empty($goods_info['numberic'])){
                        if($goods_info['state']==1){
                            if($goods_info['type_h']==1){
                                $v['count']=$v['count']-1;
                            }elseif($goods_info['type_h']==2){
                                $v['count']=$v['count']+1;
                            }else{
                                $v['count']=$v['count'];
                            }
                        }elseif($goods_info['state']==2){
                            $v['count']=$goods_info['count'];
                        }
                        $newgoods= json_encode($goods);
                        //setcookie('goods_info',$newgoods,time()+7200,'/');
                        $_SESSION['goods_info'] = $newgoods;
                        exit(json_encode(array('code'=>"1",'message'=>'商品已更改数量成功','goods_id'=>$goods_info['id'],'activity_id'=>$goods_info['activityId'])));
                        $bool=true;
                    }else{
                        exit(json_encode(array('code'=>"1",'message'=>'商品已存在','goods_id'=>$goods_info['id'],'activity_id'=>$goods_info['activityId'])));
                    }
                }
            }
            if($bool==false){       
                $goods[]=$goods_info;
                $goods=json_encode($goods);
                //setcookie('goods_info',$goods,time()+3600,'/');
                $_SESSION['goods_info'] = $goods;
                exit(json_encode(array('code'=>"1",'message'=>'成功','goods_id'=>$goods_info['id'],'activity_id'=>$goods_info['activityId'])));
            }
          }else{
            $arr[]=$goods_info;
            $arr=json_encode($arr);
             $_SESSION['goods_info']=$arr;
            //setcookie('goods_info',$arr,time()+3600,'/');
             $_SESSION['goods_info'] = $arr;
            if(isset($_SESSION['goods_info'])){
                exit(json_encode(array('code'=>"1",'message'=>'成功','goods_id'=>$goods_info['id'],'activity_id'=>$goods_info['activityId'])));
            }else{
                exit(json_encode(array('code'=>"0",'message'=>'失败')));
            }
            
          }
    }
    /*
     * 上拉刷新 下拉加载
     */
    public function locadAction(){
        $url = $this->_view->weixinapi."/goods/goodsList";
        $brand=isset($_POST['bid'])?$_POST['bid']:0;
        $type_t=isset($_POST['tid']) ? $_POST['tid']:0;
        $activity=isset($_GET['activity']) ? $_GET['activity']:0;
        $goods_name=isset($_POST['goods_name']) ? $_POST['goods_name']:'';
        if(isset($_GET['type']) && $_GET['type']==1){
             $num=10;
        }else{
            $num=254;
        }
        $arr = array(
            'activity'=>$activity,
            'goods_name'=>$goods_name,
            'brand'=>$brand,
            'goods_type'=>$type_t,
            'pageNum'=>1,
            'pageSize'=>$num,
            'uid'=>$_COOKIE['uid']
    
        );
        //商品
        $result = Util::httpRequest($url,$arr);
        $result = json_decode($result,true);    
        if($result['success']==1 && !empty($result['data'])){
            exit(json_encode(array('code'=>"1",'num'=>$num,'goods_info'=>$result['data'])));
        }else{
            exit(json_encode(array('code'=>"0",'goods_info'=>'刷新失败')));
        }
      
     
    }
}