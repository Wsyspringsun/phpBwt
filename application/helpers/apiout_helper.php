<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

//成功获取
function show200($data=[],$msg='获取成功')
{
    if(is_string($data)){       //处理只传提示信息的场景如show200('操作成功 '); 
        $msg=$data;
        unset($data);
        $data=[];
    }
    $out['code']=200;
    $out['msg']=$msg;
    $out['data']=$data;
    echo json_encode($out,JSON_UNESCAPED_UNICODE);
    exit();
}
//用户级提示性输出接口,用于输出针对用户的提示信息
 function show300($msg)
{
    $out['code']=300;
    $out['msg']=$msg;
    $out['data']=[];
    echo json_encode($out,JSON_UNESCAPED_UNICODE);
    exit();
}
//开发人员提示性输出接口,用于输出供开发人员参考的提示信息
function show301($msg)
{
    $out['code']=300;
    $out['msg']=$msg;
    $out['data']=[];
    echo json_encode($out);
    exit();
}
//获取失败
function show400($msg='暂无数据')
{
     
    $out['code']=400;
    $out['msg']=$msg;
    $out['data']=[];
    echo json_encode($out);
    exit();
}
//参数错误接口
function show401($msg='参数错误')
{
    $out['code']=401;
    $out['msg']=$msg;
    $out['data']=[];
    echo json_encode($out,JSON_UNESCAPED_UNICODE);
    exit();
}
//未登录
function show888($msg='您还未登录,请先登录')
{
    $out['code']=888;
    $out['msg']=$msg;
    $out['data']=[];
    echo json_encode($out);
    exit();
}
//后台用
function show200_admin($data,$count,$msg='获取成功'){
    $out['code']=200;
    $out['msg']=$msg;
    $out['count']=$count;
    $out['data']=$data;
    echo json_encode($out);
    exit();    
}
//后台用
function show400_admin($msg='暂无数据'){
    $out['code']=400;
    $out['msg']=$msg;
    $out['count']=0;
    $out['data']=[];
    echo json_encode($out);
    exit();
}

/*
 * param fixed $data    参数可以是除了对象以外的所有数据类型，比如：字符串，数组，jason等
function console($data){
     $stdout = fopen('php://stdout', 'w');
     fwrite($stdout,json_encode($data)."\n");   //为了打印出来的格式更加清晰，把所有数据都格式化成Json字符串
     fclose($stdout);
}
 */

/* End */
