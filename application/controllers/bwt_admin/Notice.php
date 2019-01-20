<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * 我的消息
* */
class Notice extends CI_Controller
{
    private static $data = array();
    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('notice_model'));
    }
    /**
     *@title 公告列表_后台
     *@desc 公告列表_后台
     * */
    public function noticeList(){
		$page=empty($this->input->post('page'))?1:($this->input->post('page'));//页数
		$page=1;
		$offset=$this->getPage($page,NOTICELIMIT);//偏移量
		$where=['1'=>1];
		$data['data']=$this->notice_model->getNoticeList(NOTICELIMIT,$offset,$where);
		show200($data);
    }
	/**
     *@title 公告添加_后台
     *@desc 公告添加_后台
     * */
    public function addNotice(){
		$data['title']=trim($this->input->post('title'));
		$data['content']=trim($this->input->post('content'));
		$data['admin_id']=trim($this->input->post('admin_id'));
		$data['admin_name']=trim($this->input->post('admin_name'));
		$data['type']=trim($this->input->post('type'));
		$data['pic']=trim($this->input->post('pic'));
		$data['is_show']=trim($this->input->post('is_show'));
		
		//模拟数据
		
		$data['title']='测试标题';
		$data['content']='测试内容';
		$data['admin_id']=1;
		$data['admin_name']='小果子';
		$data['type']=0;
		$data['pic']='gfdgfdgfd';
		$data['is_show']=1;
		
		//模拟数据
		$res=$this->notice_model->insert($data);
		if($res){
			show200('上传成功');
		}else{
			show300('上传失败');
		}
    }
	/**
     *@title 公告更新_后台
     *@desc 公告更新_后台
     * */
    public function editNotice(){
		$notice_id=trim($this->input->post('notice_id'));
		$data['content']=trim($this->input->post('content'));
		$data['admin_id']=trim($this->input->post('admin_id'));
		$data['admin_name']=trim($this->input->post('admin_name'));
		$data['type']=trim($this->input->post('type'));
		$data['pic']=trim($this->input->post('pic'));
		$data['is_show']=trim($this->input->post('is_show'));
		
		//模拟数据
		$notice_id=6;
		$data['title']='测试修改标题';
		$data['content']='测试修改内容';
		$data['admin_id']=1;
		$data['admin_name']='小果子';
		$data['type']=0;
		$data['pic']='ghgfhfgh';
		$data['is_show']=1;
		
		//模拟数据
		
		
		$res=$this->notice_model->updatewhere(['notice_id'=>$notice_id],$data);
		if($res){
			show200('更新成功');
		}else{
			show300('更新失败');
		}
    }
	/**
     *@title 公告删除_后台
     *@desc 公告删除_后台
     * */
    public function delNotice(){
		$notice_id=trim($this->input->post('notice_id'));
		$notice_id=6;
		$res=$this->notice_model->delWhere(['notice_id'=>$notice_id]);
		if($res){
			show200('删除成功');
		}else{
			show300('删除失败');
		}
    }
	
}