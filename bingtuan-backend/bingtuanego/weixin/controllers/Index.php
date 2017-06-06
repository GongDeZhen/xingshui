<?php
class IndexController extends BaseController
{
    public function init()
    {
        parent::init();
    }

    public function indexAction()
    {
        header('Location: /Login/login');
        exit;
    }
}