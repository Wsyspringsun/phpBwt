<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Index extends CI_Controller 
{
    private static $data = array();
    public function __construct()
    {
        parent::__construct();        
    }
    public function index()
    {
        echo 'Welcome to'.SITENAME;
    }
	public function test(){
		
		echo "wsy测试成功";
	
	}
}
