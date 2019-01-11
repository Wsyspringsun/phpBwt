<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Member_pay_record extends CI_Controller 
{
    private static $data = array();
    public function __construct()
    {
        parent::__construct();  
		$this->load->model(array('member_pay_record_model','admin_receive_model','member_audit_model'));		
    }
	
	 /**
     * @title 获取支付信息
     * @desc  (获取缴费支付信息二维码)
     * @output {"name":"code","type":"int","desc":"200:成功,300各种提示信息"}
     * @output {"name":"msg","type":"string","desc":"信息说明"}
     * @output {"name":"data.receive_qrcode","require":"true","type":"string","desc":"收款二维码"}
     * @output {"name":"data.receive_money","require":"true","type":"int","desc":"收款金额"}
     * @output {"name":"data.receive_name","require":"true","type":"string","desc":"收款人"}
     * @output {"name":"data.receive_id","require":"true","type":"string","desc":"收款账号"}
     */
	
	
	public function  getReceiveQrcode(){
		$id=$this->getId();
		$data=$this->admin_receive_model->getwhererow(['is_open'=>1],'receive_qrcode,receive_money,receive_name,receive_id');
		show200($data);
	}
	 /**
     * @title 完成缴费
     * @desc  (点击完成支付)
     * @output {"name":"code","type":"int","desc":"200:成功,300各种提示信息"}
     * @output {"name":"msg","type":"string","desc":"信息说明"}
     * @output {"name":"data.pay_id","type":"int","desc":"缴费记录id"}
     */
	public function  finishReceive(){
		//$id=$this->getId();
		$id=1;
		$mem['is_pay']=1;
		$mem['user_id']=$id;//付款人
		$res=$this->member_pay_record_model->insert($mem);
		$data['pay_id']=$res;
		if($res){
			show200($data);
		}else{
			show300();
		}
	}
	
	 /**
     * @title 上传缴费截图
     * @desc  (上传缴费截图)
	 * @input {"name":"screenshots","require":"true","type":"string","desc":"base64图片"}
	 * @input {"name":"pay_id","require":"true","type":"int","desc":"缴费id"}
     * @output {"name":"code","type":"int","desc":"200:成功,300各种提示信息"}
     * @output {"name":"msg","type":"string","desc":"信息说明"}
     */
	
	public function  uploadScreenshots(){
		//$id=$this->getId();
		$id=1;
		$pay_id=3;
		/*$requires = array("screenshots"=>"支付截图不能为空","pay_id"=>"缴费记录id不能为空");
        $params = array();
        foreach($requires as $k => $v)
        {
            if(empty($this->input->post($k))){
                show300($v);
            }
            $params[$k] = trim($this -> input -> post($k));
        }*/
		
		//模拟数据
		//$id=1;
        //$params['screenshots'] = 'http://bwt.glq.cc/upload/201901072108473379.jpg';
		//$params['recive_id']='1768188814';//接收账号
		//$params['receive_name']='测试';//接收名称
		//$params['pay_time']='2018-8-8 :13:02:05';//接收账号
		//$params['pay_money']='99.99';//接收账号
		//模拟数据
	
		///$par_rec['screenshots']=$this->base64_upload($params['screenshots']);//支付截图
		$par_rec['screenshots']='kjknknkj';//支付截图
		$par_rec['user_id']=$id;//付款人
		//$par_rec['recive_id']=$params['recive_id'];//收款账号
		//$par_rec['receive_name']=$params['receive_name'];//收款人
		//$par_rec['pay_time']=$params['pay_time'];//支付时间
		//$par_rec['pay_money']=$params['pay_money'];//支付金额
		$res_scre=$this->member_pay_record_model->updateWhere(['id'=>$pay_id],$par_rec);
		$audit['user_id']=$id;//付款人
		$res_audi=$this->member_audit_model->insert($audit);
		if($res_scre && $res_audi){
			show200('上传成功');
		}else{
			show300('上传失败');
		}
	}
	
	
	
	
	
}
