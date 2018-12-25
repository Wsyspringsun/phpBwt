<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Course {
    private static $courseFlag=1;       //多个地方用到的标记位,资讯:0 课程:1
    private static $data = array();
    public function __construct()
    {
        parent::__construct();        
        $this->load->model('User_model','user_model');//用户模型    
        $this->load->model('Course_model','course_model');//资讯模型    
        $this->load->model('Expert_model','expert_model');//资讯模型   
        $this->load->model('Tags_model','tags_model');//资讯模型    
        $this->load->model('Type_model','type_model');//分类模型    
        $this->load->model('Banner_model','banner_model');//轮播模型    
        $this->load->model('Comment_model','comment_model');//评论模型
        $this->load->model('Userhandle_model','handle_model');//操作日志
        $this->load->model('App_common_model','app_common_model');//通用功能模型    
        $this->load->model('City_model','city_model');//通用功能模型    
    }
    
    /**
     *@title 约见主页
     *@desc 约见主页接口     
     *@input {"name":"order","type":"string","desc":"首页资讯列表排序,默认为最新排序,如果要以最热排序请传此参数:值为'hot'"}
     *
     *@output {"name":"code","type":"int","desc":"200:成功,400失败"}
     *@output {"name":"msg","type":"string","desc":"获取成功/获取失败"}
     *@output {"name":"data","type":"array","desc":"数据数组"}
     *@output {"name":"data.banner","type":"array","desc":"轮播图数据","child":"1"}
     *@output {"name":"data.banner[index].bannerName","desc":"图片名称","child":"2"}
     *@output {"name":"data.banner[index].imagePath","desc":"图片路径","child":"2"}
     *
     *@output {"name":"data.type","type":"array","desc":"资讯分类","child":"1"}
     *@output {"name":"data.type[index].typeId","desc":"分类ID","child":"2"}
     *@output {"name":"data.type[index].typeName","desc":"分类名称","child":"2"}
     *
     *@output {"name":"data.expert","type":"array","desc":"专家数据,下面是详细说明,index表示数组下标","child":"1"}
     *@output {"name":"data.expert[index].expertId","desc":"专家ID","child":"2"}
     *@output {"name":"data.expert[index].name","desc":"专家名称","child":"2"}
     *@output {"name":"data.expert[index].avatar","desc":"专家头像","child":"2"}
     *@output {"name":"data.expert[index].brief","desc":"专家简介","child":"2"}
     *@output {"name":"data.expert[index].tags","type":"array","desc":"专家标签","child":"2"}
     * */
    public function home()
    {
        $order=$this->input->post('order');
        if(!$order){
            $order='createTime';
        }else{
            $order='zanNum';
        }
        $banner=$this->banner_model->getBanner_yuejian();     //轮播图
        $type=$this->type_model->getType_course('typeId,typeName');      //资讯分类
        
        $select='expertId,name,concat("'.IMAGEHOST.'",avatar) as avatar,tags,brief';
        $expert=$this->expert_model->getHome($select,$order);
        if($expert){
            foreach ($expert as $key=>$val){
                if($val['tags']){
                    $tags=$val['tags'];
                    $tagsArr=explode('|', $tags);
                    $expert[$key]['tags']=$tagsArr;
                }else{
                    $expert[$key]['tags']=[];       //没有标签,置为空数组
                }
            }
        }
        if($expert||$type||$banner){
            show200(['banner'=>$banner,'type'=>$type,'expert'=>$expert]);
        }else{
            show400();
        }
    }
    
    /**
     *@title 约见主页加载更多专家数据
     *@desc 约见主页加载更多专家数据
     *@input {"name":"order","type":"string","desc":"首页资讯列表排序,默认为最新排序,如果要以最热排序请传此参数:值为'hot'"}
     *@input {"name":"page","type":"int","desc":"资讯分页数,从0开始","default":"0"}
     *@input {"name":"size","type":"int","desc":"一页显示的条数","default":"20"}
     *
     *@output {"name":"code","type":"int","desc":"200:成功,400失败"}
     *@output {"name":"msg","type":"string","desc":"获取成功/获取失败"}
     *     
     *@output {"name":"data.expert","type":"array","desc":"专家数据,下面是详细说明,index表示数组下标","child":"0"}
     *@output {"name":"data.expert[index].expertId","desc":"专家ID","child":"1"}
     *@output {"name":"data.expert[index].name","desc":"专家名称","child":"1"}
     *@output {"name":"data.expert[index].avatar","desc":"专家头像","child":"1"}
     *@output {"name":"data.expert[index].brief","desc":"专家简介","child":"1"}
     *@output {"name":"data.expert[index].tags","type":"array","desc":"专家标签","child":"1"}
     * */
    public function getMore_home()
    {
        $order=$this->input->post('order');
        $page=$this->input->post('page');
        $size=$this->input->post('size');
        if(!$order){
            $order='createTime';
        }else{
            $order='zanNum';
        }
        if(!$page){
            $page=0;
        }
        if(!$size){
            $size=20;
        }
        $offset=$page*$size;        //数据库起始偏移量
        $select='expertId,name,concat("'.IMAGEHOST.'",avatar) as avatar,brief,tags';
        $expert=$this->expert_model->getHome($select,$order,$offset,$size);
        if($expert){
            foreach ($expert as $key=>$val){
                if($val['tags']){
                    $tags=$val['tags'];
                    $tagsArr=explode('|', $tags);
                    $expert[$key]['tags']=$tagsArr;
                }else{
                    $expert[$key]['tags']=[];       //没有标签,置为空数组
                }
            }
        }
        if($expert){
            show200($expert);
        }else{
            show400();
        }
    }
    
    /**
     *@title 专家课程页
     *@desc 专家课程页
     *@input {"name":"expertId","require":"true","type":"string","desc":"专家Id"}
     *
     *@output {"name":"code","type":"int","desc":"200:获取成功,400获取失败,无数据"}
     *@output {"name":"msg","type":"string","desc":"获取成功/获取失败"}     
     *
     *@output {"name":"data","type":"array","desc":"专家数据,下面是详细说明,index代表该数组下标"}
     *@output {"name":"data.expertId","desc":"专家ID","child":"1"}
     *@output {"name":"data.name","desc":"专家名称","child":"1"}    
     *@output {"name":"data.brief","desc":"专家简介","child":"1"}
     *
     *@output {"name":"data.commentNum","type":"string","desc":"课程数量","child":"1"} 
     *@output {"name":"data.courseList","type":"array","desc":"课程数组,下面是字段详细说明,index表示数组下标","child":"1"}
     *@output {"name":"data.courseList[index].courseId","type":"array","desc":"课程ID","child":"2"}
     *@output {"name":"data.courseList[index].title","type":"array","desc":"课程标题","child":"2"}
     *@output {"name":"data.courseList[index].price","type":"array","desc":"课程价格","child":"2"}
     *@output {"name":"data.courseList[index].start_time","type":"array","desc":"课程开始时间","child":"2"}
     *@output {"name":"data.courseList[index].end_time","type":"array","desc":"课程结束时间","child":"2"}
     *
     *@output {"name":"data.commentNum","type":"string","desc":"评论条数","child":"1"}     
     *@output {"name":"data.commentList","type":"array","desc":"评论数组,下面是字段详细说明,index表示数组下标","child":"1"}
     *@output {"name":"data.commentList[index].userId","type":"string","desc":"评论人ID","child":"2"}
     *@output {"name":"data.commentList[index].nickName","type":"string","desc":"评论人昵称","child":"2"}
     *@output {"name":"data.commentList[index].avatar","type":"string","desc":"评论人头像","child":"2"}
     *@output {"name":"data.commentList[index].commentId","type":"string","desc":"评论ID","child":"2"}
     *@output {"name":"data.commentList[index].content","type":"string","desc":"评论内容","child":"2"}
     *@output {"name":"data.commentList[index].createTime","type":"string","desc":"评论时间","child":"2"}
     *@output {"name":"data.commentList[index].score","type":"string","desc":"评分","child":"2"}
     *@output {"name":"data.commentList[index].judge","type":"string","desc":"评价,0:未进行评价,1:好评,2:中评,3:差评","child":"2"}
     * */
    public function expert_course(){
        $expertId=$this->input->post("expertId");
        if (!$expertId){
            show300('专家ID不正确');
        }
        
        $select='expertId,name,tags,score,concat("'.IMAGEHOST.'",avatar) as avatar,concat("'.IMAGEHOST.'",banner) as banner,brief,position';
        $expert = $this->expert_model->getDetail($expertId ,$select);      //获取专家详情
        if (!$expert){
            show400();
        }
        $expert['tags']=explode('|', $expert['tags']);
        $courseList=$this->course_model->getCourse_expert($expertId);
        $commentNum=$this->comment_model->getCommentNum_expert($expertId);
        
        $expert['commentNum']=$commentNum;      //评论总数
        $expert['commentList']=$this->getComment_expert($expertId);     //评论列表
        $expert['courseNum']=count($courseList);        //课程总数
        $expert['courseList']=$courseList;      //课程列表
        show200($expert);
    }
    
    /**
     *@title 加载更多评论
     *@desc 加载更多评论
     *@input {"name":"expertId","require":"true","type":"string","desc":"专家Id"}    
     *@input {"name":"page","type":"int","require":"true","desc":"评论分页的页数,第一次加载更多,则page=1,以此类推"}
     *
     *@output {"name":"code","type":"int","desc":"200:获取成功,400获取失败,无数据"}
     *@output {"name":"msg","type":"string","desc":"获取成功/获取失败"}
     *
     *@output {"name":"data","type":"array","desc":"评论数组,下面是字段详细说明,index表示数组下标","child":"0"}
     *@output {"name":"data[index].userId","type":"string","desc":"评论人ID","child":"1"}
     *@output {"name":"data[index].nickName","type":"string","desc":"评论人昵称","child":"1"}
     *@output {"name":"data[index].avatar","type":"string","desc":"评论人头像","child":"1"}
     *@output {"name":"data[index].commentId","type":"string","desc":"评论ID","child":"1"}
     *@output {"name":"data[index].content","type":"string","desc":"评论内容","child":"1"}
     *@output {"name":"data[index].createTime","type":"string","desc":"评论时间","child":"1"}
     *@output {"name":"data[index].judge","type":"string","desc":"评价,0:未进行评价,1:好评,2:中评,3:差评","child":"1"}
     * */
    public function getMoreComment_expert(){
        $expertId=$this->input->post("expertId");
        if (!$expertId){
            show300('专家ID不正确');
        }
        $page=$this->input->post('page');
        $size=$this->input->post('size');        
        $page=empty($page)?0:$page;
        $size=empty($size)?20:$size;
        $offset=$page*$size;
        $comment=$this->getComment_expert($expertId,$offset,$size);
        
        if (!empty($comment)){
            show200($comment);
        }else {
            show400();
        }
    }
    //获取专家评论,并格式化评价时间 
    private function getComment_expert($expertId,$offset=0,$limit=20){
        $commentList=$this->comment_model->comment_expert($expertId,$offset,$limit);
        foreach ($commentList as $key=>$val){
            $commentList[$key]['createTime']=format_time($val['createTime']);
        }
        return $commentList;
    }
    
    /**
     *@title 专家详情页
     *@desc 专家详情页
     *@input {"name":"expertId","require":"true","type":"string","desc":"专家Id"}
     *
     *@output {"name":"code","type":"int","desc":"200:获取成功,400获取失败,无数据"}
     *@output {"name":"msg","type":"string","desc":"获取成功/获取失败"}     
     *
     *@output {"name":"data","type":"array","desc":"专家数据,下面是详细说明,index代表该数组下标"}
     *@output {"name":"data.expertId","desc":"专家ID","child":"1"}
     *@output {"name":"data.name","desc":"专家名称","child":"1"}
     *@output {"name":"data.brief","desc":"专家简介","child":"1"}         
     * */
    public function expert_detail(){
        $expertId=$this->input->post("expertId");
        if (!$expertId){
            show300('专家ID不正确');
        }
        
        $select='expertId,name,tags,concat("'.IMAGEHOST.'",avatar) as avatar,concat("'.IMAGEHOST.'",banner) as banner,brief,intro,position';
        $expert = $this->expert_model->getDetail($expertId ,$select);      //获取专家详情
        if (!$expert){
            show400();
        }
        $expert['tags']=explode('|', $expert['tags']);        
        show200($expert);
    }
    
    /**
     *@title 课程列表
     *@desc 课程列表接口
     *@input {"name":"typeId","type":"int","desc":"课程分类的分类ID,如果不传此参数则表示获取全部课程"}
     *@input {"name":"page","type":"int","desc":"课程分页数,从0开始","default":"0"}
     *@input {"name":"size","type":"int","desc":"一页显示的条数","default":"20"}
     *@input {"name":"order","type":"string","desc":"排序方式;new:最新,hot:最热,score:评分,不传则默认按最新排序","default":"new"}
     *
     *@output {"name":"code","type":"int","desc":"200:获取成功,400获取失败,无数据"}
     *@output {"name":"msg","type":"string","desc":"获取成功/获取失败"}
     *@output {"name":"data","type":"array","desc":"课程数组,下面是详细说明,index代表该数组下标"}
     *@output {"name":"data[index].courseId","desc":"课程ID","child":"1"}
     *@output {"name":"data[index].title","desc":"课程标题","child":"1"}
     *@output {"name":"data[index].brief","desc":"课程简介","child":"1"}
     *@output {"name":"data[index].avatar","desc":"专家头介","child":"1"}
     *@output {"name":"data[index].name","desc":"专家名称","child":"1"}
     *@output {"name":"data[index].tags","type":"array","desc":"专家标签","child":"1"}
     *@output {"name":"data[index].start_time","desc":"课程开始时间","child":"1"}
     *@output {"name":"data[index].end_time","desc":"课程结束时间","child":"1"}
     * */
    public function courseList()
    {       
        $typeId=$this->input->post('typeId');
        $page=$this->input->post('page');
        $size=$this->input->post('size');
        $order=$this->input->post('order');
        if(!$page){
            $page=0;
        }
        if(!$size){
            $size=20;
        }
        $offset=$page*$size;        //数据库起始偏移量
        if($order=='hot'){
            $order='course.zanNum';
        }elseif ($order=='score'){
            $order='course.score';
        }else{
            $order='course.createTime';
        }
        $select='courseId,title,course.brief,concat("'.IMAGEHOST.'",avatar) as avatar,name,tags,start_time,end_time';
        $course=$this->course_model->getList($typeId,$select,$order,$offset,$size);
        if(!$course) show400();
        
        foreach ($course as $key=>$val){
            $course[$key]['tags']=explode('|', $val['tags']);
        }
        show200($course);
    }
    
    /**
     *@title 课程预约页面
     *@desc 课程预约页面(课程信息)
     *@input {"name":"courseId","require":"true","type":"string","desc":"课程Id"}
     *@input {"name":"userId","type":"int","desc":"用户ID,如果用户已登录则传此参数,用来判断用户是否已收藏,预约或竞拍"}
     *
     *@output {"name":"code","type":"int","desc":"200:获取成功,400获取失败,无数据"}
     *@output {"name":"msg","type":"string","desc":"获取成功/获取失败"}     
     *     
     *@output {"name":"data","type":"array","desc":"课程数据"}
     *@output {"name":"data.courseId","desc":"课程ID","child":"0"}
     *@output {"name":"data.title","desc":"课程标题","child":"0"}
     *@output {"name":"data.brief","desc":"课程简介","child":"0"}
     *@output {"name":"data.avatar","desc":"专家头像","child":"0"}
     *@output {"name":"data.name","desc":"专家名称","child":"0"}
     *@output {"name":"data.expert_score","desc":"专家评分","child":"0"}
     *@output {"name":"data.courseNum","desc":"专家开设课程数","child":"0"}
     *@output {"name":"data.tags","type":"array","desc":"专家标签","child":"0"}
     *@output {"name":"data.start_time","desc":"课程开始时间","child":"0"}
     *@output {"name":"data.end_time","desc":"课程结束时间","child":"0"}     
     * */
    public function courseInfo(){
        $courseId=$this->input->post("courseId");
        if (!$courseId){
            show300('课程ID不正确');
        }
        
        $select='courseId,title,course.brief,content,concat("'.IMAGEHOST.'",avatar) as avatar,name,tags,start_time,end_time,expert.score as expert_score,courseNum';
        $course = $this->course_model->getDetail($courseId ,$select);      //获取课程详情
        if (!$course){
            show400();
        }
        $course['tags']=explode('|', $course['tags']);        
        show200($course);
    }
    
    
}
