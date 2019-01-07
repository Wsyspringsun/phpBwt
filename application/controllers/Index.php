<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Index extends CI_Controller 
{
    private static $data = array();
    public function __construct()
    {
        parent::__construct();        
        $this->load->library(array('sms/api_demo/SmsDemo'));
    }
    public function index()
    {
		echo  "欢迎使用".SITENAME;
      
    }

}
