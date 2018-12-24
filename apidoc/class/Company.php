<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Company {
    private static $data = array();
    //该控制器需要用到的模型数组
    private $model=['company_model','banner_model','app_common_model','comment_model','type_model',
                    'technician_model','project_model'];  
    public function __construct()
    {
        parent::__construct($this->model); 
    }
        
    /**
     *@title 店铺列表
     *@desc 店铺列表接口
     *@input {"name":"tradeId","type":"int","desc":"店铺所属行业ID,筛选用"}
     *@input {"name":"search","type":"int","desc":"按名称搜索店铺"}
     *
     *@input {"name":"page","type":"int","desc":"店铺分页数,从0开始","default":"0"}
     *@input {"name":"limit","type":"int","desc":"一页显示的条数","default":"20"}
     *
     *@output {"name":"code","type":"int","desc":"200:获取成功,400获取失败,无数据"}
     *@output {"name":"msg","type":"string","desc":"获取成功/获取失败"}
     *
     *@output {"name":"data","type":"array","desc":"店铺数组,下面是详细说明,index代表该数组下标"}
     *@output {"name":"data[index].companyId","desc":"店铺ID","child":"1"}
     *@output {"name":"data[index].poster","desc":"店铺封面","child":"1"}
     *@output {"name":"data[index].name","desc":"店铺名称","child":"1"}
     *@output {"name":"data[index].tradeName","desc":"所属行业","child":"1"}
     *@output {"name":"data[index].judge","desc":"店铺评分分值","child":"1"}  
     *@output {"name":"data[index].consume","desc":"店铺平均消费","child":"1"}  
     * */
    public function companyList()
    {       
        $tradeId=$this->input->post('tradeId');         //店铺所属行业ID        
        $search=$this->input->post('search');   //搜索       
        $page=$this->input->post('page');
        $limit=$this->input->post('limit');
        
        $page=!empty($page)?$page:0;        
        $limit=!empty($limit)?$limit:20;     
        $where=[];  //筛选条件数组
        //以下是为了组装查询条件数组
        if($tradeId){
            $where['company.tradeId']=$tradeId;
        }
        $offset=$page*$limit;        //数据库起始偏移量
        $dbArray=[];
        if($search){
            $dbArray['like']=['company.name'=>$search];
        }
        $company=$this->company_model->getList($where,$dbArray);
        if(!$company){
            show400();
        }        
        show200($company);              
    }
    
    
    /**
     *@title 店铺详情页
     *@desc 店铺详情页
     *@input {"name":"companyId","require":"true","type":"string","desc":"店铺Id"}
     *@input {"name":"userId","type":"int","desc":"用户ID,如果用户已登录则传此参数,用来判断用户是否已收藏等"}
     *
     *@output {"name":"code","type":"int","desc":"200:获取成功,400获取失败,无数据,300:各种提示信息"}
     *@output {"name":"msg","type":"string","desc":"获取成功/获取失败"}
     *
     *@output {"name":"data","type":"array","desc":"店铺数组,下面是详细说明,index代表该数组下标"}
     *@output {"name":"data.detail","desc":"店铺详情数据","child":"1"}
     *@output {"name":"data.detail.companyId","desc":"店铺ID","child":"2"}
     *@output {"name":"data.detail.name","desc":"店铺名称","child":"2"}
     *@output {"name":"data.detail.judge","desc":"评价分值","child":"2"} 
     *@output {"name":"data.detail.services","type":"array","desc":"所提供的服务数组,如WIFI,停车等","child":"2"}  
     *@output {"name":"data.detail.phone","desc":"联系方式","child":"2"}    
     *@output {"name":"data.detail.address","desc":"店铺地址","child":"2"}  
     *@output {"name":"data.detail.positionObj","desc":"店铺位置信息,内容为JSON字符串,前端解析可以得到相关位置信息","child":"2"}  
     *
     *@output {"name":"data.detail.banner","type":"array","desc":"店铺详情顶部轮播图","child":"2"}   
     *@output {"name":"data.detail.banner[index].bannerName","desc":"图片名称","child":"3"}   
     *@output {"name":"data.detail.banner[index].imagePath","desc":"图片路径","child":"3"}
     * 
     *@output {"name":"data.detail.is_collect","type":"boolean","desc":"是否收藏,是:true,否:flase","child":"2"} 
     *
     *@output {"name":"data.detail.technician","type":"array","desc":"技师数组","child":"2"} 
     *@output {"name":"data.detail.technician[index].technicianId","type":"string","desc":"技师ID","child":"3"}
     *@output {"name":"data.detail.technician[index].realName","type":"string","desc":"技师姓名","child":"3"} 
     *@output {"name":"data.detail.technician[index].sevriceNo","type":"string","desc":"技师编号","child":"3"} 
     *@output {"name":"data.detail.technician[index].giftNum","type":"string","desc":"礼物数量","child":"3"} 
     *@output {"name":"data.detail.technician[index].jobName","type":"string","desc":"职业名称,如足疗师","child":"3"}
     *@output {"name":"data.detail.technician[index].judge","type":"string","desc":"评价分值","child":"3"}
     *@output {"name":"data.detail.technician[index].poster","type":"string","desc":"技师封面图片","child":"3"}
     *@output {"name":"data.detail.technician[index].skill","type":"array","desc":"技师的技能数组","child":"3"}
     *@output {"name":"data.detail.technician[index].tags","type":"string","desc":"技师职称,如高级技师","child":"3"}
     *
     *@output {"name":"data.detail.project","type":"array","desc":"项目数组","child":"2"} 
     *@output {"name":"data.detail.project[index].projectId","type":"string","desc":"项目ID","child":"3"}
     *@output {"name":"data.detail.project[index].title","type":"string","desc":"项目名称","child":"3"}
     *@output {"name":"data.detail.project[index].poster","type":"string","desc":"项目封面","child":"3"}
     *@output {"name":"data.detail.project[index].price","type":"string","desc":"项目价格","child":"3"}
     *@output {"name":"data.detail.project[index].judge","type":"string","desc":"项目评价分值","child":"3"}
     *@output {"name":"data.detail.project[index].sales","type":"string","desc":"项目销售数量","child":"3"}
     *
     *@output {"name":"data.otherCompany","desc":"推荐的公司列表,参数和公司列表的参数一致,请自行参考","child":"1"}
     * */
    public function detail()
    {
        $companyId=$this->input->post('companyId');
        if (!$companyId){
            show301('店铺ID不正确');
        }
        $userId=$this->input->post('userId'); 
        $select='companyId,name,serviceTypeIds,judge,phone,address,positionObj';
        $company = $this->company_model->getWhereRow(['companyId'=>$companyId] ,$select);      //获取店铺详情
        if (!$company){
            show400();
        }
        $company['services']=$this->type_model->getServices(explode('|', @$company['serviceTypeIds']));        //店铺提供的服务,如WIFI,电视等
        //店铺详情页轮播图
        $banner=$this->banner_model->getWhere(['companyId'=>$companyId],'bannerName,concat("'.IMAGEHOST.'",imagePath) as imagePath');
        $company['banner'] =$banner;    //店铺详情页部轮播顶图        
        //技师数据
        $company['technician']=$this->getTichnician($companyId,2);
        
        $is_collect=$this->app_common_model->is_collect($userId,$companyId,4);        
        $company['is_collect']=$is_collect;
        $company['project']=$this->project_model->getList(['companyId'=>$companyId]);
        $otherCompany=$this->getOtherCompany($companyId);
        $data=[
            'detail'=>$company,
            'otherCompany'=>$otherCompany
        ];
        show200($data);
    }
    //获取技师数据
    private function getTichnician($companyId,$limit=''){
        $select='technicianId,realName,judge,job.typeName as jobName,concat("'.IMAGEHOST.'",poster) as poster,giftNum,
                sevriceNo,tags,skill';
        $dbArray=[];
        if ($limit){
            $dbArray['page']=['limit'=>$limit];
        }
        $tichnician=$this->technician_model->getList(['companyId'=>$companyId],$select,$dbArray);
        foreach ($tichnician as $key=>$val){
            $tichnician[$key]['skill']=$this->type_model->getSkill(explode('|', $val['skill']));
        }
        return $tichnician;
    }
    //获取推荐数据
    private function getOtherCompany($companyId){
        $where=['companyId!='=>$companyId];
        $dbArray['order']=['company.createTime'=>'desc'];
        $dbArray['page']=['limit'=>3];
        $company=$this->company_model->getList($where,$dbArray);
        return $company;
    }
    /**
     *@title 项目详情页
     *@desc 项目详情页
     *@input {"name":"projectId","require":"true","type":"string","desc":"项目Id"}
     *@input {"name":"userId","type":"int","desc":"用户ID,如果用户已登录则传此参数,用来判断用户是否已收藏等"}
     *
     *@output {"name":"code","type":"int","desc":"200:获取成功,400获取失败,无数据,300:针对用户的提示信息,可直接对用户输出提示,301,针对开发人员的提示,帮助开发人员定位问题"}
     *@output {"name":"msg","type":"string","desc":"获取成功/获取失败"}
     *
     *@output {"name":"data","type":"array","desc":"店铺数组,下面是详细说明,index代表该数组下标"}
     *@output {"name":"data.detail","desc":"店铺详情数据","child":"1"}
     *@output {"name":"data.detail.companyId","desc":"店铺ID","child":"2"}
     *@output {"name":"data.detail.projectId","desc":"项目ID","child":"2"}
     *@output {"name":"data.detail.title","desc":"项目标题","child":"2"}
     *@output {"name":"data.detail.judge","desc":"项目评价分值","child":"2"}
     *@output {"name":"data.detail.services","type":"array","desc":"所提供的服务数组,如WIFI,停车等","child":"2"}
     *@output {"name":"data.detail.typeName","desc":"项目所属分类名称,如:中式,泰式","child":"2"}
     *@output {"name":"data.detail.effect","type":"array","desc":"项目功效数组","child":"2"}
     *@output {"name":"data.detail.poster","desc":"项目封面","child":"2"}
     *@output {"name":"data.detail.duration","desc":"服务时长,单位为分钟","child":"2"}
     *@output {"name":"data.detail.sales","desc":"销售量","child":"2"}
     *@output {"name":"data.detail.price","desc":"价格","child":"2"}
     *@output {"name":"data.detail.content","desc":"项目详情","child":"2"}
     *@output {"name":"data.detail.tips","desc":"项目购买须知","child":"2"}
     *    
     *@output {"name":"data.otherProject","type":"array","desc":"店铺的其他项目列表,其参数请参考项目列表","child":"1"}
     *@output {"name":"data.otherProject[index].projectId","type":"string","desc":"项目ID","child":"2"}
     *@output {"name":"data.otherProject[index].title","type":"string","desc":"项目名称","child":"2"}
     *@output {"name":"data.otherProject[index].poster","type":"string","desc":"项目封面","child":"2"}
     *@output {"name":"data.otherProject[index].price","type":"string","desc":"项目价格","child":"2"}
     *@output {"name":"data.otherProject[index].judge","type":"string","desc":"项目评价分值","child":"2"}
     *@output {"name":"data.otherProject[index].sales","type":"string","desc":"项目销售数量","child":"2"}
     * */
    public function projectDetail()
    {
        $projectId=$this->input->post('projectId');
        if (!$projectId){
            show301('项目ID不正确');
        }
        $userId=$this->input->post('userId');
        $project = $this->project_model->getDetail($projectId);      //获取店铺详情
        if (!$project){
            show400();
        }
        $project['services']=$this->type_model->getServices(explode('|', @$project['serviceTypeIds']));        //店铺提供的服务,如WIFI,电视等
        $project['effect']=explode('|', $project['effect']);
        $is_collect=$this->app_common_model->is_collect($userId,$projectId,5);
        $project['is_collect']=$is_collect;
        $where=['companyId'=>$project['companyId'],'projectId!='=>$projectId];
        $otherProject=$this->project_model->getList($where);        //店铺的其他项目
        $data=[
            'detail'=>$project,
            'otherProject'=>$otherProject
        ];
        show200($data);
    }
    
    
    
    
    
    
    
    
    
}
