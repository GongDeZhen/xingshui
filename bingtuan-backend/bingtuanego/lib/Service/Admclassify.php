<?php
class Service_Admclassify extends Service
{
    public function classifyList($page, $pageSize, $name)
    {
        $whsql = '';
        $whereStr[] = ($name)?" name LIKE '%$name%' ":'';
        $whsql = $whereStr? implode(' and ',$whereStr):'';
        if (!empty($whsql)) {
            $where = 'where';
        }
        
        $sql = "SELECT * FROM `goods_type` $where $whsql order by `order` asc, create_time desc limit $page, $pageSize";
        return array($this->db->fetchAll($sql), $this->db->fetchOne("select count(*) from goods_type $where $whsql"));
    }

    
    
    public function upShow($id, $is_show)
    {
     	return $this->db->update('goods_type', array('is_show'=>$is_show), "id=$id");
    }
    
    public function getGoodsType($id)
    {
        return $this->db->fetchRow('SELECT * FROM `goods_type` where id='.$id);
    }

    public function add($data)
    {
        $data['create_time'] = time();
        $this->db->insert('goods_type', $data);
        return $this->db->lastInsertId();
    }

    public function delete($id)
    {
        return $this->db->delete('goods_type',"id=$id");
    }

    public function update($id, $data)
    {
        return $this->db->update('goods_type', $data, "id=$id");
    }
}