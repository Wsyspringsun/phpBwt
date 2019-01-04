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
        $templateId = 'SMS_141945019';   //短信模板ID
        $smsSign = "众合致胜";           // 签名
        $sms = new SmsDemo();
        $res = $sms->sendSms('13203561153',$templateId ,$smsSign ,['code'=>'一个测试']);
        if($res->Code=='OK'){            
            echo '发送成功';
        }else{
            echo '发送失败';
        }
        /**
        **/
    }

    public function test(){
            
            echo "wsy测试成功";
    
    }
}
