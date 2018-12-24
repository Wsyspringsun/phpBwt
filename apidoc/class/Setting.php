<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * 安全设置
* */
class Setting {
    private static $data = array();
    public function __construct()
    {
        parent::__construct();
        $this->load->model('User_model','user_model');
    }
    
    /**
     *@title 获取用户手机号
     *@desc 获取用户手机号
     *@input {"name":"userId","require":"true","type":"int","desc":"登录用户ID"}
     *
     *@output {"name":"code","type":"int","desc":"200:成功,400失败,3:未登录,300:用户未设置手机号"}
     *@output {"name":"msg","type":"string","desc":"信息说明"}
     *
     *@output {"name":"data.userId","type":"int","desc":"用户ID"}
     *@output {"name":"data.phone","type":"string","desc":"用户手机号"}     
     * */
    public function getPhone(){
        $userId=$this->input->post('userId');
        if(!$userId){
            show3();
        }
        $select='userId,phone';
        $userinfo=$this->user_model->getUserRow($userId,$select);
        if(!$userinfo){
            show400('没有该用户信息');
        }
        if(!$userinfo['phone']) show300('还未设置手机号');
        $userinfo['phone']=substr_replace($userinfo['phone'], '****', 3,4);
        show200($userinfo);        
    }
    
    /**
     *@title 绑定手机号
     *@desc 绑定手机号
     *@input {"name":"userId","require":"true","type":"int","desc":"登录用户ID"}
     *@input {"name":"phone","require":"true","type":"string","desc":"用户手机号"}
     *
     *@output {"name":"code","type":"int","desc":"200:成功,400失败,3:未登录,300各种提示信息"}
     *@output {"name":"msg","type":"string","desc":"信息说明"}
     * */
    public function bindPhone(){
        $userId=$this->input->post('userId');
        $phone=$this->input->post('phone');
        
        if(!$userId){
            show3();
        }
        if(!$phone){
            show300('手机号不能为空');
        }
        $res=$this->user_model->update($userId,['phone'=>$phone]);
        if ($res){
            show200('修改成功');
        }
        else{
            show400('修改失败');
        }                
    }
    
    /**
     *@title 检测手机号输入是否正确
     *@desc 检测手机号输入是否正确(用于用户更改手机号时输入旧手机号是否正确的判断)
     *@input {"name":"userId","require":"true","type":"int","desc":"登录用户ID"}
     *@input {"name":"phone","require":"true","type":"string","desc":"用户手机号"}
     *
     *@output {"name":"code","type":"int","desc":"200:成功,400失败,3:未登录,300各种提示信息"}
     *@output {"name":"msg","type":"string","desc":"信息说明"}
     * */
    public function checkPhone(){
        $userId=$this->input->post('userId');
        $phone=$this->input->post('phone');
        
        if(!$userId){
            show3();
        }
        if(!$phone){
            show300('手机号不能为空');
        }
        $select='userId,phone';
        $userinfo=$this->user_model->getUserRow($userId,$select);
        if(@$userinfo['phone']==$phone){
            show200('手机号输入正确');
        }else{
            show400('手机号输入不正确');
        }                    
    }
    
    
    
    
    
    
    
}