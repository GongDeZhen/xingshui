<?php
class Service_App extends Service
{
    /**
     * 版本更新
     * @param string $type
     */
    public function index()
    {
        $sql = 'SELECT * FROM `app_version` AS av ORDER BY create_time DESC';
        $list = $this->db->fetchRow($sql);
        return $list;
    }
    /**
     * 更新版本
     * @param unknown $id
     * @param unknown $name
     * @param unknown $code
     * @param unknown $details
     * @return boolean
     */
    public function update($id, $name, $code, $details)
    {
        $sql = 'SELECT * FROM `app_version` AS av WHERE id=?';
        $info = $this->db->fetchRow($sql, array($id));
        $path = Yaf_Application::app()->getConfig()->get('file')->get('dir'); // 获取文件目录路径
        if ($info) {
            $return = $this->db->update('app_version', array('name'=>$name, 'code'=>$code, 'details'=>$details, 'update_time'=>time()), 'id='.$id);
            if ($return) {
                if ($_FILES['hash']['error'] == 0 && $_FILES['hash']['size'] > 0) {
                    $file = Util::uplodeHash($_FILES['hash'], $path);
                    if (self::updateVersion($id, $file['hash'])) {
                        @Util::delFile($info['hash']);
                    }
                }
                echo 200;die;
            }
        } else {
            $this->db->insert('app_version', array('name'=>$name, 'code'=>$code, 'details'=>$details, 'create_time'=>time()));
            $id = $this->db->lastInsertId();
            if ($id) {
                if ($_FILES['hash']['error'] == 0 && $_FILES['hash']['size'] > 0) {
                    $file = Util::uplodeHash($_FILES['hash'], $path);
                    self::updateVersion($id, $file['hash']);
                }
                echo 200;die;
            }
        }
    }
    
    public function updateVersion($id, $hash)
    {
        return $this->db->update('app_version', array('hash'=>$hash), 'id='.$id);
    }
}