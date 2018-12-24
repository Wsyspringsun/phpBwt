
<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 登录管理
 * @author lxn
 */
class Login {
	private static $data = array();
	public function __construct()
	{
		parent::__construct();
		$this->load->model('User_model');
		$this->load->model('Group_model');
		$this->load->model('Group_user_model');
	}

    /**
     *@title 微信小程序登录接口
     *@desc 微信小程序登录接口
     *@input {"name":"code","require":"true","type":"int","desc":"这个code是用wx.login接口获取到的code"}
     *
     *@output {"name":"code","type":"int","desc":"200:成功,400失败,3:未登录,300各种提示信息"}
     *@output {"name":"msg","type":"string","desc":"信息说明"}
     *
     *@output {"name":"data.userId","type":"array","desc":"登录用户的ID,小程序端自己维护用户登录态,若有其他要求,再商量"}
     * */
    public function weixin_login()
    {
        $code=$this->input->post('code');
        if (!$code){
            show300('参数错误');
        }
        
        $appid = '';
        $appsecret = '';
        //$url = urlencode('http://www.tanqinba.cn/login/oauth');
        $wx_url='https://api.weixin.qq.com/sns/jscode2session?appid='.$appid.'&secret='.$appsecret.'&js_code='.$code.'&grant_type=authorization_code';
        $res=$this->curl_request($wx_url);
        
        $data=json_decode($res);
        $openid=@$data->openid;
        $userId=$this->getId($openid);
        if ($userId){            
            $resopne=['userId'=>$userId];
            show200($resopne);
        }else{
            show400('获取用户数据失败');
        }
    }
    //如果未注册则自动注册并返回用户ID,如果已注册则直接返回用户ID
    private function getId($openid){
        $user=$this->User_model->is_reg($openid);
        if($user&&$user['userId']){ //已注册
            return $user['userId'];
        }else{
            $userId=$this->User_model->insertData(['wxId'=>$openid]);
            return $userId;
        }
    }
    
    //curl
    private function curl_request($url,$timeout=30,$header=array()){
        if (!function_exists('curl_init')) {
            throw new Exception('server not install curl');
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        if (!empty($header)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }
        $data = curl_exec($ch);
        @curl_close($ch);
        return $data;exit();
        
    }
    
    
	
	


}
/* End of file Login.php */