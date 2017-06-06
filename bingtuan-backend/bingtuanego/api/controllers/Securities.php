<?php

/**
 * 代金券管理控制类
 */
class SecuritiesController extends AdminController
{
    /**
     *数据模型对象
     */
    public function init()
    {
        parent::init();
        if (  Yaf_Registry::get( 'isLogin' ) <> true )
        {
            $this->respon( 0 , "登录超时!"  );
        }
        $this->_service = Service::getInstance('Admsecurities');
    }
    
    /**
     * 代金券列表
     */
    public function listAction()
    {
        $where      = '1=1';
        $postData   = $this->getGet();
        $where      = $this->_commbineSearchWhere($postData, $where);
        $curPage    = $this->getCurPage($this->getGet('page'), $where);
        $list       = $this->_service->getsecuritiesList($where,$curPage);
        $this->assignProvinceCityAreaList($postData);
        $this->assignProvinceCityAreaMapByList($list);
        $this->assignPageList($postData);

        $this->_view->list          = $list;
        $this->_view->searchData    = $postData;  
    }
    
     /**
     * 查看视图
     * @return [type] [description]
     */
    public function detailAction()
    {
        if($_GET['id']) {
            $record     = $this->_service->getRecordById($_GET['id']);

            $this->_view->record    = $record;
        }
        $this->assignProvinceCityAreaList($record);
    }

    /**
     * 更新数据
     * @return [type] [description]
     */
    public function updateAction()
    {
        $verifyFields   = array('name' => '名称', 'line' => '代金券数额', 'least' => '金额下限');
        $data   = $this->_verifyPostData($verifyFields);
        if(!is_array($data)) {
            $this->flash('/securities/detail', '操作失败，' . $data);
            exit();
        }
        $data['securities_number']   = strftime("%Y%m%d%H%M%S") . mt_rand(100,999);
        $id     = $this->_service->updateData($data);
        if(!$id) {
           $this->flash('/securities/detail', '操作失败');
           exit();
        }
        $this->flash('/securities/list', '提交成功');
        exit();
    }
}