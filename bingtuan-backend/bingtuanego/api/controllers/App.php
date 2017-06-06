<?php
class AppController extends BaseController
{
    /**
     * 获取新的App更新
     */
    public function getNewAppAction()
    {
        $info = Service::getInstance('app')->index();
        if ($info) {
            $hash = $info['hash'];
            if ($hash) {
                $base = Yaf_Application::app()->getConfig()->get('file')->get('url');
                $info['hash'] = $base . "/{$hash[0]}{$hash[1]}/{$hash[2]}{$hash[3]}/$hash.apk";
            }
            $this->respon(1,$info);
        } else {
            $this->respon(1,array());
        }
    }
}