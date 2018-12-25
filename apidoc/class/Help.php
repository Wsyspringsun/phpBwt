<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * 我的消息
* */
class Help {
    private static $data = array();
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Help_model','help_model');
        $this->load->model('Type_model','type_model');//分类模型    
    }
    
    /**
     *@title 帮助
     *@desc 帮助(用于个人中心页)
     *     
     *@input {"name":"typeId","type":"string","desc":"分类ID,如果不传此参数则默认返回第一种分类下的帮助数据"}    
     *     
     *@output {"name":"code","type":"int","desc":"200:获取成功,400获取失败,无数据"}
     *@output {"name":"msg","type":"string","desc":"获取成功/获取失败"}
     *
     *@output {"name":"data","type":"array","desc":"帮助数组,下面是详细说明,index代表该数组下标"}
     *@output {"name":"data[index].question","desc":"问题","child":"1"}
     *@output {"name":"data[index].answer","desc":"答案","child":"1"}
     * */
    public function index()
    {   
        $typeId=$this->input->post('typeId');       //帮助分类Id
        $type=$this->type_model->getType_help('typeId,typeName');      //帮助分类
        
        if (!isset($type[0]['typeId'])){
            show300('暂无分类数据');
        }
        $typeId=empty($typeId)?$type[0]['typeId']:$typeId;
        $help=$this->help_model->get($typeId);
        if($help){
            show200(['type'=>$type,'help'=>$help]);
        }else{
            show400();
        }
    }
    
    
    
    
    
    
    
    
}