<?php

class DataController extends BaseController
{
    public function init()
    {
        parent::init();
    }

    public function indexAction()
    {

        $perpage = 20;
        $page = $this->getQuery('page', 1);

        $data = Service::getInstance('company')->lists( $page , $perpage  );

        $uri = str_replace('page='.$page, '', $_SERVER['REQUEST_URI'] );
        $uri = str_replace('?', '', $uri );
        $url = $uri .'?'. 'page=__page__';
        $pagebar = Util::buildPagebar($data['total'], $perpage, $page, $url);

        //dump( $data );
        $this->_view->series = Service::getInstance('dataseries')->count();
        $this->_view->goods = Service::getInstance('datagoods')->count();
        $this->_view->deals = Service::getInstance('dataebookscomposition')->count();
        $this->_view->total = $data['total'];
        $this->_view->lists = $data['list'];
        $this->_view->pagebar = $pagebar;
    }

    public function testAction( )
    {

    }

    public function imgsAction()
    {
        $perpage = 18;
        $page = $this->getQuery('page', 1);

        $data = Service::getInstance('datagoods')->filestmp( $page , $perpage  );

        $url = '?page=__page__';

        foreach ( $this->getRequest()->getQuery() as $k => $v) 
        {
            if ( $k <> "page" ) $url .= "&".$k."=".$v;
        }
        $pagebar = Util::buildPagebar($data['total'], $perpage, $page, $url);
        //dump($data['list']);
        $this->_view->lists = $data['list'];
        $this->_view->pagebar = $pagebar;
    }

    public function seriesAction()
    {
        $perpage = 18;
        $page = $this->getQuery('page', 1);
        $companyid = $this->getQuery('companyid', 0);

        $data = Service::getInstance('dataseries')->lists( $companyid , $page , $perpage  );

        $url = '?page=__page__';

        foreach ( $this->getRequest()->getQuery() as $k => $v) 
        {
            if ( $k <> "page" ) $url .= "&".$k."=".$v;
        }
        $pagebar = Util::buildPagebar($data['total'], $perpage, $page, $url);

        $this->_view->lists = $data['list'];
        $this->_view->pagebar = $pagebar;

        $this->_view->company = Service::getInstance('company')->info( $companyid );
        $this->_view->series = Service::getInstance('dataseries')->count( $companyid );
        $this->_view->ebooks = Service::getInstance('dataebooks')->count( $companyid );
        //$this->_view->ebooksComposition = Service::getInstance('dataebooksComposition')->count( $companyid );
    }

    public function ebookAction()
    {
        $perpage = 18;
        $page = $this->getQuery('page', 1);
        $seriesid = $this->getQuery('seriesid', 0);

        $this->_view->info = Service::getInstance('dataseries')->info( $seriesid );
        //dump($this->_view->info);
        $data = Service::getInstance('dataebooks')->lists( $seriesid , $page , $perpage  );
        $this->_view->companyinfo = Service::getInstance('company')->info( $this->_view->info['companyid'] );

        $url = '?page=__page__';

        foreach ( $this->getRequest()->getQuery() as $k => $v) 
        {
            if ( $k <> "page" ) $url .= "&".$k."=".$v;
        }
        $pagebar = Util::buildPagebar($data['total'], $perpage, $page, $url);

        $this->_view->lists = $data['list'];
        $this->_view->pagebar = $pagebar;
    }

    public function modernAction()
    {
        $perpage = 18;
        $page = $this->getQuery('page', 1);
        $seriesid = $this->getQuery('seriesid', 0);

        $this->_view->info = Service::getInstance('dataseries')->info( $seriesid );
        //dump($this->_view->info);
        $data = Service::getInstance('dataebooks')->modern( $page , $perpage  );
        $this->_view->companyinfo = Service::getInstance('company')->info( $this->_view->info['companyid'] );

        $url = '?page=__page__';

        foreach ( $this->getRequest()->getQuery() as $k => $v) 
        {
            if ( $k <> "page" ) $url .= "&".$k."=".$v;
        }
        $pagebar = Util::buildPagebar($data['total'], $perpage, $page, $url);

        $this->_view->lists = $data['list'];
        $this->_view->pagebar = $pagebar;
    }

    public function goodsAction()
    {
        $perpage = 18;
        $page = $this->getQuery('page', 1);
        $ebookid = $this->getQuery('ebookid', 0);

        $this->_view->info = Service::getInstance('dataebooks')->info( $ebookid );
        $data = Service::getInstance('datagoods')->getlistbyebookid( $ebookid , $page , $perpage  );
        $this->_view->companyinfo = Service::getInstance('company')->info( $this->_view->info['companyid'] );

        $url = '?page=__page__';

        foreach ( $this->getRequest()->getQuery() as $k => $v) 
        {
            if ( $k <> "page" ) $url .= "&".$k."=".$v;
        }
        $pagebar = Util::buildPagebar($data['total'], $perpage, $page, $url);
        //dump($data['list']);
        $this->_view->lists = $data['list'];
        $this->_view->pagebar = $pagebar;
    }

    public function goodsinfoAction()
    {
        $perpage = 18;
        $page = $this->getQuery('page', 1);
        $id = $this->getQuery('gid', 0);

        $this->_view->info = Service::getInstance('datagoods')->info( $id );
        //dump($this->_view->info);
        $this->_view->companyinfo = Service::getInstance('company')->info( $this->_view->info['ebooke']['companyid'] );
        //dump($this->_view->info);
    }

    public function coAction()
    {
        /*
        > db.data_ebooks_composition.ensureIndex({goodsid: 1},{unique:true});
        > db.data_ebooks_composition.getIndexes();

        base_ebooks logourl
        base_goods hdurl logourl
        base_series logourl
        */

        $db = $this->mongo->paicang;
        $where = array('_id'=>1);
        $data = $db->base_ebooks->find( $where );
        foreach ( $data as $document ) {
            dump( $document );
        }
        dump( $data );
        exit();
        $data = file_get_contents( 'http://ebookadmin.artron.net:8387/tulu/SearchEbooksBySerie.ashx?appcode=tulu&keywords=&serieId=17108&sorts=1&pg=1&sz=21' );
        $data = json_decode( $data , true );

        dump($data);


        exit;
        $data = file_get_contents( ROOT_PATH . "/cron/company.data" );
        $data = json_decode( $data , true );


        $fields = $this->db->desc( 'artron_company' );

        foreach ( $data['datas']['base_company'] as $key => $value ) 
        {
            if ( !isset( $value['id'] ) ) continue;
            
            foreach ( $value as $k => $v ) 
            {                
                if ( $v  == "" ) unset( $value[$k] );
                if ( !isset( $fields[$k] ) ) unset( $value[$k] );
            }
            $co = $this->db->fetchRow('SELECT * FROM artron_company WHERE id = ?', $value['id']);
            if ( !$co )
            {
                $this->db->insert('artron_company', $value);
            }

        }

        dump( $fields );
        exit;
        
    }




 }
 ?>