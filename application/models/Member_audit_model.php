<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');
class Member_audit_model extends MY_Model
{
    private $table ='member_audit';
	public function __construct()
	{
		parent::__construct($this->table);
	}
	
}
?>
