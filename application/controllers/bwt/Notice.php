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
        $this->load->model(array('notice_model','notice_tag_model'));
    }
     /**
     *@title 获取公告
     *@desc 获取公告
	 * @input {"name":"page","require":"true","type":"int","desc":"页数"}
     *@output {"name":"code","type":"int","desc":"200:成功,400失败,3:未登录,300各种提示信息"}
     * @output {"name":"msg","type":"string","desc":"信息说明"}
	 * @output {"name":"data.data.notice_id","require":"true","type":"int","desc":"公告id"}
	 * @output {"name":"data.data.title","require":"true","type":"int","desc":"标题"}
	 * @output {"name":"data.data.create_time","require":"true","type":"int","desc":"时间"}
	 * @output {"name":"data.data.pic","require":"true","type":"int","desc":"图片路径"}
	 * @output {"name":"data.data.is_read","require":"true","type":"int","desc":"是否阅读"}
	 * @output {"name":"data.count","require":"true","type":"int","desc":"总计数"}
     * */
    public function getNoticeList(){
        //$id=$this->getId();
		$page=empty($this->input->post('page'))?1:($this->input->post('page'));//页数
        $id=1;
		$page=1;
		$offset=$this->getPage($page,NOTICELIMIT);//偏移量
		$where=['is_show'=>1];
		$data['data']=$this->notice_model->getNoticeList(NOTICELIMIT,$offset,$where);
		$tag=$this->notice_tag_model->getwhere(['user_id'=>$id],'notice_id');
		$tags=array();
		if(!empty($tag)){
			foreach($tag as $k=>$v){
			$tags[]=$v['notice_id'];
			}
		}
		if(!empty($data['data'])){
			foreach($data['data'] as $k=>$v){
			if(in_array($v['notice_id'],$tags)){
				$data['data'][$k]['is_read']=1;
			}else{
				$data['data'][$k]['is_read']=0;
			}
			}
		}else{
			$data['data']=array();
		}
		$data['count']=$this->notice_model->getNoticeCount();
		show200($data);
    }
	
	 /**
     *@title 获取公告详情
     *@desc 获取公告详情
     *@output {"name":"code","type":"int","desc":"200:成功,400失败,3:未登录,300各种提示信息"}
     * @output {"name":"msg","type":"string","desc":"信息说明"}
	 * @output {"name":"data.data.notice_id","require":"true","type":"int","desc":"公告id"}
	 * @output {"name":"data.data.title","require":"true","type":"string","desc":"标题"}
	 * @output {"name":"data.data.create_time","require":"true","type":"date","desc":"时间"}
	 * @output {"name":"data.data.pic","require":"true","type":"string","desc":"图片路径"}
	 * @output {"name":"data.data.content","require":"true","string":"int","desc":"内容"}
     * */
    public function getNoticeDetail(){
        //$id=$this->getId();
		//$notice_id= trim($this->input->post('notice_id'));
		$notice_id= 6;
		$id=1;
		$tag_is=$this->notice_tag_model->getWhere_num(['notice_id'=>$notice_id,'user_id'=>$id]);
		//echo "<pre>";
		//print_r($tag_is);exit;
		if(!$tag_is){
			$tag['notice_id']=$notice_id;
			$tag['user_id']=$id;
			$tag_is=$this->notice_tag_model->insert($tag);
		}
		$data=$this->notice_model->getwhererow(['notice_id'=>$notice_id,'is_show'=>1],'notice_id,title,content,create_time,pic');
		show200($data);
    }
}