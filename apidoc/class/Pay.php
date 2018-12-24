<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Pay {
    private static $data = array();
    protected $models=['orderlist_model','project_model','technician_model','gift_model','user_mode','notice_model','refund_model'];
    public function __construct()
    {
        parent::__construct($this->models); 
    }
    /**
     *@title 支付通用接口
     *@desc 支付通用接口(该接口用于生成新订单,如果是对已有未付款订单进行支付的话请调用下一个支付接口)
     *
     *@input {"name":"userId","require":"true","type":"string","desc":"当前登录用户的ID"}
     *@input {"name":"listId","require":"true","type":"string","desc":"项目或礼物Id"}
     *@input {"name":"num","type":"int","desc":"购买数量"}
     *@input {"name":"typeFlag","require":"true","type":"string","desc":"类型:1项目,2给技师送礼物"}
     *@input {"name":"technicianId","type":"int","desc":"技师Id,该参数用于对项目里的某个技师付款时的参数或用户对技师发送的礼物,没有则不传"}
     *
     *
     *@output {"name":"code","type":"int","desc":"200:成功,400失败,3:未登录,300各种提示信息"}
     *@output {"name":"msg","type":"string","desc":"返回信息;取消收藏成功/失败"}
     *
     *@output {"name":"data","type":"array","desc":"返回小程序支付接口必要参数"}
     * */
    public function pay(){
        
        $userId=$this->input->post('userId');
        $listId=$this->input->post('listId');
        $num=$this->input->post('num');
        $typeFlag=$this->input->post('typeFlag');
        $technicianId=$this->input->post('technicianId');
        if(!$userId){
            show3();
        }
        if(!$listId||!$typeFlag){
            show401('必填参数不能为空');
        }
        $num=empty($num)?1:$num;
        $payInfo=$this->getPayInfo($listId, $typeFlag);        //获取支付相关信息,如课程或竞拍的价格,标题等
        $pay_money=$payInfo['pay_money'];
        $title=$payInfo['title'];
        $openId=$this->getOpenIdByUserId($userId);
        $trade_no=date("YmdHis").(intval($userId)+666);
        $totalPrice=$pay_money*$num;
        $data=[
            'trade_no'=>$trade_no,
            'listId'=>$listId,
            'userId'=>$userId,
            'typeFlag'=>$typeFlag,
            'price'=>$pay_money,
            'num'=>$num,
            'totalPrice'=>$totalPrice,
            'payTitle'=>$title
        ];
        if($technicianId){
            $data['technicianId']=$technicianId;
        }
        $orderId=$this->orderlist_model->insert($data);
        if($orderId){
            $this->wxpay($openId, $trade_no, $title, $totalPrice,$orderId,$trade_no);
        }else{
            show400('生成订单失败');
        }
    }
    /**
     *@title 对未付款的订单进行重新付款
     *@desc 对未付款的订单进行重新付款(用于个人中心里订单列表中未付款的订单)
     *
     *@input {"name":"userId","require":"true","type":"string","desc":"当前登录用户的ID"}
     *@input {"name":"orderId","require":"true","type":"string","desc":"项目或礼物Id"}
     *
     *@output {"name":"code","type":"int","desc":"200:成功,400失败,3:未登录,300各种提示信息"}
     *@output {"name":"msg","type":"string","desc":"返回信息;取消收藏成功/失败"}
     *
     *@output {"name":"data","type":"array","desc":"返回小程序支付接口必要参数"}
     * */
    public function payByOrderId(){
        $orderId=$this->input->post('orderId');
        $userId=$this->input->post('userId');
        if(!$userId){
            show3();
        }
        if(!$orderId){
            show401('订单Id不能为空');
        }
        
        $trade_no=$this->getTradeNumByOrderId($orderId);     //获取订单编号 
                
        $order=$this->orderlist_model->getWhereRow(['orderId'=>$orderId]);
        if($order){
            $openId=$this->getOpenIdByUserId($userId);            
            $this->wxpay($openId, $trade_no, $order['payTitle'], $order['totalPrice'],$orderId,$order['trade_no']);            
        }else{
            show400('获取订单信息失败');
        }
    }
    //获取订单号
    private function getTradeNumByOrderId($orderId){
        $order=$this->orderlist_model->getWhereRow(['orderId'=>$orderId]);
        if(!@$order){
            show300('订单数据获取失败');
        }
        $trade_no=$order['trade_no'];        //订单编辑
        if(!$trade_no){
            show300('订单编号出错,请联系管理员');
        }
        return $trade_no;
    }
    private function getOpenIdByUserId($userId){
        $user=$this->user_model->getWhereRow(['userId'=>$userId]);
        $openId=$user['wxId'];
        if(!$openId){
            show300('用户参数有误');
        }
        return $openId;
    }
    //获取支付相关信息,如课程或竞拍的价格,标题等
    private function getPayInfo($listId,$typeFlag,$technicianId){
        $price=0;
        $title='';  //支付标题
        if($typeFlag==1){   //项目
            $project=$this->project_model->getWhereRow(['projectId'=>$listId]);
            if($project){
                $price=$project['price'];                
                $title=$project['title'];
            }else{
                show401('项目数据为空,无法进行支付操作');
            }
            
        }elseif ($typeFlag==2){ //给技师送礼物
            $gift=$this->gift_model->getWhereRow(['giftId'=>$listId]);
            if($gift){
                if(!$technicianId) show401('给技师送礼物时,技师ID不能为空');
                $technician=$this->technician_model->getWhereRow(['technicianId'=>$technicianId]);
                if(!$technician) show300('技师数据获取失败');
                $price=$gift['price'];                
                $title='送给'.$technician['realName'].'的'.$gift['name'];
            }else{
                show401('没有该礼物数据,无法进行支付操作');
            }
            
        }else{
            show300('typeFlag参数只能为1或2');
        }
        $data=['pay_money'=>$price,'title'=>$title];
        return $data;
    }
    //微信支付调起函数
    private function wxpay($openId,$trade_no,$title,$pay_money,$orderId,$trade_no){
        require_once "/application/libraries/wxpay/WeixinPay.php";
        $wxpay=new WeixinPay($openId,$trade_no, $title, $pay_money*100);
        $res=$wxpay->pay();
        $res["orderId"] = $orderId;
        $res['trade_no']=$trade_no;
        show200($res);
    }
    
    
    /**
     *@title 退款接口
     *@desc 退款接口
     *
     *@input {"name":"userId","require":"true","type":"string","desc":"当前登录用户的ID"}
     *@input {"name":"orderId","require":"true","type":"string","desc":"要退款的订单ID,此参数在约见里对应预付款订单ID:advanceOrderId和尾款订单ID:tailOrderId,按需传参即可"}
     *@input {"name":"typeFlag","require":"true","type":"string","desc":"类型标记:1约见,2竞拍"}
     *@input {"name":"reason","require":"true","type":"string","desc":"退款原因"}
     *@input {"name":"desc","require":"true","type":"string","desc":"退款说明"}
     *
     *
     *@output {"name":"code","type":"int","desc":"200:成功,400失败,3:未登录,300各种提示信息"}
     *@output {"name":"msg","type":"string","desc":"返回信息;取消收藏成功/失败"}
     *
     * */
    public function refund(){
        
        $userId=$this->input->post('userId');
        $orderId=$this->input->post('orderId');
        $typeFlag=$this->input->post('typeFlag');
        $reason=$this->input->post('reason');
        $desc=$this->input->post('desc');       
        
        if(!$userId){
            show3();
        }
        if(!$orderId){
            show300('订单ID不能为空');
        }
        if(!$reason){
            show300('退款原因不能为空');
        }
        if(!$desc){
            show300('退款说明不能为空');
        }
        $where=['userId'=>$userId,'orderId'=>$orderId];     //查询条件和数据数组
        $payInfo=$this->orderlist_model->getWhere($where);
        if(!$payInfo){
            show300('没有该订单信息,请与管理员联系');
        }
        
        $refund=$this->refund_model->getWhere($where);
        if($refund){
            show300('您的已提交过退款申请,请耐心等待管理员审核');
        }
        $refund_no='refund'.date("YmdHis").(intval($userId)+666);       //退款单号
        $data=$where;
        $data['refund_no']=$refund_no;
        $data['reason']=$reason;
        $data['desc']=$desc;
        $res=$this->refund_model->insert($data);
        if($res){
            $this->addNotice($userId);
            show200('退款申请提交成功');
        }else{
            show400('退款申请提交失败,请重试');
        }
    }
    
    //添加消息列表通知
    private function addNotice($userId){        
        $noticeContent='您的退款申请已提交，请等待客服确认。';        
        $this->notice_model->insert(['userId'=>$userId,'noticeContent'=>$noticeContent]);  //添加消息列表通知
    }
    
}    
    