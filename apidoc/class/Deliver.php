<?php
defined('BASEPATH') OR exit('No direct script access allowed');
//投递
class Deliver {
    private static $data = array();
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Deliver_model','deliver_model');//用户收藏模型
        $this->load->model('User_model','user_model');//用户收藏模型
        
    }
   
    /**
     *@title 我投递的简历
     *@desc 我投递的简历
     *@input {"name":"userId","require":"true","type":"int","desc":"用户ID"}
     *@input {"name":"page","type":"string","desc":"页数"}
     *@input {"name":"limit","type":"string","desc":"每页显示条数"}
     *
     *@output {"name":"code","type":"int","desc":"200:成功,400失败,3:未登录,300各种提示信息"}
     *@output {"name":"msg","type":"string","desc":"返回信息;收藏成功/失败"}
     *     
     *@output {"name":"data","type":"array","desc":"职位数组,下面是详细说明,index代表该数组下标"}
     *@output {"name":"data[index].jobId","desc":"职位ID","child":"1"}
     *@output {"name":"data[index].logo","desc":"职位LOGO","child":"1"}
     *@output {"name":"data[index].jobName","desc":"职位名称","child":"1"}
     *@output {"name":"data[index].salaryName","desc":"薪资","child":"1"}
     *@output {"name":"data[index].companyName","desc":"所必公司名称","child":"1"}
     *@output {"name":"data[index].cityName","desc":"公司所在城市","child":"1"}
     *@output {"name":"data[index].createTime","desc":"职位发布时间","child":"1"}
     * */
    public function index(){
        $userId = $this->input->post('userId');
        $page=$this->input->post('page');
        $limit=$this->input->post('limit');
        if(!$userId){
            show3();
        }
        
        $page=empty($page)?0:$page;
        $limit=empty($limit)?20:$limit;
        $offset=$limit*$page;
        
        $job=$this->deliver_model->getByUserId($userId,$limit,$offset);
        
        if(!$job){
            show400();
        }
        foreach ($job as $key=>$val){
            $job[$key]['createTime']=format_time($val['createTime']);
        }
        show200($job);
    }
    
    /**
     *@title 公司用户收到的投递简历用户列表
     *@desc 公司用户收到的投递简历用户列表
     *
     *@input {"name":"companyUserId","type":"int","desc":"公司用户ID,公司用户登录时,即为当前登录用户ID"}
     *
     *@input {"name":"workCityId","type":"int","desc":"应聘者希望的工作城市ID,如果不传则显示所有地区,即全国"}
     *@input {"name":"search","type":"int","desc":"查询应聘用户名称"}
     *@input {"name":"jobId","type":"int","desc":"按职位ID筛选,不筛选则可不传"}     
     *
     *@input {"name":"page","type":"int","desc":"职位分页数,从0开始","default":"0"}
     *@input {"name":"limit","type":"int","desc":"一页显示的条数","default":"20"}
     *
     *@output {"name":"code","type":"int","desc":"200:获取成功,400获取失败,无数据"}
     *@output {"name":"msg","type":"string","desc":"获取成功/获取失败"}
     *
     *@output {"name":"data","type":"array","desc":"用户列表,下面是详细说明,index代表该数组下标"}
     *@output {"name":"data[index].userId","desc":"用户Id","child":"1"}
     *@output {"name":"data[index].avatar","desc":"用户头像","child":"1"}
     *@output {"name":"data[index].birthday","desc":"用户生日,如果需要年龄,可以前端自己转换","child":"1"}
     *@output {"name":"data[index].exp","desc":"用户工作经验","child":"1"}
     *@output {"name":"data[index].jobName","desc":"期望的职位名称","child":"1"}
     *@output {"name":"data[index].realName","desc":"用户姓名","child":"1"}
     *@output {"name":"data[index].salaryName","desc":"期望的薪资名称","child":"1"}
     * */
    public function resume_receiveList()
    {
        $companyUserId=$this->input->post('companyUserId');
        if(!$companyUserId){
            show3('请先登录');
        }
        $workCityId=$this->input->post('workCityId');       //工作城市ID
        $search=$this->input->post('search');   //搜索
        $order=$this->input->post('order');     //排序,默认不传为推荐排序,传此参数则意为最新排序
        $jobId=$this->input->post('jobId');
        
        $page=$this->input->post('page');
        $limit=$this->input->post('limit');
    
        $page=!empty($page)?$page:0;
        $limit=!empty($limit)?$limit:20;
        $where=[];  //筛选条件数组
        //以下是为了组装查询条件数组
        if($jobId){
            $where['user.jobId']=$jobId;
        }
        if($workCityId){
            $where['user.workCityId']=$workCityId;
        }
    
    
        $offset=$page*$limit;        //数据库起始偏移量
        $job=$this->user_model->resume_receiveList($companyUserId,$offset,$limit,$search,$where,$order);
    
        if(!$job){
            show400();
        }
    
        show200($job);
    }
    
}
