<?php
use phpDocumentor\Reflection\Types\This;

defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * 我的订单
* */
class Order {
    private static $data = array();
    private $models=['orderlist_model','refund_model','project_model','comment_model'];
    public function __construct()
    {
        parent::__construct($this->models);
        
    }
    
    /**
     *@title 我的订单(项目)
     *@desc 我的订单
     *@input {"name":"userId","require":"true","type":"string","desc":"登录用户ID"}
     *@input {"name":"status","type":"int","desc":"订单状态,1未付款,2已付款未使用,3已使用未评价,4已评价,5退款中"}
     *@input {"name":"page","type":"string","desc":"页数,不传默认为0"}
     *@input {"name":"limit","type":"string","desc":"每页显示条数,不传默认为20"}
     *     
     *@output {"name":"code","type":"int","desc":"200:获取成功,400获取失败,无数据"}
     *@output {"name":"msg","type":"string","desc":"获取成功/获取失败"}
     *
     *@output {"name":"data","type":"array","desc":"订单数组,下面是详细说明,index代表该数组下标"}
     *@output {"name":"data[index].orderId","desc":"预约订单ID","child":"1"}
     *@output {"name":"data[index].projectId","desc":"项目ID","child":"1"}
     *@output {"name":"data[index].name","desc":"公司名称","child":"1"}
     *@output {"name":"data[index].logo","desc":"公司LOGO链接","child":"1"}
     *@output {"name":"data[index].title","desc":"项目标题","child":"1"}
     *@output {"name":"data[index].price","desc":"订单单价","child":"1"}
     *@output {"name":"data[index].num","desc":"购买数量","child":"1"}
     *@output {"name":"data[index].totalPrice","desc":"订单总价","child":"1"}
     *@output {"name":"data[index].technicianName","desc":"技师的名称,没有技师则为NULL","child":"1"}
     *@output {"name":"data[index].createTime","desc":"订单生成时间","child":"1"}
     *@output {"name":"data[index].status","desc":"预付款订单状态,1未付款,2已付款未使用,3已使用未评价,4已评价","child":"1"}
     *@output {"name":"data[index].refundStatus","desc":"退款状态,0待审核,1财务初审未通过,2财务初审通过,3财务复核未通过,4财务复核通过,5退款成功","child":"1"}
     * */
    public function project()
    {   
        $userId=$this->input->post('userId');       //登录用户ID
        if (!$userId){
            show3();
        }
        $status=$this->input->post('status');       //订单状态标记
        $page=$this->input->post('page');
        $limit=$this->input->post('limit');
        $page=empty($page)?0:$page;
        $limit=empty($size)?20:$limit;
        $offset=$page*$limit;
        $where=['orderlist.userId'=>$userId,'typeFlag'=>1];      //只获取预付款订单,将与其相关联的尾款订单做join显示
        if ($status){
            $where['orderlist.status']=$status;
        }
        $orderlist = $this->orderlist_model->getUserOrder($where, $limit, $offset);
        foreach ($orderlist as $key=>$val){
            $orderlist[$key]['createTime']=format_time($val['createTime']);
        }
        if($orderlist){
            show200($orderlist);
        }else{
            show400();
        }
    }
    
    /**
     *@title 订单详情
     *@desc 订单详情
     *@input {"name":"userId","require":"true","type":"string","desc":"登录用户ID"}
     *@input {"name":"orderId","require":"true","type":"string","desc":"订单Id"}
     *
     *@output {"name":"code","type":"int","desc":"200:获取成功,400获取失败,无数据"}
     *@output {"name":"msg","type":"string","desc":"获取成功/获取失败"}
     *
     *@output {"name":"data","type":"array","desc":"订单数组,下面是详细说明,index代表该数组下标"}
     *@output {"name":"data.orderId","desc":"预约订单ID","child":"1"}
     *@output {"name":"data.projectId","desc":"项目ID","child":"1"}
     *@output {"name":"data.name","desc":"公司名称","child":"1"}
     *@output {"name":"data.logo","desc":"公司LOGO链接","child":"1"}
     *@output {"name":"data.address","desc":"公司地址","child":"1"}
     *@output {"name":"data.phone","desc":"公司联系方式","child":"1"}
     *@output {"name":"data.title","desc":"项目标题","child":"1"}
     *@output {"name":"data.price","desc":"订单单价","child":"1"}
     *@output {"name":"data.num","desc":"购买数量","child":"1"}
     *@output {"name":"data.totalPrice","desc":"订单总价","child":"1"}
     *@output {"name":"data.technicianName","desc":"技师的名称,没有技师则为NULL","child":"1"}
     *@output {"name":"data.createTime","desc":"订单生成时间","child":"1"}
     *@output {"name":"data.trade_no","desc":"订单编号","child":"1"}
     *@output {"name":"data.status","desc":"预付款订单状态,1未付款,2已付款未使用,3已使用未评价,4已评价","child":"1"}
     *@output {"name":"data.refundStatus","desc":"退款状态,0待审核,1财务初审未通过,2财务初审通过,3财务复核未通过,4财务复核通过,5退款成功","child":"1"}
     * */
    public function getOrderDetail()
    {
        $userId=$this->input->post('userId');       //登录用户ID
        if (!$userId){
            show3();
        }
        $orderId=$this->input->post('orderId');       //订单ID
        if(!$orderId){
            show300('订单ID错误');
        }
        $order=$this->orderlist_model->getDetail($orderId,$userId);
        
        if(!$order){
            show400('获取订单数据失败');
        }
        $order['createTime']=format_time($order['createTime']);
        
        show200($order,'获取成功');
    }
    
    
    /**
     *@title 获取订单评价数据
     *@desc 获取订单评价数据
     *@input {"name":"userId","require":"true","type":"string","desc":"登录用户ID"}
     *@input {"name":"orderId","require":"true","type":"string","desc":"订单Id"}
     *
     *@output {"name":"code","type":"int","desc":"200:获取成功,400获取失败,无数据"}
     *@output {"name":"msg","type":"string","desc":"获取成功/获取失败"}
     *
     *@output {"name":"data","type":"array","desc":"评价数据"}
     *@output {"name":"data.companyComment","desc":"对公司(店铺)的评价","child":"1"}
     *@output {"name":"data.companyComment.content","desc":"评价内容","child":"2"}
     *@output {"name":"data.companyComment.judge","desc":"评价分值","child":"2"}
     *@output {"name":"data.companyComment.name","desc":"公司(店铺)名称","child":"2"}
     *@output {"name":"data.companyComment.logo","desc":"公司(店铺)LOGO","child":"2"}
     *
     *@output {"name":"data.projectComment","desc":"对项目的评价","child":"1"}
     *@output {"name":"data.companyComment.content","desc":"评价内容","child":"2"}
     *@output {"name":"data.companyComment.judge","desc":"评价分值","child":"2"}
     *@output {"name":"data.companyComment.title","desc":"项目标题","child":"2"}
     *@output {"name":"data.companyComment.poster","desc":"项目封面图片","child":"2"}
     *
     *@output {"name":"data.technicianComment","desc":"对技师的评价","child":"1"}
     *@output {"name":"data.companyComment.content","desc":"评价内容","child":"2"}
     *@output {"name":"data.companyComment.judge","desc":"评价分值","child":"2"}
     *@output {"name":"data.companyComment.realName","desc":"技师名称","child":"2"}
     *@output {"name":"data.companyComment.sevriceNo","desc":"技师编号","child":"2"}
     *@output {"name":"data.companyComment.avatar","desc":"技师头像","child":"2"}
     *@output {"name":"data.companyComment.tags","desc":"技师职称:如高级技师等","child":"2"}
     *
     * */
    public function getOrderComments(){
        $userId=$this->input->post('userId');       //登录用户ID
        if (!$userId){
            show3();
        }
        $orderId=$this->input->post('orderId');       //订单ID
        if(!$orderId){
            show300('订单ID错误');
        }
        $companyComment=$this->comment_model->getComment_company($orderId,$userId);
        $projectComment=$this->comment_model->getComment_project($orderId,$userId);
        $technicianComment=$this->comment_model->getComment_technician($orderId,$userId);
        $data=[
            'companyComment'=>$companyComment,
            'projectComment'=>$projectComment,
            'technicianComment'=>$technicianComment
        ];
        if($data){
            show200($data);
        }else{
            show400();
        }
    }
    
    
    
    
    
    
    
    
    
    
    
}