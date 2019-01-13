<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');
class Admin_receive_model extends MY_Model
{
    private $table ='admin_receive';
	public function __construct()
	{
		parent::__construct($this->table);
	}
	
}
?>
