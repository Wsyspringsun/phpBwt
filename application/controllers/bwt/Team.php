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
	 * @output {"name":"data.user_res.dirCount","require":"true","type":"int","desc":"直推客户"}
     * @output {"name":"data.user_res.regCount","require":"true","type":"int","desc":"团队"}
     * @output {"name":"data.user_res.dirName","require":"true","type":"int","desc":"推荐人"}
     * @output {"name":"data.team_res.dirCount","require":"true","type":"int","desc":"直推人数"}
     * @output {"name":"data.team_res.validCount","require":"true","type":"int","desc":"团队算力"}
     * @output {"name":"data.team_res.regCount","require":"true","type":"int","desc":"注册人数"}
     * @output {"name":"data.team_res.id","require":"true","type":"int","desc":"用户id"}
     * @output {"name":"data.team_res.member_lvl","require":"true","type":"int","desc":"用户等级"}
     * @output {"name":"data.team_res.mobile","require":"true","type":"int","desc":"用户账号"}
     * @output {"name":"data.team_res.real_name","require":"true","type":"int","desc":"用户名字"}
     */
	public function myTeam(){
		$id=$this->getId();
		 //$id=3;
		 $page=empty($this->input->post('page'))?1:($this->input->post('page'));//页数
		 $offset=$this->getPage($page,TEANLIMIT);//偏移量
		 $data=$this->getTeamData($id,$offset);
		 show200($data);
	}
	 /**
     * @title 查看团队
     * @desc  (查看团队)
	 * @input {"name":"id","require":"true","type":"int","desc":"用户id"}
     * @output {"name":"code","type":"int","desc":"200:成功,300各种提示信息"}
     * @output {"name":"msg","type":"string","desc":"信息说明"}
      * @output {"name":"data.user_res.dirCount","require":"true","type":"int","desc":"直推客户"}
     * @output {"name":"data.user_res.regCount","require":"true","type":"int","desc":"团队"}
     * @output {"name":"data.user_res.dirName","require":"true","type":"int","desc":"推荐人"}
     * @output {"name":"data.team_res.dirCount","require":"true","type":"int","desc":"直推人数"}
     * @output {"name":"data.team_res.validCount","require":"true","type":"int","desc":"团队算力"}
     * @output {"name":"data.team_res.regCount","require":"true","type":"int","desc":"注册人数"}
     * @output {"name":"data.team_res.id","require":"true","type":"int","desc":"用户id"}
     * @output {"name":"data.team_res.member_lvl","require":"true","type":"int","desc":"用户等级"}
     * @output {"name":"data.team_res.mobile","require":"true","type":"int","desc":"用户账号"}
     * @output {"name":"data.team_res.real_name","require":"true","type":"int","desc":"用户名字"}
     */
	
	public function seeTeam(){
		 $id = trim($this->input->post('id'));
		 //$id = 4;
		  if (empty($id)) {
				show300('会员id不能为空');
			}
		 $page=empty($this->input->post('page'))?1:($this->input->post('page'));//页数
		 $offset=$this->getPage($page,TEANLIMIT);//偏移量
		 $data=$this->getTeamData($id,$offset);
		show200($data);
	}
	
	 /**
     * @title 后台用
     * @desc  (后台用)
     */
	public function getTeamData($id,$offset){
		$data['user_res']['dirCount']=$this->member_model->getTeamCount($id);//直推人数
		$data['user_res']['dirName']=$this->member_model->getDirName($id)['real_name'];//推荐人名称
		$user_ids=$this->member_model->getChild($id);
		if(!empty($user_ids)){
					$user_ids_arr=explode(',',$user_ids);  
					if(!empty($user_ids_arr)){
						unset($user_ids_arr[0]);
						if(!empty($user_ids_arr)){
						$data['user_res']['regCount']=count($user_ids_arr);//注册人数
						}else{
						$data['user_res']['regCount']=0;//注册人数 
					}
					}else{
					 $data['user_res']['regCount']=0;//注册人数 
				  }
				 }else{
					$data['user_res']['regCount']=0;//注册人数 
			}
		$data['team_res']=$this->member_model->getTeam(TEANLIMIT,$offset,$id);
		if(!empty($data['team_res'])){
				foreach($data['team_res'] as $k => $v){
					$data['team_res'][$k]['mobile']=substr_replace($v['mobile'],'****',3,4);
					$data['team_res'][$k]['real_name']="**".mb_substr($v['real_name'], -1);
				  $data['team_res'][$k]['dirCount']=$this->member_model->getTeamCount($v['id']);//直推人数
				  $team_ids=$this->member_model->getChild($v['id']);
				  if(!empty($team_ids)){
					$team_ids_arr=explode(',',$team_ids);  
					if(!empty($team_ids_arr)){
						unset($team_ids_arr[0]);
						if(!empty($team_ids_arr)){
						$data['team_res'][$k]['validCount']=$this->member_model->getValidNum($team_ids_arr,1);//认证人数 或 团队算力 
						$data['team_res'][$k]['regCount']=count($team_ids_arr);//注册人数
						}else{
					 $data['team_res'][$k]['validCount']=0;//认证人数 或 团队算力 
						$data['team_res'][$k]['regCount']=0;//注册人数 
				  }
					}else{
					 $data['team_res'][$k]['validCount']=0;//认证人数 或 团队算力 
						$data['team_res'][$k]['regCount']=0;//注册人数 
				  }
				  }else{
					 $data['team_res'][$k]['validCount']=0;//认证人数 或 团队算力 
						$data['team_res'][$k]['regCount']=0;//注册人数 
				  }
			}  
		}
		return   $data;
	}	
}
