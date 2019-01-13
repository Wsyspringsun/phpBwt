<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Member extends CI_Controller
{
    private static $data = array();

    public function __construct()
    {
        parent::__construct();
        $this->load->library(array('sms/api_demo/SmsDemo', 'weixin/wechatCallbackapiTest'));
        $this->load->model(array('member_model', 'machine_model', 'member_resouce_model','member_pay_record_model','admin_receive_model','member_audit_model'));
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
		$paw_str=strlen($pwd);
		$pwd_second_str=strlen($pwd_second);
		$paw_str=strlen($pwd);
		$pwd_second_str=strlen($pwd_second);
        if (!$yzm) {
            show300('验证码不能为空');
        }
        if (!$pwd) {
            show300('登录密码不能为空');
        }
		if($paw_str<6){
			show300('密码长度不能少于6位');
		}
        if (!$pwd_again) {
            show300('确认登录密码不能为空');
        }
        if (!$pwd_second) {
            show300('二次密码不能为空');
        }
		if($pwd_second_str<6){
			show300('二次密码长度不能少于6位');
		}
        if (!$pwd_second_again) {
            show300('确认二次密码不能为空');
        }
		
        if ($pwd != $pwd_again) {
            show300('两次登录密码不一致');
        }
        if ($pwd_second != $pwd_second_again) {
            show300('两次二次密码不一致');
        }
        $is_user = $this->member_model->getwhereRow(['mobile' => $mobile], 'id');
        if ($is_user) {
            show300('已经是会员,请直接登陆');
        }
        if ($referee_mobile) {
            $is_reg = $this->member_model->getwhereRow(['mobile' => $referee_mobile], 'id');
            if ($is_reg) {
                $referee_id = $is_reg['id'];
            } else {
                show300('填写的手机号无效,请重新核实');
            }
        } else {
            $is_openReg = $this->member_model->getwhereRow(['id' => $id], 'is_openReg');
            if (!empty($is_openReg) && $is_openReg['is_openReg'] == 1) {
                $id = $this->member_model->getMin('id');
                $referee_id = $id['id'];
            } else {
                show300('推荐人缺失,请向推荐人索取推荐链接或者手机号完成注册');
            }
        }
        if (empty($this->session->tempdata('yzm'))) {
            show300('验证码失效，请重新发送');
        }
        if ($yzm == $this->session->tempdata('yzm')) {
            $mem['mobile'] = $mobile;
            $mem['pwd'] = $pwd;
            $mem['pwd_second'] = $pwd_second;
            $mem['referee_id'] = $referee_id;
            $user_name = $this->member_model->getMax('user_name');
            if (!$user_name['user_name']) {
                $mem['user_name'] = 300001;
            } else {
                if ($user_name['user_name'] < 300000) {
                    $mem['user_name'] = 300001;
                } else {
                    $mem['user_name'] = $user_name['user_name'] + 1;
                }
            }
            $res = $this->member_model->insert($mem);
			$resouce['id']=$res;
			$resouce_res=$this->member_resouce_model->insert($resouce);//会员资产增加
            if ($res&&$resouce_res) {
                show200('注册成功');
            } else {
                show300('注册失败');
            }
        } else {
            show300('验证码输入错误');
        }
    }

    /**
     * @title 我的
     * @desc  (点击我的)
     * @output {"name":"code","type":"int","desc":"200:成功,300各种提示信息"}
     * @output {"name":"msg","type":"string","desc":"信息说明"}
     * @output {"name":"data.id","require":"true","type":"int","desc":"用户id"}
     * @output {"name":"data.real_name","require":"true","type":"string","desc":"用户真实名字"}
     * @output {"name":"data.head_icon","require":"true","type":"string","desc":"用户头像"}
     * @output {"name":"data.member_lvl","require":"true","type":"int","desc":"用户级别"}
     */

    public function getMyInfo()
    {
        $id=$this->getId();
        $id=1;
		$this->getValid($id);
		$data = $this->member_model->getwhererow(['id' => $id], 'id,real_name,head_icon,member_lvl');
		$data['member_lvl']=$this->member_model->getLevel($data['member_lvl']);
        show200($data);
    }

    /**
     * @title 发送验证码
     * @desc 发送手机验证码
     * @input {"name":"phone","require":"true","type":"string","desc":"用户手机号"}
     *
     * @output {"name":"code","type":"int","desc":"200:发送成功,300各种提示信息"}
     * @output {"name":"msg","type":"string","desc":"信息说明"}
     *
     * @output {"name":"data.sessionId","type":"int","desc":"sessionid,因为小程序在请求头中并没有cookie,所以要想在小程序中依然用session的话必须请求头中加入cookie参数,如'Cookie':'ci_session=4vd6svd57d5e25pfjg3ntp3k798d00rk"}
     * */
    public function sendSms()
    {
        $mobile = trim($this->input->post('mobile'));
        if (empty($mobile)) {
            show300('请输入手机号码');//手机号为空
        }
        if (!preg_match("/^1[0-9]{10}$/i", $mobile)) {
            show300('手机号格式不对');//手机格式不正确
        }
        $templateId = 'SMS_141945019';   //短信模板ID
        $smsSign = "众合致胜";           // 签名
        $yzm = rand(1000, 9999);           //验证码
        $sms = new SmsDemo();
        $res = $sms->sendSms($mobile, $templateId, $smsSign, ['code' => $yzm]);
        if ($res->Code == 'OK') {
            $this->session->set_tempdata('yzm', $yzm, 300);
            $sessionId = session_id();
            show200(['sessionId' => $sessionId], '发送成功');
        } else {
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
    public function backPwd()
    {
        $mobile = trim($this->input->post('mobile'));
        $yzm = trim($this->input->post('yzm'));
        $pwd_second = trim($this->input->post('pwd_second'));
        $pwd_second_again = trim($this->input->post('pwd_second_again'));
        /*$mobile = '17681888141';
        $yzm = 666;
        $this->session->set_tempdata('yzm', $yzm, 60);
        $pwd = '66666666';
        $pwd_again = '66666666';*/
        if (!$mobile) {
            show300('手机号不能为空');
        }
        if (!$yzm) {
            show300('验证码不能为空');
        }
        if (!$pwd_second) {
            show300('二次密码不能为空');
        }
        if (!$pwd_second_again) {
            show300('确认二次密码不能为空');
        }
        if ($pwd_second != $pwd_second_again) {
            show300('两次登录密码不一致');
        }
        if (empty($this->session->tempdata('yzm'))) {
            show300('验证码失效，请重新发送');
        }
        $user_pad = $this->member_model->getwhereRow(['mobile' => $mobile], 'pwd_second,id');
        if (empty($user_pad)) {
            show300('您还不是会员，请先注册');
        }
        if ($yzm == $this->session->tempdata('yzm')) {
            $mem['pwd_second'] = $pwd_second;
            $res = $this->member_model->updateWhere(['id' => $user_pad['id']], $mem);
            if (!$res) {
                show300('更新失败');
            } else {
                show200('已更改');
            }
        } else {
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
    public function backPwdSecond()
    {
        $mobile = trim($this->input->post('mobile'));
        $yzm = trim($this->input->post('yzm'));
        $pwd = trim($this->input->post('pwd'));
        $pwd_again = trim($this->input->post('pwd_again'));

        /*$mobile = '17681888141';
        $yzm = 666;
        $this->session->set_tempdata('yzm', $yzm, 60);
        $pwd = '66666666';
        $pwd_again = '66666666';*/

        if (!$mobile) {
            show300('手机号不能为空');
        }
        if (!$yzm) {
            show300('验证码不能为空');
        }
        if (!$pwd) {
            show300('登录密码不能为空');
        }
        if (!$pwd_again) {
            show300('确认登录密码不能为空');
        }
        if ($pwd != $pwd_again) {
            show300('两次登录密码不一致');
        }
        if (empty($this->session->tempdata('yzm'))) {
            show300('验证码失效，请重新发送');
        }
        $user_pad = $this->member_model->getwhereRow(['mobile' => $mobile], 'pwd,id');
        if (empty($user_pad)) {
            show300('您还不是会员，请先注册');
        }
        if ($yzm == $this->session->tempdata('yzm')) {
            $mem['pwd'] = $pwd;
            $res = $this->member_model->updateWhere(['id' => $user_pad['id']], $mem);
            if (!$res) {
                show300('更新失败');
            } else {
                show200('已更改');
            }
        } else {
            show300('验证码输入错误');
        }
    }

    /**
     * @title 获取验证码
     * @desc 获取验证码
     *
     * @output {"name":"code","type":"int","desc":"200:发送成功,300各种提示信息"}
     * @output {"name":"msg","type":"string","desc":"信息说明"}
     *
     * @output {"name":"data.loginYzm","type":"string","desc":"登陆验证码4"}
     * */

    public function getLoginYzm()
    {
        //$str = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $str = '0123456789';
        $loginYzm = "";
        for ($i = 0; $i < YAMLENGTH; $i++) {
            $loginYzm .= $str[mt_rand(0, strlen($str) - 1)];
        }
        $this->session->set_tempdata('loginYzm', $loginYzm, 300);
        show200(['loginYzm' => $loginYzm], '获取成功');
    }

    /**
     * @title 用户登陆
     * @desc  (用户登陆)
     * @input {"name":"mobile","require":"true","type":"int","desc":"手机号"}
     * @input {"name":"loginYzm","require":"true","type":"int","desc":"登陆验证码4"}
     * @input {"name":"pwd","require":"true","type":"int","desc":"登陆密码"}
     * @output {"name":"code","type":"int","desc":"200:成功,300各种提示信息"}
     * @output {"name":"msg","type":"string","desc":"信息说明"}
     * @output {"name":"data.id","require":"true","type":"int","desc":"用户id"}
     */
    public function login()
    {
        $mobile = trim($this->input->post('mobile'));
        $loginYzm = strtolower(trim($this->input->post('loginYzm')));
        $pwd = trim($this->input->post('pwd'));
        /*$mobile = '17681876666';
		$loginYzm=strtolower($loginYzm);
        $pwd = '123456';*/
        $this->session->set_tempdata('loginYzm', $loginYzm, 300);
        if (!$mobile) {
            show300('手机号不能为空');
        }
        if (!$loginYzm) {
            show300('验证码不能为空');
        }
        if (!$pwd) {
            show300('登录密码不能为空');
        }
        //print_r($this->session->tempdata('loginYzm'));exit;
        if (strtolower($this->session->tempdata('loginYzm')) != $loginYzm) {
         //TODO:del   show300('验证码错误');
        }
        $user_pad = $this->member_model->getwhereRow(['mobile' => $mobile], 'pwd,id');
        //$data['id']=$user_pad['id'];
        if (empty($user_pad)) {
            show300('您还不是会员，请先注册');
        }
        if ($pwd != $user_pad['pwd']) {
            show300('密码错误');
        }
        $session_user['id'] = $user_pad['id'];
        $this->session->set_tempdata($session_user);
        //print_r($this->session->tempdata('id'));exit;
        show200('登陆成功');
    }
	
	 /**
     * @title 用户退出
     * @desc  (用户退出)
     * @output {"name":"code","type":"int","desc":"200:成功,300各种提示信息"}
     * @output {"name":"msg","type":"string","desc":"信息说明"}
     */
	
	public function loginOut(){
		$this->session->unset_userdata('id');
		show200('退出成功');
	}

    /**
     * @title 个人资料
     * @desc  (个人资料)
     * @input {"name":"id","require":"true","type":"int","desc":"用户id"}
     * @output {"name":"code","type":"int","desc":"200:成功,300各种提示信息"}
     * @output {"name":"msg","type":"string","desc":"信息说明"}
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
    public function getMemberInfo()
    {
		//$id=$this->getId();
		$id=1;;
        $data = $this->member_model->getwhereRow(['id' => $id], '*');
        if (!empty($data)) {
			$data['pwd']='******';
			$data['pwd_second']='******';
            $data['referee_mobile'] = $this->member_model->getwhereRow(['id' => $data['referee_id']], 'mobile')['mobile'];
			$data['member_lvl']=$this->member_model->getLevel($data['member_lvl']);
        }
        show200($data);
    }
  /**
     * @title 获取支付宝二维码内容
     * @desc  (获取支付宝二维码内容 http)
     */	
	public function getAlipay($picPath){
		//$alipay_url='https://way.jd.com/jisuapi/qrcodeRead?qrcode=http://bwt.bangweikeji.com/upload/201901092035267296.jpg&appkey=342007b36d37150df7de516a5183b127';
		//$alipay_url='https://way.jd.com/jisuapi/qrcodeRead?qrcode='.$picPath'&appkey='.APPKEY;
		//$curl=new wechatCallbackapiTest();
        //$res_alipaydata= $curl->curl_request($alipay_url);
       // $data_alipay=json_decode($res_alipaydata,true);
		
		//模拟数据
		$data_alipay['code']=10000;
		$data_alipay['charge']='';
		$data_alipay['remain']=0;
		$data_alipay['msg']='查询成功';
		$data_alipay['result']['status']=0;
		$data_alipay['result']['msg']='ok';
		$data_alipay['result']['result']='HTTPS://QR.ALIPAY.COM/FKX000110OFRHJMZSGGE63';
		//模拟数据
		if($data_alipay['code']==10000){
			if(empty($data_alipay['result'])  or empty($data_alipay['result']['result']) ){
				show300('二维码地址不正确');
			}else{
				$is_qrcode=$this->member_model->getWhereRow(['qrcode_text'=>1],'qrcode_text');
				if(!empty($is_qrcode)){
					show300('收款码已有人使用,请重新上传');
				}
			}
		}else if(empty($data_alipay)){
			show300('支付宝商家接口调用异常，请稍后再试');
		}else{
			show300($data_alipay['code']);
		}
		return $data_alipay['result']['result'];
	}
	 /**
     * @title 验证身份证信息唯一
     * @desc  (验证身份证信息唯一 http)
     */	
	public function validationIDCard($body){
		$params = array('appkey' => '342007b36d37150df7de516a5183b127');
		$url = 'https://way.jd.com/huojudata/idCradTow';
		$body=json_encode($body);
		$curl=new wechatCallbackapiTest();
		$res=$curl->wx_http_request($url, $params , $body, true );
		$data_IDCard=json_decode($res,true);
		if($data_IDCard['code']==10000 && $data_IDCard['result']['code']!=200){
			show300($data_IDCard['result']['message']);
		}
		return true;
	}
	  /**
     * @title 获取身份证信息
     * @desc  (获取身份证信息 http)
     */	
	public function getIDCard(){
		 $params = array(
        'appkey' => '342007b36d37150df7de516a5183b127');
		$url = 'https://way.jd.com/huojudata/idCradTow';
		//$body = '{ "serviceCode":"X01", "name":"张三", "idNumber":"32XXXXXXXXXXXXXXXX"}';
		$body='{"serviceCode":"X01", "name":"郭丽琴","idNumber":"142201199205154021"}';
		//echo wx_http_request($url, $params , $body, true );
		$curl=new wechatCallbackapiTest();
		$res=$curl->wx_http_request($url, $params , $body, true );
		$data_positive=json_decode($res,true);
	}
	/**
     * @title 认证接口
     * @desc  (认证接口)
     * @input {"name":"china_id","require":"true","type":"string","desc":"身份证号"}
     * @input {"name":"real_name","require":"true","type":"string","desc":"姓名"}
     * @input {"name":"alipay_id","require":"true","type":"string","desc":"支付宝号"}
     * @input {"name":"alipay_qrcode","require":"true","type":"string","desc":"支付宝二维码"}
     * @input {"name":"mobile","require":"true","type":"string","desc":"手机号"}
     * @input {"name":"yzm","require":"true","type":"int","desc":"验证码"}
     * @output {"name":"code","type":"int","desc":"200:成功,300各种提示信息"}
     * @output {"name":"msg","type":"string","desc":"信息说明"}
     */
    public function certification(){
		
      /*$requires = array("china_id"=>"缺少身份号","real_name"=>"缺少姓名","alipay_id"=>"缺少支付宝号",
	   "alipay_qrcode"=>"缺少支付宝收款码","yzm"=>"缺少验证码","mobile"=>"缺少手机号");
        $params = array();
        foreach($requires as $k => $v)
        {
            if(empty($this->input->post($k))){
                show300($v);
            }
            $params[$k] = trim($this -> input -> post($k));
        }     		
		
		$id=$this->getId();*/
		$id=1;
		$params['alipay_qrcode'] ="data:image/jpeg;base64,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/DemFcsW1MMOlOcC2FhXLEsWHev5F5Cd+6E6wrx0FoReAYQ2lF4BgoGRKxL0DBkJrg4J2H5am4flyc6AvMlNvCdYV5VbYTbwjaUN1mBZg6ViYVf0nmXSO5ywg0DZF4HlSDsssJ7IEhYVyxberWQZWJYsO9fyo3BO/dCfYvCIh0IWhPvKbYE4TiwgIbCxL01gLZKDAEXAboOB2RYCjhiJTbwnWHqxmzkbSgJMLK+1lfaG0Kn51IiRCc2nuazypPnrUeUXEiCgY2RcTv1zCi8kQUDBkKszKrKrMyi8kQVmFEyZKzCiZMlVmIVRqqWYUTJkqogQFUeesnwVWYhDQyi8kR1DyBAWYdkDBkLMcsxyzHLMcsxyLid/9PjDMSsorKKyisorKKyisorKKyisorKKyisorKKyisorKKyisorKKyisorKKyisorKKyisorKKyisorKKyisorKKyisorKKyisorKKyisorKKyisopzYMHtW2j1+Ld2rbR6/Fu7Vto9fi3dq20evxbu1baPX4t3attHr8W7tW2j1+Ld2rbR6/Fu7Vto9fi3dq20evxbu1baPX4t3attHr8W7tW2j1+Ld2rbR6/Fu7Vto9fi3dq20evxbu1baPX4t3attHr8W7tW2j1+Ld2rbR6/Fu7Vto7iVPbYt3attHdDtcW7tW2jtz3GLd2rbR2/nuMW7tW2j1+Ld2rbR6/Fu7Vto9fi3dq20evxbu1baPX4t3attHr8W7tW2j1+Ld2rbR6/Fu7Vto9fi3dq20evxbu1baPX4t3attHr8W7tW2j1+Ld2rbR6/Fu7Vto9fi3dq20evxbu1baPX4t3attHr8W7tW2j1+Ld2rbR6/Fu7Vto9fi3dq20evxbu1baPX4t3attHr8W7tW2j1+Ld2rbR2fnvMW7tW2jsx3mLd2rbR6/Fu7Vto9fi3dqHCkaqocqocqocqocqocqocqocqocqocqocqocqocqocqocqocqocqocqocqocqocqocqocqocqocqocqocqocqocqocqocqocqocqocqocqocqocqocqocqocqocqocqocqocqocqocqocrEMu0/3S5u0Kj7RZHlCPyVIiZVIiZRidFSeEG6GQqTx0A+JcehHwBQEmAqCizX4osIEoNkTKLdYGqp/8ttUGaalZfB/S0fIaIt+fAVA/wAkWRGqo0kH0gJG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BVqmCeyDBKoUwRxAsgLKCIbhDVAKDtQZIhuENUAoO1BlsZewRQkAERc1/wDCt//aAAwDAQACAAMAAAAQ8888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888888889rPPPPPPd28888888888888888888888888888888888888888888888888888888888M+++w/8AvvifOMJccPNXPPOPPB1fNPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPFPucnc8t/qvKAHwQKH0AHaQEMMIdPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPFPrjPBTHvqvLCTDPzF6N/KQANSQNPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPFPuADTkd/qvPLOZdPFfNVKTDFbAPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPFIUBFzqdPqvPKeTWdFfPNYfPHfP1PPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPEDvvpu+P/rvHLPPLPLPPPHPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPFOteWNrH8JfPKFPHPKNOFfLFfHEPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPNrvfXPvvvlPPPePPfKFKBfLPXKFPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPOtM888888fPPHPPLPLPLPPHLPLHPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPDzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzz/vvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvufvvvt9/v++s8Nu9vuM8881fvvM/vvtvfvvPffNfvcfvvvvvvvvvvvvvvvvvvvvvvvqAHZAAau/P/ALv7LKM4zZb7Rb5egDTwQv5n3+v9L3f/AP2V++++++++++++++++++++++++9+/Tyxd860/+86f7l5+Wo2r9X9+t4y3d+8/rpvBxxxBG++++++++++++++++++++++++FT/o+ef/AKuvvqMGaiPPVtX1vV5hv/0qafaLK6acY/TDTvvvvvvvvvvvvvvvvvvvvvvvvioUJZHvv6ofvvNLqOfPVovYNV7kOZK/qPbngKaUsvs+fPvvvvvvvvvvvvvvvvvvvvvvvDrcsP2vv/uOfv8ATqe73Rb/APRXXcY34Upo9++kp7//AFfK8vvvvvvvvvvvvvvvvvvvvvvvvnTvvD/rDvrjDfXnbTjHLHbbz/77PvjfrjPvrz/TDDDDL/vvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvtfPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPN/vvvvvvvvvvvvvvvvvvvvPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPvvvvvvvvvvvvvvvvvvvvPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPvvvvvvvvvvvvvvvvvvvvPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPvvvvvvvvvvvvvvvvvvvvPPPPPPPPPPPPFPPPPPFPPPFPPPPFPPPPPFPPPPPPPPPPPPPPvvvvvvvvvvvvvvvvvvvvPPPLPPPPPOPPFPPPPPPPPPPFKPPFHPPPPFPOPPPPPLPPPPPPvvvvvvvvvvvvvvvvvvvvPPPPLPPPOKPPFPPPPHDHODFHPPPPHPLDDHPKOPPPLPPPPPPPvvvvvvvvvvvvvvvvvvvvPPPPPPPPKKPPGMMMPPPPJPHOMMPPPPPPPPPKKPPPPPPPPPPPvvvvvvvvvvvvvvvvvvvvPPPPPPPPKKPPPOOPPNNPPPPFOLMMPPPOMFPKKPPPPPPPPPPPvvvvvvvvvvvvvvvvvvvvPPPPPPPPKKPPPKPPPPFPPPPFKPPPPPPPPPPKKPPPPPPPPPPPvvvvvvvvvvvvvvvvvvvvPPPPLDDDDKPPBDPPDDLCDDNFKPPBLHLDDFPKLDDDHPPPPPPPvvvvvvvvvvvvvvvvvvvvPPPPDDDDDDPPFLHDPLJDGPFFKPPFPPLDPFPLDDDDDHPPPPPPvvvvvvvvvvvvvvvvvvvvPPPPPPPPPPPNFPPPMPFPLMFEJPMHONPMPHNPPPPPPPPPPPPPvvvvvvvvvvvvvvvvvvvvPPPPPMMMNPMPFPPPPMNOPPPPPLPPPPPKMMMMMPPOMMMPPPPPvvvvvvvvvvvvvvvvvvvvPPPPPPPPKKPPFPPPPPFPPPFPPPPPPPPPPPPPKPPFPPPPPPPPvvvvvvvvvvvvvvvvvvvvPPPLCPPPKPPBPLLPPPLCPPFPPKDHDHPOPPPPPPJPPPPPPPPPvvvvvvvvvvvvvvvvvvvvPPPPPPPPKOPPPPLPPPPPLPNFPKPHHPPKPPPPPPFPLPPPPPPPvvvvvvvvvvvvvvvvvvvvPPPPOPPPLLPNPPPPPPPOPPHFOLPPPPPLPNPOPPHNPPPPPPPPvvvvvvvvvvvvvvvvvvvvPPPOKPPNPPPHPOOPPNNKOPPFKPPPPPOPPFPLPPNFPPPPPPPPvvvvvvvvvvvvvvvvvvvvPPPOJPPPKLMMPKMPPOPONPOFKNPFONPNPMHKOMPHPPPPPPPPvvvvvvvvvvvvvvvvvvvvPPPPPPPPKPPPFPPPPPPKPPFFPPPFPPPPPFPKKPFPPPPPPPPPvvvvvvvvvvvvvvvvvvvvPPPPOPPPPPPPLHPPPPJDpLSyUjPLPPPPDPPPPPPLDDDPPPPPvvvvvvvvvvvvvvvvvvvvPPPPKPPPODDNPKLPDDPOYxx67oDNLHPLPNDDGPPPPLPPPPPPvvvvvvvvvvvvvvvvvvvvPPOMKMNPKPMGNLNMMMMIWXN85acGPPOMMHPPKPMNONMPPPPPvvvvvvvvvvvvvvvvvvvvPPPPOMFPKLMPONPPMNPKMKNf6xPPOMMIPPPPIPPOINMPPPPPvvvvvvvvvvvvvvvvvvvvPPPPKPPPKPPPFPPPPPPPqFgHlgvPPPPPPPPKPPPFPPPPPPPPvvvvvvvvvvvvvvvvvvvvPPPLPDDPPDDDPLPPPPNLrfM+R0vHPLDGPPPKKPBHPHPPPPPPvvvvvvvvvvvvvvvvvvvvPPPLHPPNPPPPJDDPPPFPPTdbSajDPPPPPPLDKPPPPPPPPPPPvvvvvvvvvvvvvvvvvvvvPPPPPPPFPPMMHPPPMPGNPPEMJPPPNPPMMNNOKMNPPPPPPPPPvvvvvvvvvvvvvvvvvvvvPPPPPPHNPLPPPLOPPNNPKPFPOLPPPPPOPFNLPPNPPPPPPPPPvvvvvvvvvvvvvvvvvvvvPPPPKPPPKPPPFPPPPPPPKPPFPPPFPPPPPFPKKPPPPPPPPPPPvvvvvvvvvvvvvvvvvvvvPPPPPPPPKKPPPKPPPPPKPPPFPKPPPPPPPPFPKPPPKPPPPPPPvvvvvvvvvvvvvvvvvvvvPPPPLPPNOOPHPKPPPHHLPPPNLPPPHPLPPFPOPPHNPLPPPPPPvvvvvvvvvvvvvvvvvvvvPPPPLDDHKCPPPKPPDPJDHPJHKPPPPLDPPPPLPPPLGLPPPPPPvvvvvvvvvvvvvvvvvvvvPPONPPPOJLMMNLPPPPFPPPHNKMPPONPPPMMMNPONLMMPPPPPvvvvvvvvvvvvvvvvvvvvPPPPPPPPPPPNEMMMMMFPOMMFOKPPPPPPPFNPKPPFPOPPPPPPvvvvvvvvvvvvvvvvvvvvPPPPPPPPPPPPPPPPPPPKPPPFPKPPPPPPPFPKKPPFPPPPPPPPvvvvvvvvvvvvvvvvvvvvPPPPDDDDDCPPPKDPPPDDDDDJHPDBHLDDPFDDKPPFLHDPPPPPvvvvvvvvvvvvvvvvvvvvPPPPLDDDCKPPJDHDDPDCDDDHODDDLDHPPLDDDPPFPPDPPPPPvvvvvvvvvvvvvvvvvvvvPPPPPPPPKKPPFPPPPOPKMMMMJPPNPPOPPPPOPPPFPOPPPPPPvvvvvvvvvvvvvvvvvvvvPPPPPPPPKKPPFPPPMNPPPPPPPOPMPOMNPEMIPPPHPPPPPPPPvvvvvvvvvvvvvvvvvvvvPPPPPPPPKKPPFPPPPPFPPPFFPPPFPPPKPPPKPPPPPPPPPPPPvvvvvvvvvvvvvvvvvvvvPPPPLPPPPKPPPLLPPPLHOPBFLKDPLHLLPPNKDDDPPLDPPPPPvvvvvvvvvvvvvvvvvvvvPPPLPPPPPPPPPKLPPHPPLPHHPKPHHPLPPNHKPPPPLLPPPPPPvvvvvvvvvvvvvvvvvvvvPPPPPPPPPPPPPLPPPPPPPPPPPLPPPPPPPHPLPPPPPPPPPPPPvvvvvvvvvvvvvvvvvvvvPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPvvvvvvvvvvvvvvvvvvvvPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPvvvvvvvvvvvvvvvvvvvvPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPvvvvvvvvvvvvvvvvvvvvPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPvvvvvvvvvvvvvvvvvvvvPPPPPPPPOICUMPPP9Od7PMONIKKNGBMLDCPKNPPPPPPPPPPPvvvvvvvvvvvvvvvvvvvvPPPPPPPPPHIBFBEBen0kCNPIAPPNBIPFJMEdAPPPPPPPPPPPvvvvvvvvvvvvvvvvvvvvPPPPPPPPPIAXOJOOzdiyAAVJIOPPPOOHIDHHLPPPPPPPPPPPvvvvvvvvvvvvvvvvvvvvPPPPPPPPPHPHPHHPPLfPDLPPPDLPPHPPPPLPPPPPPPPPPPPPvvvvvvvvvvvvvvvvvvvvPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPPvvvvvvvvvvvvvvvvvvvsffffffffffffffffffffffffffffffffffffffffffffffdPvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvuuuvaAln8J8/8Av47jPLXryr/3z7777f73/wC++++++++++++++++++++++++++++++++++/78V0f38OGUb0HnPVWNCuZfu8ww9Nl/c++++++++++++++++++++++++++++++++++++fm8VO0/fuxFt3G/teydC8asfu999t0O9+++++++++++++++++++++++++++++++++++++0mWw9w+3/eWe9e88PNDgB968+++DV/00+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++px9rW2f7kV07z122x79+++7j8U17+0+7098++7+++++++++++++++++++++++++++++tlEon+dl7xe52dzFazZHr++toyd9HCsiSQNk7pt++++++++++++++++++++++++++++++YXBmo4f8AQ/2CTW8BVs5q/vmamb9Xayig3Pv3uAPvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvv/EACoRAAIBAgYDAAICAwEBAAAAAAABESExQEFhcaHwEFBRgbEgkXCAwdHh/9oACAEDAQE/EP8AWhpIUGk3DpJ0k6SdJOknSRDcJAmmps93S5V/BbKkT9/wqNvd3fCepXgmNSwURnwnEFPF7b3d3w4oZ0PyKvOfdPyL+fi9t7u74dQ03b8JMcIKiiR8L23u7p/QZEVKAnWQJIokxAXIZp4y35vbe8bLKPOWWo2p2sXkWAIzkrsj3d0dK4htIImXIc/oa9bGxSHVFObh+8WJ/fCXRBvo/wCSbatF7tBDW8x0QdEHRB0QdEHRA28gv/XvOyDsg7IOyDsg7IOyDsg7IOyDsg7IOyDsg7IOyDsg7IOyDsg7IOyDsg7IOyDsg7IOyDsg7IOyDsg7IOyDsg7IOyDsg7IOyDsg7IOyDsg7IOyDsg7IOyDsg7IOyDsg7IOyDsg7IOyDsg7IOyDsg7IOyDsg7IOyDsj/AAu5OcREp/gmaTYmUcx4mJQXfIk3ZDZzSNBqqHP4GmrolTM0EpNgzNhSR/odIOkHSDpBe0+ZhzBck/wZPcExSnxE/ZC2QJNuEaj+jUf0Kif0GlE3+DUf0NlZGIVkmybJCGUdXZOpsjvYnQrrsiSCYpa8BhKimoSCiZN43hUiZQhKEQUVDRIZNycQdWUGbcvA2soM3eD9Q1kk2R2Vn4HRWRqbI9omCJXMUQ5IgjV+Aloio1KhipZT/wCDQzZGgzQZoMSRZ+WJpzQ2xiWyEP4GuvEhFFossy0DiLu3hslrSW9A7SSig6OylMhtty0nEJBxkUQVgxKEkIK0/UJNpm2OcgxYa6GBmqCKp3MgPWipTPMOIxW0TQxtUPgdIOkHSBBRrYQVViVmAdwEhKYQZuSwyi7IubFjc/Yn6Eu7eFSGF9YbTFkK2A4x/wAmZxBkiRZP4MbtItGfqHqOgrkjRISEAII4Ls5hxCBNSY0qcw4jGaS6bn9G5/RYkRdTjY3v6HYWJsmVhP6UPuUFVurIVneLuxY3IdrD6jqXdvDZPjT/AKMl4/E/5MziDQydBpRMyNzkQhMz9QwCEZ/D/wAUQsxi4lcbbzBzDiCcI0TQbMt9OIxoVs3zfE5SaGh4ttDVKW39CcTCjltlRQoTCQqkbcoEThd2LG/m9J8P6j/j/p+z+hDviykw5xPC4CKRzIhsj9QsOdRlKVS4C6BlLVRR0bocw4nnMTiMiVzJCQSE+BOnmTP2U0uZxNGA3XT42EuzEoZQ95C2P4PKQPJ3Mb2m8hsV7oyAFGBjLwkqExjLjEtEdhs+pqjyFOFBsu3lKuDazOgzQmQ2Nn5TLCG1NhmYGoahqGoahEzt/h9pEaqaqNVGqjVRqo1UaqNVGqjVRqo1UaqNVGqjVRqo1UaqNVGqjVRqo1UaqNVGqjVRqo1UaqNVGqjVRqo1UaqNVGqjVRqo1UaqNVGqjVQzCxwl6+ztheEvX2dsLwl6+ztheEvX2dsLwl6+ztheEvX2dsLwl6+ztheEvX2dsLwl6+ztheEvX2dsLwl6+ztheEvX2dsLwl6+ztheEvX2dsLwl6+ztheEvX2dsLwl6+ztheEvX2dsLwl6+ztheEvX2dsLwlhZJZL8ic4WztheEsJkMSoNQNeLsLZ2wvCWEyE1NSUPWhmbCWFs7YXhLCwZVJ8lecLZ2wvCWFmpJNTLwsLZ2wvCWGgginhYWztheEsLBGJs7YXhL19nbC8Jevs7YXhL19nbC8Jevs7YXhL19nbC8Jevs7YXhL19nbC8Jevs7YXhL19nbC8Jevs7YXhL19nbC8Jevs7YXhL19nbC8Jevs7YXhL19nbC8Jevs7YXhL19nbC8Jevs7YXhL19nbC8Jevs7YXhL19nbC8JYGZsKROcZZ2wvCWAdiylMTyYsixlnbC8JYB2oRDlieUCRXGWdsLwlgkotjbO2F4S9fZ2wq6HkXr9ttttttttttttttttttttttkIbTT/NCTdkQUMqilRL/AGQHK0FZ1QNaNS9Cvt7CJB5QmKUw/Oooa4aacMX/AFO/hCTu5HkVqtf2Z4o3GF4gtskQTPwE0/p8qJaNKfwSOy/4vbHQTuvyZGkJqZi8i49JdMDfJipykcwhBQeSVVIqVrIglYI0QxqKiEy7VGF6dxJrWbIIVJMcEZIYhQtZJVFxU1/QfQz4EprD+iv3GZiPNkNtJOo1P4HO/g2TZGhsiDbDNJo3RG2JCH8ON6SucHuRkTo0cpHME3ZMdy9YGck5jbd2JwiDnVFO+gmFacicOUO3cVRWx+8K50MdnZtr6QpUBX7lvSNa/wBFDRMVGi7ZktOjJfSX0lZsfKUKyJODQfJmvhxvSbQLRlSgZbkomfokES8hjASp8zfhIanqHXJ/g1XoQnlGkM0kIZRpFRCk5kOpjY55RQ8/2SRoFRpCHAaiwJahB0ucr+K6aw3nRnxEQzuJHzlf6ZSzI+DETzCTCYElf0RSawxkkkvokm4SN06tNL4ZZ5k2mzEyliolEKSUVuyUw0NqyIRB3UkkjgaKRCEO4tP4SMeSJJWOeBwVHcTNoORO4hqdxsKp7DTIiEFCASXEBESWAQgGmUeEm3FwglMc0I4kadFapUabbS4ScE4tJgcpKp/krupbjm6OF+P0SVFH2hElOn4/4JkUqIJtiZ0+MaYKKjt/4Jbbu1KIG6SrDoQwi61G/k2U/wBGwL6UC7pFKKSQiNPw8180UUJhtLKg7hcnpAk1K12WhkldkiM9iZmRUpHynZjS5/WLUITf6OYKCzEiZZv4JxsFksNUEt5EXnHJSiEpGk5bgepiiFmXXDoy5by+DSdSitbjJK7RsZat2okUNl0hE1YNOiUktRaJ4nTMiTZMdLFZ/MiZRymQEOxbIZKMeR5ChamlJKJnbjG4kkhpMs/PNLCkQKEPShEC3Cj9ENM2vBQZUUusJvmbeQjqXJjisGY2yyU0EhZEGYCTkTgm9htJpxItZBjYOmf9IP/EACgRAQACAQMFAAIBBQEAAAAAAAEAETEhYXEQQEFQoSBRkXCAscHwgf/aAAgBAgEBPxD+2gFaIOtX5gALrVcSmn3es/ChUDb8Gke9qDSzfgAqBa034I0PT/N71C2cyts0nhM0VtmC9P8AN71CkbATblJaRyqVHR/m965busHzJb62Ra6ARbr/AJveOR831CAGgTCJlEXwlA94QnGwPknhMLKEdM9Eamk3Q948OimSAYPyrRpr7t7CCGuk3Zuzdm7N2bsQPKJY/t72ZszZmzNmbM2ZszZmzNmbM2ZszZmzNmbM2ZszZmzNmbM2ZszZmzNmbM2ZszZmzNmbM2ZszZmzNmbM2ZszZmzNmbM2ZszZmzNmbM2ZszZmzNmbM2ZszZmzNmbM2Zs/0XsKTXqr8E3QRUIrpq1TGIoZYdK1hpvSCOGNUQhowKEatfiAMIrrfCpjlfgVAuU1quis/SFwQtFs3ibxMoBCtTeII4e4cNSt0rdG7/aCmcrdD59TQDLKmKWhm/BKUSWiwv6nBOCJYU6xbbjdtE1FCpkiApDTCXAQ0g6Rvq6lbpqSG5rdBXd1LBTV3MU3TfihSw0bIls/9rAsE3ibxN4j2Jl3anbEJCOaEqhaxmfoACBj5mGZZlEziAVTzDCatSAFEyQa5VG71zFXOo3f7dK0OkYMGjHNBAI8Eww9UOV0LDzEDUuBNn3+AAK/rEK6Hc0C0IuFP3K5Ya0YSmzzMfMz8Q4h5jFzMMKsJX6EpX7gbEw89HiZIioszRARZkrpEazWOUMp+5SCsLl9vjqgLe0dHfosPMQaYTi/mcX8xy1Ghpc4P5hNe5HV5ifpsGv3j6DQl74emPmZ+JYBmBETFzMMyT/aGUcsHR4mSKAiUoykIlJdIIDvaeKLvCRFfiABR1QSxJW2EyXMPMS0HWkppg0WAg1oH8x+5RxFTRFBMEUtSoB5i2CY+Zn466NVTD0/9/8AUP8ArmPRmPooRMnTfZ0ArXROoIBSzCI5hAihiAj8UPCTDzLGKfqU/UW0xtoRUP0mp0x3O3AGDqpmELEEVAK/wVIQBUcRAhP3KkMMpDoBQmpcAKIo2yu10AFQpYawLB1WyQJsIlpQBB1VZAGyINk2ZszZmzNmD/R8QUR0m0zaZtM2mbTNpm0zaZtM2mbTNpm0zaZtM2mbTNpm0zaZtM2mbTNpm0zaZtM2mbTNpm0zaZtM2mbTNpm0zaZtM2mbTNpm0zaZtM2mHd2r7PX5ue1+z1+bntfs9fm57X7PX5ue1+z1+bntfs9fm57X7PX5ue1+z1+bntfs9fm57X7PX5ue1+z1+bntfs9fm57X7PX5ue1+z1+bntfs9fm57X7PX5ue1+z1+bntfs9fm57X7PX5ue1+z1+bntfs7TzUrA3VygvWcpSsytZ2ubntfs7QwhFiLNWCiGkdFt0DQ32ubntfs7TBEySzeaasy0JdEKwHa5ue1+ztDCISnEC4XUZxEV0lRb2ubntfs7QySrAlDZE0MpHTERXNQ+Tbfa5ue1+ztDMwJSqiUWRYCU1+0w3ntc3Pa/Z2ophm4zefzNxlvLOe1zc9r9nr83Pa/Z6/Nz2v2evzc9r9nr83Pa/Z6/Nz2v2evzc9r9nr83Pa/Z6/Nz2v2evzc9r9nr83Pa/Z6/Nz2v2evzc9r9nr83Pa/Z6/Nz2v2evzc9r9nr83Pa/Z6/Nz2v2evzc9r9nr83Pa/Z6/Nz2v2evzc9r9nYBbRKqEIo17zNz2v2dgZJlpJXmp5U7zNz2v2dgZ1l0o0iYbxAdDvM3Pa/Z2S3nvc3Pa/Z6/Nz2tqh59eAAAAAAAAAAAAAAAAAAAAAAAgwef60KGWX6nmfur+IWqlEhpuCoajebq5iU01EmlheA8zYQbLIn8l0RZggC4pWgzwusBomAXLcDmYvCLuixLGfxBcHWUDyy37yoqxMQov0gYIbu7SfJ0SDSgGOjUattKmiKVNtr2iSYdoLo18RS3wS63QpC6h2RUraRuE4myoFZUEjOfqOHEAVAq+IAUgAIEHVQwEtPOIa1qdJWpPt9JoNahWqwz5OiQykA1tIhRXiAGCJZTCjMmIluVnimGfEdMPM/x54n/AHIQhQNpfU2jhxMwqBARt1SNDTmUJqSn6lP1KeCBSMQDCbpPBn2+ktuxA+TCtMWCjFQlHmBQiqePRdDQhpKlf+yWp5y1vGpa3lGFysCmqp/1IMKPmIjw4lwXNIKtlM1ii7aQqKfipS0gKa1TZj3fxFYUp/syoqX+4Koi0XUvoi2hMy0UuWoWqFxRJ5MyD4lwWOkfJqZlgllWMLR5l7bMMooLg2iL2Xlv+FAHmUWHivsLJGImttIgyP8AEt8JzBGhiFKWlsQVSg5IFLU1qvooFsUrviXS6hWoDBALrUuU0KrhSrp/E0VoXiIDUtlGTr/7Auk1/wDYhZWtzSDWeeEHQM3KAA1q4Aq6aazMPxA/Y5lVPL+pdmzxGllVCqnr0NavmBUNfOsBbSk3ippcyQqwiqMyqSvSagqYKI/UVWFtQ1voikqh+5pBYrRquIE6vHfLRbNWWV/lCoNeJgGBQBQQUudoVRlLiLQN2atxllTEE1Qq7Raout4Ng8wKsaH2WGpVShQ0v/2BbKeU1ZriC1LxCCiqxNF+uEaysnbeYq0NZkgxcfOzXDMQqAIoaNkB5TAu80QLXuXwKxHaHMBQJiNplICJ1X9kH//EAC0QAQABAgMGBgMBAQEBAAAAAAERAPAhMVEQIEFhwdEwcYGRobFAUPHhYKCQ/9oACAEBAAE/EP8Ay4pcT3rmnvXNPeuae9c0965p71zT3rmnvXNPeuae9c0965p71zT3obJPf/uMfYswl8uhTWLGih/tOPiiqRinsVpKfZzqYOJmcT/tcrhGnhlWf8/BAOUwmpxoQGRBN+HuQmE+tK/59qfdQGGIgTezl2/SmlAEzGTyyriRuQmfD09d0iYExa4k61yPe70gOoO6qIoOBOFf01FxyQScH/KmhwF8vQ8BRGIJUf5RSHaPBWdmD7FD42gFeBNf39f39f39NcQUoM9maS/CcALX9/X9/X9/SYk0SgwJ6bJzYv0jI9X62QQxPQO79G+98J9U5kIo8cmv6agyCsANXkFX+3QIBjJLj4q+ulKkqQoX6q/265aOQ9mkpPWmezWoaYykUX4/5H4v6fA+GorJ6eDaNds0aiV/L1/L1/L0+C4ZEbFOwTXElCv5ev5ev5em6QEIDETrSglyzpuMvQGB39azDT8mr6GNAzAT6FTGOLSlgpYKWCkz4QnH5M8dj5hkeOjPDOKCw3qv6GmpI1WjedVHDTwV/Q0CojkJHH/yPxf07gGFZzU4MaVYHSrA6VYHSo3kVCn62OVWB0qwOlWB0qwOlYwLFEK5Rqc9y0a7jNpAgLlzTauXDrwykZ8Y2CmVVGeCOG4uXLjGgZADjlMVKjh/Li9sPXYaCslF7lQYUyiz1aOC1gRHljVi9aDCTJAj6m498J9UgZhXGeBPhEiRIkSJEiRIdUFQQnBTh5f8j8X9O4JGKc1OLOtcv3O9cv3O9cv3O9E9EVCn72OBXL9zvXL9zvXL9zvXL9zvWGgxRAmcc+W5aNdxCyQIBy5jvECBAgQl/n0Hmy2ADLEdAxX2okAgCCk6pwhIvF74elGSZMSsNlr1qDjAj8n4k2Z86fRuD3wn1WW0i9SK5ntdq5ntdq5ntdqDvNgVMR5ctnkw8DPFw5Fcz2u1cz2u1cz2u1TEQISy4s9f+R+L+ndCJgSbRVn/ADVn/NYGwV2HKrP+as/5qz/mh+EciTu2jXdhcpJEC45suJ/lElkILLkk4lCsJykxWm0ZuZDOLw9H35bEQILzH+s/bdMWvWkBDllT68B5ZnxFTGwvSYPjP03HvhPqmyQCT5VyV/KuSv5VyV/KgI8KQYQ6nPZhuymJmMWRzK5K/lXJX8q5K/lXA5JhOMcf+R+L+ndCDDA496u3vV296u3vV296u3vV296PACmA02Q9xF65fXzu2jXdnDBD3OdPzl4xYJ31KFLyTI0amPiZj7nekIlELp189KVUuedE8YYDxnDy19qMN0xa9dkUmE/z/wAJ7bMZ8QHzMH5Ha98J9ViPGKxMSRud3RWfENQWZlps7hLi5kZbnd3AkpOZKuUuv/I/F/TuOQFdAlrKfdlZj7trMzzI3M/IKZEQD0oirgBNP5MeXD43bRrvIpi9zmPBqUOOwE+NDQVpESMUyqVWVWWmgXNMnYL50UAAQAQG8YteuwM4AlhPE4+df2uylk0cYkmMMDlte+E+vAEflX8RWRo+T/yXxf07AqAlcKBDzov29qEgzkRWFYUgxPivmkEvcr59MFYsA82Ps0qQcESHm3rswI9Bx7eu9aNd6YGNx5fVyPunVMbOw9XjU1TyMDzcin5VmMGol48JMHl0UMEP8xr6bxi16773wn14AjwEOSRTJ8YeXB9opamTwcTiephRIjIkn/I/F/Tswx4p1PT3pcAGCuTlzp32Qw+KVzX3rJz5MrPQdB1KwlnrY1DkNRk3MiAglKHlSMIicEh3bRrtXqEJjgYtT4hr/U/FS5M/6nF9WkME5I9zhURLa8erM+kUBOuAQVEUODknk5lSMJrx6Mj6xU/Dwkj2ePpUDyG5fTN7zSYkAIkXQTrG4Yteu4kwsRBgxhRJCAA54EY0C4FFAeAKKHmWPkTX8r2r+U7V/K9qGsQzS5CRlz2Y7NPCMXev5XtTBUyRliYT7R7bJWuJl9PiP+R+L+nYg4MJ5ub9759YZjDj6caaSOGWCLdwCB9GPvWKLyOD3z+6zqDXoZ0iYOy0a7YoJFnLESm4mcpvl7VCoS4uftl8UAIMst18JMxCezUkp6V7OHtFJeeioB4ZjsgsoZaQkRJrs6Byalrcpdw9tREWLLGxQOgckjQdYA2FRIi9a5y3nXOW865y3nXOW865y3nXOW865y3nXOW865y3nSdUgJ/Z/wCR+L+minziPY/jvIgEqwAS1gi5TOP92TtlGHLm1DE3uepRMj6sfU3Pmwxe9ffTD5x+afiQxiM8ev8A2nxf07OFxkcuHamR8WGTy1KRQkPltcI5Qg/2gJ9UfRw2FMXwHn2pwqplXYnKROIw1FhNTL/VRBDqYP8AvpuZ/N0N5GGGccXI9D7p22MHy7H73ARL5lOKxw3MFzhSJH1Nok8mtHoJZPHypWgEwT04FH8MY3EcF4+e1QpeEMcUYGB8FDxoZ8zB+R2ykzl9HJ9H7qbfHw1Vl7OHtUY8PjqDP3YPepSZz+hm+r9f8l8X9OwwkJ7PJokDg4px9Na+ZwNLPep6TOsJ96yqVCffoVOS5U8XalXewc5DF851HElzx91BChHiMlZ/N0N1UkAKfKmjZWXJw9gKkJAb8sj9t3HadNy86biD49a+6tEEBKrBTNEDgaAy+Ns0GB6j4mnhcSPOdCD1qUdy7HR87QTlCOTUclifNcfaHzKnzmbTifb5tCjAAcj/AJI+y+naKULBFoPtnRMz5KU/I+atBxgckfOdMyquqy+E5K+rD2yqLmS5EGDHTdj1xY+Wfww9aBNiCBRmsPp7VkmKHFxMck9qkNn3Awe/ruY7TpuXnTcQfHrDpoFxZ/KmMQZ4/Aih2U5CiI5Bw2we4SvP/Ee9Zf0nExI4+npUQSuRIV5cn3KIdun+bmIp7Q+9eX+5AC+8nt/yahEjD6f5P4SIAlWK5ZGfPj87rSU+6uL0PSnAyCgYGMSlyDikGM/ialVgnrGHwj23MaohATYh5Vz9nLbedNxB8etfdXcSKEPyCWloklGcGfYr+bo0NilBCZe+PtU8WR6D4jb8BR8j/wAoJDl4ocP8/BDBEYpn5u8YsvHpFmcTz2AokiQ0GZZjEmJG0iCBMMwlerX9XQXd7c+tOGHGmABNy3CR6G0cFimGHBHpSoQgGZ5bk2UEMMPvThgsVnhM9NkZlgwMMlDiIQo8LaUBRHEMYTrTQEU8yxgOn/KozJuKj4yoOH3danf3qd/ep396nf3qd/ep396nf3qd/ep396nf3qd/ep396I4icodaenmCl7H/AJlJ39qnf2qd/ap39qnf2qd/ap39qnf2qd/ap39qnf2qd/ap39qnf2qd/ap39qnf2qd/ap39qnf2qd/ap39qnf2qd/ap39qnf2qd/ap39qnf2qd/ap39qnf2qd/ap39qnf2qd/ap39qnf2qd/ap39qnf2qd/ap39qnf2qd/ap39qnf2qd/ap39qnf2qd/ap39qnf2qd/ap39qnf2qd/ap39qnf2qd/ap39qnf2qd/ap39qnf2qd/ap39qnf2qd/ap39qnf2qd/ap39qnf2qd/ap39qnf2qd/ap39qnf2qd/ap39qnf2qd/ap39qnf2qd/ap39qnf2qd/ap39qnf2qd/ap39qnf2qd/ap39qnf2qd/ap39qnf2qd/ap39qnf2qd/ap39qnf2qd/ap39qnf2qd/ap39qnf2qd/ap39qnf2qd/ap39qnf2qd/ap39qnf2qd/ap39qnf2qd/ap39qnf2qd/ap39qnf2qd/ap39qnf2qd/b/5xD8H2rlvtXLfauW+1ct9q5b7Vy32rlvtXLfauW+1OYH2/wCbjYeYPCv4GkAJ0DXfCoM1inGFiVji51ynsoMYBlg4v+fe6pIcHhza5T2UCAjBw5uw4rEkRxX9FQIypGlLZDNn1oKUQJz7AwXE+6tOh8UISgUQxxFW/wDNZ6cSWco77AX2VQVBUFQVBUFQVBUFEjZnvANJIvmj/A1/A0aABJgEGTfNSkYMOL/n3XKeysfAEGDNypVZfPcRiTj4cyuU9lOAPRz2TM8InE41znud65z3O9c57neuc9zvThEID7VyKS2AxhDjXOe53rnPc71znud65z3O9IjH683SRB96ND7tcj7tcj7tcj7tHoQWXYxOVglcj7tcj7tcj7tcj7tOsIGNmMzqOHf02ZMEn+VAMki8+Otc1bzrmredc1bzrmredGIzixiOM7JHUIRiyl67L9pUG5bNKEsVxkoGIQE9MaakzzZprOtf1v8AaWiRIRxynjst2mwaRLGwcXlyrkLeVKazEiZy2AzG0HCuQt5U4cDCxcB67MuzPYsCpEy6Fcj7tcj7tcj7tFgiIjLtdmVmy1yPu1yPu1yPu0VwDBl0bAVBnlQ8ZiXzc9hIYiLNKz9H3XNW865q3nXNW865q3nUDLEhg07bCmIhExNCZxDKI4p0r4HX4blWf5v17uyD7Gjc93dJJAxiRsZywjIlxY3Xd3TSwgxI2QJOpcNmcrsC+VIusiuZUNahrUNdqPU/WyHgJhM5V/Ce1fwntX8J7V/Ce1JQwlAzjhpzq+at2+ctlu02fG/bsyLsdlhy2fNfRsy7M9ifGTwS4G47vidAHm24gBQxg3Hd8Skc0DBHZiI6jh39NmXwj/lINjI92oa1DWoa1DWhNvwH218Dr2JKhgTyV/Yr+xX9iv7Ff2K/sU1oGBeTscqz/N+0sOWyw5N5NkmzHz7bAZ0FyloL0UoSE0Pbe+A/bs+E+9j4H62MQB5gpyEPIOz5v0UbiBwJs/MrJ6UgWY/YVZ7FWexTI4SYwGflst2mwmYmQkfVWR0omqkrjE47ACgMhOpVkdKRuhzEnIOBy2Zdme9ZtaNl3y3QAaoU/hCyxnYYgyZIY5E++9n+f7Nltz2N8Oa4RPera9qbMCGOLkj0rnKc5TnKc5TnKc5QUNScITMira9qvr2pSXnP7AeuYO5mZYSQs4s7HgyonGOM1zPvrmffXM++uZ99ZxwBjPAn5moDtV3ypMgxJCcPMr+t2UVYzJTobfgP27PhPt2fA/Wy8aVkbL5v0VB9S+aMq9J3xTG5fh0oSGe4dNtu02FpJJyWuT93asywiYjYEMRYMFrk/d2pVCDwJsy7M9g2JihDGc7kzON0AeTa6dJLjK5n31zPvrmffRVAiMTeVfCUiNmV5euw+4R5g8q/vdlIjmns2Z/n+zZbc9nxOuoKgqCoKgqCoKgqCoKQis/zfsDpyHB3qwutWF1qwutWF1qwutWF1qwutSJ2gV2ZfCP+UzWal67PlPt2XfKtJQz5OD8UVh7izPMxPnb8B+3Z8J9uz4H62MZqAmZ9NYPsImb9uz5v0VE7ZKK9Bj3Qq7aV8z9bbdps+N+3ZkXY7LDls+a+jZl2Z7AcaMJBrrVhdasLrVgdasLrVhdasLrVhdasDrQxQiSIybBA5HHzf8+9mRZlsy/L9OznBvdifezDWRezB6bM/wA/2bLbnsXicDRlPeuW9/auW9/auW9/auW9/alSIhh57CbQ5y6vav6DX9Br+g1/YaUl1Z/YPJOMZE8s86s96pIIOEkffJpxAiMIkO4DNUwBU3cUYvQ5bDk8FPm09Nvyn27Lvls1hSPmYPzTWnuM8nE+9nwH7dnwn27JdIjhw+KElECeMfVHARCYmdg6JZipg+VFg1MWYwZ05VFfP7E7Inqj4VdtK+Z+ttu02JhESRpm6VYXSjIyymDnsF3CWCeHKrC6UbsTYUORrsy7M9jhiOJMURMokf8AajkIYZk33UXxLKGD/u6gDAZC2VACCmzzyGrSpJVVdmRZlsy/L9OzRCf1P8XZCH+bh9xsz/P9my257AwSrAV/Ir+RX8iv5FFxCAnpsaiAGZq1znud65z3O9c57nepzAwmT9iyIwjImdHnAHvzNgUMjCGfJrC40eD5OzGo1eDzawlizGHwaGxgmI/rzpV2/Kfbsu+WyTtB6/x2QlxH6Zffxs+A/bs+E+3ZjLy/dQ8nfGz2lPlL6qZOjfNeQJfLZEmoPhq7aV8z9bbdptg3INuXZntzlp9uZRZBEkRwpiZXCpufO5PPXYigJXClQGGZ39qAQYGVLngCpOwGA0/3bkWZbMvy/Ts5EmfLJ+KKDVk7UiYVm+f7Nltz2uPCtGv7JwUiMiMNRShkMg+ejRSIAeCUfPF10o8IHANkshkrmHdpwpVZVcdz5T7dl3y2NTiYExXIW8qxOcAOPps+A/bs+E+3ZAnKfH+0vZ/nZ7SwL8KM+o+FQHJPo2YX5/h71dtK+Z+ttu02HCBDIeL2rk/bSBSngjJdizFKHA1yftpWkosgcDZl2Z7jMOI4yy5lAyJ8nJ2KI88MPUrFROpi/wCbMMjQ4vIKl+AZSwP93MizLZl+X6drcKgJwTHHKuQt5UZJiITMTWf5/s2W3PZmVKFc+nn08+nn08c4OGwOQPAPFrLAmJKuYqEHAWCal5FI5D9iBJ8R91nPmD0mnZJyJfLUIgNU7gowPFQ123qHash9DurOV6B60kUHB2fKfbsu+W98B+3Z8J9uyBtX9FKPQfDZ7TwOdr3nqZtGfJsiHmvaO9XbSvmfrbbtNmT5Pt2fN+zc58z9GzLsz2S8sM4rR/n3qyLvVUNLvLE8zdUx1rg9CvkOlVqbyPepoBlPDZkWZbMvy/TvZvn+zZbc/BP437a5Kn3H+bPIw9Ov7L4j62nyoHnWu+gn5yrER5FT78Kw+b5knuUDJDkHc+J9Gz5T7dl3y3DOiLJ7UBgNnwn27Ig6tSjnh8mz2nh+b5oJvMKkXQPl7bJj0T67VdtKQEPlUIMiUEabLdpsyfJ9uz5v2beAAE2EJmURACZgAfGzLsz2ZfP17ZKyMOky+2dYMq1wHtn9UvCDniPZrN46TD7NSbbhrsyLMtmX5fp3ZQB8nKsiA9Nltz2HkJBIfury6VeXSry6VeXSgwAGABBsYlyIxDUnjmcA2QYEeCSUw9NWFeb9gCDIBaKyJ+71rQByB/tKyxzTuOSkdRhrL284+1ZgP1D90TMPlDpXEUjCZyI2PooTAIz86ujrQeISY57hRdH3V0daujrRU0AYgnN77HAkXEiMVah5MHGIwppNgwCPuro60jUIWU5ufvWa1eDiedY0mgHCdXnV0daDkBnBOfKkxhAsaujrSySCMhxjR2PZ0eCMDzqxO9ACQDi83vsUCRcSIxZqxO9GIJLjFOmIvB5narE71YneopTHFEbIWkelPLnS8l5p7Vl75HfNZxuWA9jdyycpR7VnXmD0ig5jyU71P5pGceXpsiapIwCaujrUc1jiCPvdYEWWAac2ro61dHWik2DEI+9jwqAMGMq/lK/lK/lK/lK/lK/lK/lK/lK/lK/lKf8AKU5Lqz/9VpvixMWSODV3uVd7lXe5V3uVd7lXe5V3uVd7lXe5V3uVd7lXe5V3uVd7lXe5V3uVd7lXe5V3uVd7lXe5V3uVd7lXe5V3uVd7lXe5V3uVd7lXe5V3uVd7lXe5V3uVd7lXe5V3uVd7lXe5V3uVd7lXe5V3uVd7lXe5V3uVd7lXe5V3uVd7lXe5V3uVd7lXe5V3uVd7lXe5V3uVd7lXe5V3uVd7lXe5V3uVd7lXe5V3uVd7lXe5V3uVd7lXe5V3uVd7lXe5V3uVd7lXe5V3uVd7lXe5V3uVd7lXe5V3uVd7lXe5V3uVd7lXe5V3uVd7lXe5URwYGbMHF/U3HN/z1xyfqbjm/wCeuOT9Tcc3/PXHJ+puOb/nrjk/U3HN/wA9ccn6m45v+euOT9Tcc35F207t+7afBu2n8K/fdtfjVfu2v8i45P1NxzfkXbTu37tp8G7afwr9921+NV+7a/yLjk/U3HN4PePfQR3eIZmZmZmZ/KNKRmlrptu2ndv3bT4JmZ3b5nETETDGm0z4c7DRSZg/jd+UaUrJLXXYfwjSkyQ0126ojYJkJjLlsPjTs9MIiH8bbtr2d499BHd4hnV+7a9vwjSkzQ008QzMzMzMz7R56iezwbjk/U3HN+RU9207t+7afBu2vxb7tr3b921+NVX7tr/Ivq45P1NxzeHTASCVgAzV4FXd1q7utXd1q7utXd1q7utXd1o2tzAUhhhMMzdnu2ndvrXACaIZEnBq7utXd1q7utXd1q7utXd1qaylOGzlMOGT7bELAQEqrgDi1d3Sru6UOfIBnGsPDB9thBwAAlVyA4tXd0q7ulOxuJSEkkjy2CreIISBK4Z1d3Wru60BICRkRcI8TY/eASM5cvHBq7utXd1oCQEjIi4R4mzQkmOxjiXHM96u7rV3ddupdMNnDMGGT7Vd3Sru6Vd3Sru6U/eAGcZ8PDB2XbXu3uxuJCEsErhnV3dau7rV3dau7rV3dau7rV3daICACMiOSPE8Orjk/U3HN4dXbX4t8920/h36v3bT+XPdtfi3z3bT4dXHJ+puObw6u2vxb57tp/Dv1fu2n8ue7a/Fvnu2nw6uOT9Tcc3h1dtfi3z3bTsClEpDIJxHDF49mzZs2bNmjpxIYgV4rHFsspYhK2iAAMSEwm2zZNcY4CYAIleBrsd8Y9QYCRImZpu2bNmy8uCOIU4DHB4Vmzo/CJqTK6NhqiBEwlEnlhts2SlQRggTiuOLZdtfi3z3bT4dXHJ+puObw6u2vxb57tp/Dq7a/wAa+/V217t+7a/Fvnu2nw6uOT9Tcc3h1dtfi3z3bTuvHgQmiZiTg1d3WprKU4bOUw4ZPtu6Ekx2McS45nvV3daOHAAdVyAnF2hnCAdjWFywau7rQ1wQOquAJxfAvePAhNEzEnBq7utGfSCiBqJMShPMq7ulXd0q7ulXd0rjTUY7GcSY5nvv0hYCAlVcAcWru6Vd3Sn7wAzjPh4YOy7a/Fvnu2nw6uOT9Tcc3h1dtfi3z3bTu3bX4NXbT4t++7a/w79Vdtfi3z3bT4dXHJ+puObw6u2vxb57tp3btr8Grtp8W/fdtf4d+qu2vxb57tp8Orjk/U3HN4dB9JxKSJCScvAyyyyyyyxO8glCVXFVzXdnu2nZM+0OUXiAmYbcsk+l4gJVlYMM/BrLLLLJSzAUkKGEMEjbllNnsSRACGGeJE47uWWWWR3glCUcVXNd2+7a92/H1NJoBgSyyNmV515lPGnLdyy92qLFGR1d3LLLKJ9gcoPEAM12XbXu34HeSSlCJiI5h4GWWWWWWQfQcSsAQEvI8Orjk/U3HN+RU920+Nfq7a/Fvz33bXu37tr8ar921/kX1ccn6m45vB9+rDNyOp4mWWWWWWWWWWWS3gxEI4oMh23bTu35frxFIkjDo2ZXnXkUcaM93LL3aoME5HU2ZZqWYCglASpisbcsps1iSaAyUzwJnDZlL9OIpVgJYZ+BPfdte7fu2vfr2aopmMxo7MofrxNAErBoN2/dte3NbwYCUMRGSeJlllllllllll7dWE4cho+Dccn6m45vyLtp3b920+DVXbTu37tp8Ce+7a92/dtfg1V217t+7a/yLjk/U3HN+RdtO7fu2nwaq7ad2/dtPgT33bXu37tr8Gqu2vdv3bX+Rccn6m45t+7ulXd0q7ulXd0q7ulXd0q7ulXd0q7ulXd0q7ulXd0q7ulXd0q7ulXd0q7ulEDAACADIDgbt+7ad2ru61d3Wrqw5iJgmM4Ku7pRwYAhomSMYO7fIKACMImSPBq7utXd1p2txKUggleRsNjcwkJIYTlV3dau7rS1pSUlUSVVlWru6U59IKoupgxIMciru61d3WlrSkpKokqrKtXd0q6sOQmJYnKWru61d3Wru6Vd3Srqw5CYlicpau7rQVpSUkQWERhGru6Vd3Slz5IEZ1g44HtsYGQQkiOYnEq7ulXd0q7ulXd0q7ulXd0q7ulXd0q7ulXd0q7ulXd0q7ulXd0q7ulXd0q7um/ccn6m45vzb920/jVV+7ad2/dtPg1V20/m3HJ+puObwzZVKBjAVjnh4FmzZs2bNk1xhgBgSmFMzXYa6xwBxARKGbrts2TZQKJjAEnnju37tp360fhM1JlNGyyfMFUSUBOnHa8sCGZE4LDBssuzJQlgKxrw2lu4YHgSmEZmu7Zs2bLqihRwkBjlhsaVBWCAOA44Ntmy6ooUcJAY5YbNXYRNCIHVv2bNGqqEDGAWOeHhWbNmzZs2bNkt3DI8CUwnI03QpRISyi8UwxeNZs2bNmzZuOT9Tcc3h3bX4t89207t+7afBq7ad27a/wBnfV9+45P1NxzeHdtfi3z3bTu37tp8Grtp3btr/Z31ffuOT9Tcc3g6lkx2MMw45vvV3daCtKSkiCwiMI1d3Sru6U/eICM58HHA373a3EhSGSRwzKu7pV3dKKpKylABgAAAq7utGfQGihgJEwpPNq7ulXd0ogYAAQAZAcDZxpqMNnOJMMj2q7ulXd03SCgAjCJkjwau7rV3daPvALOMuXhi7Ltr8K9+8Qs4y5OGBV3dau7rSEkBWVUyrxdmpZMdjDMOOb71d3Wru67tAWQBJETInEq7ulXd0q7ulXd0q7ulXd0q7ulXd0q7ulHxeYBJIYQnLaJcEBgDgCcCru60Z9AaKGAkTCk82ru6Vd3Sru6Vd3Sru6Vd3Sru6b9xyfqbjm8Ortp8W/fdtf4d+/dtfi337tp8Gqu2nxbtr/Av3HJ+puObw60RHmJgYnhltM+HOw0UmYP437/lElIzQ66bTPREeZiRieOezjzsNVJmH+t0zM+8e+gju8IzMzM+POz1QiJf62agjRMSExxz2mdvl5BGHNy2H8I0pMkNNfAv6ojYJgJjLlsPvHvoI7t+rtp8W7a9nHnYaqTMP9bTPREeZiBieOfh3HJ+puOb/gavnqe+/V20+Ldte7dtPh3HJ+puOb/gavnqe+/V20+Ldte7dtPh3HJ+puObw7tp8W7a/Gv1dte7fu2vwL7tr/Dv3bX+RffuOT9Tcc3h3bT4t21+Nfq7a92/dtfgX3bX+Hfu2v8AIvv3HJ+puObwTPtHnqJ7Nl207uiI8xMjE8Mtpn8IkpM0Gmmz5RJSM0Oum0z0RHmYkYnjnu39URsEyExly2H3j30Ed272jz1E9mw9URsUSExnz28adnrhES/rYeqI2KJCYz5+Bfdtezjzs9EIiT+NpnqCNETITHDLfM+0eeons3eNOz1wiJf1sO7fMomJmJJ12mdvl5BGHNy3TMz0RHmYgYnjn+BfuOT9Tcc3h1dtO7dtfi3z37tr/Gq+7a92/dtfjVdtPgT3bT+BfuOT9Tcc3g6l0w2cMwYZPtV3dKUmAUwAyrGBu3bXu3ureBKQjAciru6Vd3Sru6Vd3SnPoDVVwMCYFjk1d3Wru60dSVlLRAhEREq7ulaE0x2McSY5nvuzWWpw2cpgwyfaru6U4WBKYBmrGBtTCkibOkhniVd3SnCwJTAM1YwNp9XmESGGEIzKu7pWB1UKkCQAlQ80q7utXd1o6krKWiBCIiJV3dKbtAJs5cpng+227a9k1lqcNnKYMMn2q7ulXd0q7utXd1q6sOYiYYnOGru6U4WBKYBmrGBtTCkibOkhniVd3SlJgFMAMqxgbs5tbmEpBLAciru6Vd3SkrSkoRBZESEau7rRdogdiOYHLE8a45P1NxzfgXbX4t9+7afBq7a92/dtfgXz3bTu3bX4NVdtfi3577tr/AuOT9Tcc34F21+Lffu2nwau2vdv3bX4F89207t21+DVXbX4t+e+7a/wLjk/U3HN4OrsJmhEJq2WTVkKCwAxrx3btr2tu4ZXiSmAZum7Zs2bJqghAgkFjljsClEpDIJxHDFts2XZUKrjAAnnhv6+xmcURCatllLUSMikpRMLEjts2QHlkKkqVQAHhMvGQoCEZV8oRMH1qFaoAmBDAmeOlCQc5D6qL3Cry2GUcSUwOLVrtUM7xQTABEmpRwiFjEKkDBco+hVBiaVzAAXDlWbcEekImT60eaMZERwTOP8AjCJts2QtRIyiQFMSsSuyz2ZXBoGeZnhv2bNOyiVHGAjHPHbZspSi0llA4Bhg2GqIETCUSeWG2zZNcY4CYAIleBrsNdYYC4kphODrts2TZBKBhKVjljsKVBSGQOI4YvGs2bNmzZs3HJ+puObw6u2ndu2vwLtp8a7adrM1uQggTn0KiQVTSKTjIw01PRUTBhUGcZilORLM6YNYLIYMR78aRgBJOEUyzpoqxGKcmENPQ89nlgMzz6227afGqrtr/Iv3HJ+puObw6u2ndu2vafV5hEhhhCMyru6Vd3Sru6Vd3SkrSkoRBZESEau7rSYwkHY1gcsTa8eBCaJmJODV3damspThs5TDhk+227ulXd0q7ulXd0pSYBTADKsYG0lhTiJMlB4AS2dJKcolMyNNKMlnCJbxaKwCVMBlIg4YBTEiWBYSLi4ZsTnSYKgJz8msgKQya4ZoLDD1KcfJhoiVQNXPnV3dKu7pSVpSUIgsiJCNXd1q6sOYiYYnOGru6Vd3TdpgJBKwAZq8Cru61d3Wlw5IkZ0k44mxCwEBKq4A4tXd0q7ulXd0q7ulPxeJBJYJUjPwr/Gkox2M4lxzPeru61d3XfuOT9Tcc3h1dtO7dtfgX3bXu37tr8SjWQIy5VQglOKxWNKczHCSEMhi51C+dGCrA8MsgqXB2s6Yq40bWELjCnU4GJ8UVgIktcdKKQwQPEez9lNRyOYkp0YmWWVErBSh4JjSVuq+W27a/Bqrtr/Nvz36uOT9Tcc3h1dtO7dtfgX3bXu37tr8SgYJdPloWvBPD1eFAwqRGIFYCJx4U5lgwmDjDOEmpgtcFnlMHrSgMZmAUPUE/FS8aXrTGMaXFYZEgiMY41gkyYGhVJUucWrDm23bX4NVdtf5t+e/Vxyfqbjm8HLL2aopichobE+h4gYRkYcMzbllE+wOUHiAGa7Ltr3b8jtDCEg4ImS7css1LMASQAJVwCNmS+dtOWUYhJmG2V68RSrKwQz2ZXnXkUcKM/CyyyyyyyyyL+CKJAOIAOL70e6oKMtAGYxxj0kj2ipBFQplxMz5mHMrOE8jCsHIJyokcyOeNY/JgZIo65ohAJwpaVNwOPzTAGY8fikeFE4THvUqUM8ojSETMNuWWalmAJIAEq4BGzL3aoMUZnV3fdqixRkdXZlmpZgCCQJIuIztyyVztoziBMp4B4U8rV0ilSrBDPZkvnbTllGISZhtj6mk0AwJZZGzK868ynjTl4OWVxyfqbjm/Av3bX4F8920/g0xHBfQonF1wJrEk4FmfQUswhBwkYL80M0vDzrGiSdKwgnHYfdEIAPSkUDEDWlGuFaRRZGM8ZxaQo2zdtPg3bT4s9207t21+HVxyfqbjm8MPpOJCVYCXDPbllM+wGUTmUmY7Ltr3b8jvBCUI4qGQ7csg6g0kYQiSYZmwHOyHOIsQFyHblkn0vEJIkjDy36yy92qLBOZ1N1kLMioeyi8AMKcEIoz8NMIPNSSeXDLP4qIMQJz5rzrFp80WJ4rU6AkThRsxTaEioCRxssY/wBUENYMDSoMcedNZdP1tXbTs92qDBOR1N/LLLLLKHq6TQBVglkeBgt5JgJBMRGSbMslvBiIRxQZDtu2nYDnZDnEWIC5DtyyzUswFJCEkTBJ2ZXnXkUcaM/ByyuOT9Tcc3h3bTu3bX4t9+7afEqjNeYpy/OL8rUZujZGJivnCj5whM4mjEzWI6ajRkhwEcc6OZhUGQJJjE/dYteTGWdRqxBMxSKEScU5a6UMm5MHP2pLScZ6aue27afxqnu2ndv3bT4dXHJ+puObw7tp3btr8W+/dtPiVWbkJlMGd9CX0oRqEoRi8WeRgY48KnxkcE5+fLChxqDhzKkYseDNMsGAJCojl7tcmnM4Vg+WtDCRpKVcafhFBDuj62rtp/Gqe7ad2/dtPh1ccn6m45vB0Jphs44kwyParu6U9SVlKiBIiCJV3dau7rV3dau7rTh4ELiOYk4m0uLzAJLLAMZ1d3Wru61d3Wru61d3Wru60Z9AaKGAkTCk82ru6Vd3SiBgABABkBwPBqgLIAkiJkTiVd3Sru6Vj7zAiuGMHkY1iJAQnVgn5mseIsEHNAwlInz8gKcQJxCYnDLJw4UiHCDAl44Ye9EMMkw0qWBmtYGghgSJOHPg/FAOZJnNypkWW0k8ygCVcliA86jUzNOJy196jTJWBIKDDOMfdXd0q7ulEDAACADIDgbtXd1q7utXd1q7utKXAC4iZEnE8KdVbxBSEJHDMq7ulXd0oqkrKUAGAAACru60GNIA2dYXPF2mDgAGAZATgVd3Wrqw5iJgmM4Ku7pV3dN+45P1NxzeHV21/l36q7adqdIWD0kn4mkqAFljCopQwChx88I0wjWkWTKcsMcTD4UUypxc8Yo1YPg4UTRGTi41OvXhTsOJIMsD4nLzaSMiSInBdajvB8ioTwOJNKpsuce5UONDGSa4XpXqrHz4lXbT4s9921/gVccn6m45vB0fhM1JlNGyyOsBUSUCdOO67IBVcJQE8sfCs2bNmzZs2bKtUYEyqBQCIfHTds2bNnV2ETQiF1b9XbTtOnEriFTiMcVNStiJx/iuNQxZgeSnxTuwtCXsMDwJTC4lOTExGf8AbS8ysRa0Dld86MpY86Y0rc0SMacKgvnJfve2bNmzZ7srg1DPMxw3bNnX2MTiiIHVssurIQlgFjXhumyqUDGArHPDbZsm+MOAMDJhTM13bxVgIiwiTrx37NmzZs2dH4TNSZDR4Nxyfqbjm/Au2n8a/V20+Lfvu2v8Oqu2v8i+45P1NxzfgXbT+Nfq7afFv33bX+HVXbX+Rfccn6m45vGLtALsZcpli1d3SjgwBDRMkYwd01bwJCQYTlV3dau7rV3dau7rRn0BooYCRMKTzau7pV3dKAsBAQAOAOBu3d1q7utXVhzETBMZwVd3SnqSspUQJEQRKu7rV3daHDkCzjSXhi7ALIAkiJkTiVd3Sru6VgdFCpRhAJVfNau7rV3dau7rV3daWtKSkqiSqsq1d3Sm7RC7GXIZYHttWmAV1UyrGLV3dKmstTjsZTBjm++27ulXd0q6sOQmJYnKWru61d3Wru61d3Wj7wCzjLl4YuxgZBCSI5icSru6Vd3SjVvAEJRgMM9hsbmEhJDCcqu7rV3daQkgKyqmVeL+Bccn6m45vzb89+7a/Bq7a927afAnu2ndv3bT+NV+e7afwLjk/U3HN4eoI0TEBMcc9pnx52eqERL/AFs0RHmJkYnhltM/hElJmg002fCJKDJJprumZn267mkuGI457TO7fMomJiYJ02H2jz1E9m72jz1E9mw9URsUSExnz3btp2/CNKDNDTTYfyjSkZpa6bdURsEwExly2H367kkOOZ4ZbTO7fM4iYmJY13zMzM7P+RHmmeWw9ER8EyMTly3b+iI8xMjE8Mtpnb5eYTjyM9h/KNKRmlrpt1RGwTATGXLYfGnZ6YREP48a45P1NxzeHdtO7dtfi337tp8artp8We/dtfg1dte7fu2v8i+e/ccn6m45vDu2ndu2vxb7920+NV20+LPfu2vwau2vdv3bX+RfPfuOT9Tcc34FXbX4F89207t21/jVfnu2nxr9217t20+BPdtO7dtfh3HJ+puOb8Crtr8C+e7ad27a/wAar8920+Nfu2vdu2nwJ7tp3btr8O45P1NxzeHWiI8xMjE8Mt0zM7t8yiYmYknXaZ2+XkEYc3LYdvl5hOHMz2meoI0RMBMcMt27a9+u8e+gju2HoiPgmRicuW3jTs9cIiX9b5mZmdvl5hOHMz2meoI0RMBMcMtnDnZ6IREn8bpmZ9o89RPZumfePfQR3bLtr28adhppMw/jYd2+ZxExEwxptM/hElJmg002fCJKDJJprtM9QRoiYCY4ZbOHOw1UmYf62meiI8zEjE8c9nePfQR3eCZ3HJ+puObw6u2vdu2nxZ77tr8Gqu2v8u+qu2vdv3bX+Rffq45P1NxzeHSFgICVVwBxau7pV3dKu7pV3dKGtKShVEgAJVq7utXd1rE6qFQDAIwieY1d3SsDqoVIEgBKh5pV3dau7rV3dau7rSYwkHY1gcsTbdte7V3dKu7pV1YchMSTGUlXd1pqkrKWADKqgBV3dKu7pV3dKu7pThYEpgGasYG0+rzCJDDCEZlXd0odXxAkgSpGe148CE0TMScGru60mMJB2NYHLE8Cru6Vd3StCaY7GOJMcz32XbXtbtADsRzC5YPtV3daGuCB1VwBOLtDV8QJKEizlV3dau7rV3dau7rV3dau7rRn0gogaiTEoTzKu7pV3dKYOQQkImYnB2aEkx2McS45nvV3dau7rv3HJ+puOb8Crtr8W+e/dtfg1V207t21+BfdtfjX6u2v8K/dtfh3HJ+puOb8Crtr8W+e/dtfg1V207t21+BfdtfjX6u2v8K/dtfh3HJ+puObwbNnX2MziiIDVvuyiVHGAjHPHbZsuusMocSUwBm6bHXGOUGACJEzNN2zZs2Rag0IhRSAUyeOu2zZNkEoGEpWOWOzX2MziiITVu2bNmzo/GJwTMro2XbTu3bXtbdwyvElMAzdNlkv3Ho8BIlGZptPmSgJKVjTjssi1BoRCikApk8ddtmyaoIQIJBY5Y7NH4xOCZkdG/Zs3bXtOXBDECcVji37NmzZs2W/cOLxMmA5Ou09ZCAkgsacdlkWoNCIUUgFMnjrts2QtRIyiQFMSsSvh2bNmzccn6m45v01+7ad27a/Fvv3bT4N21+Lfnu2ndv3bT4dxyfqbjm37u61d3Wru61d3Wru61d3Wru61d3Wru61d3Wru61d3Wru61d3Wru61d3Wru60QEAEZEckeJsPvEDOM+DhiVd3Sru6Vd3Sru6Vd3Sru6Vd3Sru6Vd3Sru6Vd3ShrSkoVRIACVau7rV3daXDkiRnSTjibELAQEqrgDi1d3Sru6Vd3Sru6VgdVCpAkAJUPNKu7rV3daICACMiOSPE3b9207t3dau7rV3dau7rQ1wQOquAJxd2+wEglYAM1eBV3dau7rRtbmApDDCYZm7PdtO7fu2nZxpKMdjOJccz3q7utXd137jk/U3HN+RdtPjXbX4t+e7ad2/dtPjX7tr8C+e7ad2/dtPh1ccn6m45vyLtp8a7a/Fvz3bTu37tp8a/dtfgXz3bTu37tp8Orjk/U3HN4Pt1YTjzGh4mWWWWWWWWWWWS3hwEg4KMl23bTux9TSaAYEssjZl7dWU48hob4fScSkiQknLbllFmtQTSGSuWBMYb+WWWWWWrFrcmECYDg8NuWQfQcSsAQEvI2A42QZwEmUOS7csslLMBQSpYAxWdmXt1ZTjyGhv5ZZZRtXSaEaMMMzdvh9JxKSJCSctuWWJ3kEoSq4qua7s9207t+H6cTQBAS6DZledeZTxpy8HLK45P1NxzfkVPdtO7dtf5dX77tr8Grtr8W/PdtP4F+rjk/U3HN4dJ9DxCwBKwcjwMsssssssTtIZShRwQcx3Z7tp2A52Q5xFiAuQ7css1LMBSQhJEwSdmV515FHGjPdyyyy9mqCZjIaOxPoeIWAJWDkbcsos9qCIQQxyxIjHfyyyyyyz20OAkDATmm2F6cTQjCSQzN3LLLLNSzAUkISRMEnZledeRRxoz3csssvZqgmYyGjsu2vbP+wGWTmQmY7+WWWWWWS3gxEI4oMh23bTv3/fqwzcjqeDllccn6m45vDq7a/Fvnu2ndv3bT4NVdtfi37tr3btp8Gqu2vxbtp/Av3HJ+puObw6u2vxb57tp3b920+DVXbX4t+7a927afBqrtr8W7afwL9xyfqbjm8Ortr8W+e7ad2/dtO7V3dKu7pV1YchMSxOUtXd1pw8CFxHMScTamdJA2dYHPAq7utXd1q7utXd1q7utXd1q7utXd1q7utMFIJWVXNXi7D7wARnPh44tXd0q7ulEDAACADIDgbtXd0q7ulTWWpx2MpgxzffZdte0u0AuxlymWLV3dKu7pV3dKu7pRq3gCEowGGe7dtO/f0Jphs44kwyParu6Vd3TfuOT9Tcc3h1dtfi3z3bTu37tp/SVV+rtr8W/PdtP4F+rjk/U3HN4dXbX4t89207t+7ad+rNnV2ETQiF1bHZAKrhKAnljts2QpRKyQqcQxxbpbuGB4EphGZrsstu44XiAiQZuu02YKIsASdeOyycsCOZU4jDFtfmSpDKRjThss92VwahnmY4b9mzZs2bIWKSMogoTEpMDts2VYowghQAqUy+Gm7Zs2bMPVIARHIEzXHKN2zZs2XVFCjhIDHLDY0qCsEAcBxwbtmzZs6PwmakyGjwbjk/U3HN4dXbX4t89207t+7afBq7afFv3z3bTu3bX4NXbT+bfvuOT9Tcc3h1dtfi3z3bTu37tp8Grtp8W/fPdtO7dtfg1dtP5t++45P1NxzeHTAyCEkRzE4lXd0q7ulXd0q7ulXd0q7ulXd0o2NzAQllgMM92e7adg48gCM6w8cWru6Vd3SiBgABABkBwNnGmow2c4kwyParu6Vd3TdIKACMImSPBq7utXd1ocOQLONJeGLsAsgCSImROJV3dKu7pWB0UKlGEAlV81q7utYnRQqEJRGEHzCru6Vd3SiBgABABkBwNj94hZxlycMCru61d3WmCkErKrmrxdk1lKcdjKYcc33q7utXd126E0w2ccSYZHtV3dKODAENEyRjB3b4FkASREyJxKu7pV3dKwOihUowgEqvmtXd1q7utXd1q7utXd1q7utF2gA2Y5hc8Xd1LJjsYZhxzferu61d3XfuOT9Tcc35FT3bT+HdtP5c99+r920/kT36uOT9Tcc3g9o89RPZ4hmZmZmZn8I0oMkNNdt207uqI2CZCYy5bD7x76CO7d7x76CO7fMzMz0RHwTAxOXLb8I0oM0NNNh/KNKVmlrp4F/REfFEDE589h9o89RPZu9o89RPZumZn367mkOGI4Z7D0RHwTAxOXLwp+POw1UmYf62meiI8zEDE8c9nePfQR3eCZ3HJ+puOb8i7ad27a/y6q/dtP5dX57tp8O45P1NxzfkXbTu3bX+XVX7tp/Lq/PdtPh3HJ+puOb/AJ645P1Nxzf89ccn6m45v+euOT9Tcc3/AD1xyfqbjm/5645P1Nxzf89ccn6m45v+euOT9Tcc3/PXHJ+puOb9JjROP7q45P1NxzfiALAsGTIOnOv63+USRLkMF9Ir+t/lf1v8pQHlO3AYiYwnKoUBmBAJQOevpuozNFBFIS51CqBk2uQyIIZY/FKBxTDWFmYdKwZqECPRuMxz0oFJBE5qKpisOUM5GtOYfmuee9SmTigCCGITk1zz3qc8sEERExDSuee9COWyEEvBI+2yG9ISsFHztMIybE7QQHyz5VBKOJkOScKi4nvXPPeuee9DcT3pMgAUrABmtWh1q0OtEiMiSPCklmCJJwM2PY9aWZCUIJ4nplshccykH3SBIyJP4lxyfqbjm/DN4UwcsCaxrNiFj7r+896Lo7RwmceLhX95700QAK4vencmDlGQ8fOjBcq4SmY58qRGKRQhOGfGiKQYTgiYa0E0Y6JMCxM8qQOK4HCBnPnQ/Y96A7ApMROK9ajjFGHRRZcHUpRF4xMUsTFgxqWUEU4UMQRCE4i8fKju8WMSlwY86hoMhTmeHnSGSIJCc7P1r4f6oDEn5CZ1vRXytEopsAc6/i6cwYMBLD1V/F0IgZECWKP4ul4BLKQ4q9axrIQGq5FYwKYzA4Q+jSKTEiaBAOWbPKn/AMzBiocYeHk0gabNgJUc3yKzmUmAKOUnDSkJEIRJKhXBRNmXgvHkpCivEEEtKkhEQGNMcWJBBJTeJrCAyTrWea3jGCQ0XJhZSYg+mmGELCDMdKXGPF1yAo8kgOGHA81xpKohqcBngeeZ67BKwTBjEeCU++IH5Y+hMT8S45P1NxzfhrsCQuWJFIs8JxEpqaCetHZRBTOxyyr5P21LILJBZ1xK4UkAIkZmDy9qcwcWjIOJyouApVrPipCQQ4AnF4/yrRpq066fIChmdCslxYkk0M8V8UIklOESVJDJ8Uz6UNBaUzUqMuhUARHuUCSBlgUVAEzxJoKCIAwQBlzh96WToRAMCxg4Y0xLAHNGOC85mlpKI4Dij0qAmJjiMsH2UsJUwq1MdkDZSvQ51iXViEDSXFxf5RNAWJlgj2on5PgEqF+aSnGEw4Tza8tnHfj1Lue9E7Az6FDGqQMCY4vCpVgCSNlwjj5qGkWkyiGHHNXjWH0SCceTxp2AQIEAM0ZjnicqZwgAmBOjk7M6u86bAKuQTS27hBxwLQSEHMxKgj1rIxIEOFoVEoMVJOrycvjCoVwwY6Zv1ye9DxSBJDknHzPnZhvZHzQnwx6Vk2GfUn8O45P1NxzfiJlk6DYThiGtDJAAw4PSuHRAZgyYRrU5uDAZh6c6YYJwcWJeJhx2OCYmRII6lH3gYrxXitBXIUoOEsKgb2IZxhKcDEqMiSwyIQdKPCjIDDmPJocYADSKQLIyaEgADgVDZtGMfSoMjOURjRGAMYWMfWoKh5ijDAmMoqVWM9d7Z+lOeYXqo+Ap/naBlgwxXH1ppEY7iwTBwObPnyqXymiOGpjy71huhJmCzD54YGQUNHnR9nPDjUBIxIMX5HWpEYC1I+7A+VGQAyRGKFffYIXVJQZYBDma5FFz55cyWYc+ewQnIh9A/eyGFhBkYZNB2Skphg0icLKDBlXj50XKUoBBizWFYDI4UOPFNmdXedNjIckishKrJmBwXRqaxPmAoYmOBRKG1g52GTQgChF0EwrACKmPwgUhNYwMP92HxhXuJSrf5KdPw7jk/U3HN+Iv8hFKODCl+9Kv3pV+9KewGEwQmU4OxeoQnkZ1l7HAvHIjypJAcIQQA4aNHy8YD1Hon5rHlBgkCTJNa5PAECHicnH/AEoYgiCJk0EsTPLClWpjhpQWMomWpKGKMIjhhxqAEaLHSpCERFHDRLzCkPUM6AIQEPlSgcimZZzzUGdlgjDLRGlQdimZsxxx0mIyngLGDi+OFPmWAM0yQ5OE1J5VmMKauVHmAACAoeEdEQSjgvD2oFgYAIFRhxxcJxyohpnYUhidOW3F+Ux8/reyRZI1eAebWrEOZc/QMPXlszq7zptCVBWJOLpVLPgQIzHN6DRRMacpMubwrGVjMcB5lF0RZRlz8zkf2hzAAHLYE1j7APUU7eZJ5pL8v4dxyfqbjm/EEMKNhk6S5VzVLmqXNUuFOOKylgw2Th5wCQDgvCe9OWj3p4ehBU25ICUQBj0+a5y3lT7biEEZBxmYp9DEggwYmHDhU6JKHBHL0oGAJzJoUMhhlhTYaoBPFhXD5+pQiIYzckUmIC0woKAByI2WvNUfj+UEvKaWEREQARlAYnvQQ6gReYSYqcl54jyEzI9c6hMeTl5nDbYLEJ4QmAipWGZfJ1KdWcJOSJk8q4yAyAc3HlTVCZZZ6JeOvrRJyqvmz9oH3qdeE9c75+u6CKQ2RMeK5Bl/tA5IiDCy4uXrs8qCSZaXKKbDBTwTB5bPEuOqhJhj51z6AXVi+xUBAdR8jjT8iEIklYoQgFAc/O2NYQYi5+Y8jh/u15gwV4QOL1cPb8S45P1NxzfiPS1ESlasLpVhdKsLpS3EBADKmUc9zDQ0S4vfYQ2WZOY8t2CZiuF8UxGPlUG4V3ggSWAnDnQOUiBgD3qwM9FRIXkBfN41kREDEDycygRYCquKuq7AxAEoTgJxOdMooQueBFOBIQiSPmUr/vSgmGzktABhQKJIkJGFJmrigPLBy4e3Pd4O9ANJ08MVy8XTkaHHYqTGCCDlA41iMQHpHI/EuOT9Tcc3/PXHJ+puOb/nrjk/U3HN/wA9ccn6m45v+euOT9SjS8AIxV151dHWro61dHWro61dHWro61dHWro61dHWro61dHWro61dHWro61dHWro61dHWro61dHWro61dHWro61dHWro61dHWro61dHWro61dHWro61dHWro61dHWro61dHWro61dHWro61dHWro61dHWro61dHWro61dHWro61dHWro61dHWro61dHWro61dHWro61dHWro61dHWro61dHWro61dHWro61dHWro61dHWro61dHWro61dHWro61dHWro61dHWro61dHWro61dHWro61dHWro61dHWro61dHWro61dHWro61dHWro61dHWro61dHWro61dHWgaV4gjBHXl/wDWNGLnF6kZ74kjHJ1/Z1/Z1/Z1/Z0RIjmg3ZlXgYMzeHOBCHBxy8+G0lIYJlTPyOVM00Y3HEQUSXCnlgkMEA6eBmUBieZQfEUuLr42ZQGJ8qDYilxdf+OGkQzuK1jOZ7FIZEGKqCjQKyBlZENQ2EJiH9UuLwRg8Sc+U7AYWCX80opkDOaGpDOLxSCJBL+KPkS0nF7VLQwGcWyMxkmQrlfbXK+2hikkyTgY1yvtrlfbQIAUnOdicImXyM6GckEuhfLbYedfOfewzjRmAGDzK56/lXPX8qEvDDHgONc9fypCBzleU8WoJlHKNDnRakMCSZNGkiTeKDDjkRBzx4Ukhh4B9tZxgJg6aNIMYQ4DjzaM5OIUWID7Gv4RX8IqFKFIw8FJDDwD7azjATB00aQYwhwHHm0ZycQosQHDmNfwiv4RUKUKRhSSCEzMGTGHnFf3NCiMgQDinL/hwYjBkeHrSMLEMQ8UU+Y0wDOPrs+S/ar7m7nwX01mebXw1fG/btzrCYk+yrvaq72qjJMmwcTGrvaq72qGSgRkH1swawcORw98/aidxPFwcEY7cYMjMxMc6KYpEzjQMUmIZngTj50dUZxDxmKDyUAdb1q73Ku9yrvcrBZZmDx5NIZISwTSvifZXxv27JXOOWHgGnKucBLH0rgwF4elROWwsXF7+JgpnHLDwDTlXOElj6VwYC8PSonLYWLi997LTy/4g0KpAFBCAJiZfjZ8l+1X3N2OiIZhPE71/WUUhIOBFLV42qmEkEJULJhnE8XvX9ZSssy+xjYab3VynuodhVGTLKigk4DWoLssWJwv62YtmCji8vKiB8Q+bDD0r5J9KBVgGJXpSoIRRKsPOvnPvaDUAGHh6Vy13KoCzJsHMxrlruVYjspyGz4n2V8b9u2XmTEpGlHGsICYKh6DHhBCYRXNv8q59/lReSECXHYac4Gwq6OlXR0q6OlXR0q6OlFHFOJIydDlsl5kxKRpRxrCAmCoegxoQQmEVzb/ACrm3+VF5IQJz22nl/w5sSRiTx5O1PiQYBIM4yKKTHcAg2fJftV9zdj0sojBFf0lZZIYpyP9pZ66gVURgtKEDIuDOLX9JSVKyrOwYEsMkxz9KwC4uGQ9mgVg8qwg1LyNPNriwOBocDYRiCksnU+Svkn0pTF4vo3rQHkYXzP8+qsPOvnPvYEhEPua5r2965r296dkTMjQiua9vejE2WEOGz4n2V8b9uwIQnS0cnHLjUilSlHBSzBJQYnzpGUWUjDX9JX9JSilVYrLsDsvmrC61YXWrC61YXWrC60XBUzC0oIUnS0cnHLjUilSlHBSzBJQYnzpGUWUjDX9JX9JSilVYrLttPL/AIj7+18l+1X3N2gqDPKsaODXv2dNnw1fG/bus1jAhh5n6qWDiIpMHI151BjAcDq7lxzK+SfTYvLLmzVh518597vwfpWF1hIZTymoXm8V0eez4n2V8b9u37f2V8r90ZnIkYutXe5V3uUacBiTPhfb+yvlfujM5EjF1q73Ku9yjXgMSZ22nl/w7pMDDMGkSkkMMsf9bAkUrBLBUXqRwk5JSCOAfew4EqwFDykCep50kGDI589nw1fG/btDznjMvagco4weyo92MBiD040tBkcwhPTiUglgtXuH+V2V6ysbTFELOHpsuOZXyT6bECBAcVPbCpGZDg4YUxU4p2JtEIYVz/xXP/FIyBmSTgRTYhIyMlGgkHAAPOmRJ0NK+J9lfG/bsDqAQTWpaTLFEUBnEa+r/lLiDAnB0qz/AJqV/aoOESsTPgg1AIJrUtJliiKAziNfV/ylxBgTg6VK/tUr+1QcIlYmdtp5f8O0kYIZJKOjAGYCDYSzBB6UHB5DvpsiqyrsMKamj3fPXbHlKknKicUmQRtiADjlPtU4Zc2doqkwaz1TTuVgyngEGzCHMsRJUJOBMGGUbJhQwmUlLMQIQA8IEFAcjHEilbj5o8RUcQGD50KSEQelCxA8jSZFXFVl3BAAEGDwRSQiD0oWIHkaTIq4qsu4IAAgwUDikpgxxE61dnWg8WGYjMdf/D2uECRjB3TgSuAFS2EgiMcakQUTCbjIAqsAEtTJKJh3QFEhmxgeez5yEbeKscTJrFFAlWAqcJHBIdqPv05M9kwDMZMPHaAZTJjsISQSxw2ogM1isSBGCT/lFeRjK4THDy1a0AAgH1S5XMZA+qKBqWBc3nsBDuCQZUZz7BDB80FmTaMcIaJC0cQYzcKkFXMYzlMNKbJZ6tYviGBOtEmSBKH0cephLo+dC2Pl7qFmBnmvHnS2BFzmuT7xRwNB65/M0DOw4twxMuJQuGXJcBwx1rBEnDFHNvTYqQgEIxMJ2GfRh8z/AJNPbNM+XD4pDu6PlKgKiDBM5uH9piWMZco71dclJJjgZTR8+lJoInBIaUhTEgjFw41ikMHAIx9aDzMwQiD1peacIUNTTIA58Y5eB8r7KCO4sSa9PmnwHJywF60dxCTI4cJ5ZlGQAgTJpOGsRFDgIkhzaLh9QSwxw4aU4VTUPspZGUwoMs2TNTjhUYSTwDq0lheLhCc6gswLOLKksxVy9A81GAKk0iMgn5oT2fhGKBnKYChJEtA6tI4oJiIfTWgZymAoSRLQOrSOKCYiH013I2ZZrkUEj2Q61xIATEcJ/dpJACQyXm0x4ZAInQydKIurIy50mouBAZeOLFZWKY4E4cuez4PrRxTPmM8u9S6d0mONeQ7PObip3grheVCBmInpUqNACAiJ5c6wjLgB9opPKEDGfkj5pGTMCBh5MUKSUIMIojJJwYmXrUSERmk9/SguNIQTCPBx7UsROxvWikMCVgVEkrxjA4r0pazEHSnHhT+CDhJn5lJljLI4utPIiRh9E+yoP+QjCdKmbUBng5+VPpUAr4ipgjFx4GGVILGBekvlXEcE0uHHSgQhmTPhHWgbYAdK95fYO+yQgw9aHdRSSUqEcsmkUSESIMHlTxCKEIxcnDHT0p5AKYAGWbhpFfz+ygwRAgiOOOFGPMg4WWlCIKMIPF1OVI3lMrEV8t9lfKfdOjkerSWsmDQGKxtlEOIcE+avpE0iGQxEmRT0BHRIaBQwmJJzIpoClm8zLSrbqpHFngeeHSaYcsFyRwinoxgPKeVLBxHW4ek0w5YLkjhFNRjAeU8q9PDynDa10hOBLzfTAqc11VPvUz+8RUJGLyTrU/IEETxtrMUoz9KhUVXB5B02FJ2TJJv1pKQLinzqchAhPh60fTQgNympMjKGXHJ8jjRs5ZxZGFYqEySGFMH5QBQhLXKCiJUGSw9cFF1DCAJyzzaBBgwGGelBCoBhycaCCKsB/FLkZiyiPIjGkmE3K0VhDEkNKNwVTgEuSjkiI0JnKxb0nDDLr71OeT9CMH5KML+WHSiBEScSetE7CYsGHFrRYEEoBERz41Mk3MmHpxoQndcjmxEVK3FSAIJ8qbuzBhfOnbWBC0wmMKmKiQgnidqdIqljxetFWyqTIjDR8qOCAB6OFSSYPOnD13Q5ISiVw71gOohA4oOnrTbjKRkaetSCTNIEOsEMf5QoNeAzBLwpZqUKiZpdqCLiQRJEvprRgQLQ0xyr5b7K+U+6FZgn3pCUFLhDzzpXnnhlhJw9qjnAGJjlS3CDzPWhqimc45nalSzFXpXyH2VbdVDUkYtFQJuAWb3yPapdRHAPfl5VORbK6Q1Am4BZvfI9qlVEcA9+XlUHQTYnDDP6rm+72qKhVWDJi0kQoYuRUVMbDAic6EOALHCTP7/eChVWACiqWtHz7FXrRsTAicEhri7FMSj2q28qUnlzGJP+S0/lCKUjipCTKWxyLMAk61zXvoMABJgTBwq257DDCWP3Q8YAC4ZnjU8BjAOmnnTM5nH6Ip0EkHl56nOoCSQdYmjGTgOQ8ikDStEUOZgEpxA5RjjrHvSSznOOyDCrAAlqPhSFiZiP7UOCgXBOGvvFGY1yYZf4UcKjCWT601khnq0zkOWY4zz50fzoky+zYpWBIs83zdKmmrfmsPkQjz8JndRtEygxUFkJCVwyaGiDKGfN7UpMwyExbU0OAKiJ4/LjsNEQIlMY1cNXZ8t9lfKfdCzlZExSqIHLPQs6I5x/uFY5RKJ5/wC03Cb5qE6OEHBjNWm0irAJccaThJGKOJTlFJc8ZtLTMU9M/ug5YkiJGpzARiRM8qIBMkfBoOWJIiRqcwEYkTPKp0hjBGHKMEqwutSYGqlo0ZGIM8czzEowQxxVAU0WaL1iPp/djGhyiPaeFPBwxMyevF6UwaVuCgGBGKDgRxoSkBGIJ+a40YhGdKZMcSA9ZWkR4OlA8uFcFJlwFOOEGAD7ZPxWaORDs5fWxMPCBwRE8udclZyoh/WeODCH3RBADwnM4emwm6ITLy5c6mRryT7ZNMYg69SgOL2dq9KRUMMsCZmeexlgTjwlNcVAA4UmIvY+6M60crvzpYMZLGHs47JdUGZyGcXnU4QxOYDynh5U6Ac53xfWj3kgz/vKpVUBcdCCfmouKSwVhkKSSc571mVKWowhwmpcJsoLxjA89isy4TCI03WL84ZA8saEfPmI5EYxXCsYfo1fSvRtkPodWhIgYGQ9+H1SMZSYiGmJWDuckgalSEACRlmvWgpQgLpzpNCrhKeeBxyqYdVaMPAZMKAYl1xKwNhmgQeutS6hiMpPKjGKvmlGww0MD11o6BSc8sSK/jvel+296KIiMiZ0XHOIhpsEbDBj70UREZEouOcRDTYI2GDH33EmRLFRJUYU1xacKqZV/wDCt//Z";
		$params['real_name']='郭丽琴';
		$params['china_id']='142201199205154021';
		$params['alipay_id'] = '7657567567';
		$params['mobile'] = '17681888141';
        $params['yzm'] = '6666';
		$this->session->set_tempdata('yzm',$params['yzm'],60);
		
		
		$ID_data['serviceCode']="X01";
		$ID_data['name']=trim($params['real_name']);
		$ID_data['idNumber']=trim($params['china_id']);
		$IDCard_res=$this->validationIDCard($ID_data);//通过身份证号和姓名验证唯一  
		$pic_qrcode=$this->base64_upload($params['alipay_qrcode']);//支付宝二维码上传
		$qrcode_text=$this->getAlipay($pic_qrcode);
		$mem['alipay_id']=$params['alipay_id'];//支付宝号
		//$mem['id_photo_positive']=$pic_positive['picPath'];//正面上传路径
		//$mem['id_photo_reverse']=$pic_reverse['picPath'];//反面上传路径
		//$mem['id_photo_unity']=$pic_unity['picPath'];//人像上传路径
		$mem['alipay_qrcode']=$pic_qrcode;//支付宝二维码
		$mem['qrcode_text']=$qrcode_text;
		$mem['real_name']=$params['real_name'];//真实姓名
		$mem['china_id']=$params['china_id'];	//身份证号
		$mobile = $this->member_model->getwhereRow(['id' => $id],'mobile')['mobile'];
		$is_IDCard = $this->member_model->getwhereRow(['china_id' => $mem['china_id']],'id');
		if(!empty($is_IDCard)){
			show300('该身份证号已经认证，请重新输入');
		}
		if($params['mobile']!=$mobile){
			show300('认证手机号与注册手机号不一致,前往更改手机号再认证');
		}
		if ($params['yzm'] == $this->session->tempdata('yzm')){
			$alipay_id_res= $this->member_model->getwhereRow(['alipay_id' => $mem['alipay_id']],'id');//查询支付宝号是否重复
					if(!empty($alipay_id_res)){
						show300('支付宝号重复,请重新填入');
					}
			$this->member_model->start();
			$referee_res= $this->member_model->updateWhere(['id' => $id],$mem);//认证资料更新
			if($referee_res){
					$this->member_model->commit();
					show200('认证信息提交成功'); 
			}else{
				$this->member_model->rollback();
				show300('认证信息提交失败');
				}	
		}else{
				show300('验证码错误');				
			}    
    }
	/**
     * @title 判断认证的阶段
     * @desc  (判断用户认证走到了哪一步 )
     * @output {"name":"code","type":"int","desc":"300:信息说明"}
     * @output {"name":"msg","type":"string","desc":"信息说明"}
     * @output {"name":"data.tag","type":"int","desc":"1认证页面2缴费页面3上传截图4审核页面"}
     */
	public function getValid(){
		//$id=$this->getId();
		$id=1;
		$res_pay=$this->member_model->getwhererow(['id'=>$id],'china_id,is_pay');
		if(empty($res_pay['china_id'])){
			$data['tag']=1;
			show200($data,'认证页面  认证接口');
		}else if($res_pay['is_pay']==0){
			$data['tag']=2;
			show200($data,'掉缴费页面  确认完成缴费接口');
		}else{
			$res_jt=$this->member_pay_record_model->getwhererow(['user_id'=>$id],'id');
			if(empty($res_jt)){
				$data['tag']=3;
				show200($data,'掉上传截图页面 上传截图接口 ');
			}else{
				$data['tag']=4;
				show200($data,'掉等待页');
			}
		}
	}
	
	/**
     * @title 判断认证的阶段
     * @desc  (判断用户认证走到了哪一步 )
     * @output {"name":"code","type":"int","desc":"999:您还未认证,请前去认证"}
     * @output {"name":"msg","type":"string","desc":"信息说明"}
     */
	public function getAudit(){
		$id=$this->getId();
		$res_audit=$this->member_audit_model->getwhererow(['id'=>$id],'status,id');
		if($res_audit['status']==2){
			sho300('审核中');
		}else if($res_audit['status']==1){
			sho300('审核不通过');
		}else{
			sho300('审核通过');
		}
	}
	
    //升级会员等级判断
    public function updateLevel($id=19)
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
