<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');
class Bill_model extends MY_Model
{
    private $table ='member_resouce';
	public function __construct()
	{
		parent::__construct($this->table);
	}
	public function getBillOutline($id){
		return $this->db->get_where($this->table,array('id' => $id))->row();
	}
}
?>
