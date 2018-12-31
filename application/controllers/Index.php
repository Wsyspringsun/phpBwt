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
        define('ORIGIN_BILL_STATS',array(
            "A" => "001"
        )) ;
        $arr = explode('-', '0-0-0-0');
        var_dump($arr) ;
        echo count($arr);
        echo array_keys(ORIGIN_BILL_STATS)[0];
    }
	public function test(){
		
		echo "wsy测试成功";
	
	}
}
