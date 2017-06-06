<?php
class Template
{
    private $_vars = array();
    private $_errors = array();
    private $_basePath = '';

    public function __construct() 
    {
        //
    }

    public function __set($name, $value)
    {
        if ($name{0} != '_') {
            $this->_vars[$name] = $value;
        }

        return false;
    }

    public function __get($name)
    {
        if (isset($this->_vars[$name])) {            
            return $this->_vars[$name];
        }

        return false;
    }    
    
    public function setBasePath($path)
    {
        $this->_basePath = $path;
    }

    protected function escape($name)
    {
        return htmlspecialchars($this->_vars[$name], ENT_COMPAT);
    }
    
    public function error($message = '')
    {
        if (!$message) {
            return count($this->_errors);
        }

        $this->_errors[] = $message;
    }
    
    public function showErrors()
    {
        if (!$this->error()) {
            return false;
        }

        $html  = '<div class="div_errors">';
        $html .= '<ul>';
        foreach ($this->_errors as $message) {
            $html .= '<li>' . $message . '</li>';
        }
        $html .= '</ul>';
        $html .= '</div>';

        echo $html;
    }
    
    public function flash($message, $url, $seconds = 2)
    {
        $this->url = $url;
        $this->message = $message;
        $this->seconds = $seconds;

        $this->display('flash.html');

        exit;
    }
    
    public function halt($message, $url = 'javascript: history.go(-1)')
    {
        $this->url = $url;
        $this->message = $message;

        $this->display('halt.html');

        exit;
    }

    public function render($file)
    {
        error_reporting(E_ALL ^ E_NOTICE);

        ob_start();
        include $file;
        
        return ob_get_clean();
    }
    
    public function display($file)
    {
        $html = $this->render($this->_basePath . $file);

        echo $html;
    }
    
    // helpers
    public function param($name, $default = '', $gpc = 'pg')
    {
        $val = $default;
        
        if ($gpc == 'pg') {
            $val = isset($_GET[$name]) ? $_GET[$name] : '';
            $val = isset($_POST[$name]) ? $_POST[$name] : $val;
        } elseif ($gpc == 'gp') {
            $val = isset($_POST[$name]) ? $_POST[$name] : '';
            $val = isset($_GET[$name]) ? $_GET[$name] : $val;
        } elseif ($gpc == 'p' && isset($_POST[$name])) {            
            $val = $_POST[$name];
        } elseif ($gpc == 'g' && isset($_GET[$name])) {
            $val = $_GET[$name];
        }

        return $val;
    }

    public function formSelect($name, $options = array(), $default = null, $onchange = "", $multiple = false)
    {
        $multiple = $multiple ? ' multiple="true"' : '';
        $html  = '<select name="' . $name . '" ' . $multiple . ' '. $onchange .' >';
        foreach ($options as $k => $v) {
            if ($multiple && is_array($default)) {
                $selected = in_array($k, $default) ? ' selected="selected"' : '';
            } else {
                $selected = $k == $default ? ' selected="selected"' : '';
            }

            $html .= '<option value="' . $k . '"' . $selected. '>' . $v . '</option>';
        }
        $html .= '</select>';

        return $html;
    }

    public function formRadio($name, $options = array(), $default = null, $sep = '&nbsp; ')
    {
        $items = array();
        foreach ($options as $v => $label) {
            $checked = $v == $default ? ' checked="checked"' : '';
            $items[] = "<input type=\"radio\" id=\"radio_{$name}_{$v}\" name=\"$name\" value=\"$v\" $checked /> <label for=\"radio_{$name}_{$v}\">$label</label>";
        }

        return join($sep, $items);
    }
}