<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
* @title 处理私募订单
* @desc  处理私募订单
**/
class Funding extends CI_Controller 
{
    public function __construct()
    {
        parent::__construct();  
	$this->load->model(array('bill_model','member_model'));		
        $this->load->library(array('sms/api_demo/SmsDemo'));
    }

    /** 接受私募的会员商操作区域 **/

    

    /**
    * @title 判断运营商余额是否符合出售条件
    * @desc  判断运营商余额是否符合出售条件,少于后台指定的参数数值就无法销售
    * @input {"name":"sale_member_id","require":"true","type":"int","desc":"运营商id"}	
    * @output {"name":"msg","type":"string","desc":"信息说明"}
    public function valid_saleable()
    {
        $this -> valid_fund_time();
    }
    **/
    
    /**
    * @title 返回购买的DTSC数量，并判断是否足够
    * @desc  返回购买的DTSC数量，并判断是否足够
    * @input {"name":"sale_member_id","require":"true","type":"int","desc":"运营商id"}	
    * @input {"name":"usdt_amount","require":"true","type":"int","desc":"运营商id"}	
    * @output {"name":"msg","type":"string","desc":"信息说明"}
    * @output {"name":"dtsc_amount","type":"float","desc":"兑换的原始资产数量"}
    public function valid_buy_amount()
    {
        $this -> valid_fund_time();
    }
    **/
    

    /**
    * @title 当前用户私募订单
    * @desc  如果存在获取当前用户私募订单,否则不存在可显示兑换页面
    **/
    public function get_buy_fund_bill_detail()
    {
        //判断交易时间
        $rec = $this -> valid_fund_time();
        //获取登录用户，没有登录用户则推出
        $loginer_id = $this->session->tempdata('id');
        if($loginer_id == null){
            show300("请先登录");
        }
        $member = $this->member_model->getwhereRow(['id' => $loginer_id],'*');
        $bill = $this->bill_model -> getCurrentBuyFundingBill($loginer_id);
        if($bill != null){
            $bill -> usdt_code =  $rec -> usdt_code;
        }
        show200($bill);

    }

    /**
    * @title 获取兑换的数量
    * @desc  获取兑换的数量
    * @output {"name":"msg","type":"string","desc":"信息说明"}
    * @output {"name":"data","type":"string","desc":"可兑换USDT数值范围,usdt_min:最小,usdt_max:最大"}
    **/
    public function get_usdt_amount_span(){
        //判断交易时间
        $rec = $this -> valid_fund_time();
        $span = array(
            "usdt_min" => $rec -> usdt_min,
            "usdt_max" => $rec -> usdt_max,
        );
        $member_res = $this -> bill_model -> getBillOutline($rec -> sale_member_id);
        //获取冻结的资产
        $foren_amount = $this -> bill_model -> getForenOriginAmountByFunding($rec -> sale_member_id);
        $enable_origin_amount = $member_res -> origin_amount - $foren_amount;
        if($enable_origin_amount < 2000){
            $max_usdt_amount = (int)($enable_origin_amount / $rec -> exchange_percent);
            $span["usdt_max"] = $max_usdt_amount;
        }  
        show200($span);
    }

    /**
    * @title 确认兑换下单
    * @desc  确认兑换下单
    * @input {"name":"usdt_amount","require":"true","type":"int","desc":"支付的usdt数量"}	
    * @input {"name":"pwd_second","require":"true","type":"string","desc":"买家会员二级密码"}	
    * @input {"name":"china_id","require":"true","type":"int","desc":"身份证id"}
    * @output {"name":"msg","type":"string","desc":"信息说明"}
    * @output {"name":"data.id","type":"string","desc":"订单id"}
    * @output {"name":"data.usdt_code","type":"string","desc":"收款码"}
    **/
    public function confirm_exchange()
    {
        //判断交易时间
        $rec = $this -> valid_fund_time();
        //获取登录用户，没有登录用户则推出
        $loginer_id = $this->session->tempdata('id');
        if($loginer_id == null){
            show300("请先登录");
        }
        $member = $this->member_model->getwhereRow(['id' => $loginer_id],'*');

        if($member["pwd_second"] != $this->input->post("pwd_second") ){
            show300("二级密码错误");
        }
        if($member["china_id"] != $this->input->post("china_id") ){
            //show300("身份证号错误");
        }

        $current_bill = $this->bill_model -> getCurrentBuyFundingBill($loginer_id);
        if($current_bill != null){
            show300("存在未完成的订单");
        }

        $usdt_amount = $this -> input -> post("usdt_amount");
        if($usdt_amount == null){
            show300("缺少USDT数量");
        }
        //验证支付数量
        if($usdt_amount > 2000 || $usdt_amount < 200){
            show300("兑换的USDT在200到2000之间");
        }
        $dtsc_amount = $usdt_amount * $rec -> exchange_percent;

        $member_res = $this -> bill_model -> getBillOutline($rec -> sale_member_id);
        if($member_res == null){
            show300("缺少会员资产信息");
        }
        //获取冻结的资产
        $foren_amount = $this -> bill_model -> getForenOriginAmountByFunding($rec -> sale_member_id);
        $enable_origin_amount = $member_res -> origin_amount - $foren_amount;
        if($enable_origin_amount <= $dtsc_amount){
            $max_usdt_amount = (int)($enable_origin_amount / $rec -> exchange_percent);
            show300("您的权限只能兑出".$max_usdt_amount." USDT");
        } 

        $bill = array(
            "dtsc_amount" => $dtsc_amount,
            "usdt_amount" => $usdt_amount,
            "stat" => "0",
            "funding_bill_no" => get_bill_unique_id($loginer_id),
            "buy_member_id" => $loginer_id,
            "sale_member_id" => $rec -> sale_member_id
        );
        $data = $this -> bill_model -> create_funding_bill($bill);
        if(is_string($data)){
            show300($data);
        }else{
            if($data <= 0){
                show300("错误影响行数，请联系管理员");
            }else{
                show200(array(
                    "id" => $data,
                    "usdt_code" => $rec -> usdt_code,
                ));

            }
        }

    }

    /**
    * @title 取消兑换下单
    * @desc  取消兑换下单
    **/
    public function cancel_exchange(){
        $this -> valid_fund_time();
        //获取登录用户，没有登录用户则推出
        $loginer_id = $this->session->tempdata('id');
        if($loginer_id == null){
            show300("请先登录");
        }
        $id = $this -> input -> post("id");
        if($id == null){
            show300("缺少订单id");
        }

        $data = $this -> bill_model -> update_funding_bill($loginer_id, $id, "X");
        if(is_string($data) ){
            show300($data);
        }else{
            if($data == true){
                show200($data);
            }else{
                show300("未知错误");
            }
        }


    }


    /**
    * @title 买家完成转账后点完成付款
    * @desc  买家完成转账后点完成付款
    * @input {"name":"id","require":"true","type":"string","desc":"订单id"}	
    * @output {"name":"msg","type":"string","desc":"信息说明"}
    * @output {"name":"data","type":"string","desc":"订单信息"}
    **/
    public function confirm_usdt_payed()
    {
        $this -> valid_fund_time();
        //获取登录用户，没有登录用户则推出
        $loginer_id = $this->session->tempdata('id');
        if($loginer_id == null){
            show300("请先登录");
        }
        $id = $this -> input -> post("id");
        if($id == null){
            show300("缺少订单id");
        }

        $data = $this -> bill_model -> update_funding_bill($loginer_id, $id, "1");
        if(is_string($data) ){
            show300($data);
        }else{
            show200($this -> bill_model -> getFundingBillById($id));
        }

    }
    
    /**
    * @title 买家确认收到了Dtsc原始资产
    * @desc  买家确认收到了Dtsc原始资产
    * @input {"name":"id","require":"true","type":"string","desc":"订单id"}	
    * @output {"name":"msg","type":"string","desc":"信息说明"}
    * @output {"name":"data","type":"string","desc":"订单信息"}
    public function confirm_dtsc_arrive()
    {
        $this -> valid_fund_time();
        //获取登录用户，没有登录用户则推出
        $loginer_id = $this->session->tempdata('id');
        if($loginer_id == null){
            show300("请先登录");
        }
        $id = $this -> input -> post("id");
        if($id == null){
            show300("缺少订单id");
        }

        $data = $this -> bill_model -> update_funding_bill($loginer_id, $id, "S");
        if(is_string($data) ){
            show300($data);
        }else{
            show200($this -> bill_model -> getFundingBillById($id));
        }

    }

    **/
    
    /** 接受私募的会员操作区域 End **/


    /** 运营商操作区域 **/

    /**
    * @title 卖家确认收到付款后放币
    * @desc  卖家确认收到付款后放币
    * @input {"name":"id","require":"true","type":"string","desc":"订单id"}	
    * @output {"name":"msg","type":"string","desc":"信息说明"}
    * @output {"name":"data","type":"string","desc":"true:操作成功"}

    **/
    public function confirm_usdt_arrive()
    {
        $this -> valid_fund_time();
        //获取登录用户，没有登录用户则推出
        $loginer_id = $this->session->tempdata('id');
        if($loginer_id == null){
            show300("请先登录");
        }
        $id = $this -> input -> post("id");
        if($id == null){
            show300("缺少订单id");
        }

        
        $data = $this -> bill_model -> update_funding_bill($loginer_id, $id, "S");
        if(is_string($data) ){
            show300($data);
        }else{
            if($data == true){
                //发送短信
                $bill = $this -> bill_model -> getFundingBillDetail($id);
                $dtsc_amount = $bill -> dtsc_amount;
                $buyer_res = $this -> bill_model -> getBillOutline($bill -> buy_member_id);
                $saler_res = $this -> bill_model -> getBillOutline($bill -> sale_member_id);
                $sms = new SmsDemo();
                $resBuy = $sms->sendSms($bill -> buy_member_mobile, "SMS_155357227", SMS_SIGN,['customer'=>$bill -> buy_member_username, 'amount' => $dtsc_amount, 'balance' =>$buyer_res -> origin_amount]);
                $resSale = $sms->sendSms($bill -> sale_member_mobile, "SMS_155455571", SMS_SIGN,['customer'=>$bill -> sale_member_username, 'amount' => $dtsc_amount, 'balance' => $saler_res -> origin_amount]);
                show200(true);
                
            }else{
                show300("未知错误");
            }
        }
    }

    /**
    * @title 运营商的私募交易单列表
    * @desc  运营商的私募交易单列表
    * @input {"name":"page","require":"true","type":"int","desc":"页码，1开始"}	
    * @output {"name":"data.dtsc_amount","type":"string","desc":"兑入原始资产数量"}
    * @output {"name":"data.usdt_amount","type":"string","desc":"兑出USDT数量"}
    * @output {"name":"data.stat","type":"string","desc":"状态-申请:0,买家确认付款:1,卖家确认收款:2,买家确认收币:S,买家撤销或订单作废:X"}
    * @output {"name":"data.funding_bill_no","type":"string","desc":"订单编号"}
    * @output {"name":"data.pay_date","type":"string","desc":"买家付款日期"}
    * @output {"name":"data.confirm_date","type":"string","desc":"卖家确认收款日期"}
    * @output {"name":"data.buy_member_id","type":"string","desc":"买家id"}
    * @output {"name":"data.sale_member_id","type":"string","desc":"卖家id"}
    * @output {"name":"data.complete_date","type":"string","desc":"买家确认收币日期"}
    * @output {"name":"data.buy_member_username","type":"string","desc":"买家用户名(放到ID位置)"}
    * @output {"name":"data.buy_member_mobile","type":"string","desc":"买家手机号"}
    **/
    public function fund_bill_list()
    {
        //获取登录用户，没有登录用户则推出
        $loginer_id = $this->session->tempdata('id');
        if($loginer_id == null){
            show300("请先登录");
        }

        $page = $this->input->post('page') == null ? 1 : $this->input->post('page');
        $offset = $this -> getPage($page,PAGESIZE) ;
        $data = $this -> bill_model -> getFundingBillList($loginer_id, $offset);
        if(is_string($data)){
            show300($data);
        }else{
            show200($data);
        }
    }

    /** 运营商操作区域 End **/


    /** 系统参数操作**/

    /**
    * @title 获取私募倒计时
    * @desc  获取私募倒计时,如果是0 就是已经结束了
    * @input {"name":"machine_id","require":"true","type":"int","desc":"矿机id"}	
    * @output {"name":"msg","type":"string","desc":"信息说明"}
    * @output {"name":"data.start_date","type":"string","desc":"私募开始日期"}
    * @output {"name":"data.stop_date","type":"string","desc":"私募结束日期"}
    * @output {"name":"data.exchange_percent","type":"string","desc":"兑换比例"}
    * @output {"name":"data.total_days","type":"string","desc":"活动总天数"}
    * @output {"name":"data.cnt_days","type":"string","desc":"活动剩余天数"}
    * @output {"name":"data.is_busy","type":"string","desc":"是否繁忙,true:禁止交易,false:允许交易"}
    **/
    public function cnt_days()
    {
        $rec = $this -> valid_fund_time();
        $member_id = $rec -> sale_member_id;
        $data = array(
            "start_date" => $rec -> start_date,
            "stop_date" => $rec -> stop_date,
            "exchange_percent" => $rec -> exchange_percent,
            "total_days" => $rec -> total_days,
            "cnt_days" => $rec -> cnt_days,
            "sale_member_id" => $member_id,
            "is_busy" => false
        );
        //判断资产是否允许继续募集
        $member_res = $this -> bill_model -> getBillOutline($member_id);
        $foren_amount = $this -> bill_model -> getForenOriginAmountByFunding($member_id);
        if(($member_res -> origin_amount - $foren_amount) <= $rec -> lateast_origin_amount){
            $data["is_busy"] = true;
        } 
        if($rec != null){
            show200($data);
        }
    }

    //验证是否处于可交易时间
    private function valid_fund_time(){
        $rec = $this -> bill_model -> getFundingRec();
        if($rec -> cnt_days <= 0){
            show300("私募活动未开放");
        }
        return $rec;
    }

    /** 系统参数操作 End**/

}
