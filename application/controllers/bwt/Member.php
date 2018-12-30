<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Member extends CI_Controller 
{
    private static $data = array();
    public function __construct()
    {
        parent::__construct();  
		 $this->load->library(array('sms/api_demo/SmsDemo','weixin/wechatCallbackapiTest'));
		$this->load->model(array('member_model'));		
    }	
	 /**
	 * @title 用户注册
     * @desc  (用户注册)	 
	 * @input {"name":"mobile","require":"true","type":"int","desc":"手机号"}	
	 * @input {"name":"yzm","require":"true","type":"int","desc":"验证码"}	
	 * @input {"name":"pwd","require":"true","type":"int","desc":"登陆密码"}	
	 * @input {"name":"pwd_again","require":"true","type":"int","desc":"确认登陆密码"}	
	 * @input {"name":"pwd_second","require":"true","type":"int","desc":"二次密码"}	
	 * @input {"name":"pwd_second_again","require":"true","type":"int","desc":"确认二次密码"}	
	 * @input {"name":"referee_mobile","require":"true","type":"int","desc":"推荐人手机号"}		 
	 * @output {"name":"code","type":"int","desc":"200:成功,300各种提示信息"}
     * @output {"name":"msg","type":"string","desc":"信息说明"}
	 * @output {"name":"data.id","require":"true","type":"int","desc":"用户id"}	
	 */
    public function registered()
    {
		$mobile = trim($this->input->post('mobile'));
		$yzm = trim($this->input->post('yzm'));
		$pwd = trim($this->input->post('pwd'));
		$pwd_again = trim($this->input->post('pwd_again'));
		$pwd_second = trim($this->input->post('pwd_second'));
		$pwd_second_again = trim($this->input->post('pwd_second_again'));
		$referee_mobile = trim($this->input->post('referee_mobile'));
		
		$mobile = '17681888141';
		$yzm =666 ;
		$this->session->set_tempdata('yzm',$yzm,60);
		$pwd = 123456;
		$pwd_again = 123456;
		$pwd_second = 123456;
		$pwd_second_again = 123456;
		$referee_mobile = '17681888141';
        if(!$yzm){
            show300('验证码不能为空');
        }
		if(!$pwd){
            show300('登录密码不能为空');
        }
		if(!$pwd_again){
            show300('确认登录密码不能为空');
        }
		if(!$pwd_second){
            show300('二次密码不能为空');
        }
		if(!$pwd_second_again){
            show300('确认二次密码不能为空');
        }
		if($pwd!=$pwd_again){
            show300('两次登录密码不一致');
        }
		if($pwd_second!=$pwd_second_again){
            show300('两次二次密码不一致');
        }
		$is_user=$this->member_model->getwhereRow(['mobile'=>$mobile],'id');
		if($is_user){
			show300('已经是会员,请直接登陆');
		}
		if($referee_mobile){
			$is_reg=$this->member_model->getwhereRow(['mobile'=>$referee_mobile],'id');
			if($is_reg){
				$referee_id=$is_reg['id'];
			}else{
				show300('填写的手机号无效,请重新核实');
			}
		}else{
			$is_openReg=$this->member_model->getwhereRow(['id'=>$id],'is_openReg');
			if(!empty($is_openReg)&&$is_openReg['is_openReg']==1){
				$id=$this->member_model->getMin('id');
				$referee_id=$id['id'];
			}else{
				show300('推荐人缺失,请向推荐人索取推荐链接或者手机号完成注册');
			}
		}	
		if (empty($this->session->tempdata('yzm'))){
            show300('验证码失效，请重新发送');
        }
		 if($yzm==$this->session->tempdata('yzm')){
			 $mem['mobile']=$mobile;
			 $mem['pwd']=$pwd;
			 $mem['pwd_second']=$pwd_second;
			 $mem['referee_id']=$referee_id;
			 //$mem['msg_validcode']=$yzm;
			 $user_name=$this->member_model->getMax('user_name');
			 if(!$user_name['user_name']){
				 $mem['user_name']=300001; 
			 }else{
				 if($user_name['user_name']<300000){
					 $mem['user_name']=300001;  
				 }else{
					 $mem['user_name']=$user_name['user_name']+1; 
				 }
			 }
			 $res=$this->member_model->insert($mem);
				if($res){
					show200(['id'=>$res],'注册成功');
				}else{
					show300('注册失败');
				}
		 }else{
			  show300('验证码输入错误');
		 }
    }
	/**
	 * @title 我的
     * @desc  (点击我的)
	 
	 * @input {"name":"id","require":"true","type":"int","desc":"用户id"}	
	 
	 * @output {"name":"code","type":"int","desc":"200:成功,300各种提示信息"}
     * @output {"name":"msg","type":"string","desc":"信息说明"}
	 * @output {"name":"data.id","require":"true","type":"int","desc":"用户id"}	
	 * @output {"name":"data.real_name","require":"true","type":"string","desc":"用户真实名字"}	
	 * @output {"name":"data.head_icon","require":"true","type":"string","desc":"用户头像"}	
	 * @output {"name":"data.member_lvl","require":"true","type":"int","desc":"用户级别"}	
	 */
	
	public function getMyInfo(){
		$id = trim($this->input->post('id'));
		//$id = 2;
		if(empty($id)){
            show300('会员id不能为空');
        }
		$data=$this->member_model->getwhereRow(['id'=>$id],'id,real_name,head_icon,member_lvl');
		show200($data);
	}
	 /**
     *@title 发送验证码
     *@desc 发送手机验证码
     *@input {"name":"phone","require":"true","type":"string","desc":"用户手机号"}
     *
     *@output {"name":"code","type":"int","desc":"200:发送成功,300各种提示信息"}
     *@output {"name":"msg","type":"string","desc":"信息说明"}
     *
     *@output {"name":"data.sessionId","type":"int","desc":"sessionid,因为小程序在请求头中并没有cookie,所以要想在小程序中依然用session的话必须请求头中加入cookie参数,如'Cookie':'ci_session=4vd6svd57d5e25pfjg3ntp3k798d00rk"}
     * */
    public function sendSms(){
        $mobile = trim($this->input->post('mobile'));
		$mobile = '17681888141';
        if(empty($mobile)){
            show300('请输入手机号码');//手机号为空
        }
        if(!preg_match("/^1[0-9]{10}$/i",$mobile)){
            show300('手机号格式不对');//手机格式不正确
        }
        $templateId= 'SMS_141945019';   //短信模板ID
        $smsSign = "众合致胜";           // 签名
        $yzm=rand(1000,9999);           //验证码
        $sms=new SmsDemo();
        $res=$sms->sendSms($mobile,$templateId,$smsSign,['code'=>$yzm]);
        if($res->Code=='OK'){            
            $this->session->set_tempdata('yzm',$yzm,300);
            $sessionId=session_id();
            show200(['sessionId'=>$sessionId],'发送成功');
        }else{
            show300('发送失败');
        }
    }
	 /**
	 * @title 忘记二次密码
     * @desc  (用户找回二次密码)
	 
	 * @input {"name":"mobile","require":"true","type":"int","desc":"手机号"}	
	 * @output {"name":"data.pwd_second","require":"true","type":"int","desc":"用户二次密码"}
	 * @input {"name":"pwd_second_again","require":"true","type":"int","desc":"确认二次密码"}	
	 
	 * @output {"name":"code","type":"int","desc":"200:成功,300各种提示信息"}
     * @output {"name":"msg","type":"string","desc":"信息说明"}
	 */		
	public function backPwd(){
		$mobile = trim($this->input->post('mobile'));
		$yzm = trim($this->input->post('yzm'));
		$pwd_second = trim($this->input->post('pwd_second'));
		$pwd_second_again = trim($this->input->post('pwd_second_again'));		
		
		$mobile = '17681888141';
		$yzm =666 ;
		$this->session->set_tempdata('yzm',$yzm,60);
		$pwd = '66666666';
		$pwd_again = '66666666';		
		
		if(!$mobile){
            show300('手机号不能为空');
        }
        if(!$yzm){
            show300('验证码不能为空');
        }
		if(!$pwd_second){
            show300('二次密码不能为空');
        }
		if(!$pwd_second_again){
            show300('确认二次密码不能为空');
        }
		if($pwd_second!=$pwd_second_again){
            show300('两次登录密码不一致');
        }
		if (empty($this->session->tempdata('yzm'))){
            show300('验证码失效，请重新发送');
        }
		$user_pad=$this->member_model->getwhereRow(['mobile'=>$mobile],'pwd_second,id');
		if(empty($user_pad)){
			 show300('您还不是会员，请先注册');
		}
		if($yzm==$this->session->tempdata('yzm')){
			$mem['pwd_second']=$pwd_second;
			$res=$this->member_model->updateWhere(['id'=>$user_pad['id']],$mem);
			if(!$res){
				show300('更新失败');
			}else{
				show200('已更改');
			}
		}else{
			show300('验证码输入错误');
		}
	}	
	 /**
	 * @title 忘记密码
     * @desc  (用户找回密码)
	 
	 * @input {"name":"mobile","require":"true","type":"int","desc":"手机号"}	
	 * @input {"name":"pwd","require":"true","type":"int","desc":"登陆密码"}	
	 * @input {"name":"pwd_again","require":"true","type":"int","desc":"确认登陆密码"}		
	 
	 * @output {"name":"code","type":"int","desc":"200:成功,300各种提示信息"}
     * @output {"name":"msg","type":"string","desc":"信息说明"}
	 */		
	public function backPwdSecond(){
		$mobile = trim($this->input->post('mobile'));
		$yzm = trim($this->input->post('yzm'));
		$pwd = trim($this->input->post('pwd'));
		$pwd_again = trim($this->input->post('pwd_again'));		
		
		$mobile = '17681888141';
		$yzm =666 ;
		$this->session->set_tempdata('yzm',$yzm,60);
		$pwd = '66666666';
		$pwd_again = '66666666';		
		
		if(!$mobile){
            show300('手机号不能为空');
        }
        if(!$yzm){
            show300('验证码不能为空');
        }
		if(!$pwd){
            show300('登录密码不能为空');
        }
		if(!$pwd_again){
            show300('确认登录密码不能为空');
        }
		if($pwd!=$pwd_again){
            show300('两次登录密码不一致');
        }
		if (empty($this->session->tempdata('yzm'))){
            show300('验证码失效，请重新发送');
        }
		$user_pad=$this->member_model->getwhereRow(['mobile'=>$mobile],'pwd,id');
		if(empty($user_pad)){
			 show300('您还不是会员，请先注册');
		}
		if($yzm==$this->session->tempdata('yzm')){
			$mem['pwd']=$pwd;
			$res=$this->member_model->updateWhere(['id'=>$user_pad['id']],$mem);
			if(!$res){
				show300('更新失败');
			}else{
				show200('已更改');
			}
		}else{
			show300('验证码输入错误');
		}
	}	
	 /**
     *@title 获取验证码
     *@desc 获取验证码
     *
     *@output {"name":"code","type":"int","desc":"200:发送成功,300各种提示信息"}
     *@output {"name":"msg","type":"string","desc":"信息说明"}
     *
     *@output {"name":"data.loginYzm","type":"string","desc":"登陆验证码4"}
     * */
	
	public function getLoginYzm(){
		$str='abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';		
		$loginYzm = ""; 
		for ( $i = 0; $i < YAMLENGTH; $i++ ) { 
		$loginYzm .= $str[ mt_rand(0, strlen($str) - 1) ]; 
		} 
		$this->session->set_tempdata('loginYzm',$loginYzm,300);
		show200(['loginYzm'=>$loginYzm],'获取成功');
	}
	
	 /**
	 * @title 用户登陆
     * @desc  (用户登陆)
	 
	 * @input {"name":"mobile","require":"true","type":"int","desc":"手机号"}	
	 * @input {"name":"yzm","require":"true","type":"int","desc":"登陆验证码4"}	
	 * @input {"name":"pwd","require":"true","type":"int","desc":"登陆密码"}	
	 
	 * @output {"name":"code","type":"int","desc":"200:成功,300各种提示信息"}
     * @output {"name":"msg","type":"string","desc":"信息说明"}
	 * @output {"name":"data.id","require":"true","type":"int","desc":"用户id"}	
	 */
	public function login(){
		$mobile = trim($this->input->post('mobile'));
		$loginYzm = trim($this->input->post('loginYzm'));
		$pwd = trim($this->input->post('pwd'));
		
		$mobile = '17681888141';
		$loginYzm = 'wThR';
		$pwd = '123456';
		if(!$mobile){
            show300('手机号不能为空');
        }
        if(!$loginYzm){
            show300('验证码不能为空');
        }
		if(!$pwd){
            show300('登录密码不能为空');
        }
		//print_r($this->session->tempdata('loginYzm'));exit;
		if($this->session->tempdata('loginYzm')!=$loginYzm ){
			 show300('验证码错误');
		}
		$user_pad=$this->member_model->getwhereRow(['mobile'=>$mobile],'pwd,id');
		//$data['id']=$user_pad['id'];
		if(empty($user_pad)){
			 show300('您还不是会员，请先注册');
		}
		if($pwd!=$user_pad['pwd'] ){
			 show300('密码错误');
		}
		show200(['id'=>$user_pad['id']],'登陆成功');
	}
	
	/**
	 * @title 个人资料
     * @desc  (个人资料)
	 
	 * @input {"name":"id","require":"true","type":"int","desc":"用户id"}	
	 
	 * @output {"name":"code","type":"int","desc":"200:成功,300各种提示信息"}
     * @output {"name":"msg","type":"string","desc":"信息说明"}
	 * @output {"name":"data.id","require":"true","type":"int","desc":"用户id"}	
	 * @output {"name":"data.real_name","require":"true","type":"string","desc":"用户真实名字"}	
	 * @output {"name":"data.head_icon","require":"true","type":"string","desc":"用户头像"}	
	 * @output {"name":"data.pwd","require":"true","type":"string","desc":"用户密码"}	
	 * @output {"name":"data.pwd_second","require":"true","type":"string","desc":"用户二次密码"}	
	 * @output {"name":"data.china_id","require":"true","type":"int","desc":"用户id"}	
	 * @output {"name":"data.referee_id","require":"true","type":"int","desc":"推荐人id"}	
	 * @output {"name":"data.member_lvl","require":"true","type":"int","desc":"用户级别"}	
	 * @output {"name":"data.profit_lvl","require":"true","type":"int","desc":"用户收益级别"}	
	 * @output {"name":"data.is_valid","require":"true","type":"int","desc":"是否认证"}	
	 * @output {"name":"data.alipay_id","require":"true","type":"string","desc":"支付账号"}	
	 * @output {"name":"data.alipay_qrcode","require":"true","type":"string","desc":"支付二维码"}	
	 * @output {"name":"data.mobile","require":"true","type":"string","desc":"手机号"}	
	 * @output {"name":"data.user_name","require":"true","type":"string","desc":"用户名"}	
	 * @output {"name":"data.create_date","require":"true","type":"date","desc":"创建时间"}	
	 * @output {"name":"data.modify_date","require":"true","type":"date","desc":"更新时间"}	
	 * @output {"name":"data.referee_mobile","require":"true","type":"string","desc":"推荐人手机号"}	
	 */
	public function getMemberInfo(){
		$id = trim($this->input->post('id'));
		//$id = 2;
		$data=$this->member_model->getwhereRow(['id'=>$id],'*');
		if(!empty($data)){
		$data['referee_mobile']=$this->member_model->getwhereRow(['id'=>$data['referee_id']],'mobile')['mobile'];
		}
		show200($data);
	}	
	
}
