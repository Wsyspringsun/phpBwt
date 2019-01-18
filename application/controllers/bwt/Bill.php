<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/**
* @title 处理各种订单业务
* @desc  处理各种订单业务
**/
class Bill extends CI_Controller 
{
    private static $data = array();
    public function __construct()
    {
        parent::__construct();  
	$this->load->model(array('bill_model','member_model'));		
        $this->load->library(array('sms/api_demo/SmsDemo','weixin/wechatCallbackapiTest'));
    }

    /** 处理矿机订单Start **/

    /**
    * @title 购买矿机
    * @desc  执行矿机购买流程
    * @input {"name":"machine_id","require":"true","type":"int","desc":"矿机id"}	
    * @input {"name":"bill_hour_amount","require":"true","type":"int","desc":"租用时长"}	
    * @output {"name":"code","type":"int","desc":"200:成功,300各种提示信息"}
    * @output {"name":"msg","type":"string","desc":"信息说明"}
    **/
    public function buy_machine()
    {
        //:改session
        $loginer_id = $this->session->tempdata('id');
        if($loginer_id == null){
            show300("请先登录");
        }
        $requires = array("machine_id"=>"缺少矿机id","bill_hour_amount"=>"缺少租用时长");
        $params = array();
        foreach($requires as $k => $v)
        {
            if($this->input->post($k) == null){
                show300($v);
            }
            $params[$k] = $this -> input -> post($k);
        }
        
        $params["member_id"] = $loginer_id;
        //TODO:用参数控制,定量为3600小时
        $params["bill_hour_amount"] = 3600;
        $data = $this -> bill_model -> buyMachine($params);
        if($data > 0){
            show200($data);
        }else{
            show300($data);
        }
    }

    /**
    * @title 购买矿机清单列表
    * @desc  购买矿机清单列表
    * @input {"name":"page","require":"true","type":"int","desc":"页码，1开始"}	
    * @input {"name":"type","require":"true","type":"int","desc":"可用:0,过期:1"}	
    * @output {"name":"code","type":"int","desc":"200:成功,300各种提示信息"}
    * @output {"name":"msg","type":"string","desc":"信息说明"}
    * @output {"name":"data.create_date","type":"timestamp","desc":"创建时间"}
    * @output {"name":"data.modify_date","type":"timestamp","desc":"更新时间"}
    * @output {"name":"data.member_id","type":"int(11)","desc":"会员id"}
    * @output {"name":"data.machine_id","type":"int(11)","desc":"矿机id"}
    * @output {"name":"data.machine_title","type":"varchar(45)","desc":"矿机名称"}
    * @output {"name":"data.bill_unit_produce","type":"double(16,6)","desc":"每小时产量"}
    * @output {"name":"data.bill_price","type":"double(16,6)","desc":"单位租金"}
    * @output {"name":"data.bill_hour_amount","type":"int(11)","desc":"租用时长"}
    * @output {"name":"data.bill_real_pay","type":"double(16,6)","desc":"花费金额"}
    * @output {"name":"data.prod_cnt","type":"int","desc":"运营时间(小时)"}
    * @output {"name":"data.prod_amount","type":"double(16,6)","desc":"已生产产量"}
    * @output {"name":"data.to_origin_amount","type":"double(16,6)","desc":"已领取数量"}
    * @output {"name":"data.bill_date_start","type":"datetime","desc":"租用起始时间"}
    * @output {"name":"data.bill_date_end","type":"datetime","desc":"租用截止时间"}
    */
    public function machine_bill_list()
    {
        //$loginer_id = 1;
        $loginer_id = $this->session->tempdata('id');
        if($loginer_id == null){
            show300("请先登录");
        }
        $type = $this->input->post('type') == null ? 0 : $this->input->post('type');
        $page = $this->input->post('page') == null ? 1 : $this->input->post('page');
        $offset = $this -> getPage($page,PAGESIZE) ;
        $data = $this -> bill_model -> machine_bill_list($loginer_id, $type, $offset);
        show200($data);
    }

    /**
    * @title 购买矿机清单详情
    * @desc  购买矿机清单详情
    * @input {"name":"id","require":"true","type":"int","desc":"订单id"}	
    * @output {"name":"code","type":"int","desc":"200:成功,300各种提示信息"}
    * @output {"name":"msg","type":"string","desc":"信息说明"}
    * @output {"name":"data.create_date","type":"timestamp","desc":"创建时间"}
    * @output {"name":"data.modify_date","type":"timestamp","desc":"更新时间"}
    * @output {"name":"data.member_id","type":"int(11)","desc":"会员id"}
    * @output {"name":"data.machine_id","type":"int(11)","desc":"矿机id"}
    * @output {"name":"data.machine_title","type":"varchar(45)","desc":"矿机名称"}
    * @output {"name":"data.bill_unit_produce","type":"double(16,6)","desc":"每小时产量"}
    * @output {"name":"data.bill_price","type":"double(16,6)","desc":"单位租金"}
    * @output {"name":"data.bill_hour_amount","type":"int(11)","desc":"租用时长"}
    * @output {"name":"data.bill_real_pay","type":"double(16,6)","desc":"花费金额"}
    * @output {"name":"data.prod_cnt","type":"int","desc":"已运营时间(小时)"}
    * @output {"name":"data.prod_cnt_other","type":"int","desc":"剩余运营时间(小时)"}
    * @output {"name":"data.prod_amount","type":"double(16,6)","desc":"已生产产量"}
    * @output {"name":"data.to_origin_amount","type":"double(16,6)","desc":"已领取数量"}
    * @output {"name":"data.ready_origin_amount","type":"double(16,6)","desc":"可领取数量"}
    * @output {"name":"data.bill_date_start","type":"datetime","desc":"租用起始时间"}
    * @output {"name":"data.bill_date_end","type":"datetime","desc":"租用截止时间"}
    */
    public function machine_bill_detail()
    {
        $loginer_id = $this->session->tempdata('id');
        if($loginer_id == null){
            show300("请先登录");
        }
        $id = $this->input->post('id') ;
        if($id == null){
            show300("缺少订单id");
        }
        $data = $this -> bill_model -> machine_bill_detail($loginer_id, $id);

        if(is_string($data)){
            show300($data);
        }else{
            if($data != null){
                $data -> prod_cnt_other = $data -> bill_hour_amount - $data -> prod_cnt;
                $data -> ready_origin_amount = $data -> prod_amount - $data -> to_origin_amount;
                if($data -> bill_hour_amount <= $data -> prod_cnt){
                    $data -> stat = '过期';
                }else{
                    $data -> stat = '运行中';
                }
            }
            show200($data);
        }

        
    }


    /** 处理矿机订单 End **/

    /** 处理交易原始资产相关数据 **/

    /**
     * @title 购买原始资产下单
     * @desc  会员买入挂单
     * @input {"name":"pwd_second","require":"true","type":"string","desc":"买家会员二级密码"}	
     * @input {"name":"amount","require":"true","type":"int","desc":"购买数量"}	
     * @input {"name":"unit_price","require":"true","type":"int","desc":"单价"}	
     * @output {"name":"code","type":"int","desc":"200:成功,300各种提示信息"}
     * @output {"name":"msg","type":"string","desc":"信息说明"}
     * @output {"name":"data","type":"int","desc":"生成的订单id"}
     **/
    public function buy_bill_origin_res()
    {
        $requires = array("pwd_second"=>"缺少二级密码","amount"=>"缺少购买数量","unit_price"=>"缺少购买单价");
        $params = array();
        foreach($requires as $k => $v)
        {
            if($this->input->post($k) == null){
                show300($v);
            }
            $params[$k] = $this -> input -> post($k);
        }
        if($params["amount"] <= 0){
            show300("数量不得小于0");
        }
        //获取登录用户的id
        $loginer_id = $this->session->tempdata('id');
        if($loginer_id == null){
            show300("请先登录");
        }
        $buy_member = $this->member_model->getwhereRow(['id' => $loginer_id],'*');
        //交易时间为(1,2级9:00-21:00)和(2级以上:14:00-21:00)
        $this -> valid_trade_time($buy_member["member_lvl"]);
        //验证二级密码
        if($buy_member["pwd_second"] != $params["pwd_second"]){
            show300("二级密码错误");
        }
        $params["buy_member_id"] = $loginer_id;
        $data = $this -> bill_model -> buyOriginRes($params);
        if($data > 0){
            show200($data);
        }else{
            show300($data);
        }
    }

    /**
     * @title 撤销购买原始资产
     * @desc  撤销购买原始资产
     * @input {"name":"id","require":"true","type":"int","desc":"买单id"}	
     * @output {"name":"code","type":"int","desc":"200:成功,300各种提示信息"}
     * @output {"name":"msg","type":"string","desc":"信息说明"}
     * @output {"name":"data","type":"int","desc":"true是购买成功"}
    **/
    public function cancel_buy_bill_origin_res()
    {
        $requires = array("id"=>"缺少id");
        $params = array();
        foreach($requires as $k => $v)
        {
            if($this->input->post($k) == null){
                show300($v);
            }
            $params[$k] = $this -> input -> post($k);
        }
        //改成登录者id
        $loginer_id = $this->session->tempdata('id');
        if($loginer_id == null){
            show300("请先登录");
        }
        $params["loginer_id"] = $loginer_id;
        $data = $this -> bill_model -> cancelBuyOriginRes($params);
        if($data === true){
            show200(true);
        }else{
            show300($data);
        }
    }

    /**
    * @title 针对某个买入订单进行卖出
    * @desc  点击买单，然后确认卖出后调用的接口
    * @input {"name":"buy_id","require":"true","type":"int","desc":"买入订单的id"}
    * @input {"name":"china_id","require":"true","type":"int","desc":"身份证id"}
    * @input {"name":"pwd_second","require":"true","type":"int","desc":"二级密码"}
    * @output {"name":"code","type":"int","desc":"200:成功,300各种提示信息"}
    * @output {"name":"msg","type":"string","desc":"信息说明"}
    * @output {"name":"data","type":"int","desc":"生成的订单id"}
    **/
    public function sale_2_buy_bill_origin_res()
    {
        $requires = array("buy_id" => "缺少买入订单id");
        $params = array();
        foreach($requires as $k => $v)
        {
            if($this->input->post($k) == null){
                show300($v);
            }
            $params[$k] = $this -> input -> post($k);
        }

        //获取登录用户，没有登录用户则推出
        $loginer_id = $this->session->tempdata('id');
        if($loginer_id == null){
            show300("请先登录");
        }
        $member = $this->member_model->getwhereRow(['id' => $loginer_id],'*');
        //必须认证用户可以买入
        if($member["is_valid"] != "1" ){
            show300("必须认证用户可以交易,请尽快认证");
        }
        //判断交易时间
        $this -> valid_trade_time($member["member_lvl"]);

        if($member["pwd_second"] != $this->input->post("pwd_second") ){
            show300("二级密码错误");
        }
        if($member["china_id"] != $this->input->post("china_id") ){
            show300("身份证号错误");
        }

        $params["sale_member_id"] = $loginer_id;
        $data = $this -> bill_model -> sale2BuyBillOriginRes($params);
        if($data > 0){
            //TODO:给买家发送短信,使用自己号码测试     
            //$sms = new SmsDemo();
            //$res = $sms->sendSms('13203561153', SMS_ID, SMS_SIGN,['code'=>'你好，这是测试内容']);
            //if($res->Code=='OK'){            
                //echo '发送成功';
            //}else{
                //echo '发送失败';
            //}

            show200($data);
        }else{
            show300($data);
        }
    }

    /**
    * @title 支付宝付款成功后用的回执
    * @desc  用户向支付宝付款，上传付款成功截图，核准付款成功
    * @input {"name":"origin_bill_id","require":"true","type":"int","desc":"原始资产业务订单的id"}
    * @input {"name":"pic_dir","require":"true","type":"string","desc":"图片凭据地址"}
    * @input {"name":"thirdpart_bill_no","require":"true","type":"string","desc":"第三方单据凭据"}
    * @output {"name":"code","type":"int","desc":"200:成功,300各种提示信息"}
    * @output {"name":"msg","type":"string","desc":"信息说明"}
    * @output {"name":"data","type":"int","desc":"生成的订单id"}
    **/
    public function pay_4_bill_origin_res()
    {
        $requires = array("origin_bill_id" => "缺少原始资产业务订单的id","pic_dir" => "缺少图片地址","thirdpart_bill_no" => "缺少第三方订单标号凭据");
        $params = array();
        foreach($requires as $k => $v)
        {
            if($this->input->post($k) == null){
                show300($v);
            }
            $params[$k] = $this -> input -> post($k);
        }
        //获取登录用户，没有登录用户则推出
        $loginer_id = $this->session->tempdata('id');
        if($loginer_id == null){
            show300("请先登录");
        }

        $data = $this -> bill_model -> payed4BillOriginRes($loginer_id, $params);
        if($data > 0 ){
            show200($data);
        }else{
            show300($data);
        }

    }

    /**
    * @title 卖出原始资产确认
    * @desc  收到买家付款后，进行确认，确认后将放币
    * @input {"name":"origin_bill_id","require":"true","type":"int","desc":"原始资产业务订单的id"}
    **/
    public function payed_confirm_4_bill_origin_res()
    {
        //获取登录用户，没有登录用户则推出
        $loginer_id = $this->session->tempdata('id');
        if($loginer_id == null){
            show300("请先登录");
        }

        $requires = array("origin_bill_id" => "缺少原始资产业务订单的id");
        $params = array();
        foreach($requires as $k => $v)
        {
            if($this->input->post($k) == null){
                show300($v);
            }
            $params[$k] = $this -> input -> post($k);
        }
        $params["loginer_id"] = $loginer_id;
        $data = $this -> bill_model -> payedConfirm4BillOriginRes($params);
        if($data > 0 ){
            show200($data);
        }else{
            show300($data);
        }

    }

    /**
    * @title 交易大厅买单列表
    * @desc  获取原始资源交易单买入列表
    * @input {"name":"page","require":"true","type":"int","desc":"页码"}
    **/
    public function all_buy_bill_origin_res_list()
    {
        $page = $this->input->post('page') == null ? 1 : $this->input->post('page');
        $offset = $this -> getPage($page,PAGESIZE) ;
        $data = $this -> bill_model -> all_buy_bill_origin_res_list($offset );
        if(is_string($data)){
            show300($data);
        }else{
            show200($data);
        }

    }


    /**
    * @title 获取原始资源交易单买入列表
    * @desc  获取原始资源交易单买入列表
    * @input {"name":"page","require":"true","type":"int","desc":"页码"}
    **/
    public function buy_bill_origin_res_list()
    {
        //获取登录用户，没有登录用户则推出
        $loginer_id = $this->session->tempdata('id');
        if($loginer_id == null){
            show300("请先登录");
        }
        $page = $this->input->post('page') == null ? 1 : $this->input->post('page');
        $offset = $this -> getPage($page,PAGESIZE) ;
        $data = $this -> bill_model -> buy_origin_bill_res_list($offset, $loginer_id );
        if(is_string($data)){
            show300($data);
        }else{
            show200($data);
        }

    }

    /**
    * @title 获取原始资源交易单卖出列表
    * @desc  获取原始资源交易单卖出列表
    * @input {"name":"page","require":"true","type":"int","desc":"页码"}
    **/
    public function sale_bill_origin_res_list()
    {
        $page = $this->input->post('page') == null ? 1 : $this->input->post('page');
        //:获取登录用户，没有登录用户则推出
        $loginer_id = $this->session->tempdata('id');
        if($loginer_id == null){
            show300("请先登录");
        }
        $offset = $this -> getPage($page,PAGESIZE) ;
        $member_id = $loginer_id;
        $data =  $this -> bill_model -> sale_origin_bill_res_list($member_id, $offset);
        if(is_string($data)){
            show300($data);
        }else{
            show200($data);
        }
    }


    /**
    * @title 获取原始资源交易单列表
    * @desc  获取原始资源交易单列表
    * @input {"name":"type","require":"true","type":"string","desc":"获取方式-买家获取:0,卖家获取:1"}
    * @input {"name":"page","require":"true","type":"int","desc":"页码"}
    * @output {"name":"data","require":"true","desc":"'amount', 'double(16,6)', '购买数量' 'unit_price', 'double(16,6)', '单价' 'pay_amount', 'double(16,2)', '购买最终支付金额 人民币' 'stat', 'char(10)', '' 'origin_bill_no', 'varchar(45)', '业务订单号' 'buy_bill_id', 'varchar(45)', '买入订单id' 'sale_bill_id', 'varchar(45)', '卖出订单id' 'match_date', 'timestamp', '匹配时间' 'pay_date', 'timestamp', '买家付款时间' 'confirm_date', 'timestamp', '卖家确认时间' 'buy_member_id', 'int(11)', '买家会员id' 'sale_member_id', 'int(11)', '卖家会员id' 'tax', 'double(16,6)', '卖家缴纳手续费' "}
    **/
    public function bill_origin_res_list()
    {
        $loginer_id = $this->session->tempdata('id');
        if($loginer_id == null){
            show300("请先登录");
        }
        $type = $this->input->post('type');
        $page = $this->input->post('page') == null ? 1 : $this->input->post('page');
        $offset = $this -> getPage($page,PAGESIZE) ;
        $data =  $this -> bill_model -> origin_bill_res_list($type, $offset, $loginer_id);
        if(is_string($data)){
            show300($data);
        }else{
            show200($data);
        }
    }

    /**
    * @title 获取匹配的原始资源交易单详情
    * @desc  获取原始资源交易单详情
    * @input {"name":"type","require":"true","type":"string","desc":"获取方式-买家获取:0,卖家获取:1"}
    * @input {"name":"page","require":"true","type":"int","desc":"页码"}
    * @input {"name":"id","require":"true","type":"int","desc":"订单id"}
    * @output {"name":"data","require":"true","desc":"'amount', 'double(16,6)', '购买数量' 'unit_price', 'double(16,6)', '单价' 'pay_amount', 'double(16,2)', '购买最终支付金额 人民币' 'stat', 'char(10)', '' 'origin_bill_no', 'varchar(45)', '业务订单号' 'buy_bill_id', 'varchar(45)', '买入订单id' 'sale_bill_id', 'varchar(45)', '卖出订单id' 'match_date', 'timestamp', '匹配时间' 'pay_date', 'timestamp', '买家付款时间' 'confirm_date', 'timestamp', '卖家确认时间' 'buy_member_id', 'int(11)', '买家会员id' 'sale_member_id', 'int(11)', '卖家会员id' 'tax', 'double(16,6)', '卖家缴纳手续费' "}
    **/
    public function bill_origin_res_detail()
    {
        $loginer_id = $this->session->tempdata('id');
        if($loginer_id == null){
            show300("请先登录");
        }
        $type = $this->input->post('type');
        $id = $this->input->post('id');
        $requires = array("type" => "缺少操作类型", "id" => "缺少订单id");
        $params = array();
        foreach($requires as $k => $v)
        {
            if($this->input->post($k) == null){
                show300($v);
            }
        }

        $page = $this->input->post('page') == null ? 1 : $this->input->post('page');

        $offset = $this -> getPage($page,PAGESIZE) ;
        $data =  $this -> bill_model -> origin_bill_res_detail($type, $loginer_id, $id);
        if(is_string($data)){
            show300($data);
        }else if($data == null){
            show300('数据不存在');
        }else{
            $buy_member = $this->member_model->getwhereRow(['id' => $data -> buy_member_id],'*');
            $sale_member = $this->member_model->getwhereRow(['id' => $data -> sale_member_id],'*');
            $data -> buy_member = array(
                "mobile" => $buy_member["mobile"],
                "user_name" => $buy_member["user_name"] ,
            );
            $data -> sale_member = array(
                "mobile" => $sale_member["mobile"],
                "user_name" => $sale_member["user_name"] ,
                "alipay_qrcode" => $sale_member["alipay_qrcode"] 
            ); 
            show200($data);
        }
    }

    /**
    * @title 获取原始资源交易手续费
    * @desc  获取原始资源交易手续费
    * @input {"name":"buy_amount","require":"true","type":"int","desc":"买入数量"}
    **/
    public function bill_origin_res_tax()
    {
        $loginer_id = $this->session->tempdata('id');
        if($loginer_id == null){
            show300("请先登录");
        }
        $id = $this->input->post("id");
        if($id == null){
            show300("缺少要买入的订单id");
        }
        //获取交易大厅的买入订单 
        $bill = $this -> bill_model -> buy_bill_origin_res_list($id);
        if(is_string($bill)){
            show300($bill);
        }
        //获取卖出费率
        $percent = $this -> bill_model -> member_tax_percent($loginer_id);
        if($percent <= 0){
            $percent = 0.3;
        }
        $amount = $bill -> amount;
        $total_amount = $amount + $amount * $percent;
        //组织语言
        $data = "本次出售".$bill -> amount ."个交易资产,可收".$bill -> pay_amount."元,需要".$percent."手续费出售".$amount."扣".$total_amount.",确认出售吗?";
        show200($data);
    }
    
    //验证是否处于可交易时间
    private function valid_trade_time($lvl){
        if(is_int($lvl)){
            show300("会员等级有错误");
        }
        $now_hour = date("H",time());
        //交易时间为(1,2级9:00-21:00)和(2级以上:14:00-21:00)
        if($lvl < 3 ){
            if($now_hour < 9 || $now_hour > 21){
                show300("1,2级9:00-21:00交易,现在".date("H:i",time()).",请耐心等待");
            }
        }else{
            if($now_hour < 14 || $now_hour > 21){
                show300("3级以上14:00-21:00交易,现在".date("H:i",time()).",请耐心等待");
            }
        }

    }



    /** 处理交易原始资产相关数据 End **/

    /** 处理各种资产的转换  **/

    /**
    * @title 领取矿机产值变为原始资产
    * @desc  领取矿机产值变为原始资产,一键全领
    * @output {"name":"data","type":"int","desc":"领取的数量"}
    **/
    public function machine_prod_2_origin_res()
    {
        //模拟矿机产出 TODO:上线时删除
        //$this -> bill_model -> machineProduct();

        $loginer_id = $this->session->tempdata('id');
        if($loginer_id == null){
            show300("请先登录");
        }
        $data = $this -> bill_model -> machine_prod_2_origin_res($loginer_id);
        if(is_string($data)){
            show300($data);
        }else{
            show200($data);
        }

    }


    /**
     * @title 原始资产转为可售资产
     * @desc  原始资产转为可售资产
     * @input {"name":"amount","require":"true","type":"int","desc":"转的数量"}	
     * @input {"name":"pwd_second","require":"true","type":"int","desc":"二级密码"}	
     * @output {"name":"code","type":"int","desc":"200:成功,300各种提示信息"}
     * @output {"name":"msg","type":"string","desc":"信息说明"}
     * @output {"name":"data","type":"int","desc":"是否成功"}
    **/
    public function origin_2_totrade_res()
    {
        $pwd_second = $this->input->post("pwd_second");
        if($pwd_second == null){
            show300("缺少二级密码");
        }

        $amount = $this->input->post("amount");
        if($amount == null){
            show300("缺少转化数量");
        }
        if(!is_numeric($amount)){
            show300("数量必须是数字");
        }
        $len = strlen($amount);
        //取整十数字
        $n = 10 ** ($len-1);
        $amount = (int)($amount / $n) * $n;
        if($amount <= 0 ){
            show300("数量必须大于0");
        }
        //登录用户
        $loginer_id = $this->session->tempdata('id');
        if($loginer_id == null){
            show300("请先登录");
        }
        $member = $this->member_model->getwhereRow(['id' => $loginer_id],'*');
        if($member == null){
            return "缺少用户记录";
        }
        //验证二级密码
        if($member["pwd_second"] != $pwd_second){
            show300("二级密码错误");
        }

        $data = $this -> bill_model -> origin_2_totrade_res($loginer_id, $amount);

        if(is_string($data)){
            show300($data);
        }else{
            if($data == 1){
                show200(true);
            }else{
                show300("错误影响行数，请联系管理员");
            }
        }
    }


    /**
    * @title 释放可售资产为可交易资产
    * @desc  释放可售资产为可交易资产
    * @output {"name":"code","type":"int","desc":"200:成功,300各种提示信息"}
    * @output {"name":"msg","type":"string","desc":"信息说明"}
    * @output {"name":"data","type":"boolean","desc":"true:成功"}
    **/
    public function releaseTradeableRes()
    {
        $loginer_id = $this->session->tempdata('id');
        if($loginer_id == null){
            show300("请先登录");
        }
        //释放可售资产为可交易资产
        $data = $this -> bill_model -> releaseTradeableRes($loginer_id);
        if(is_string($data)){
            show300($data);
        }else{
            show200($data);
        }

    }

    
    /** 处理各种资产的转换 End **/

        
    
    /** 处理获取个人资产汇总相关数据 **/

    /**
     * @title 钱包资产统计
     * @desc  显示了钱包资产统计
     * @input {"name":"id","require":"true","type":"int","desc":"会员id"}	
     * @output {"name":"code","type":"int","desc":"200:成功,300各种提示信息"}
     * @output {"name":"msg","type":"string","desc":"信息说明"}
     * @output {"name":"data.base_amount","require":"true","type":"float","desc":"矿机产值"}	
     * @output {"name":"data.origin_amount","require":"true","type":"float","desc":"原始资产"}	
     * @output {"name":"data.totrade_amount","require":"true","type":"float","desc":"可售资产"}	
     * @output {"name":"data.tradeable_amount","require":"true","type":"float","desc":"可交易资产"}	
     * @output {"name":"data.machine_amount","require":"true","type":"float","desc":"有效矿机"}	
     * @output {"name":"data.profit_amount","require":"true","type":"float","desc":"总收益"}	
     * @output {"name":"data.saleable_top","require":"true","type":"float","desc":"可动态出售额度"}	
     * @output {"name":"data.profit_lastday","require":"true","type":"float","desc":"昨日网体收益"}	
     * @output {"name":"data.tradeable_foren_amount","require":"true","type":"float","desc":"冻结的可交易资产"}	
     * @output {"name":"data.traded_lastday","require":"true","type":"float","desc":"昨日网体买入"}	
     * @output {"name":"data.","require":"true","type":"float","desc":""}	
     * @output {"name":"data.","require":"true","type":"float","desc":""}	
     * @output {"name":"data.create_date","require":"true","type":"date","desc":"创建时间"}	
     * @output {"name":"data.modify_date","require":"true","type":"date","desc":"更新时间"}	
     **/
    public function bill_outline()
    {
        $loginer_id = $this->session->tempdata('id');
        if($loginer_id == null){
            show300("请先登录");
        }
        $data = $this -> bill_model -> getBillOutline($loginer_id);
        show200($data);
    }

    /*** 处理获取个人资产汇总相关数据End **/
	
}
