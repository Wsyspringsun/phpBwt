<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');
class member_pay_record_model extends MY_Model
{
    private $table ='member_pay_record';
	public function __construct()
	{
		parent::__construct($this->table);
	}
	
}
?>
