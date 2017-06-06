<?php
class Service_Admbrand extends Service
{
    public function brandList($page, $pageSize, $name)
    {
        $whsql = '';
        $whereStr[] = ($name)?" name LIKE '%$name%' ":'';
        $whsql = $whereStr? implode(' and ',$whereStr):'';
        if (!empty($whsql)) {
            $where = 'where';
        }
        $sql = "SELECT * FROM `brand` $where $whsql order by `order` asc, create_time desc limit $page, $pageSize";
        return array($this->db->fetchAll($sql), $this->db->fetchOne("select count(*) from brand $where $whsql"));
    }

    public function upShow($id, $is_show)
    {
        $is_show = ($is_show == 1) ? 0 : ($is_show == 0 ? 1 : 0);
         return $this->db->update('brand', array('is_show'=>$is_show), "id=$id");
    }
    
    public function getBrand($id)
    {
        return $this->db->fetchRow('SELECT * FROM `brand` where id='.$id);
    }

    public function brandAdd($data)
    {
        $data['create_time'] = time();
        $this->db->insert('brand', $data);
        return $this->db->lastInsertId();
    }

    public function delete($id)
    {
        return $this->db->delete('brand',"id=$id");
    }

    public function update($id, $data)
    {
        return $this->db->update('brand', $data, "id=$id");
    }
}
