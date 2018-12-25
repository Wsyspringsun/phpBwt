<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Feedback {
    private static $data = array();
    public function __construct()
    {
        parent::__construct();
        $this->load->model('feedback_model'); 
        $this->load->model('app_common_model');       //app接口统一输出格式
    }
    //反馈
    public function index()
    {   
        $userId=$this->input->get_post('userId');       //登录用户ID
        if (!$userId){
            $this->app_common_model->show_401();
        }
        $content=$this->input->get_post('content');
        if (empty(trim($content))){
            $this->app_common_model->show_300('反馈内容不能为空');
        }
        $data=[
            'userId'=>$userId,
            'content'=>$content,
            'createTime'=>time()
        ];
        $res=$this->feedback_model->insertData($data);
        if ($res){
            $this->app_common_model->show_200(['msg'=>'反馈成功'],'反馈成功');
        }else{
            $this->app_common_model->show_400('反馈失败');
        }
    }   
}