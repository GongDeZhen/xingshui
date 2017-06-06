<?php

class StupidView extends Yaf_View_Simple
{
    protected $_layout;
    protected $_enableLayout = true;
    protected $_layoutContent = '';

    protected $_ajax = false;

    protected $_title = 'foxchat';
    protected $_metas = array();
    protected $_css = array();
    protected $_js = array();

    public $error = '';
    public $errno = 0;

    public function setLayout($layout)
    {
        $this->_layout = $layout;
    }

    public function enableLayout($switch = true)
    {
        $this->_enableLayout = $switch;
    }

    public function disableLayout()
    {
        $this->_enableLayout = false;
    }

    public function ajaxResponse($switch = true)
    {
        $this->_ajax = $switch;
    }

    public function isAjax() {
        return $this->_ajax;
    }

    public function setTitle($title = 'foxchat')
    {
        $this->_title = $title;
    }

    public function setMeta($name, $value = '')
    {
        $this->_metas[$name] = $value;
    }

    public function addCss($css)
    {
        $this->_css[] = $css;
    }

    public function addJs($js)
    {
        $this->_js[] = $js;
    }

    public function fill($name, $default = '')
    {
        if (isset($_REQUEST[$name])) {
            return $_REQUEST[$name];
        }

        return $default;
    }

    public function escape($str)
    {
        return htmlspecialchars($str);
    }

    public function formatTime($timestamp)
    {
        return Util::formatTime($timestamp);
    }

    public function render($view_path, $tpl_vars = NULL)
    {
        if ($tpl_vars) {
            foreach ($tpl_vars as $k => $v) {
                $this->assign($k, $v);
            }
        }

        // ajax
        if ($this->_ajax) {
            $data = isset($this->data) ? $this->data : array();
            $response = array(
                'errno' => $this->errno,
                'errmsg' => $this->error,
                'data' => $data,
            );

            header('Content-type: application/json');
            return json_encode($response);
        }

        // layout
        if ($this->_enableLayout && $this->_layout) {
            $this->disableLayout();
            $this->_layoutContent = $this->render($view_path, $tpl_vars);
            $html = $this->render($this->_layout . '.phtml', $tpl_vars);
            $this->enableLayout();

            return $html;
        }

        ob_start();
        error_reporting(E_ALL ^ E_NOTICE);
        include $this->_tpl_dir . '/' . $view_path;
        $html = ob_get_clean();

        return $html;
    }

    public function display($view_path, $tpl_vars = NULL)
    {
        $html = $this->render($view_path, $tpl_vars);

        echo $html;
    }
}