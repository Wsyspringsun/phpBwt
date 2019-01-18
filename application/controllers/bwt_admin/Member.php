<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Member extends CI_Controller 
{
    private static $data = array();
    public function __construct()
    {
        parent::__construct();  
		$this->load->model(array('member_model','member_audit_model'));		
    }
	
	/**
     * @title 解封
     * @desc  (解封)
	 * @input {"name":"id","require":"true","type":"int","desc":"用户id"}
     * @output {"name":"code","type":"int","desc":"300:信息说明"}
     * @output {"name":"msg","type":"string","desc":"信息说明"}
     */
	
	public function thaw(){
		$id = trim($this->input->post('id'));
		$id=1;
		if(!$id){
			show300('用户id不能为空');
		}
		$update['is_freeze']=0;
		$update['freze_reason']="测试解封";
		$res = $this->member_model->updateWhere(['id' => $id], $update);
		if($res){
			show200('解封成功');
		}else{
			show300('解封失败');
		}
	}
    
}
