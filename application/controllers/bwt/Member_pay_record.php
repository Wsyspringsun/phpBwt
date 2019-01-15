<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Member_pay_record extends CI_Controller 
{
    private static $data = array();
    public function __construct()
    {
        parent::__construct();  
		$this->load->model(array('member_pay_record_model','admin_receive_model','member_audit_model','member_model','member_audit_model'));		
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
		$res_pay=$this->member_model->getwhererow(['id'=>$id],'china_id');
		if(!empty($res_pay)){
		  $data=$this->admin_receive_model->getwhererow(['is_open'=>1],'receive_qrcode,receive_money,receive_name,receive_id');
		  show200($data);
		}else{
			show300('接口调用错误');
		}
	}
	 /**
     * @title 完成缴费
     * @desc  (点击完成支付)
     * @output {"name":"code","type":"int","desc":"200:成功,300各种提示信息"}
     * @output {"name":"msg","type":"string","desc":"信息说明"}
     * @output {"name":"data.pay_id","type":"int","desc":"缴费记录id"}
     */
	public function  finishReceive(){
		$id=$this->getId();
     	 $res_pay=$this->member_model->getwhererow(['id'=>$id],'china_id,is_pay');
			if(!empty($res_pay['china_id']) && $res_pay['is_pay']==0){
				$mem['is_pay']=1;
				$res=$this->member_model->updateWhere(['id'=>$id],$mem);
				if($res){
					show200('缴费成功');
				}else{
					show300('缴费失败');
				}
			}else{
			show300('接口调用错误');
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
		$id=$this->getId();  	
        $is_pay=$this->member_pay_record_model->getwhererow(['user_id'=>$id],'screenshots');//是否上传          
		if(!empty($is_pay)){
			show300('接口调用错误');
		}else{
			$requires = array("screenshots"=>"支付截图不能为空","pay_id"=>"缴费记录id不能为空");
			$params = array();
			foreach($requires as $k => $v){
				if(empty($this->input->post($k))){
					show300($v);
				}
				$params[$k] = trim($this -> input -> post($k));
			}
			$par_rec['screenshots']=$this->base64_upload($params['screenshots']);//支付截图
			$par_rec['user_id']=$id;//付款人
			$res_scre=$this->member_pay_record_model->insert($par_rec);
			$audit['user_id']=$id;//付款人
			$res_audi=$this->member_audit_model->insert($audit);
			if($res_scre && $res_audi){
				show200('上传成功');
			}else{
				show300('上传失败');
			}
        
      }
   }     	
}
