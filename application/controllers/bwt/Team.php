<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Team extends CI_Controller 
{
    private static $data = array();
    public function __construct()
    {
        parent::__construct();  
		$this->load->model(array('member_model','member_resouce_model'));		
    }
	
	 /**
     * @title 获取团队列表
     * @desc  (获取团队列表)
     * @output {"name":"code","type":"int","desc":"200:成功,300各种提示信息"}
     * @output {"name":"msg","type":"string","desc":"信息说明"}
     * @output {"name":"data.dirCount","require":"true","type":"int","desc":"直推人数"}
     * @output {"name":"data.validCount","require":"true","type":"int","desc":"团队算力"}
     * @output {"name":"data.regCount","require":"true","type":"int","desc":"注册人数"}
     * @output {"name":"data.id","require":"true","type":"int","desc":"用户id"}
     * @output {"name":"data.member_lvl","require":"true","type":"int","desc":"用户等级"}
     * @output {"name":"data.mobile","require":"true","type":"int","desc":"用户账号"}
     * @output {"name":"data.real_name","require":"true","type":"int","desc":"用户名字"}
     */
	public function myTeam(){
		 		
		$id=$this->getId();
		 $page=empty($this->input->post('page'))?1:($this->input->post('page'));//页数
		 //$page=2;
		 $offset=$this->getPage($page,TEANLIMIT);//偏移量
	
		$data=$this->member_model->getTeam(TEANLIMIT,$offset,$id);
		if(!empty($data)){
				foreach($data as $k => $v){
					$data[$k]['mobile']=substr_replace($v['mobile'],'****',3,4);
				  $data[$k]['dirCount']=$this->member_model->getTeamCount($v['id']);//直推人数
				  $ids=$this->member_model->getChild($v['id']);
				  if(!empty($ids)){
					$ids_arr=explode(',',$ids);  
					if(!empty($ids_arr)){
						unset($ids_arr[0]);
						if(!empty($ids_arr)){
						$data[$k]['validCount']=$this->member_model->getValidNum($ids_arr,1);//认证人数 或 团队算力 
						$data[$k]['regCount']=count($ids_arr);//注册人数
						}else{
					 $data[$k]['validCount']=0;//认证人数 或 团队算力 
						$data[$k]['regCount']=0;//注册人数 
				  }
					}else{
					 $data[$k]['validCount']=0;//认证人数 或 团队算力 
						$data[$k]['regCount']=0;//注册人数 
				  }
				  }else{
					 $data[$k]['validCount']=0;//认证人数 或 团队算力 
						$data[$k]['regCount']=0;//注册人数 
				  }
			}  
		}
		
		show200($data);
	}
	 /**
     * @title 查看团队
     * @desc  (查看团队)
	 * @input {"name":"id","require":"true","type":"int","desc":"用户id"}
     * @output {"name":"code","type":"int","desc":"200:成功,300各种提示信息"}
     * @output {"name":"msg","type":"string","desc":"信息说明"}
     * @output {"name":"data.dirCount","require":"true","type":"int","desc":"直推人数"}
     * @output {"name":"data.validCount","require":"true","type":"int","desc":"团队算力"}
     * @output {"name":"data.regCount","require":"true","type":"int","desc":"注册人数"}
     * @output {"name":"data.id","require":"true","type":"int","desc":"用户id"}
     * @output {"name":"data.member_lvl","require":"true","type":"int","desc":"用户等级"}
     * @output {"name":"data.mobile","require":"true","type":"int","desc":"用户账号"}
     * @output {"name":"data.real_name","require":"true","type":"int","desc":"用户名字"}
     */
	
	public function seeTeam(){
		 $this->getId();
		 $id = trim($this->input->post('id'));
		  if (empty($id)) {
				show300('会员id不能为空');
			}
		 $page=empty($this->input->post('page'))?1:($this->input->post('page'));//页数
		 //$page=2;
		 $offset=$this->getPage($page,TEANLIMIT);//偏移量
		 //$id=2;
		
		$data=$this->member_model->getTeam(TEANLIMIT,$offset,$id);
		if(!empty($data)){
				foreach($data as $k => $v){
				  $data[$k]['mobile']=substr_replace($v['mobile'],'****',3,4);
				  $data[$k]['dirCount']=$this->member_model->getTeamCount($v['id']);//直推人数
				  $ids=$this->member_model->getChild($v['id']);
				  if(!empty($ids)){
					$ids_arr=explode(',',$ids);  
					if(!empty($ids_arr)){
						unset($ids_arr[0]);
						if(!empty($ids_arr)){
						$data[$k]['validCount']=$this->member_model->getValidNum($ids_arr,1);//认证人数 或 团队算力 
						$data[$k]['regCount']=count($ids_arr);//注册人数
						}else{
					 $data[$k]['validCount']=0;//认证人数 或 团队算力 
						$data[$k]['regCount']=0;//注册人数 
				  }
					}else{
					 $data[$k]['validCount']=0;//认证人数 或 团队算力 
						$data[$k]['regCount']=0;//注册人数 
				  }
				  }else{
					 $data[$k]['validCount']=0;//认证人数 或 团队算力 
						$data[$k]['regCount']=0;//注册人数 
				  }
			}  
		}
		show200($data);
	}
}
