<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
header('Access-Control-Allow-Origin:*');
class User {
    private static $data = array();
    public function __construct()
    {
        parent::__construct();
        $this->load->library(array('sms/api_demo/SmsDemo','weixin/wechatCallbackapiTest'));
        $this->load->model(array('user_model','sms_info_model','sms_record_model','wx_user_model',
            'shop_model','coupon_model','coupon_claim_real_model','wx_user_staff_relation_model',
            'notice_model'));
    }
    /**
     * @title 小程序识别二维码登录
     * @desc  小程序识别二维码登录
     * @input {"name":"code","require":"true","type":"string","desc":"coed"}
     * @output {"name":"code","type":"int","desc":"200:成功,300各种提示信息"}
     * @output {"name":"msg","type":"string","desc":"信息说明"}
     * @output {"name":"data.user_id","type":"int","desc":"用户id"}
     * */
    public function weixin_login()
    {
       $code=$this->input->post('code');
        if (!$code){
            show300('参数错误');
        }
        $wx_url='https://api.weixin.qq.com/sns/jscode2session?appid='.XCXAPPID.'&secret='.XCXMY.'&js_code='.$code.'&grant_type=authorization_code';
        $res=$this->curl_request($wx_url);
        $data=json_decode($res);
        $unionid=@$data->unionid;
        $openid=@$data->openid;
        if(!$unionid){
            show300('小程序appid可能不一致,请检查后重试');
        }
        //$unionid='unionid';
        //$openid='openid';
        $user_id=$this->user_model->getWhereRow(['unionid'=>$unionid],'user_id');
        if (!empty($user_id)&&!empty($user_id['user_id'])){
            show200($user_id,'是会员');
        }else{
            $userId['user_id']=$this->user_model->insert(['unionid'=>$unionid,'user_type'=>'00','openid'=>$openid]);     //3为帮客普通用户标记
            show300($userId,'不是会员（获取用户信息失败）');//显示用户的昵称和头像（可以获取）
        }
    }
    /**
     * @title 用户个人信息
     * @desc  小程序"我的"
     * @input {"name":"user_id","require":"true","type":"int","desc":"用户id"}
     * @output {"name":"code","type":"int","desc":"200:成功,300各种提示信息"}
     * @output {"name":"msg","type":"string","desc":"信息说明"}
     *
     * @output {"name":"data.user_id","type":"int","desc":"用户id"}
     * @output {"name":"data.nick_name","type":"string","desc":"用户昵称"}
     * @output {"name":"data.mobi","type":"string","desc":"用户手机号"}
     * @output {"name":"data.photos","type":"string","desc":"用户头像"}
     * @output {"name":"data.noticeNum","type":"int","desc":"用户提醒计数"}
     * */
    public function getUserinfo(){
        $user_id=1;
        //$user_id=$this->input->post('user_id');
        if(!$user_id){
            show300('用户id不能为空');
        }
        $userData=$this->user_model->getWhereRow(['user_id'=>$user_id],'user_id,nick_name,mobi,photos');
        $userData['noticeNum']=$this->notice_model->getUserNotice($user_id);
        if (empty($userData)){
            show300('获取用户信息失败');
        }else{
            show200($userData);
        }
    }

    /**
     * @title 用户优惠券
     * @desc  我的__优惠券
     * @input {"name":"user_id","require":"true","type":"int","desc":"用户id"}
     * @input {"name":"page","require":"true","type":"int","desc":"页数"}
     * @output {"name":"code","type":"int","desc":"200:成功,300各种提示信息(用户id不能为空;没有红包可使用)"}
     * @output {"name":"msg","type":"string","desc":"信息说明"}
     *
     * @output {"name":"data.coupon_id","type":"int","desc":"红包id"}
     * @output {"name":"data.coupon_name","type":"string","desc":"红包名称"}
     * @output {"name":"data.status","type":"int","desc":"使用状态"}
     * @output {"name":"data.shop_name","type":"string","desc":"店铺名称"}
     * @output {"name":"data.start_date","type":"string","desc":"有效开始日期"}
     * @output {"name":"data.valid_days","type":"string","desc":"有效天数（开始时期+此字段=结束时间）"}
     * */
    public function getUserCoupon(){
        $user_id=1;
        //$user_id=$this->input->post('user_id');
        //$page=$this->input->post('page');
        //$page=empty($page)?1:$page;
        //$offset=($page-1)*LIMIT;
        if(!$user_id){
            show300('用户id不能为空');
        }
       $coupons= $this->coupon_claim_real_model->getUserCoupon($user_id);
        if (!empty($coupons)){
            show200($coupons);
        }else{
            show300('没有红包可使用');
        }
    }

    /**
     * @title 用户关注得店铺(首页店铺)
     * @desc  我的__关注的店铺
     * @input {"name":"user_id","require":"true","type":"int","desc":"用户id"}
     * @output {"name":"code","type":"int","desc":"200:成功,300各种提示信息（用户id不能为空;没有关注任何店铺）"}
     * @output {"name":"msg","type":"string","desc":"信息说明"}
     *
     * @output {"name":"data.shop_id","type":"int","desc":"店铺id"}
     * @output {"name":"data.shop_name","type":"string","desc":"店铺名称"}
     * @output {"name":"data.province","type":"string","desc":"省"}
     * @output {"name":"data.city","type":"string","desc":"市"}
     * @output {"name":"data.addr","type":"string","desc":"详细地址"}
     * @output {"name":"data.pictures","type":"string","desc":"店铺图片"}
     * @output {"name":"data.lat_lng","type":"string","desc":"店铺经纬度，先经度，后纬度"}
     * @output {"name":"data.telphone","type":"string","desc":"店铺座机号，可能会多个"}
     * @output {"name":"data.rooms","type":"int","desc":"店铺房间总数"}
     * @output {"name":"data.skillers","type":"int","desc":"店铺技师总数"}
     * @output {"name":"data.description","type":"string","desc":"活动描述"}
     *
     * */
    public function getUserShop(){
        $user_id=1;
        //$user_id=$this->input->post('user_id');
        if(!$user_id){
            show300('用户id不能为空');
        }
        $where=['wx_users.user_id'=>$user_id];
        $shops=$this->shop_model->getUserShop($where);
        foreach ($shops as $k=>$v){
            $shops[$k]['lat_lng'] = explode(',',$v['lat_lng']);
            $shops[$k]['telphone'] = explode(';',$v['telphone']);
        }
        if (!empty($shops)){
            show200($shops);
        }else{
            show300('没有关注任何店铺');
        }
    }
    /**
     * @title 用户取消关注店铺
     * @desc  我的__取消关注
     * @input {"name":"user_id","require":"true","type":"int","desc":"用户id"}
     * @input {"name":"shop_id","require":"true","type":"int","desc":"店铺id"}
     * @output {"name":"code","type":"int","desc":"200:成功,300各种提示信息"}
     * @output {"name":"msg","type":"string","desc":"信息说明"}
     */
    public function cancelShop(){
        //$user_id=$this->input->post('user_id');
        //$shop_id=$this->input->post('shop_id');
        $user_id=1;
        $shop_id=1;
        $this->wx_user_model->start();
        $res=$this->wx_user_model->delWhere(['user_id'=>$user_id,'shop_id'=>$shop_id]);
        if ($res){
            $this->wx_user_model->commit();
            $this->shop_model->updateNumPluss(['shop_id'=>$shop_id],'collectNum');
            show200('取消成功');
        }else{
            $this->wx_user_model->rollback();
            show300('取消失败');
        }
    }

    /**
     * @title 用户关注得技师
     * @desc  我的__关注的技师
     * @input {"name":"user_id","require":"true","type":"int","desc":"用户id"}
     * @output {"name":"code","type":"int","desc":"200:成功,300各种提示信息（用户id不能为空;没有关注任何技师）"}
     * @output {"name":"msg","type":"string","desc":"信息说明"}
     *
     * @output {"name":"data.user_id","type":"int","desc":"用户id"}
     * @output {"name":"data.technician_id","type":"int","desc":"技师id"}
     * @output {"name":"data.user_name","type":"string","desc":"技师名称"}
     * @output {"name":"data.photos","type":"string","desc":"技师头像"}
     * @output {"name":"data.shop_name","type":"string","desc":"店铺名称"}
     * */
    public function getUserTech(){
        $user_id=1;
        //$user_id=$this->input->post('user_id');
        if(!$user_id){
            show300('用户id不能为空');
        }
        $shops=$this->wx_user_staff_relation_model->getUserTech($user_id);
        if (!empty($shops)){
            show200($shops);
        }else{
            show300('没有关注任何技师');
        }
    }
    /**
     * @title 用户取消关注技师
     * @desc  我的__取消关注
     * @input {"name":"user_id","require":"true","type":"int","desc":"用户id"}
     * @input {"name":"technician_id","require":"true","type":"int","desc":"技师id"}
     * @input {"name":"shop_id","require":"true","type":"int","desc":"店铺id"}
     * @output {"name":"code","type":"int","desc":"200:成功,300各种提示信息"}
     * @output {"name":"msg","type":"string","desc":"信息说明"}
     */
    public function cancelTech(){
        //$user_id=$this->input->post('technician_id');//用户id
        //$technician_id=$this->input->post('technician_id');//技师id（用户id）
        //$shop_id=$this->input->post('shop_id');//店铺id
        $user_id=1;
        $technician_id=2;
        $shop_id=1;
        $this->user_model->start();
        $res=$this->wx_user_staff_relation_model->delWhere(['user_id'=>$user_id,'shop_id'=>$shop_id,'technician_id'=>$technician_id]);
        if ($res){
            $this->user_model->updateNumPluss(['user_id'=>$technician_id],'collectNum');
            $this->user_model->commit();
            show200('取消成功');
        }else{
            $this->user_model->rollback();
            show300('取消失败');
        }
    }
    /**
     *@title 更换手机号
     *@desc 更换手机号
     *@input {"name":"user_id","require":"true","type":"int","desc":"用户id"}
     *@input {"name":"phone","require":"true","type":"string","desc":"用户手机号"}
     *@input {"name":"yzm","require":"true","type":"string","desc":"验证码"}
     *
     *@output {"name":"code","type":"int","desc":"200:成功,3:未登录,300各种提示信息"}
     *@output {"name":"msg","type":"string","desc":"信息说明"}
     *
     *
     * */
    public function changePhone(){
        $phone=$this  ->input->post('phone');//手机号
        $yzm=$this    ->input->post('yzm');
        $user_id=$this->input->post('user_id');
        //$this->session->set_tempdata('yzm',$yzm,60);
        if(!$phone){
            show300('手机号不能为空');
        }
        if(!$yzm){
            show300('验证码不能为空');
        }
        if (empty($this->session->tempdata('yzm'))){
            show300('验证码失效，请重新发送');
        }
        if(!$user_id){
            show300('用户id不能为空');
        }
        if($yzm==$this->session->tempdata('yzm')) {
            $res=$this->user_model->updateWhere(['user_id'=>$user_id],['phone'=>$phone]);
            if ($res){
                show200('修改成功');
            }
            else{
                show400('修改失败');
            }
        }else{
            show400('验证码输入错误');
        }
    }

}
/* End of file Login.php */
