<?php

class IndexController extends BaseController {


	public function init()
	{
		parent::init();

		if ( ! (bool)Yaf_Registry::get("isLogin") )
		{
			$this->respon( 0 , "Login Access Denied" );
		}
		
		//权限认证
		$pid = $this->getPost( 'pid',0 );
		if ( $pid )
		{
			Yaf_Registry::set( 'pid' , $pid ); 
			if ( ! Service::getInstance('projects')->checkProjectPermission( $pid ) )
			{
				$this->respon( 0 , "无权限访问该项目" );
			}
		}

	}

	//项目列表 @/ 
	public function indexAction( ) 
	{
		$list = Service::getInstance('projects')->getMyproject();
		$uid = Yaf_Registry::get('uid');
		foreach ($list['act'] as $k=>$v) {
		    $notifread = Service::getInstance('feednotif')->getNotifRead($v['Id'],$uid);
		    if(count($notifread) > 0 && $v['feed']['user']['Id'] != $uid) {
		        $list['act'][$k]['notifRead'] = 1;
		    } else {
		        $list['act'][$k]['notifRead'] = 0;
		    }
		}
		$this->respon( 1 , $list );
		return false;
	}
	public function projectinfoAction(){
		$pid = $this->getPost('pid','');
		$uid = Yaf_Registry::get('uid');
		$project = Service::getInstance('projects')->getInfo($pid);
		if ($project['OwnerId']==$uid) {
			$project['Admin']=1;
		}else{
			$project['Admin']=0;
		}
		$this->respon(1,$project);
		
	}
	//项目成员列表 @/index/userlist/
	public function userlistAction( )
	{
	    $pid = Yaf_Registry::get( 'pid' );    

		$list = Service::getInstance('projects')->userList( $pid );
		
		$this->respon( 1 , $list );
		return false;		
	}
	function delprojectuserAction() {
		$pid = $this->getPost('pid');
		$uid = $this->getPost('uid');
		if (!$pid) {
			$this->respon(0,'项目不能为空');
		}
		if (!$uid) {
			$this->respon(0,'用户不能为空');
		}
		if (Service::getInstance('projects')->deluser( $pid, $uid )){
			$this->respon(1 ,'删除成功');	
		}else{
			$this->respon(0,'删除失败');
		};
	}
	//项目动态 @/index/feedlist/
	public function feedlistAction()
	{
		$pid = Yaf_Registry::get( 'pid' ); 

		$list = Service::getInstance('projects')->getProjectFeedByid( $pid );
		
		$this->respon( 1 , $list );
		return false;
	}

	//项目通知 @/index/notifications/
	public function notificationsAction()
	{
		$list = Service::getInstance('feednotif')->Notifications(  );

		if ( count( $list ) )
		{
			foreach ( $list as $k => $v ) 
			{
				$v['Contents'] = Util::pToNR( $v['Contents'] );
				$list[$k] = $v;
			}
		}

		$this->respon( 1 , $list );
	}
	function notifisreadAction(){
		$id = $this->getPost('id');
		if (Service::getInstance('feednotif')->NotifIsRead($id)!==false){
			$this->respon(1,'设置成功');
		}else {
			$this->respon(0, '设置失败');
		};
	}
	//邀请成员 @/index/adduser/
	public function adduserAction()
	{
		$account = $this->getPost( 'account' , '' ); 
		if ( !$account ) $this->respon( 0 , '参数错误' );

		if ( Service::getInstance('projects')->adduser( $account ) )
		{
			Log::simpleappend('email', '项目加入成功');
			$user = Yaf_Registry::get( 'invite-user' );
			$newstate = isset( $user['new'] ) ? intval( $user['new'] ) : 0 ;
 			if ( $newstate )		
 			{
				$email = $account; 
		        $vars = array(
		            'email' => $email,
		            'password'=>$user['password'],
		            'link' => sprintf('http://www.haojindu.com/wap/download')
		        );
		        $this->_view->disableLayout();
		        $body = $this->_view->render('sys/mail_signup.phtml', $vars);
		        $this->_view->enableLayout();

		        $to = $email;
		        $subject = '开发者中心-注册邀请';

		        $Res = array(
		            'Uid' => $user['Id'],
		            'Title'=> $subject,
		            'MailContents' => $body,
		            'Updatetime' => time(),
		        );
		        
		        $this->db->insert("MailTo",$Res);

// 		        Util::sendmail( $to , $subject ,  $body );

				Log::simpleappend('email', $to.'--'.$subject.'--'.$body);

		        //$this->message("一封包含注册链接邮件已发送到你的邮箱：{$email}，请点击邮件中的链接完成注册<hr>" );

			}


			$this->respon( 1 , '邀请成功!' );
		}{
			Log::simpleappend('email', '项目加入失败');
			$this->respon(0,Service::getInstance('projects')->getError());
		}
		$this->respon( 0 , '邀请失败!' );
	}

	//创建项目 @/index/create/
	public function createAction()
	{
		$name = $this->getPost( 'name' , '' ); 
		if ( !$name ) $this->respon( 0 , '项目名不能为空！' );	
		$res = Service::getInstance('projects')->create( $name );	
		if ( $res )
		{
			$this->respon( 1 , $res );
		}

		$this->respon( 0 , "亲，出错啦~~" );
	}

}
