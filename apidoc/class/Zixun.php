<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Zixun {
    private static $data = array();
    public function __construct()
    {
        parent::__construct();        
        $this->load->model('User_model','user_model');//用户模型    
        $this->load->model('Zixun_model','zixun_model');//资讯模型    
        $this->load->model('Type_model','type_model');//分类模型    
        $this->load->model('Banner_model','banner_model');//轮播模型    
        $this->load->model('Comment_model','comment_model');//评论模型
        $this->load->model('Userhandle_model','handle_model');//操作日志
        $this->load->model('App_common_model','app_common_model');//通用功能模型    
    }
    
    /**
     *@title 资讯主页
     *@desc 资讯主页接口     
     *@input {"name":"order","type":"string","desc":"首页资讯列表排序,默认为最新排序,如果要以最热排序请传此参数:值为'hot'"}
     *@output {"name":"code","type":"int","desc":"200:获取成功,400获取失败,无数据"}
     *@output {"name":"msg","type":"string","desc":"获取成功/获取失败"}
     *@output {"name":"data","type":"array","desc":"数据数组"}
     *@output {"name":"data.banner","desc":"轮播图数据","child":"1"}
     *@output {"name":"data.banner[index].bannerName","desc":"图片名称","child":"2"}
     *@output {"name":"data.banner[index].imagePath","desc":"图片路径","child":"2"}
     *@output {"name":"data.type","desc":"资讯分类","child":"1"}
     *@output {"name":"data.type[index].typeId","desc":"分类ID","child":"2"}
     *@output {"name":"data.type[index].typeName","desc":"分类名称","child":"2"}
     *@output {"name":"data.zixun","desc":"资讯数据","child":"1"}
     *@output {"name":"data.zixun[index].zixunId","desc":"资讯ID","child":"2"}
     *@output {"name":"data.zixun[index].title","desc":"资讯标题","child":"2"}
     *@output {"name":"data.zixun[index].poster","desc":"资讯封面","child":"2"}
     *@output {"name":"data.zixun[index].zanNum","desc":"点赞数","child":"2"}
     *@output {"name":"data.zixun[index].collectNum","desc":"收藏数","child":"2"}
     *@output {"name":"data.zixun[index].viewNum","desc":"浏览量","child":"2"}
     *@output {"name":"data.zixun[index].typeName","desc":"所属分类名称","child":"2"}
     *@output {"name":"data.zixun[index].typeId","desc":"所属分类ID","child":"2"}
     * */
    public function home()
    {
        $order=$this->input->post('order');
        if(!$order){
            $order='zixun.createTime';
        }else{
            $order='zixun.zanNum';
        }
        $banner=$this->banner_model->getBanner_zixun();     //轮播图
        $type=$this->type_model->getType_zixun('typeId,typeName');      //资讯分类
        
        $select='zixunId,title,concat("'.IMAGEHOST.'",poster) as poster,zanNum,collectNum,viewNum,typeName,zixun.typeId';
        $zixun=$this->zixun_model->getHome($select,$order);
        
        if($zixun||$type||$banner){
            show200(['banner'=>$banner,'type'=>$type,'zixun'=>$zixun]);
        }else{
            show400();
        }
    }
    
    /**
     *@title 资讯主页加载更多资讯
     *@desc 资讯主页加载更多资讯
     *@input {"name":"order","type":"string","desc":"首页资讯列表排序,默认为最新排序,如果要以最热排序请传此参数:值为'hot'"}
     *@input {"name":"page","type":"int","desc":"资讯分页数,从0开始","default":"0"}
     *@input {"name":"size","type":"int","desc":"一页显示的条数","default":"20"}
     *@output {"name":"code","type":"int","desc":"200:获取成功,400获取失败,无数据"}
     *@output {"name":"msg","type":"string","desc":"获取成功/获取失败"}
     *@output {"name":"data","type":"array","desc":"资讯数组,下面是详细说明,index代表该数组下标"}
     *@output {"name":"data.zixun[index].zixunId","desc":"资讯ID","child":"1"}
     *@output {"name":"data[index].title","desc":"资讯标题","child":"1"}
     *@output {"name":"data[index].poster","desc":"资讯封面","child":"1"}
     *@output {"name":"data[index].zanNum","desc":"点赞数","child":"1"}
     *@output {"name":"data[index].collectNum","desc":"收藏数","child":"1"}
     *@output {"name":"data[index].viewNum","desc":"浏览量","child":"1"}
     *@output {"name":"data[index].typeName","desc":"所属分类名称","child":"1"}
     *@output {"name":"data[index].typeId","desc":"所属分类ID","child":"1"}
     * */
    public function getMore_home()
    {
        $order=$this->input->post('order');
        $page=$this->input->post('page');
        $size=$this->input->post('size');
        if(!$order){
            $order='zixun.createTime';
        }else{
            $order='zixun.zanNum';
        }
        if(!$page){
            $page=0;
        }
        if(!$size){
            $size=20;
        }
        $offset=$page*$size;        //数据库起始偏移量
        $select='zixunId,title,concat("'.IMAGEHOST.'",poster) as poster,zanNum,collectNum,viewNum,typeName,zixun.typeId';
        $zixun=$this->zixun_model->getHome($select,$order,$offset,$size);
        if($zixun){
            show200($zixun);
        }else{
            show400();
        }
    }
    
    /**
     *@title 资讯列表
     *@desc 资讯列表接口
     *@input {"name":"typeId","type":"int","desc":"资讯分类的分类ID,如果不传此参数则表示获取全部资讯"}
     *@input {"name":"page","type":"int","desc":"资讯分页数,从0开始","default":"0"}
     *@input {"name":"size","type":"int","desc":"一页显示的条数","default":"20"}
     *@output {"name":"code","type":"int","desc":"200:获取成功,400获取失败,无数据"}
     *@output {"name":"msg","type":"string","desc":"获取成功/获取失败"}
     *@output {"name":"data","type":"array","desc":"资讯数组,下面是详细说明,index代表该数组下标"}
     *@output {"name":"data[index].title","desc":"资讯标题","child":"1"}
     *@output {"name":"data[index].poster","desc":"资讯封面","child":"1"}
     *@output {"name":"data[index].zanNum","desc":"点赞数","child":"1"}
     *@output {"name":"data[index].collectNum","desc":"收藏数","child":"1"}
     *@output {"name":"data[index].viewNum","desc":"浏览量","child":"1"}
     *@output {"name":"data[index].typeName","desc":"所属分类名称","child":"1"}
     *@output {"name":"data[index].typeId","desc":"所属分类ID","child":"1"}
     * */
    public function zixunList()
    {       
        $typeId=$this->input->post('typeId');
        $page=$this->input->post('page');
        $size=$this->input->post('size');
        if(!$page){
            $page=0;
        }
        if(!$size){
            $size=20;
        }
        $offset=$page*$size;        //数据库起始偏移量
        $select='zixunId,title,concat("'.IMAGEHOST.'",poster) as poster,zanNum,collectNum,viewNum,typeName,zixun.typeId';
        $zixun=$this->zixun_model->getList($typeId,$select,$offset,$size);
        
        if($zixun){
            show200($zixun);
        }else{
            show400();
        }        
    }
    
    
    /**
     *@title 资讯详情页
     *@desc 资讯详情页
     *@input {"name":"zixunId","require":"true","type":"string","desc":"资讯Id"}
     *@input {"name":"userId","type":"int","desc":"用户ID,如果用户已登录则传此参数,用来判断用户是否已点赞等"}
     *
     *@output {"name":"code","type":"int","desc":"200:获取成功,400获取失败,无数据"}
     *@output {"name":"msg","type":"string","desc":"获取成功/获取失败"}
     *@output {"name":"data","type":"array","desc":"资讯数组,下面是详细说明,index代表该数组下标"}
     *@output {"name":"data.zixunId","desc":"资讯ID","child":"1"}
     *@output {"name":"data.title","desc":"资讯标题","child":"1"}
     *@output {"name":"data.zanNum","desc":"点赞数","child":"1"}
     *@output {"name":"data.collectNum","desc":"收藏数","child":"1"}
     *@output {"name":"data.viewNum","desc":"浏览量","child":"1"}  
     *@output {"name":"data.brief","desc":"简介","child":"1"} 
     *@output {"name":"data.content","desc":"内容","child":"1"}    
     *@output {"name":"data.createTime","desc":"创建时间","child":"1"} 
     *@output {"name":"data.is_zan","type":"boolean","desc":"是否点过赞,是:true,否:flase","child":"1"} 
     *@output {"name":"data.createTime","desc":"创建时间","child":"1"} 
     *@output {"name":"data.commentNum","type":"int","desc":"评论数量","child":"1"} 
     *@output {"name":"data.commentList","type":"array","desc":"评论数组,下面是字段详细说明,index表示数组下标","child":"1"} 
     *@output {"name":"data.commentList[index].userId","type":"string","desc":"评论人ID","child":"2"}
     *@output {"name":"data.commentList[index].nickName","type":"string","desc":"评论人昵称","child":"2"}
     *@output {"name":"data.commentList[index].avatar","type":"string","desc":"评论人头像","child":"2"}
     *@output {"name":"data.commentList[index].commentId","type":"string","desc":"评论ID","child":"2"} 
     *@output {"name":"data.commentList[index].content","type":"string","desc":"评论内容","child":"2"}
     *@output {"name":"data.commentList[index].createTime","type":"string","desc":"评论时间","child":"2"}
     *@output {"name":"data.commentList[index].zanNum","type":"string","desc":"对该评论的点赞数量","child":"2"}
     *@output {"name":"data.commentList[index].replyNum","type":"string","desc":"对该评论的回复数量","child":"2"}
     *@output {"name":"data.commentList[index].is_zan","type":"boolean","desc":"当前登录用户是否对该评论点过赞,是:true,否:false","child":"2"}
     * */
    public function detail()
    {
        $zixunId=$this->input->post('zixunId');
        if (!$zixunId){
            show300('资讯ID不正确');
        }
        $userId=$this->input->post('userId');        
        $this->zixun_model->updateNum($zixunId,array('viewNum'=>'viewNum+1'));      //更新浏览量(+1)        
        
        $select='zixunId,title,brief,content,zanNum,commentNum,createTime';
        $zixun = $this->zixun_model->getDetail($zixunId ,$select);      //获取资讯详情
        if (!$zixun){
            show400();
        }
        $zixun['createTime'] = format_time(@$zixun['createTime']);      //格式化创建时间
        
        
        $is_zan=$this->app_common_model->is_zan($userId,$zixunId,0);   //$typeFlag:0是资讯 1是约见
        //$is_collect=$this->app_common_model->is_collect($userId,$zixunId,0);        
        
        $comment=$this->app_common_model->comment($zixunId,$userId,0);     //获取评论,0是资讯 1是约见
        $zixun['is_zan']=$is_zan;
        //$zixun['is_collect']=$is_collect;
        $zixun['commentNum']=$comment['commentNum'];
        $zixun['commentList']=$comment['commentList'];
        
        show200($zixun);
    }
    
    /**
     *@title 加载更多评论
     *@desc 加载更多评论
     *@input {"name":"zixunId","require":"true","type":"string","desc":"资讯Id"}     *
     *@input {"name":"page","type":"int","require":"true","desc":"评论分页的页数"}
     *@input {"name":"userId","type":"int","desc":"用户ID,如果用户已登录则传此参数,用来判断用户是否已点赞等"}
     *@output {"name":"code","type":"int","desc":"200:获取成功,400获取失败,无数据"}
     *@output {"name":"msg","type":"string","desc":"获取成功/获取失败"}
     *@output {"name":"data","type":"array","desc":"评论数组,下面是字段详细说明,index表示数组下标"} 
     *@output {"name":"data[index].userId","type":"string","desc":"评论人ID","child":"1"}
     *@output {"name":"data[index].nickName","type":"string","desc":"评论人昵称","child":"1"}
     *@output {"name":"data[index].avatar","type":"string","desc":"评论人头像","child":"1"}
     *@output {"name":"data[index].commentId","type":"string","desc":"评论ID","child":"1"} 
     *@output {"name":"data[index].content","type":"string","desc":"评论内容","child":"1"}
     *@output {"name":"data[index].createTime","type":"string","desc":"评论时间","child":"1"}
     *@output {"name":"data[index].zanNum","type":"string","desc":"对该评论的点赞数量","child":"1"}
     *@output {"name":"data[index].replyNum","type":"string","desc":"对该评论的回复数量","child":"1"}
     *@output {"name":"data[index].is_zan","type":"boolean","desc":"当前登录用户是否对该评论点过赞,是:true,否:false","child":"1"}
     * */
    public function getMore_comment()
    {        
        $zixunId=$this->input->post('zixunId');
        if (!$zixunId){
            show300('资讯ID不正确');
        }
        $userId=$this->input->post('userId');   
        $page=$this->input->post('page');
        $page=empty($page)?0:$page;
        $comments=$this->app_common_model->comment($zixunId,$userId,0,$page);     //获取评论,0是资讯 1是约见
        
        $comment=$comments['commentList'];
        
        if (!empty($comment)){
            show200($comment);
        }else {
            show400();
        }
    }
    
    /**
     *@title 为资讯点赞
     *@desc 为资讯点赞
     *@input {"name":"zixunId","require":"true","type":"string","desc":"资讯Id"}
     *@input {"name":"userId","type":"int","require":"true","desc":"当前登录用户ID"}       
     *   
     *@output {"name":"code","type":"int","desc":"200:点赞成功,400:点赞失败"}
     *@output {"name":"msg","type":"string","desc":"点赞成功/点赞失败"}
     * */
    public function clickZan()
    {
        $userId = $this->input->post('userId');
        $zixunId = $this->input->post('zixunId');
        if (!$userId){
            show3();
        }
        if(!$zixunId){
            show401('资讯ID错误');
        }
        $data = array(
                'userId' =>$userId,
                'listId' => $zixunId,
                'createTime' => time(),
                'typeFlag' => 0,        //点赞
                'flag' => 0         //资讯
                );
        $bool = $this->handle_model->insertData($data);
        if ($bool){
            $this->zixun_model->updateNum($zixunId,array('zanNum'=>'zanNum+1'));  
            show200([],'点赞成功');
        }else{
            show400('点赞失败');
        }
    }
    
    /**
     *@title 为资讯评论点赞
     *@desc 为资讯评论点赞
     *@input {"name":"commentId","require":"true","type":"string","desc":"评论Id"}
     *@input {"name":"userId","type":"int","require":"true","desc":"当前登录用户ID"}       
     *   
     *@output {"name":"code","type":"int","desc":"200:点赞成功,400:点赞失败"}
     *@output {"name":"msg","type":"string","desc":"点赞成功/点赞失败"}
     * */
    public function clickZan_comment()
    {
        $userId = $this->input->post('userId');
        $commentId = $this->input->post('commentId');
        if (!$userId){
            show3();
        }
        if(!$commentId){
            show401('评论ID错误');
        }
        $data = array(
            'userId' =>$userId,
            'commentId' => $commentId,
            'createTime' => time(),
            'typeFlag' => 0,
            'flag' => 0
        );
        $bool = $this->handle_model->insertData($data);
        if ($bool){
            $this->comment_model->updateNum(array('zanNum'=>'zanNum+1'),$commentId);
            show200([],'点赞成功');
        }else{
            show400('点赞失败');
        }       
    }
    
    /**
     *@title 评论
     *@desc 对资讯评论或对某条评论进行回复
     *@input {"name":"zixunId","require":"true","type":"string","desc":"资讯Id"}
     *@input {"name":"userId","type":"int","require":"true","desc":"当前登录用户ID"}
     *@input {"name":"content","type":"string","require":"true","desc":"评论内容"}
     *@input {"name":"commentId","type":"int","require":"false","desc":"某条评论的ID,如果是对资讯进行评论则不用传此参数,如果是对评论进行回复则需要传此参数"}
     *
     *@output {"name":"code","type":"int","desc":"3:未登录,300:一些提示信息,200:成功,400:失败,401:参数有误,"}
     *@output {"name":"msg","type":"string","desc":"评论成功/评论失败"}
     * */
    public function do_comment()
    {
        $userId = $this->input->get_post('userId');
        $zixunId = $this->input->get_post('zixunId');
        $content=$this->input->post('content');
        $commentId=$this->input->post('commentId');
        if (!$userId){
            show3();
        }
        if(!$zixunId){
            show401('资讯ID错误');
        }
        if(!trim($content)){
            show300('请输入内容');
        }        
        $data=[
            'listId'=>$zixunId,
            'userId'=>$userId,
            'content'=>$content,            
            'createTime'=>time(),
            'typeFlag'=>0
        ];
        if ($commentId){        //如果有评论ID这个参数则说明是对评论进行的回复
            $data['tocommentId']=$commentId;
            $data['replyFlag']=1;       //对评论进行回复的标记
        }
        $res=$this->comment->insertData($data);
        if ($res){
            $this->zixun_model->updateNum($zixunId,['commentNum'=>'commentNum+1']);         //资讯评论数+1
            if($commentId){
                $this->comment_model->updateNum(array('replyNum'=>'replyNum+1'),$commentId);    //某评论的回复数+1
            }            
            show200([],'评论成功');
        }else{
            show400('评论失败');
        }
    }
    
    /**
     *@title 评论详情页
     *@desc 评论详情页(对某一评论进行的回复数据列表)
     *@input {"name":"commentId","require":"true","type":"string","desc":"评论Id"}
     *
     *@output {"name":"code","type":"int","desc":"200:成功,400:失败"}
     *@output {"name":"msg","type":"string","desc":"数据获取成功/数据获取失败"}
     *@output {"name":"data","type":"obj","desc":"评论数据,下面是详细字段说明"}
     *@output {"name":"data.commentId","type":"string","desc":"评论ID","child":"1"}
     *@output {"name":"data.userId","type":"string","desc":"评论人ID","child":"1"}
     *@output {"name":"data.content","type":"string","desc":"评论内容","child":"1"}
     *@output {"name":"data.zanNum","type":"string","desc":"该评论的点赞数","child":"1"}
     *@output {"name":"data.createTime","type":"string","desc":"评论时间","child":"1"}
     *@output {"name":"data.nickName","type":"string","desc":"评论人昵称","child":"1"}
     *@output {"name":"data.avatar","type":"string","desc":"评论人头像","child":"1"}
     *@output {"name":"data.replyList","type":"array","desc":"对评论进行的回复列表,下面是详细说明,index代表下标","child":"1"}
     *@output {"name":"data.replyList[index].commentId","type":"string","desc":"回复ID","child":"2"}
     *@output {"name":"data.replyList[index].userId","type":"string","desc":"回复人ID","child":"2"}
     *@output {"name":"data.replyList[index].content","type":"string","desc":"回复内容","child":"2"}
     *@output {"name":"data.replyList[index].createTime","type":"string","desc":"回复时间","child":"2"}
     *@output {"name":"data.replyList[index].nickName","type":"string","desc":"回复人昵称","child":"2"}
     *@output {"name":"data.replyList[index].avatar","type":"string","desc":"回复人头像","child":"2"}
     * */
    public function comment_detail(){
        $commentId=$this->input->get_post('commentId');        //音乐ID
        if(!$commentId){
            show401('评论ID有误');
        }
        
        $select='commentId,comment.userId,content,zanNum,comment.createTime,nickName,avatar';
        $comment=$this->comment->getDzCommentRow($commentId,$select);       //评论数据
        if($comment){
            $replyList=$this->app_common_model->getReply($commentId);       //回复数据
            $content=$comment;
            $content['replayList']=$replyList;
            show200($content);
        }else {
            show400('数据获取失败');
        }
    }
    
}
