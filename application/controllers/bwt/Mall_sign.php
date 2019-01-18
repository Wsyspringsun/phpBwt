<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Mall_sign extends CI_Controller 
{
    private static $data = array();
    public function __construct()
    {
        parent::__construct();  
		$this->load->model(array('member_model','mall_sign_model'));		
    }
	
		/**连续签到的实现方式*/
		public function signList(){
			$id=1;
			$sign=$this->mall_sign_model->getwhererow(['id'=>$id],'*');
			
			//echo "<pre>";
			//print_r($sign);exit;
			
			
			if(!empty($sign)){
				
			
			}else{
				$data['uid'] = $id;
				$data['time'] = time();
				$data['points'] = 0.0001;
				$data['num'] = 1;
				$res = mall_sign_model->insert($data);
		}

	
	
}

		public function signList(){
		/**先查到是否有这个用户*/
		$m_id = $_GET['m_id'];
		$sign = D('Sign')->where(array("m_id"=>$m_id))->limit(0)->find();
		/**如果有就进行判断时间差，然后处理签到次数*/
		if($sign){
		/**昨天的时间戳时间范围*/
		$t = time();
		$last_start_time = mktime(0,0,0,date("m",$t),date("d",$t)-1,date("Y",$t));
		$last_end_time = mktime(23,59,59,date("m",$t),date("d",$t)-1,date("Y",$t));
		/**今天的时间戳时间范围*/
		// $now_start_time = mktime(0,0,0,date("m",$t),date("d",$t),date("Y",$t));
		// $now_end_time = mktime(23,59,59,date("m",$t),date("d",$t),date("Y",$t));
		/**判断最后一次签到时间是否在昨天的时间范围内*/
		if($last_start_time<$sign['time']&&$sign['time']<$last_end_time){
		$da['time'] = time();
		$da['count'] = $sign['count']+1;
		/**这里还可以加一些判断连续签到几天然后加积分等等的操作*/
		D('Sign')->where(array("m_id"=>$m_id))->save($da);
		}else{
		/**返回已经签到的操作*/
		$da['time'] = time();
		$da['count'] = 0;
		D('Sign')->where(array("m_id"=>$m_id))->save($da);
		}
		}else{
		$data['m_id'] = $m_id;
		$data['time'] = time();
		$data['sign'] = 1;
		$res = D("Sign")->add($data);
		if($res){
		/**成功就返回，或者处理一些程序，比如加积分*/
		}
		}
		}



}