<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');
class Machine_model extends MY_Model
{
    private $table ='machine';
	public function __construct()
	{
		parent::__construct($this->table);
	}
	public function getMachineList($limit=10,$page=0){
		$this->db->select();
		$this->db->from($this->table);
		$this->db->limit($limit,$page);
		$this->db->where('price !=',0);
		$result = $this->db->get()->result_array();
		return $result;
	}
	public function getMachineCount(){
		$this->db->from($this->table);
		$this->db->where('price !=',0);
        return $this->db->count_all_results();
	}
}
?>
