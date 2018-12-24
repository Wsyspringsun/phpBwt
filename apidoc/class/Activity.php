<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Activity {
    
    private static $data = array();
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Menu_model','menu');//菜单表
        $this->load->model('Dzlist_model','dz');//论坛表
        $this->load->model('Dzlistfile_model','dzfile');//论坛表
        $this->load->model('Banner_model','banner');//banner
        $this->load->model('Webcommon_model','common');//公共
        $this->load->model('User_model','user');//用户表表
        $this->load->model('Userinfo_model','userinfo');//基本信息表
        $this->load->model('Userdetail_model','userdetail');//用户详情表
        $this->load->model('Userfollow_model','follow');//关注表
        $this->load->model('Tags_model','tags');//标签表
        $this->load->model('Province_model','province');//城市表
        $this->load->model('Job_model','job');//职业表
        $this->load->model('Userhandle_model','handle');//操作表
        $this->load->model('Usercount_model','count');//用户统计表
        $this->load->model('Userpoints_model','userpoints');//积分流水表
        $this->load->model('Dzusertip_model','tip');//举报
        $this->load->model('Usercollection_model','collection');//收藏
        $this->load->model('Dzcomment_model','comment');//评论
        $this->load->model('Usermessage_model','message');//私信
        $this->load->model('Activity_model','activity');//活动
        $this->load->model('app_common_model');       //app接口统一输出格式
    }
    
    
    /**
     * 文章列表页 
     *@desc文章列表页
     *@output int code 200:成功 400:失败;
     *@output int abc 200:成功 400:失败;
     *@output int efc 200:成功 400:失败;
     *@input {"name":"userId","type":"string","require":"true","default":"123","other":"11","desc":"用户ID"}
     *@input {"name":"name","type":"string","require":"true","default":"张三","other":"","desc":"用户名称"}
     * */
    public function index()
    {       
        $start=$this->input->get_post('start');
        $limit=3;        //总共显示数据
        if (!$start){
            $start=0;     //起始下标  ,加载更多传递的参数
        }else{
            $start=$start*$limit;
        }
        $bannerList = $this->banner->getActivityBannerList();   //广告
      
      $provinceId=$this->input->get_post('provinceId');
      
      $cityId=$this->input->get_post('cityIdy');
      if ($provinceId){
          $serachparam['provinceId'] = $provinceId;
      }
      if ($cityId){
          $serachparam['cityId'] = $cityId;
      }
      if (!isset($serachparam)){
          $serachparam=[];
      }
      $select='activityId,title,stime,etime,place,addr,money,imagePath';
      $activity = $this->activity->getWebIndexList($serachparam,$select, $limit, $start);
      
      foreach ($activity as $key=>$val){
          $activity[$key]['stime']=format_time($val['stime']);
          $activity[$key]['etime']=format_time($val['etime']);
      }
      
      if($activity){
          $content=[
              'banner'=>$bannerList,
              'activity'=>$activity
          ];
          //var_dump($content);
          $this->app_common_model->show_200($content);
      }else{
          $this->app_common_model->show_400('暂无数据');
      }
    }
    /**
     *  活动详情
     * */
    public function detail(){
        $activityId=$this->input->get_post('activityId');
        if(!$activityId){
            $this->app_common_model->show_401();
        }
        $this->activity->updateNum(array('viewNum'=>'viewNum+1'),$activityId);  //更新浏览量
        $select='activityId,title,stime,etime,place,addr,money,imagePath,content';
        $activity=$this->activity->getActivityRow($activityId,$select);
        if (!$activity){
            $this->app_common_model->show_400();
        }
        $activity['stime']=format_time($activity['stime']);
        $activity['etime']=format_time($activity['etime']);
        //var_dump($activity);die();
        $this->app_common_model->show_200($activity);
    }
    
    //省列表
    public function province(){
        $provinceList = $this->province->getProvinceList();
        if ($provinceList){
            $this->app_common_model->show_200($provinceList);
        }else{
            $this->app_common_model->show_400();
        }
    }
    //城市列表
    public function city(){
        $provinceId=$this->input->get_post('provinceId');
        if(!$provinceId){
            $this->app_common_model->show_401();
        }
        
        $city=$this->db->where('provinceId',$provinceId)->get('city')->result_array();
        
        if ($city){
            $this->app_common_model->show_200($city);
        }else{
            $this->app_common_model->show_400();
        }
    }
     
    
    
}
