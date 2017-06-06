<?php

class TestController extends BaseController
{	public function init()
    {
        parent::init();
    }
     public function indexAction()
     {
      //  	$list = Service::getInstance('test')->Index('1');
      //   dump($list);
     	// $this->_view->list = $list;
     	// $this->_view->title = 'helo yaf';
     	// $this->_view->arr1 = array('1','2','3');
        var_dump('aaaaaaaaaaaaaaaaa');
        var_dump($this->getQuery('aa'));
        // $this->display();
     }
     public function upladeAction()
     {
        // $content = $this->getPost('content')? $this->getPost('content') : '111';
        // $ctime = time();
        // $data = array(
        //     'content'=>$content,
        //     'ctime'=>$ctime
        //     );
        // dump($data);
     }
}