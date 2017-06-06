<?php

class IndexController extends BaseController
{
    public function init()
    {
        parent::init();
    }

    public function indexAction()
    {
        header('Location: /developer/signin');
        exit;
    }
    function readExcelAction(){
    	$objPHPExcel = PHPExcel_IOFactory::load('./data/1.xlsx');
    	var_dump($objPHPExcel->getActiveSheet()->toArray());
    }
}