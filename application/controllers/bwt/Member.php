<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Member extends CI_Controller
{
    private static $data = array();

    public function __construct()
    {
        parent::__construct();
        $this->load->library(array('sms/api_demo/SmsDemo', 'weixin/wechatCallbackapiTest'));
        $this->load->model(array('member_model', 'machine_model', 'member_resouce_model'));
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

         $mobile = '17681876666';
         $yzm = 666;
         $this->session->set_tempdata('yzm', $yzm, 60);
         $pwd = 123456;
         $pwd_again = 123456;
         $pwd_second = 123456;
         $pwd_second_again = 123456;
         $referee_mobile = '18335018141';
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
            //$mem['msg_validcode']=$yzm;
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
            if ($res) {
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
        $id = $this->session->tempdata('id');
        $id = 1;
        if (empty($id)) {
            show300('会员id不能为空');
        }
		//$data=$this->member_model->getMyInfo($id);
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
        $mobile = '17681888141';
        if (empty($mobile)) {
            show300('请输入手机号码');//手机号为空
        }
        if (!preg_match("/^1[0-9]{10}$/i", $mobile)) {
            show300('手机号格式不对');//手机格式不正确
        }
        $templateId = 'SMS_141945019';   //短信模板ID
        $smsSign = "众合致胜";           // 签名
        $yzm = rand(1000, 9999);           //验证码
        //$yzm = '8888测999';           //验证码
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
            show300('验证码错误');
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
        $id = $this->session->tempdata('id');
		//$id=1;
        if (empty($id)) {
            show300('会员id不能为空');
        }
        $data = $this->member_model->getwhereRow(['id' => $id], '*');
        if (!empty($data)) {
            $data['referee_mobile'] = $this->member_model->getwhereRow(['id' => $data['referee_id']], 'mobile')['mobile'];
			$data['member_lvl']=$this->member_model->getLevel($data['member_lvl']);
        }
        show200($data);
    }
/**
     * @title 认证接口
     * @desc  (认证接口)
     * @input {"name":"id","require":"true","type":"int","desc":"用户id"}
     * @input {"name":"id_photo_positive","require":"true","type":"string","desc":"身份证正面图片"}
     * @input {"name":"id_photo_reverse","require":"true","type":"string","desc":"身份证反面图片"}
     * @input {"name":"id_photo_unity","require":"true","type":"string","desc":"身份证人像图片"}
     * @input {"name":"china_id","require":"true","type":"string","desc":"身份证号"}
     * @input {"name":"alipay_id","require":"true","type":"string","desc":"支付宝号"}
     * @input {"name":"alipay_qrcode","require":"true","type":"string","desc":"支付宝二维码"}
     * @input {"name":"real_name","require":"true","type":"string","desc":"真实名字"}
     * @input {"name":"mobile","require":"true","type":"string","desc":"手机号"}
     * @input {"name":"yzm","require":"true","type":"int","desc":"验证码"}
     * @output {"name":"code","type":"int","desc":"200:成功,300各种提示信息"}
     * @output {"name":"msg","type":"string","desc":"信息说明"}
     */


    public function certification()
    {
        $requires = array("id"=>"缺少会员id","id_photo_positive"=>"缺少身份证正面照","id_photo_reverse"=>"缺少身份证反面照",
                    "id_photo_unity"=>"缺少人像图片","china_id"=>"缺少身份证号","alipay_id"=>"缺少支付宝号","alipay_qrcode"=>"缺少支付宝收款码",
                    "id_photo_unity"=>"缺少名称",);
        $params = array();
        foreach($requires as $k => $v)
        {
            if(empty($this->input->post($k))){
                show300($v);
            }
            $params[$k] = trim($this -> input -> post($k));
        }      		
		//模拟数据
        $params['id'] = 13;
        $params['alipay_id'] = '17681878141';
        $params['id_photo_positive'] = 'dsfagasdagvsd';
        $params['id_photo_reverse'] = 'dsfagasdagvsd';
        $params['id_photo_unity'] = 'dsfagasdagvsd';
        $params['alipay_qrcode'] = 'dsfagasdagvsd';
        $params['china_id'] = '142201199205154021';
        $params['real_name'] = '郭丽琴11';
        $params['mobile'] = '17681878141';
        $params['yzm'] = '6666';
		$this->session->set_tempdata('yzm',$params['yzm'],60);
		//模拟数据
		
		
		$id = $params['id'];
		$mobile = $this->member_model->getwhereRow(['id' => $id],'mobile')['mobile'];
		if($params['mobile']!=$mobile){
			show300('认证手机号与注册手机号不一致,前往更改手机号再认证');
		}
		if ($params['yzm'] == $this->session->tempdata('yzm')){
			unset($params['id'],$params['mobile'],$params['yzm']);
			//在此验证支付宝号是否有效；拿到支付宝相关信息更改，先模拟
					$alipay_id_res=1;
					if(!$alipay_id_res){
						show300('支付宝号无效,请重新填写');
					}
			
			$this->member_model->start();
			$params['is_valid'] = 1;
			$referee_res= $this->member_model->updateWhere(['id' => $id], $params);//认证更新
			if($referee_res){
				$level_res=$this->updateLevel($id);//升级
				$resouce['id']=$id;
				$resouce_res=$this->member_resouce_model->insert($resouce);//会员资产增加
				if($level_res&&$resouce_res){
					$this->member_model->commit();
					show200('认证成功'); 
				}else{
					$this->member_model->rollback();
					show300('认证失败'); 
					}
			}else{
				$this->member_model->rollback();
				show300('认证失败');
				}	
		}else{
				show300('验证码错误');				
			}    
    }
    //升级会员等级判断
    public function updateLevel($id)
    {
       // print_r($id);exit;
        if (!$id) {
            show300('会员id不能为空');
        }
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

        return true;
    }
	
		/**
     * @title 图片上传接口
     * @desc  (图片上传接口)
     * @input {"name":"yzm","require":"true","type":"int","desc":"验证码"}
     * @output {"name":"code","type":"int","desc":"200:成功,300各种提示信息"}
     * @output {"name":"data.picPath","require":"true","type":"string","desc":"图片路径"}
     */
	
	public function do_upload(){
		$this->upload();
	}
	 
}
