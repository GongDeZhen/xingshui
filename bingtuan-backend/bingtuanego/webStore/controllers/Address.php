<?php
class AddressController extends BaseController
{
    /*
     * 收货地址列表
     */

    public function indexAction()
    {
        $city_url= $this->_view->weixinapi."myaccount/getCityInfo";

        $url= $this->_view->weixinapi."myaccount/addressInfo";
        $uid = Yaf_Registry::get('uid');
        $arr=array('uid'=>$uid);
        $result=Util::httpRequest($url,$arr);
        $result= json_decode($result,true);
        //$city_list=Util::httpRequest($city_url,$arr);
        //var_dump($city_list);
        $v=(int)$v=isset($_GET['v'])?$_GET['v']:0;

        if($result['data']){
            $this->_view->info=$result['data'];
        }else{
            $this->_view->info='';
        }
        if(($v=='' || !is_numeric($v)) && $v==0 ){
            $this->_view->url ="/address/edit";
        }else{
            $this->_view->url='/cart/billing';
        }
        $this->_view->title ="收货地址";
        
     }
    /*
     * 添加收货地址
     */
    public function addAction()
    {
        if(isset($_POST) && !empty($_POST['sub'])){
            $url= $this->_view->weixinapi."/myaccount/addAddress";
            $user=isset($_POST['user'])?$_POST['user']:'';
            $phone=isset($_POST['phone'])?$_POST['phone']:'';
            $city_id=isset($_POST['city'])?$_POST['city']:'';
            $arr_id=explode(',', $city_id);
            $address=isset($_POST['address'])?$_POST['address']:'';
            $uid = Yaf_Registry::get('uid');
            if(empty($arr_id) || empty($address))   echo "<script>alert('地址不能为空')</script>";
            if(empty($user))  echo "<script>alert('用户名不能为空')</script>";
            if(empty($phone)){
                  echo "<script>alert('手机号码不能为空')</script>";
            }else{
                $arr=array(
                    'uid'=>$uid,
                    'user_name'=>$user,
                    'account'=>$phone,
                    'province_id'=>$arr_id[0],
                    'city_id'=>$arr_id[1],
                    'area_id'=>$arr_id[2],
                    'address'=>$address,
                );
                $result=Util::httpRequest($url,$arr);
                $result=json_decode($result,true);   
                if($result['success']==1){
                    echo "<script>alert('添加成功')</script>";
                    header('Location: /cart/colladdress/');
                    exit;
                }else{
                    echo "<script>alert('添加失败')</script>";
                }
            }
        }

    }
    /*
     * 编辑收货地址
     */
    public function editAction()
    {
		$add_url= $this->_view->weixinapi."/myaccount/getCityInfo";
        //var_dump($city_arr);
        //获取对应的城市ID
        $province_id = isset($_GET['province_id'])?$_GET['province_id']:'';
        $city_id = isset($_GET['city_id'])?$_GET['city_id']:'';
        $area_id = isset($_GET['area_id'])?$_GET['area_id']:'';
        $this->_view->province_id = $province_id;
        $this->_view->city_id = $city_id;
        $this->_view->area_id = $area_id;
		$temp_arr['id'] = $province_id;
		$province = json_decode(Util::httpRequest($add_url,$temp_arr),true);
		$this->_view->province = isset($province['data'])?$province['data']:'';
		$temp_arr['id'] = $city_id;
		$city = json_decode(Util::httpRequest($add_url,$temp_arr),true);
		$this->_view->city = isset($city['data'])?$city['data']:'';
		$temp_arr['id'] = $area_id;
		$area = json_decode(Util::httpRequest($add_url,$temp_arr),true);
		$this->_view->area = isset($area['data'])?$area['data']:'';

        if(!empty($_POST)){
            $url= $this->_view->weixinapi."/myaccount/editAddress";
            $aid=isset($_POST['address_id'])?$_POST['address_id']:'';
            $city_id=isset($_POST['city'])?$_POST['city']:'';
            $arr_id=explode(',', $city_id);
            $account=isset($_POST['phone'])?$_POST['phone']:'';
            $uname=isset($_POST['user'])?$_POST['user']:'';
            $address=isset($_POST['address'])?$_POST['address']:'';
            $uid = Yaf_Registry::get('uid');
           // $city=$this->cityinfo($province_id,$city_id);
            $arr=array(
                'uid'=>$uid,
                'user_name'=>$uname,
                'account'=>$account,
                'province_id'=>$arr_id[0],
                'city_id'=>$arr_id[1],
                'area_id'=>$arr_id[2],
                'address'=>$address,
                'address_id'=>$aid,
            );
            $this->_view->title ="收货地址";
            $this->_view->info=$arr;
            $result=Util::httpRequest($url,$arr);
            $result= json_decode($result,true);
            if($result['success']==1 && !empty($_POST)){
                setcookie('add_info','',-1,'/');
                echo "<script>alert('修改成功')</script>";
                header('Location: /address/index/');
                exit;
            }else{
                echo "<script>alert('修改失败')</script>";
                header('Location: /address/index/');
            }
           }
      }
    /*
     * 获取城市信息
     * 
     */
    public function cityinfo($type,$parent_id=''){
        $url= $this->_view->weixinapi."/user/getCity/";
        $arr=array("type"=>$type,"parent_id"=>$parent_id,'uid'=>$_COOKIE['uid']);
        $result=Util::httpRequest($url,$arr);
        $result= json_decode($result,true);
        return $result;
    }
    /*
     * 删除地址
     */
    public function deladdressAction(){
        $url= $this->_view->weixinapi."/myaccount/delAddress";
        $id=(int)$id=isset($_POST['id'])?$_POST['id']:'';
        $arr=array("address_id"=>$id);
        $result=Util::httpRequest($url,$arr);
        $result= json_decode($result,true);
        $this->respon(1,$result);
    }

}