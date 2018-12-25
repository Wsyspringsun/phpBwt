
<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 公司端用户中心
 * @author lxn
 */
class CompanyUser {
	private static $data = array();
	public function __construct()
	{
		parent::__construct();
		$this->load->model('User_model','user_model');
		$this->load->model('Company_model','company_model');
		$this->load->model('Job_model','job_model');
		$this->load->model('Type_model','type_model');	
		$this->load->model('Banner_model','banner_model');//轮播模型
	}

	/**
	 *@title 应聘者列表
	 *@desc 应聘者列表(获取所有在找工作的用户列表)
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
	public function applyList()
	{
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
	    $select='user.userId,';
	    $job=$this->user_model->getApplyList($offset,$limit,$search,$where,$order);
	
	    if(!$job){
	        show400();
	    }
	
	    show200($job);
	}

	/**
	 *@title 添加/修改公司信息
	 *@desc 添加/修改公司信息
	 *
	 *@input {"name":"userId","require":"true","type":"int","desc":"公司端当前登录用户ID"}
	 *@input {"name":"companyId","require":"","type":"int","desc":"公司ID,不传此参数则是添加公司信息,传递此参数则是修改公司信息"}
	 *@input {"name":"name","require":"true","type":"string","desc":"公司名称"}
	 *@input {"name":"positionObj","require":"true","type":"int","desc":"公司地址信息,用小程序内置地图获取定位后将返回的位置信息转换成json格式来传递"}
	 *@input {"name":"phone","require":"true","type":"string","desc":"公司联系方式"}
	 *@input {"name":"logo","require":"true","type":"string","desc":"公司LOGO链接"}
	 *
	 *@output {"name":"code","type":"int","desc":"200:成功,400失败,3:未登录,300各种提示信息"}
	 *@output {"name":"msg","type":"string","desc":"信息说明"}
	 * */
	public function addCompany(){
	    $userId=$this->input->post('userId');
	    $companyId=$this->input->post('companyId');
	    $name=$this->input->post('name');
	    $positionObj=$this->input->post('positionObj');
	    $phone=$this->input->post('phone');
	    $logo=$this->input->post('logo');
	    if(!$name||!$positionObj||!$phone||!$logo){
	        show300('必填参数不能为空');
	    }
	    if(!$userId){
	        show3('请先登录');
	    }
	    $data=[
	        'name'=>$name,
	        'positionObj'=>$positionObj,
	        'phone'=>$phone,
	        'logo'=>$logo,
	        'userId'=>$userId,
	    ];
	    if($companyId){
	        $res=$this->company_model->update($companyId,$data);
	    }else{
	        $res=$this->company_model->add($data);
	        $companyId=$res;
	    }
	    
	    if ($res){
	        show200(['companyId'=>$companyId],'操作成功');
	    }
	    else{
	        show400('操作失败');
	    }
	}
	
	/**
	 *@title 店铺设置
	 *@desc 修改公司店铺信息
	 *
	 *@input {"name":"companyId","require":"true","type":"int","desc":"公司ID"}
	 *@input {"name":"price","require":"true","type":"string","desc":"客单价"}
	 *@input {"name":"fee","require":"true","type":"int","desc":"单钟提成"}
	 *@input {"name":"content","require":"true","type":"string","desc":"公司自述(详情)"}
	 *@input {"name":"flux","require":"true","type":"string","desc":"公司日客流量(个人中心里没有这一项,但是用户端公司详情里有,所以也加上这一参数)"}
	 *@input {"name":"images","require":"true","type":"string","desc":"公司图片,小程序端以数组形式传递"}
	 *
	 *@output {"name":"code","type":"int","desc":"200:成功,400失败,3:未登录,300各种提示信息"}
	 *@output {"name":"msg","type":"string","desc":"信息说明"}
	 * */
	public function updateCompany(){
	    $companyId=$this->input->post('companyId');
	    $price=$this->input->post('price');
	    $fee=$this->input->post('fee');
	    $content=$this->input->post('content');
	    $flux=$this->input->post('flux');
	    $images=$this->input->post('images');
	    if(!$companyId||!$price||!$fee||!$content||!$flux){
	        show300('必填参数不能为空');
	    }
	    
	    $data=[
	        'price'=>$price,
	        'fee'=>$fee,
	        'content'=>$content,
	        'flux'=>$flux,
	    ];
	    //此处还需要处理images,公司的图片,待上传功能了以后再加
	    
	    
	    
	    $res=$this->company_model->update($companyId,$data);
	    
	    if ($res){
	        show200('操作成功');
	    }
	    else{
	        show400('操作失败');
	    }
	}
	
	/**
	 *@title 获取公司信息
	 *@desc 获取公司信息
	 *@input {"name":"userId","require":"true","type":"string","desc":"当前登录公司用户ID"}	 
	 *
	 *@output {"name":"code","type":"int","desc":"200:获取成功,400获取失败,无数据,300:各种提示信息"}
	 *@output {"name":"msg","type":"string","desc":"获取成功/获取失败"}
	 *
	 *@output {"name":"data","type":"array","desc":"公司数组,下面是详细说明,index代表该数组下标"}
	 *@output {"name":"data.companyId","desc":"公司ID","child":"1"}
	 *@output {"name":"data.userJob","desc":"创建者在公司的职位","child":"1"}
	 *@output {"name":"data.name","desc":"公司名称","child":"1"}
	 *@output {"name":"data.brief","desc":"简介","child":"1"}
	 *@output {"name":"data.content","desc":"内容","child":"1"}
	 *@output {"name":"data.phone","desc":"联系方式","child":"1"}
	 *@output {"name":"data.price","desc":"客单价","child":"1"}
	 *@output {"name":"data.fee","desc":"单钟提点","child":"1"}
	 *@output {"name":"data.flux","desc":"日客流量","child":"1"}
	 *
	 *@output {"name":"data.positionObj","desc":"公司位置信息,内容为JSON字符串,前端解析可以得到相关位置信息","child":"1"}
	 *
	 *@output {"name":"data.banner","type":"array","desc":"公司详情顶部轮播图","child":"1"}
	 *@output {"name":"data.banner[index].bannerName","desc":"图片名称","child":"2"}
	 *@output {"name":"data.banner[index].imagePath","desc":"图片路径","child":"2"}
	 *	 
	 * */
	public function companyDetail()
	{
	    $userId=$this->input->post('userId');
	    if (!$userId){
	        show3();
	    }
	    	
	    $select='companyId,name,brief,content,phone,price,fee,flux,positionObj,type.typeName as userJob';
	    $company = $this->company_model->getDetailByUserId($userId ,$select);      //获取公司详情
	    if (!$company){
	        show400();
	    }
	    $companyId=$company['companyId'];
	    $banner=$this->banner_model->getBanner_company($companyId);
	    $company['banner'] =$banner;    //公司详情页部轮播顶图
	    show200($company);
	}
	
	/**
	 *@title 职位列表
	 *@desc 职位列表接口
	 *@input {"name":"userId","type":"int","desc":"公司端登录用户ID"}	 
	 *
	 *@input {"name":"page","type":"int","desc":"职位分页数,从0开始","default":"0"}
	 *@input {"name":"limit","type":"int","desc":"一页显示的条数","default":"20"}
	 *
	 *@output {"name":"code","type":"int","desc":"200:获取成功,400获取失败,无数据"}
	 *@output {"name":"msg","type":"string","desc":"获取成功/获取失败"}
	 *
	 *@output {"name":"data","type":"array","desc":"公司数组,下面是详细说明,index代表该数组下标"}
	 *@output {"name":"data.companyId","desc":"公司ID","child":"1"}
	 *@output {"name":"data.userJob","desc":"创建者在公司的职位","child":"1"}
	 *@output {"name":"data.name","desc":"公司名称","child":"1"}
	 *@output {"name":"data.brief","desc":"简介","child":"1"}
	 *@output {"name":"data.content","desc":"内容","child":"1"}
	 *@output {"name":"data.phone","desc":"联系方式","child":"1"}
	 *@output {"name":"data.price","desc":"客单价","child":"1"}
	 *@output {"name":"data.fee","desc":"单钟提点","child":"1"}
	 *@output {"name":"data.flux","desc":"日客流量","child":"1"}
	 *
	 *@output {"name":"data.job","type":"array","desc":"职位数组,下面是详细说明,index代表该数组下标","child":"1"}
	 *@output {"name":"data.job[index].jobId","desc":"职位ID","child":"2"}
	 *@output {"name":"data.job[index].logo","desc":"职位LOGO","child":"2"}
	 *@output {"name":"data.job[index].jobName","desc":"职位名称","child":"2"}
	 *@output {"name":"data.job[index].salaryName","desc":"薪资","child":"2"}
	 *@output {"name":"data.job[index].num","desc":"职位所招人数","child":"2"}
	 *@output {"name":"data.job[index].deliverNum","desc":"投递简历的人数","child":"2"}
	 *@output {"name":"data.job[index].createTime","desc":"创建时间","child":"2"}
	 * */
	public function jobList()
	{
	    $userId=$this->input->post('userId');
	    if (!$userId){
	        show3();
	    }
	    	
	    $select='companyId,name,brief,content,phone,price,fee,flux,positionObj,type.typeName as userJob';
	    $company = $this->company_model->getDetailByUserId($userId ,$select);      //获取公司详情
	    if(!$company){
	        show300('您还没有创建公司');
	    }
	    $select1='job.jobId,type.typeName as jobName,concat("'.IMAGEHOST.'",job.logo) as logo,exp.typeName as expName,
                 salary.typeName as salaryName,job.createTime,num,count(deliverId) as deliverNum';
	    $job=$this->job_model->getListByCompanyId($company['companyId'],$select1);
		    
	    foreach ($job as $key=>$val){
	        $job[$key]['createTime']=format_time($val['createTime']);
	    }
	    $company['job']=$job;
	    
	    show200($company);
	}
	

	/**
	 *@title 职位详情
	 *@desc 职位详情(用于编辑职位时,获取数据初始化页面)
	 *	 *
	 *@input {"name":"jobId","require":"","type":"int","desc":"职位ID"}
	 *	 
	 *@output {"name":"code","type":"int","desc":"200:成功,400失败,3:未登录,300各种提示信息"}
	 *@output {"name":"msg","type":"string","desc":"信息说明"}
	 *
	 *@output {"name":"data","type":"array","desc":"公司数组,下面是详细说明,index代表该数组下标"}
	 *@input {"name":"data.typeId","type":"string","desc":"职位分类ID"}
	 *@input {"name":"data.expId","type":"int","desc":"职位要求的工作经验分类ID"}
	 *@input {"name":"data.num","type":"string","desc":"职位招聘的人数"}
	 *@input {"name":"data.sex","type":"string","desc":"职位要求的性别:1男,2女,0未知"}
	 *@input {"name":"data.salaryId","type":"string","desc":"职位提供的薪资分类ID"}
	 *@input {"name":"data.balance","type":"string","desc":"职位结算方式"}
	 *@input {"name":"data.age","type":"string","desc":"职位要求的年龄限制"}
	 *@input {"name":"data.jobDesc","type":"string","desc":"职位描述"}
	 *@input {"name":"data.demand","type":"string","desc":"任职要求"}
	 * */
	public function jobDetail(){
	    $jobId=$this->input->post('jobId');
	    
	    
	    if (!$jobId){
	        show300('职位ID不正确');
	    }
	    $select='job.jobId,job.typeId,job.expId,job.num,job.sex,job.salaryId,job.balance,job.age,job.jobDesc,job.demand';
	    $job=$this->job_model->getDetail($jobId,$select);
	
	    if ($job){
	        show200($job,'操作成功');
	    }
	    else{
	        show400('操作失败');
	    }
	}
	
	/**
	 *@title 添加/修改职位信息
	 *@desc 添加/修改职位信息
	 *
	 *@input {"name":"userId","require":"true","type":"int","desc":"公司端当前登录用户ID"}
	 *@input {"name":"jobId","require":"","type":"int","desc":"职位ID,不传此参数则是添加职位信息,传递此参数则是修改职位信息"}
	 *@input {"name":"typeId","require":"true","type":"string","desc":"职位分类ID"}
	 *@input {"name":"expId","require":"true","type":"int","desc":"职位要求的工作经验分类ID"}
	 *@input {"name":"num","require":"true","type":"string","desc":"职位招聘的人数"}
	 *@input {"name":"sex","require":"true","type":"string","desc":"职位要求的性别:1男,2女,0未知"}
	 *@input {"name":"salaryId","require":"true","type":"string","desc":"职位提供的薪资分类ID"}
	 *@input {"name":"balance","require":"true","type":"string","desc":"职位结算方式"} 
	 *@input {"name":"age","require":"true","type":"string","desc":"职位要求的年龄限制"}
	 *@input {"name":"jobDesc","require":"true","type":"string","desc":"职位描述"}
	 *@input {"name":"demand","require":"true","type":"string","desc":"任职要求"}	
	 *
	 *@output {"name":"code","type":"int","desc":"200:成功,400失败,3:未登录,300各种提示信息"}
	 *@output {"name":"msg","type":"string","desc":"信息说明"}
	 * */
	public function addJob(){
	    $userId=$this->input->post('userId');
	    $jobId=$this->input->post('jobId');
	    $typeId=$this->input->post('typeId');
	    $expId=$this->input->post('expId');
	    $num=$this->input->post('num');
	    $sex=$this->input->post('sex');
	    $salaryId=$this->input->post('salaryId');
	    $balance=$this->input->post('balance');
	    $age=$this->input->post('age');
	    $jobDesc=$this->input->post('jobDesc');
	    $demand=$this->input->post('demand');
	    if(!$typeId||!$expId||!$num||!$sex||!$salaryId||!$balance||!$age||!$jobDesc||!$demand){
	        show300('必填项不能为空');
	    }
	    if (!$userId){
	        show3();
	    }
	    	
	    $select='companyId';
	    $company = $this->company_model->getDetailByUserId($userId ,$select);      //获取公司详情
	    if(!$company){
	        show300('您还没有创建公司');
	    }
	    $companyId=$company['companyId'];
	    $data=[
	        'companyId'=>$companyId,
	        'typeId'=>$typeId,
	        'expId'=>$expId,
	        'num'=>$num,
	        'sex'=>$sex,
	        'salaryId'=>$salaryId,
	        'balance'=>$balance,
	        'age'=>$age,
	        'jobDesc'=>$jobDesc,
	        'demand'=>$demand,
	    ];
	    if($jobId){
	        $res=$this->job_model->update($jobId,$data);
	    }else{
	        $res=$this->job_model->add($data);	        
	    }
	     
	    if ($res){
	        show200('操作成功');
	    }
	    else{
	        show400('操作失败');
	    }
	}
	
	/**
	 *@title 关闭职位
	 *@desc 关闭职位
	 *	 
	 *@input {"name":"jobId","require":"","type":"int","desc":"职位ID"}
	 *	 
	 *@output {"name":"code","type":"int","desc":"200:成功,400失败,3:未登录,300各种提示信息"}
	 *@output {"name":"msg","type":"string","desc":"信息说明"}
	 * */
	public function job_close(){
	    $jobId=$this->input->post('jobId');
	    
	    if(!$jobId){
	        show300('职位ID不正确');
	    }
	    
	    $data=[
	        'status'=>1,	        
	    ];
	    
	    $res=$this->job_model->update($jobId,$data);
	    
	    if ($res){
	        show200('操作成功');
	    }
	    else{
	        show400('操作失败');
	    }
	}
	
	/**
	 *@title 开启职位
	 *@desc 关闭职位
	 *
	 *@input {"name":"jobId","require":"","type":"int","desc":"职位ID"}
	 *
	 *@output {"name":"code","type":"int","desc":"200:成功,400失败,3:未登录,300各种提示信息"}
	 *@output {"name":"msg","type":"string","desc":"信息说明"}
	 * */
	public function job_open(){
	    $jobId=$this->input->post('jobId');
	     
	    if(!$jobId){
	        show300('职位ID不正确');
	    }
	     
	    $data=[
	        'status'=>0,
	    ];
	     
	    $res=$this->job_model->update($jobId,$data);
	     
	    if ($res){
	        show200('操作成功');
	    }
	    else{
	        show400('操作失败');
	    }
	}

}
/* End of file Login.php */