<?php

class ExhibitController extends BaseController
{
    public function init()
    {
        parent::init();
    }


    public function indexAction( )
    {
        $perpage = 15;
        $page = $this->getQuery('page', 1);
        $name = $this->getQuery('name', "");

        $data = Service::getInstance('Exhibit')->lists( $page , $perpage , $name  );
        $url = '/Exhibit?page=__page__';
        if ( $name ) $url .= "&name=".$name; 
        $pagebar = Util::buildPagebar( $data['total'], $perpage, $page, $url );

        $this->_view->list = $data['list'];
        $this->_view->pagebar = $pagebar;
    }

    public function newAction( )
    {

       if (!$this->isPost()) {
            return;
        }

        if (!$this->_checkForm()) {
            return;
        }

        $name = $this->getPost('name');

        $img =$this->getPost('img','');
        $englishname = $this->getPost('englishname', '');
        $detail = $this->getPost('detail', '');
        $organization = $this->getPost('organization', '');
        $datedetail = $this->getPost('datedetail1', '') . ' — ' . $this->getPost('datedetail2', '');
        $addr = $this->getPost('addr', '');
        $pro = $this->getPost('pro');
        $isAdmin=1;
        $id = Service::getInstance('exhibit')->insertExhibit($name, $englishname, $detail, $datedetail,$organization, $addr, $img, $pro, $isAdmin);
        if (!$id) {
            $this->error('添加失败，请联系管理员');
            return;
        }

        $this->flash('/exhibit/', '添加成功');
        return false;
    }
    


    public function delAction()
    {
        $id = $this->getQuery('id', 0);
        
        Service::getInstance('exhibit')->delById( $id );

        $this->flash('/exhibit/', '删除成功');

        return false;
    }

    public function showAction()
    {
        $id = $this->getQuery('id', 0);
        $exhibit = Service::getInstance('exhibit')->getExhibitByid( $id );
        $exhibit['img'] = $exhibit['img'] ? Util::getpicbyhash($exhibit['img']) : '';
        $list = Service::getInstance('exhibit')->productionlists( $id );
        $this->_view->exhibitlist = $list['list'];
        $this->_view->exhibit = $exhibit;
    }


   public function editAction()
    {
    	
        $id = $this->getQuery('id', 0);
        $exhibit = Service::getInstance('exhibit')->getExhibitByid( $id );
        $list = Service::getInstance('exhibit')->productionlists( $id );
        $this->_view->exhibitlist = $list['list'];
        $exhibit['old_img']= $exhibit['img'];
        $exhibit['img'] = $exhibit['img'] ? Util::getpicbyhash($exhibit['img']) : '';
        $this->_view->exhibit = $exhibit;

        if (!$this->isPost()) {
            return;
        }

        if (!$this->_checkForm()) {
        	
            return;
        }

        $img =$this->getPost('img');
        if($img==''){
        	$img=$exhibit['img'];
        }
        $name = $this->getPost('name');
        $englishname = $this->getPost('englishname', '');
        $detail = $this->getPost('detail', '');
        $datedetail = $this->getPost('datedetail1', '') . ' — ' . $this->getPost('datedetail2', '');
        $organization = $this->getPost('organization', '');
        $addr = $this->getPost('addr', '');
        $pro = $this->getPost('pro');
        Service::getInstance('exhibit')->updateExhibit( $id, $name, $englishname, $detail, $datedetail, $organization, $addr , $img, $pro);

        $this->flash('/exhibit/show?id=' . $id, '修改成功');
        return false;

    }

    public function getProductionAction()
    {

        $page = $this->getpost('p', 1);
        $data = Service::getInstance('Production')->getLists( $page , 12 );
        $data['page'] = Util::buildPagebar( $data['total'], 12, $page, 'javascript:getList(__page__)' );
        if ($data['total']) {
            $data['status'] = 1;
        } else {
            $data['status'] = 0;
        }
        echo Util::arrayToJson($data);
        return false;
    }

    protected function _checkForm()
    {
        $name = $this->getPost('name', '');
        if(!$name) {
            $this->error('请填写展览中文名称');
            return false;
        }

        $detail = $this->getPost('detail', '');
        if ( !$detail ) {
            $this->error('请填写展览介绍!');
            return false;
        }

        $datedetail1 = $this->getPost('datedetail1', '');
        if ( !$datedetail1 ) {
            $this->error('请填写展览时间!');
            return false;
        }

        $datedetail2 = $this->getPost('datedetail2', '');
        if ( !$datedetail2 ) {
            $this->error('请填写展览时间!');
            return false;
        }
        $addr = $this->getPost('addr', '');
        if ( !$addr ) {
            $this->error('请填写展览地址!');
            return false;
        }
        
        //$img = $this->getFile('img', '');
        if ( !$_FILES ) {
            //$this->error('请上传展览封面!');
            //return false;
        }

        return true;
    }

    public function uploadAction() {

        if ($_FILES['file']) {

            $info['id'] = 0;
            $info['result'] = '';
            $info['jsonrpc'] = '2.0';

            $pname = explode('.', $_FILES['file']['name']);
            array_pop($pname);
            $pname = implode('', $pname);
            $img = Util::uplodeHash($_FILES['file'], DATA_PATH);
            if ($img) {

                $res['name'] = $pname;
                $res['img'] = $img['hash'];
                $res['source'] = '1';
                $this->db->insert('camart_production', $res );
            }
            $img['name'] = $pname;
            $img['hash'] = Util::getpicbyhash($img["hash"]);

            $info['id'] = $this->db->lastInsertId();
            $info['result'] = $img;
            exit(json_encode($info));
        }
        exit(json_encode($info));
    }
}