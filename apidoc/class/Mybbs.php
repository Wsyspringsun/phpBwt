<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Mybbs {
    private static $data = array();
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Menu_model','menu');//菜单表
        $this->load->model('Dzlist_model','dz');//论坛表
        $this->load->model('Dzlistfile_model','dzfile');//论坛表
        $this->load->model('Commentmessage_model','comment');//公共
        $this->load->model('Webcommon_model','common');//公共
        $this->load->model('User_model','user');//用户表表
        $this->load->model('Userinfo_model','userinfo');//基本信息表
        $this->load->model('Usercount_model','usercount');//基本信息表
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
        $this->load->model('Singer_model','singer');
        $this->load->model('Usermessage_model','message');//私信
        $this->load->model('Userfollow_model','userfollow');//关注的人
        $this->load->model('Musicalstyle_model','style');
        $this->load->model('Dzjita_model','jita');
        $this->load->model('Userfollow_model','follow');
        $this->load->model('Visitor_model','visitor');
        $this->load->model('Sign_model','sign');
        $this->load->model('app_common_model');       //app接口统一输出格式
    }
    //我的话题(论坛帖子)已发布
    public function bbs_audited()
    {       
       $res=$this->common();
       $start=$this->input->get_post('start');
       $limit=20;        //总共显示数据
       if (!$start){
           $start=0;     //起始下标  ,加载更多传递的参数
       }else{
           $start=$start*$limit;
       }
       //论坛:最后一个参数是是否通过审核的标记位1:已通过 0:待审核 3草稿 2拒绝
       $select='userId,brief,listId,title,createTime,viewNum,commentNum,zanNum';
       $searchparam['menuId'] = -1;
       $searchparam['status'] = 1;  //已通过标记位       //         echo $status;
       
       $searchparam['userId'] = $res['userId'];
       $bbsList = $this->dz->getDzListMyList($searchparam,$select, $limit, $start);
       //$bbsList = $this->dz->getWebIndexList('userId,listId,title,createTime,menuId,typeFlag,twoMenuId,viewNum,commentNum,zanNum,isBetter', $limit, $start,1,$userId,1);
       
       foreach($bbsList as $key=>$value)
       {
           $bbsList[$key]['time'] = format_time($value['createTime']);
//            $image = $this->dzfile->getDzFileRow($value['listId'],0);
//            $bbsList[$key]['imagePath'] = $this->config->item('hwclouds').$image['docPath'];
            
       }
       
       $content=[
           'userInfo'=>$res['userInfo'],
           'bbsList'=>$bbsList
       ];
       $this->app_common_model->show_200($content);
    }
    //我的话题(论坛帖子)审核中
    public function bbs_auditing()
    {
        $res=$this->common();
        $start=$this->input->get_post('start');
        $limit=20;        //总共显示数据
        if (!$start){
            $start=0;     //起始下标  ,加载更多传递的参数
        }else{
            $start=$start*$limit;
        }
        //论坛:最后一个参数是是否通过审核的标记位1:已通过 0:待审核 3草稿 2拒绝
        $select='userId,brief,listId,title,createTime,viewNum,commentNum,zanNum';
        $searchparam['menuId'] = -1;
        
        $searchparam['status'] = 0;
        //         echo $status;
        
        $searchparam['userId'] = $res['userId'];
        $bbsList = $this->dz->getDzListMyList($searchparam,$select, $limit, $start);
        //$bbsList = $this->dz->getWebIndexList('userId,listId,title,createTime,menuId,typeFlag,twoMenuId,viewNum,commentNum,zanNum,isBetter', $limit, $start,1,$userId,1);
        
        foreach($bbsList as $key=>$value)
        {
            $bbsList[$key]['time'] = format_time($value['createTime']);
            //            $image = $this->dzfile->getDzFileRow($value['listId'],0);
            //            $bbsList[$key]['imagePath'] = $this->config->item('hwclouds').$image['docPath'];
            
        }
        
        $content=[
            'userInfo'=>$res['userInfo'],
            'bbsList'=>$bbsList
        ];
        $this->app_common_model->show_200($content);
    }
    //我的话题(论坛帖子)草稿
    public function bbs_caogao()
    {
        $res=$this->common();
        $start=$this->input->get_post('start');
        $limit=20;        //总共显示数据
        if (!$start){
            $start=0;     //起始下标  ,加载更多传递的参数
        }else{
            $start=$start*$limit;
        }
        //论坛:最后一个参数是是否通过审核的标记位1:已通过 0:待审核 3草稿 2拒绝
        $bbsList = $this->dz->getWebIndexDzList('userId,brief,listId,title,createTime,viewNum,commentNum,zanNum', $limit, $start,1,$res['userId'],0);
        
        //$bbsList = $this->dz->getWebIndexList('userId,listId,title,createTime,menuId,typeFlag,twoMenuId,viewNum,commentNum,zanNum,isBetter', $limit, $start,1,$userId,1);
        
        foreach($bbsList as $key=>$value)
        {
            $bbsList[$key]['time'] = format_time($value['createTime']);
            //            $image = $this->dzfile->getDzFileRow($value['listId'],0);
            //            $bbsList[$key]['imagePath'] = $this->config->item('hwclouds').$image['docPath'];
            
        }
        
        $content=[
            'userInfo'=>$res['userInfo'],
            'bbsList'=>$bbsList
        ];
        $this->app_common_model->show_200($content);
    }
    //删除贴子
    public function delete(){
        $listId=$this->input->get_post('listId');       //贴子ID
        if (!$listId){
            $this->app_common_model->show_401();
        }
        $res=$this->dz->delete($listId);
        if ($res) {
            $this->app_common_model->show_200(['msg'=>'删除成功'],'删除成功');
        }else{
            $this->app_common_model->show_400('删除失败');
        }        
    }
    private function common(){
        $userId=$this->input->get_post('userId');       //访问某一用户的ID
        if (!$userId){
            $this->app_common_model->show_401();
        }
        
        //获取个人信息
        $userInfo = $this->userinfo->getUserInfoRow($userId);
        if ($userInfo){
            $userInfo['headUrl'] = empty($userInfo['headUrl'])?'':$this->config->item('hwclouds').$userInfo['headUrl'];
        }
        
        $res=[
            'userId'=>$userId,
            'userInfo'=>$userInfo
        ];
        return $res;
    }
}