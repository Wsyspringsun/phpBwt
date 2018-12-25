<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * 我的消息
* */
class Notice {
    private static $data = array();
    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('notice_model'));
    }
    
    /**
     *@title 我的消息
     *@desc 我的消息(用于个人中心页)
     *
     *@input {"name":"user_id","require":"true","type":"string","desc":"用户id"}
     *
     *@output {"name":"code","type":"int","desc":"200:获取成功,300获取失败,无数据"}
     *@output {"name":"msg","type":"string","desc":"获取成功/获取失败"}
     *
     *@output {"name":"noticeId","desc":"消息ID"}
     *@output {"name":"noticeContent","desc":"消息内容"}
     *@output {"name":"readStatus","desc":"阅读状态,0未读,1已读"}
     * */
    public function myNotice()
    {   
        //$user_id=$this->input->post('user_id');
        $user_id=1;
        if (!$user_id){
            show3('参数错误');
        }
        $noticeList = $this->notice_model->getList($user_id);
        if(!empty($noticeList)){
            show200($noticeList);
        }else{
            show300('无消息');
        }
    }
    /**
     *@title 将消息标记为已读
     *@desc 将消息标记为已读
     *@input {"name":"noticeIds","require":"true","type":"int","desc":"消息ID"}
     *
     *@output {"name":"code","type":"int","desc":"200:成功,400失败,3:未登录,300各种提示信息"}
     *@output {"name":"msg","type":"string","desc":"返回信息;标记成功/失败"}
     * */
    public function read()
    {
        //$noticeIds=$this->input->post('noticeId');
        $noticeIds=1;
        if(!$noticeIds){
            show300('消息ID不能为空');
        }
        $bool = $this->notice_model->updateWhere(['noticeId'=>$noticeIds],['readStatus'=>1,'readTime'=>time()]);
        if($bool){
            show200('已标记为已读');
        }else{
            show300('标记已读失败');
        }
    }
}