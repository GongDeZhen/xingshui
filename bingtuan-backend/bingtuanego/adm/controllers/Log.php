<?php

/**
 * 日志管理控制器类
 * author: Nsinm
 * package: bingtuanego Log.php
 * Date: 2016/11/13
 * Time: 23:48
 */
class LogController extends BaseController
{
    /**
     * 初始化
     */
    public function init()
    {
        parent::init();
    }


    public function listAction(){
        //页数
        $page = $this->getQuery('page', '') ? : '1';

        //每页数量
        $perPage = 20;
        //索引值
        $index = ($page - 1) * $perPage;

        //记录数量
        $count = Service::getInstance('Admlog')->getLogCount();

        //获取日志列表
        $logList = Service::getInstance('Admlog')->getList($index, $perPage);

        //翻页url
        $url = "http://{$_SERVER['SERVER_NAME']}/log/list?page=__page__";

        $this->_view->list = $logList;
        //创建分页
        $this->_view->paglist = Util::buildPagebar($count, $perPage, $page, $url);
    }


}