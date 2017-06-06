<?php

class Service_Test extends Service
{
	public function Index($id)
	{
		$where['id'] = $id;
		return $data = $this->db->fetchAll("select * from test ".$where);
	}
}