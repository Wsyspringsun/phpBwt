<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auction {
    private static $auctionFlag=2;       //多个地方用到的标记位,资讯:0 课程:1 竞拍:2
    private static $data = array();
    public function __construct()
    {
        parent::__construct();        
        $this->load->model('User_model','user_model');//用户模型    
        $this->load->model('Auction_model','auction_model');//资讯模型    
        $this->load->model('Auction_enroll_model','auction_enroll_model');//资讯模型   
        $this->load->model('Expert_model','expert_model');//资讯模型   
        $this->load->model('Tags_model','tags_model');//资讯模型    
        $this->load->model('Type_model','type_model');//分类模型    
        $this->load->model('Banner_model','banner_model');//轮播模型    
        $this->load->model('Comment_model','comment_model');//评论模型
        $this->load->model('Userhandle_model','handle_model');//操作日志
        $this->load->model('Collect_model','collect_model');//用户收藏模型
        $this->load->model('App_common_model','app_common_model');//通用功能模型    
        $this->load->model('City_model','city_model');//通用功能模型    
    }
    
    /**
     *@title 竞拍列表页
     *@desc 竞拍列表页        
     *
     *@output {"name":"code","type":"int","desc":"200:成功,400失败"}
     *@output {"name":"msg","type":"string","desc":"获取成功/获取失败"}
     *
     *@output {"name":"data","type":"array","desc":"数据数组"}
     *@output {"name":"data.banner","type":"array","desc":"轮播图数据","child":"1"}
     *@output {"name":"data.banner[index].bannerName","desc":"图片名称","child":"2"}
     *@output {"name":"data.banner[index].imagePath","desc":"图片路径","child":"2"}
     *
     *@output {"name":"data.type","type":"array","desc":"资讯分类","child":"1"}
     *@output {"name":"data.type[index].typeId","desc":"分类ID","child":"2"}
     *@output {"name":"data.type[index].typeName","desc":"分类名称","child":"2"}
     *
     *@output {"name":"data.auction","type":"array","desc":"竞拍数据,下面是详细说明,index表示数组下标","child":"1"}
     *@output {"name":"data.auction[index].auctionId","desc":"竞拍ID","child":"2"}
     *@output {"name":"data.auction[index].name","desc":"专家名称","child":"2"}
     *@output {"name":"data.auction[index].avatar","desc":"专家头像","child":"2"}
     *@output {"name":"data.auction[index].tags","type":"array","desc":"专家标签","child":"2"}
     *@output {"name":"data.auction[index].position","type":"string","desc":"专家位置","child":"2"}
     *@output {"name":"data.auction[index].seat_num","type":"int","desc":"竞拍席位","child":"2"}
     *@output {"name":"data.auction[index].typeId","type":"int","desc":"分类Id","child":"2"}
     * */
    public function home()
    {
        $banner=$this->banner_model->getBanner_auction();     //竞拍的banner
        $type=$this->type_model->getType_expert('typeId,typeName');      //资讯分类
        
        if (!isset($type[0]['typeId'])){
            show300('暂无分类数据');
        }        
        $auction=$this->getAuction($type[0]['typeId']);
        if($auction||$type||$banner){
            show200(['banner'=>$banner,'type'=>$type,'auction'=>$auction]);
        }else{
            show400();
        }
    }
    
    /**
     *@title 竞拍列表页加载更多接口
     *@desc 竞拍列表页加载更多接口
     *@input {"name":"typeId","require":"true","type":"string","desc":"分类Id"}
     *@input {"name":"page","type":"string","desc":"页数"}
     *@input {"name":"size","type":"string","desc":"每页显示条数"}
     *
     *@output {"name":"code","type":"int","desc":"200:成功,400失败,300返回提示信息"}
     *@output {"name":"msg","type":"string","desc":"获取成功/获取失败"}
     *     
     *@output {"name":"data","type":"array","desc":"竞拍数据,下面是详细说明,index表示数组下标","child":"0"}
     *@output {"name":"data[index].auctionId","desc":"竞拍ID","child":"1"}
     *@output {"name":"data[index].name","desc":"专家名称","child":"1"}
     *@output {"name":"data[index].avatar","desc":"专家头像","child":"1"}
     *@output {"name":"data[index].position","type":"string","desc":"专家位置","child":"1"}
     *@output {"name":"data[index].seat_num","type":"int","desc":"竞拍席位","child":"1"}
     *@output {"name":"data[index].typeId","type":"int","desc":"分类Id","child":"1"}
     *@output {"name":"data[index].pay_start_time","type":"string","desc":"开始竞拍时间","child":"1"}
     * */
    public function getMore_home()
    {
        $typeId=$this->input->post('typeId');
        $page=$this->input->post('page');
        $size=$this->input->post('size');
        if(!$typeId){
            show300('分类ID不正确');
        }
        
        $page=empty($page)?0:$page;
        $size=empty($size)?20:$size;
        
        $auction=$this->getAuction($typeId, $page, $size);
        if($auction){
            show200($auction);
        }else{
            show400();
        }
    }
    //获取竞拍数据
    private function getAuction($typeId,$page=0,$size=20){
        $offset=$page*$size;
        $auction=$this->auction_model->getHome($typeId,$offset,$size);        
        if($auction){
            foreach ($auction as $key=>$val){
                if($val['tags']){
                    $tags=$val['tags'];
                    $tagsArr=explode('|', $tags);
                    $auction[$key]['tags']=$tagsArr;
                }else{
                    $auction[$key]['tags']=[];       //没有标签,置为空数组
                }
            }
        }
        return $auction;
    }
    
    
    /**
     *@title 竞拍报名页
     *@desc 竞拍报名页
     *@input {"name":"auctionId","require":"true","type":"string","desc":"竞拍Id"}
     *
     *@output {"name":"code","type":"int","desc":"200:获取成功,400获取失败,无数据"}
     *@output {"name":"msg","type":"string","desc":"获取成功/获取失败"}     
     *
     *@output {"name":"data","type":"array","desc":"竞拍数据,下面是详细说明,index表示数组下标","child":"0"}
     *@output {"name":"data.auctionId","desc":"竞拍ID","child":"1"}
     *@output {"name":"data.name","desc":"专家名称","child":"1"}
     *@output {"name":"data.avatar","desc":"专家头像","child":"1"}
     *@output {"name":"data.tags","type":"array","desc":"专家标签","child":"1"}
     *@output {"name":"data.position","type":"string","desc":"专家位置","child":"1"}
     *@output {"name":"data.score","type":"string","desc":"专家评分","child":"1"}
     *@output {"name":"data.seat_num","type":"int","desc":"竞拍席位","child":"1"}
     *@output {"name":"data.address","type":"string","desc":"约见地址","child":"1"}
     *@output {"name":"data.content","type":"string","desc":"竞拍详情","child":"1"}
     *@output {"name":"data.start_time","type":"string","desc":"约见开始时间","child":"1"}
     *@output {"name":"data.end_time","type":"string","desc":"约见结束时间","child":"1"}
     *@output {"name":"data.pay_start_time","type":"string","desc":"开始竞拍时间","child":"1"}
     *@output {"name":"data.start_price","type":"string","desc":"起拍价","child":"1"}
     * */
    public function detail(){
        $auctionId=$this->input->post("auctionId");
        if (!$auctionId){
            show401('竞拍ID不正确');
        }
        
        $auction = $this->auction_model->getDetail($auctionId);      //获取竞拍详情
        if (!$auction){
            show400();
        }
          
        show200($auction);
    }
    
    /**
     *@title 竞拍报名接口
     *@desc 竞拍报名接口
     *@input {"name":"auctioID","require":"true","type":"string","desc":"竞拍ID"}
     *@input {"name":"userId","require":"true","type":"int","desc":"用户ID"}     
     *
     *@output {"name":"code","type":"int","desc":"200:成功,400失败,3:未登录,300各种提示信息"}
     *@output {"name":"msg","type":"string","desc":"返回信息;报名成功/失败"}
     * */
    public function enroll(){
        $auctionId=$this->input->post('auctionId');
        $userId=$this->input->post('userId');
        if(!$auctionId){
            show300('竞拍ID不正确');
        }
        if(!$userId){
            show3();
        }
        $where=['auctionId'=>$auctionId,'userId'=>$userId];
        $enroll=$this->auction_enroll_model->getRow($where);
        if($enroll){
            show300('您已报过名了');
        }
        $res=$this->auction_enroll_model->insert($where);
        if($res){
            show200([],'报名成功');
        }else {
            show400('报名失败');
        }        
    }
    
    public function trade(){
        //竞拍
    }
    
}
