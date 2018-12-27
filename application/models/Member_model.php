<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');
class Member_model extends MY_Model
{
    private $table ='member';
	public function __construct()
	{
		parent::__construct($this->table);
	}
	public function getMachineList($limit=10,$page=0){
		$this->db->select();
		//$this->db->from($this->table);
		$this->db->from($this->table);
		$this->db->limit($limit,$page);
		$this->db->where('price !=',0);
		$result = $this->db->get()->result_array();
		return $result;
	}
	public function getMax($parm){
		$this->db->select_max($parm);
		return $this->db->get($this->table)->row_array();
	}
	public function getMin($parm){
		$this->db->select_min($parm);
		return $this->db->get($this->table)->row_array();
	}
}
?>
