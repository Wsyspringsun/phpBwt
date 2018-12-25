<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Job {
    private static $data = array();
    public function __construct()
    {
        parent::__construct();        
        $this->load->model('User_model','user_model');//用户模型    
        $this->load->model('Company_model','company_model');//职位模型    
        $this->load->model('Authen_model','authen_model');//职位模型
        $this->load->model('Job_model','job_model');//职位模型
        $this->load->model('Banner_model','banner_model');//职位模型
        $this->load->model('Type_model','type_model');//分类模型    
        $this->load->model('Banner_model','banner_model');//轮播模型    
        $this->load->model('Comment_model','comment_model');//评论模型
        $this->load->model('Userhandle_model','handle_model');//操作日志
        $this->load->model('App_common_model','app_common_model');//通用功能模型    
    }
    
    
    /**
     *@title 职位列表
     *@desc 职位列表接口
     *@input {"name":"cityId","type":"int","desc":"职位所在城市ID,如果不传则显示所有地区,即全国"}
     *@input {"name":"typeId","type":"int","desc":"职位分类ID,筛选用"}
     *@input {"name":"expId","type":"int","desc":"经验分类ID,筛选用"}
     *@input {"name":"salaryId","type":"int","desc":"薪资分类ID,筛选用"}
     *@input {"name":"search","type":"int","desc":"查询职位名称"}
     *@input {"name":"order","type":"int","desc":"如果传此参数值为1,则按最新排序,若不传此参数则按推荐排序"}
     *
     *@input {"name":"page","type":"int","desc":"职位分页数,从0开始","default":"0"}
     *@input {"name":"limit","type":"int","desc":"一页显示的条数","default":"20"}
     *
     *@output {"name":"code","type":"int","desc":"200:获取成功,400获取失败,无数据"}
     *@output {"name":"msg","type":"string","desc":"获取成功/获取失败"}
     *
     *@output {"name":"data","type":"array","desc":"职位数组,下面是详细说明,index代表该数组下标"}
     *@output {"name":"data[index].jobId","desc":"职位ID","child":"1"}
     *@output {"name":"data[index].logo","desc":"职位LOGO","child":"1"}
     *@output {"name":"data[index].jobName","desc":"职位名称","child":"1"}
     *@output {"name":"data[index].salaryName","desc":"薪资","child":"1"}
     *@output {"name":"data[index].companyName","desc":"所必公司名称","child":"1"}
     *@output {"name":"data[index].cityName","desc":"公司所在城市","child":"1"}
     *@output {"name":"data[index].createTime","desc":"创建时间","child":"1"}
     * */
    public function jobList()
    {       
        $typeId=$this->input->post('typeId');         //职位ID
        $expId=$this->input->post('expId');         //经验Id
        $salaryId=$this->input->post('salaryId');       //认证信息ID
        $cityId=$this->input->post('cityId');       //城市ID
        $search=$this->input->post('search');   //搜索
        $order=$this->input->post('order');     //排序,默认不传为推荐排序,传此参数则意为最新排序
        
        $page=$this->input->post('page');
        $limit=$this->input->post('limit');
        
        $page=!empty($page)?$page:0;        
        $limit=!empty($limit)?$limit:20;     
        $where=[];  //筛选条件数组
        //以下是为了组装查询条件数组
        if($typeId){
            $where['job.typeId']=$typeId;
        }
        if($expId){
            $where['job.expId']=$expId;
        }
        if($salaryId){
            $where['job.salaryId']=$salaryId;
        }
        if($cityId){
            $where['company.cityId']=$cityId;
        }
        
        
        $offset=$page*$limit;        //数据库起始偏移量
        $select='job.jobId,type.typeName as jobName,concat("'.IMAGEHOST.'",job.logo) as logo,exp.typeName as expName,
                 salary.typeName as salaryName,cityName,type.typeName as authenName,company.name as companyName,job.createTime';
        $job=$this->job_model->getList($select,$offset,$limit,$search,$where,$order);
        
        if(!$job){
            show400();
        }     
        foreach ($job as $key=>$val){
            $job[$key]['createTime']=format_time($val['createTime']);
        }
        show200($job);
    }
    
    
    /**
     *@title 职位详情页
     *@desc 职位详情页
     *@input {"name":"jobId","require":"true","type":"string","desc":"职位Id"}
     *@input {"name":"userId","type":"int","desc":"用户ID,如果用户已登录则传此参数,用来判断用户是否已收藏等"}
     *
     *@output {"name":"code","type":"int","desc":"200:获取成功,400获取失败,无数据,300:各种提示信息"}
     *@output {"name":"msg","type":"string","desc":"获取成功/获取失败"}
     *
     *@output {"name":"data","type":"array","desc":"职位数组,下面是详细说明,index代表该数组下标"}
     *@output {"name":"data.jobId","desc":"职位ID","child":"1"}
     *@output {"name":"data.companyId","desc":"所属公司ID","child":"1"}
     *@output {"name":"data.jobName","desc":"职位名称","child":"1"}
     *@output {"name":"data.salaryName","desc":"薪资分类名称","child":"1"} 
     *@output {"name":"data.num","desc":"招聘人数","child":"1"}  
     *@output {"name":"data.sex","desc":"性别要求:1男,2女,0不限","child":"1"}    
     *@output {"name":"data.age","desc":"年龄要求","child":"1"}  
     *@output {"name":"data.balance","desc":"结算方式","child":"1"}  
     *@output {"name":"data.jobDesc","desc":"职位描述","child":"1"} 
     *@output {"name":"data.demand","desc":"任职要求","child":"1"}   
     *@output {"name":"data.positionObj","desc":"职位位置信息,内容为JSON字符串,前端解析可以得到相关位置信息","child":"1"}  
     *@output {"name":"data.authen","type":"array","desc":"认证数据","child":"1"} 
     *@output {"name":"data.companyName","desc":"公司名称","child":"1"} 
     *@output {"name":"data.companyLogo","desc":"公司LOGO","child":"1"} 
     *@output {"name":"data.tradeName","desc":"公司所属行业名称","child":"1"} 
     *@output {"name":"data.scaleName","desc":"公司规模","child":"1"} 
     *@output {"name":"data.jobNum","desc":"公司招聘的岗位个数","child":"1"} 
     *@output {"name":"data.is_collect","type":"boolean","desc":"是否收藏,是:true,否:flase","child":"1"} 
     *
     * */
    public function detail()
    {
        $jobId=$this->input->post('jobId');
        if (!$jobId){
            show300('职位ID不正确');
        }
        $userId=$this->input->post('userId');        
        $this->job_model->updateNum($jobId,array('viewNum'=>'viewNum+1'));      //更新浏览量(+1)        
        
        $select='jobId,job.companyId,.type.typeName as jobName,jobDesc,demand,salary.typeName as salaryName,
                 job.num,job.sex,job.age,balance,positionObj,company.name as companeName,company.logo as companyLogo,
                 company.jobNum,scale.typeName as scaleName,trade.typeName as tradeName';
        $job = $this->job_model->getDetail($jobId ,$select);      //获取职位详情
        if (!$job){
            show400();
        }
        $authen=$this->authen_model->getAuthen($job['companyId']);
        $is_collect=$this->app_common_model->is_collect($userId,$jobId,2);        
        $job['authen'] =$authen;        //认证数据
        $job['is_collect']=$is_collect;
        show200($job);
    }
    
}
