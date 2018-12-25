<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

function sendSms($phone,$tplId,$par){
    
    // 签名
    $smsSign = "众合致胜"; // NOTE: 这里的签名只是示例，请使用真实的已申请的签名，签名参数使用的是`签名内容`，而不是`签名ID`
    $yzm=rand(1000,9999);
    
    require '/application/libraries/sms/api_demo/SmsDemo.php';
    $sms=new SmsDemo();
    $res=$sms->sendSms($phone,$tplId,$smsSign,$par);
    return $res;
}
function unsetArray_key($array,$key=''){
    if(is_array($array)){
        if(isset($array[$key])){
            unset($array[$key]);
        }
        return $array;
    }else{
        return false;
    }
}
/* End */