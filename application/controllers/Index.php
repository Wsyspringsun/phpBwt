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
        //echo 'Welcome wsy to'.SITENAME;

        //$mid = 12;
        //$ms = explode('\.',time().'');
        //var_dump($ms);
        //echo 'time:'.time();
        //echo microtime();
        //$n = date("Y m d H i s",time());
        //echo $n;
        list($t1, $t2) = explode(' ', microtime());
        echo date("Y m d H i s",floatval($t2)).'\n';
        echo intval(floatval($t1) * 1000);
        //echo (float)sprintf('%.0f', (floatval($t1) + floatval($t2)) * 1000);
    }
	public function test(){
		
		echo "wsy测试成功";
	
	}
}
