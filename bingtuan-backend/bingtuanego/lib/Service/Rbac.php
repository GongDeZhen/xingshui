<?php
class Service_Rbac extends Service
{
    public function getUserRbac() 
    {
        $menuArr = '';
        $developer = Yaf_Registry::get('developer');
        $menuID = $this->db2->fetchOne("SELECT tar.`permission` FROM `developers` AS adm LEFT JOIN `role` AS tar ON adm.`roleId` = tar.`id` WHERE adm.`id` = {$developer['id']} ");
//         $menuCustom = $this->buydb->fetchOne("SELECT tac.`permission` FROM `tb_adm_custom_role` AS tac WHERE user_id={$developer['adm_id']}");
//         $menuCustom = ($menuCustom)?','.$menuCustom:'';

        $menuArr = $menuID;
        $controller = array();
        $method = array();
        if ($menuArr) {
            $menuArr = $this->db2->fetchAll("SELECT controller,method FROM `menu` WHERE id IN ({$menuArr})");
            foreach ($menuArr as $k => $v) {
                $controller[] = strtolower($v['controller']);
                $method[] = strtolower($v['controller'].'_'.$v['method']);
            }
        }
        if ($developer['email'] == 'jason@suxin8.com') {
            return '*';
        } else {
            return array( 'controller' => array_unique($controller), 'action' => $method );
        }
    } 
}