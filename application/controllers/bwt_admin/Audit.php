<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Audit extends CI_Controller 
{
    private static $data = array();
    public function __construct()
    {
        parent::__construct();  
		$this->load->model(array('member_model','member_audit_model'));		
    }
	
	 /**
     * @title 获取缴费列表
     * @desc  (获取缴费列表)
     * @output {"name":"code","type":"int","desc":"200:成功,300各种提示信息"}
     * @output {"name":"msg","type":"string","desc":"信息说明"}
     * @output {"name":"data.mobile","require":"true","type":"int","desc":"用户账号"}
     * @output {"name":"data.real_name","require":"true","type":"int","desc":"用户名字"}
     */
	public function auditList(){
		//待定
		
		
	}
	 /**
     * @title 审核
     * @desc  (审核)
     * @output {"name":"code","type":"int","desc":"200:成功,300各种提示信息"}
     * @output {"name":"msg","type":"string","desc":"信息说明"}
     * @output {"name":"data.mobile","require":"true","type":"int","desc":"用户账号"}
     * @output {"name":"data.real_name","require":"true","type":"int","desc":"用户名字"}
     */
	public function audit(){
		//待定
		
	}
	
	/**
     * @title 审核通过
     * @desc  (审核通过)
     * @output {"name":"code","type":"int","desc":"200:成功,300各种提示信息"}
     * @output {"name":"msg","type":"string","desc":"信息说明"}
     * @output {"name":"data.mobile","require":"true","type":"int","desc":"用户账号"}
     * @output {"name":"data.real_name","require":"true","type":"int","desc":"用户名字"}
     */
	public function audiRsult(){
		
		print_r(654);exit;
		 $id = 1;
		 $audit_id=1;
		 
			 $audit['status']=1;
			 $audit['operation_id']='1';
			 $audit['operation_name']='小果子';
			 $audit['user_id']=$id;
			 $res=$this->member_audit_model->updateWhere(['id'=>$audit_id,'user_id'=>$id]);
		 if($res){
			$leve= $this->updateLevel($id);
			if($leve){
				show200('审核成功');
			}else{
				show300('升级成功');
			}
		 }else{
			 show300('审核失败');
		 }
	}
	
	
//升级会员等级判断
    public function updateLevel($id)
    {
       // print_r($id);exit;
        if (!$id) {
            show300('会员id不能为空');
        }
        //执行两次
        for($i=0;$i<=1;$i++){
            $ids = $this->member_model->getSup($id, $n = 0);
            $ids = explode(',', $ids);
            $where['is_valid'] = 1;
            $where_in = [
                'field' => 'id',
                'data' => $ids
            ];
            $data = $this->member_model->getWhere($where, $select = '*', $dbArray = [], $where_in);

            if (!empty($data)) {
                foreach ($data as $val) {

                    $pwhere=[
                        'referee_id'=>$val['id'],
                        'is_valid' => 1,

                    ];
                    $temp=$this->member_model->getWhere($pwhere,$select='id',$dbArray=[],$where_in=[]);
                    $result=[];//获取直推id
                    if($temp){
                        foreach ($temp as $val1){

                            array_push($result,$val1['id']);
                        }
                    }


                    switch ($val['member_lvl']) {
                        case "2":
                            $num = 3;
                            $cWhere = [
                                'is_valid' => 1,
                                'member_lvl' =>1
                            ];
                            break;
                        case "3":
                            $cWhere = [
                                'is_valid' => 1,
                                'member_lvl' =>2
                            ];
                            $num = 3;
                            break;
                        case "4":
                            $cWhere = [
                                'is_valid' => 1,
                                'member_lvl' =>3
                            ];

                            $num = 3;
                            break;
                        case "5":
                            $cWhere = [
                                'is_valid' => 1,
                                'member_lvl' =>4
                            ];
                            $num = 3;
                            break;
                        case "6":
                            $cWhere = [
                                'is_valid' => 1,
                                'member_lvl' =>5
                            ];
                            $num = 3;
                            break;
                        case "7":
                            $cWhere = [
                                'is_valid' => 1,
                                'member_lvl' =>6

                            ];
                            $num = 3;
                            break;
                        case "8":
                            $cWhere = [
                                'is_valid' => 1,
                                'member_lvl' =>7
                            ];
                            $num = 3;

                            break;

                        default:
                            $cWhere = [
                                'is_valid' => 1,
                                'referee_id' => $val['id'],
                                'member_lvl' => 1

                            ];
                            $num = 9;
                    }

                    $cWhere_in = [
                        'field' => 'referee_id',
                        'data' => $result
                    ];


                    if($val['member_lvl']==1){
                        $count = $this->member_model->getWhere_num($cWhere);//0级升一级，9个直推
                    }else{
                        //除一级以外的升级
                        $count =$this->member_model->getRefereeNum($cWhere,$dbArray=[],$cWhere_in,$groupBy='referee_id');

                    }


                    //升级
                    if ($count >= $num) {
                        $update['member_lvl'] = $val['member_lvl'] + 1;
                        $referee_id = $this->member_model->updateWhere(['id' => $val['id']], $update);

                    }

                }
            }
        }


        return true;
    }
    
}
