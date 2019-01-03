<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Index extends CI_Controller 
{
    private static $data = array();
    public function __construct()
    {
        parent::__construct();        
		 $this->load->library(array('sms/api_demo/SmsDemo','weixin/wechatCallbackapiTest'));
    }
    public function index()
    {
        $sms = new SmsDemo();
        $res = $sms->sendSms('13203561153', SMS_ID, SMS_SIGN,['code'=>'你好，这是测试内容']);
        if($res->Code=='OK'){            
            echo '发送成功';
        }else{
            echo '发送失败';
        }
    }

    public function test(){
            
            echo "wsy测试成功";
    
    }
}
