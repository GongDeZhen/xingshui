<?php
class ErrorsController extends BaseController
{
	public function init()
    {
        parent::init();
    }

    public function errorsAction()
    {
//         $url = json_decode($this->getGet('url'));
        $this->_view->errors = '抱歉，您无权访问此页面';
//         $this->_view->url = $url;
//         $this->_view->second = 3;
//         $this->display('../sys/flash');
//         return false;
    }
}