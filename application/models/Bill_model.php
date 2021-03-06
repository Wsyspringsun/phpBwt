<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');
define('DATE_TIME_FMT','Y-m-d H:i:s');
class Bill_model extends MY_Model
{
    private $tbl_member = 'member'; //会员表
    private $tbl_member_resouce ='member_resouce'; //会员资产汇总
    private $tbl_member_machine_bill ='member_machine_bill'; //会员矿机租用表
    private $tbl_origin_res_bill ='origin_res_bill';//原始资产交易表
    private $tbl_origin_res_buy_bill ='origin_res_buy_bill';//原始资产买入下单表
    private $tbl_origin_res_sale_bill ='origin_res_sale_bill';//原始资产卖出下单表
    private $tbl_origin_res_pay_bill ='origin_res_pay_bill';//原始资产卖出下单表
    private $tbl_key_val_params ='key_val_params';//各种参数数据对照表
    private $tbl_totrade_2_trade_bill ='totrade_2_trade_bill';//可售资产释放位可交易资产记录
    private $tbl_machineprod_2_origin_bill ='machineprod_2_origin_bill';//领取矿机产值转为原始资产
    private $tbl_funding_rec = 'funding_rec';//私募活动记录
    private $tbl_funding_bill = 'funding_bill';//私募交易单


    public function __construct()
    {
        parent::__construct($this->tbl_member_resouce);
        parent::__construct($this->tbl_member_machine_bill);
        parent::__construct($this->tbl_origin_res_bill);
        parent::__construct($this->tbl_origin_res_buy_bill);
        parent::__construct($this->tbl_origin_res_sale_bill);
        parent::__construct($this->tbl_origin_res_pay_bill);
        parent::__construct($this->tbl_key_val_params);
        parent::__construct($this->tbl_totrade_2_trade_bill);
        parent::__construct($this->tbl_machineprod_2_origin_bill);
        parent::__construct($this->tbl_funding_rec);
        parent::__construct($this->tbl_funding_bill);
        parent::__construct($this->tbl_member);


	$this->load->model(array('member_model'));		
    }

    /** 处理矿机订单Start **/

    //购买矿机 
    public function buyMachine($params)
    {
        $this->db->trans_start();
        $machine_id = $params["machine_id"];
//var_dump($machine_id);
        //获取矿机，判断矿机是否存在
        $m =  $this->db->get_where('machine',array('id' => $machine_id))->row();
        if($m == null)
            return "当前矿机已经不存在,请刷新数据";
        $member_id = $params["member_id"];
        //TODO:判断会员是否有权租用此矿机
        //$buy_member = $this->member_model->getwhereRow(['id' => $member_id],'*');
        $bill_hour_amount = $params["bill_hour_amount"];
        $dtfmt = DATE_TIME_FMT;
        //矿机租用起始时间
        $bill_date_start = time();
        //矿机租用截止时间
        $bill_date_end = time() + $bill_hour_amount * 60 * 60;
        //单价
        $bill_price = $m -> price;
        //花费金额
        $bill_real_pay = $bill_price * $bill_hour_amount;
        $bill_data =  array(
            "bill_no" => get_bill_unique_id($member_id),
            "member_id" => $member_id,
            "machine_id" => $machine_id,
            "machine_title" => $m -> title,
            "bill_unit_produce" => $m -> unit_produce,
            "bill_price" => $bill_price,
            "bill_hour_amount" => $bill_hour_amount,
            "bill_real_pay" => $bill_real_pay,
            "bill_date_start" => date($dtfmt, $bill_date_start) ,
            "bill_date_end" => date($dtfmt,$bill_date_end)
        );
        //减少用户原始资产
        $mem_bill_outline = $this -> getBillOutline($member_id);
        if($mem_bill_outline == null)
        {
            return "当前用户已经不存在";
        }
        $avaliable_origin_res = $mem_bill_outline -> origin_amount ;
        if($avaliable_origin_res < $bill_real_pay)
        {
            return "用户可用原始资源不足，需要".$bill_real_pay.",拥有:".$avaliable_origin_res;
        }
        $mem_bill_outline -> origin_amount = $mem_bill_outline -> origin_amount - $bill_real_pay;

        //添加矿机购买数据
        $this -> db -> insert($this -> tbl_member_machine_bill,$bill_data);
        $new_id = $this -> db -> insert_id();
        //更新会员资源数据,减少原始资产数量
        $this -> db -> where("member_id",$member_id);
        $this -> db -> update($this -> tbl_member_resouce, $mem_bill_outline);
        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE)
        {
            return "事务执行失败";
        }
        return $new_id;
    }

    //获取矿机租用明细
    public function machine_bill_list($member_id, $type, $offset)
    {
//echo 'type:'.$type;
        $data = null;
        if($type == 0){
            //有效的
            $data =  $this -> db -> order_by('modify_date DESC') -> limit(PAGESIZE,$offset) -> where('`prod_cnt` < `bill_hour_amount`') -> get_where($this->tbl_member_machine_bill,array('member_id' => $member_id))->result();

        }else{
            //过期的
            $data =  $this -> db -> order_by('modify_date DESC') -> limit(PAGESIZE,$offset) -> where('`prod_cnt` >= `bill_hour_amount`') -> get_where($this->tbl_member_machine_bill,array('member_id' => $member_id ))->result();
        }
        //echo $this->db->last_query();
        return $data;
    }

    //获取矿机订单详情
    public function machine_bill_detail($loginer_id, $id){
        $data =  $this -> db -> get_where($this->tbl_member_machine_bill,array('id' => $id))->row();
        if($data == null){
            return "订单已经不存在";
        }
        if($data -> member_id != $loginer_id){
            return "无权查看";
        }
        return $data;
    }


    /** 处理矿机订单 End **/

    /** 处理原始资产交易 **/

    //原始资产购买下单
    public function buyOriginRes($params){
        //每人每天可挂5条买入信息，且每次挂买时间间距为10分钟
        $member_id = $params['buy_member_id'];
 
        $query_cnt = $this -> db -> query("select count(id) today_cnt from origin_res_buy_bill where  buy_member_id = ".$this->db->escape($member_id) ." and  to_days(create_date) = to_days(now());");
        $today_cnt = $query_cnt -> row() -> today_cnt;
        if($today_cnt >= 5){
            return "每人每天可挂5条买入信息";
        }
        //获取当天距离上次交易的间隔时间
        $query_diff_minute = $this -> db -> query("select  timestampdiff(MINUTE,create_date,now()) diff_minute from origin_res_buy_bill where buy_member_id = ".$this->db->escape($member_id)." and  to_days(create_date) = to_days(now()) order by diff_minute limit 0,1;");
//var_dump($query_diff_minute -> row());
        if($query_diff_minute -> row() != null){
            $diff_minute = $query_diff_minute -> row() -> diff_minute;
            if($diff_minute <= 10){
                return "每次挂买时间间距为10分钟,距离上次挂单".$diff_minute."分钟";
            }
        }

        $amount = $params['amount'];
        $unit_price = $params['unit_price'];
        $pay_amount = $amount * $unit_price;
        $data = array(
            'buy_member_id' => $member_id,
            'buy_bill_no' => get_bill_unique_id($member_id),
            'amount' => $amount,
            'unit_price' => $unit_price,
            'pay_amount' => $pay_amount,
            'stat' => '0' //状态：申请:0
        );
        if($this -> db -> insert($this -> tbl_origin_res_buy_bill,$data)){
            return $this->db->insert_id();
        }
    }

    //撤销原始资产购买下单
    public function cancelBuyOriginRes($params){
        //登录人
        $loginer_id = $params['loginer_id'];
        if($loginer_id == null){
            return "缺少登录用户";
        }
        //买入单id
        $buy_id = $params["id"];
        //买单
        $buy_bill = $this -> db -> get_where($this -> tbl_origin_res_buy_bill, array('id' => $buy_id)) -> row();
        if($buy_bill == null){
            return "买单未能找到，请刷新数据";
        }
        //判断是否符合撤销条件 1:登录人是所属人，2:状态是 0 ；
        if($loginer_id != $buy_bill -> buy_member_id){
            return "无权撤销";
        }
        if(get_stat_code($buy_bill -> stat, 1) != '0'){
            return "此订单状态不能撤销";
        }
        //更新状态
        $this -> db -> set('stat', '1');
        $this -> db -> where('id', $buy_id);
        $this -> db -> update($this -> tbl_origin_res_buy_bill);
        $affected_rows = $this -> db ->affected_rows();
        if($affected_rows !== 1){
            return "错误影响行数:".$affected_rows.",请联系系统管理员";
        }
        return true;
    }


    /**  TODO:暂时未使用
    //原始资产卖出
    public function saleOriginRes($params){
        $member_id = $params['sale_member_id'];
        $amount = $params['amount'];
        $unit_price = $params['unit_price'];
        $pay_amount = $amount * $unit_price;
        $data = array(
            'sale_member_id' => $member_id,
            'sale_bill_no' => get_bill_unique_id($member_id),
            'amount' => $amount,
            'unit_price' => $unit_price,
            'pay_amount' => $pay_amount,
            'stat' => '0' //状态：申请:0
        );
        if($this -> db -> insert($this -> tbl_origin_res_sale_bill,$data)){
            return $this->db->insert_id();
        }
    }**/

    //卖给确定的买单
    public function sale2BuyBillOriginRes($params){
        $sale_member_id = $params['sale_member_id'];
        $buy_id = $params['buy_id'];

        $this->db->trans_start();
        //买单
        $buy_bill = $this -> db -> get_where($this -> tbl_origin_res_buy_bill,array('id' => $buy_id)) -> row();
        if($buy_bill === null){
            return "买入单不存在";
        }
        if($buy_bill -> stat!= '0'){
            return "此订单已经被卖出";
        }

        $amount = $buy_bill -> amount;
        $unit_price = $buy_bill -> unit_price;
        $pay_amount = $buy_bill -> pay_amount;
        $buy_member_id = $buy_bill -> buy_member_id;

        
        $stat = '0-0' ;
        //创建卖单

        //TODO:运营ID卖出的额度限制:动态额度->前一日的总销量决定次日动态额度，金泰额度->一个不变数值,账户内可交易额度
        $member_res = $this -> getBillOutline($sale_member_id);
        if($member_res == null){
            return "会员资产记录未找到";
        }
        //判断可交易额度指标是否足够
        if($member_res -> saleable_top < $pay_amount){
            return "您的可交易额度".$member_res -> saleable_top."小于要购买的额度:".$amount;
        }
        //扣除卖家手续费
        $tax = $this -> origin_bill_res_tax($sale_member_id, $amount);
        //判断可交易资产是否足够 可交易资产-冻结的可交易资产-缴纳的手续费
        $avaliable_tradeable_amount = $member_res -> tradeable_amount - $member_res -> tradeable_foren_amount ;
        if($avaliable_tradeable_amount < ($pay_amount + $tax)){
            return "需要:(卖出数目+手续费):" . $amount . "+" . $tax . ";可用:(拥有-冻结)" . $member_res -> tradeable_amount . "-" . $member_res -> tradeable_foren_amount . "!额度不足!";
        }

        $sale_data = array(
            'sale_member_id' => $sale_member_id,
            'sale_bill_no' => get_bill_unique_id($sale_member_id),
            'amount' => $amount,
            'tax' => $tax,
            'unit_price' => $unit_price,
            'pay_amount' => $pay_amount,
            'stat' => $stat //状态：匹配:0
        );
        $this -> db -> insert($this -> tbl_origin_res_sale_bill,$sale_data);
        $sale_id =  $this->db->insert_id();
        //更新买单状态
        $buy_bill -> stat = $stat;
        $this -> db -> where ('id', $buy_id);
        $this -> db -> update($this -> tbl_origin_res_buy_bill,$buy_bill);

        //新增业务订单数据
        $data = array(
            'buy_member_id' => $buy_member_id,
            'buy_bill_id' => $buy_id,
            'sale_bill_id' => $sale_id,
            'sale_member_id' => $sale_member_id,
            'origin_bill_no' => get_bill_unique_id($buy_id + $sale_id),
            'tax' => $tax,
            'amount' => $amount,
            'match_date' => date(DATE_TIME_FMT,time()),
            'unit_price' => $unit_price,
            'pay_amount' => $pay_amount,
            'stat' => $stat//状态：匹配成功
        );
        //业务订单入库
        $this -> db -> insert($this -> tbl_origin_res_bill,$data);
        $origin_res_bill_id = $this->db->insert_id();
        //增加卖家冻结额度
        $this -> addTradeableResOfForen($sale_member_id, ($amount + $tax));

        $this->db->trans_complete();//提交事务

        if ($this->db->trans_status() === FALSE)
        {
            $this ->db -trans_rollback();
            return "事务执行失败";
        } 
        return $origin_res_bill_id;
    }

    //付款完成业务处理
    public function payed4BillOriginRes($loginer_id, $params){
        $this->db->trans_start();
        $origin_bill_id = $params["origin_bill_id"];
        $origin_bill = $this->db->get_where($this -> tbl_origin_res_bill,array('id' => $origin_bill_id))->row();
        if($origin_bill === null){
            return "当前订单不存在";
        }
        if($origin_bill -> stat  != '0-0'){
            return "当前订单状态错误";
        }
        if($loginer_id != $origin_bill -> buy_member_id){
                    return "不是买家，无权操作";
        }
        $pic_dir = $params["pic_dir"];
        $thirdpart_bill_no = $params["thirdpart_bill_no"];
        $data = array(
            "origin_bill_id" => $origin_bill_id ,
            "pay_amount" => $origin_bill -> pay_amount,
            "pic_dir" => $pic_dir,
            "thirdpart_bill_no" => $thirdpart_bill_no
        );
        $this -> db -> insert($this -> tbl_origin_res_pay_bill, $data);
        $newid = $this -> db -> insert_id();

        $origin_bill -> stat = $origin_bill -> stat.'-0';
        $origin_bill -> pay_date = date(DATE_TIME_FMT,time());
        $this -> db -> where ('id', $origin_bill_id);
        $this -> db -> update($this -> tbl_origin_res_bill,$origin_bill);

        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE)
        {
            return "事务执行失败";
        }
        return $newid;
    }

    //卖家确认收到付款并确认放币
    public function payedConfirm4BillOriginRes($params){
        $this->db->trans_start();
        $origin_bill_id = $params["origin_bill_id"];
        $origin_bill = $this->db->get_where($this -> tbl_origin_res_bill,array('id' => $origin_bill_id))->row();
        if($origin_bill === null){
            return "当前订单不存在";
        }
        $loginer_id = $params["loginer_id"];
        //判断当前订单是否由登录用户卖出
        if($origin_bill -> sale_member_id != $loginer_id){
            return "您无权处理该订单";
        }

        //判断当前订单是否是已付款待确认状态:0-0-0
        $current_stat = $origin_bill -> stat;
        if($current_stat != '0-0-0'){
            return "当前订单不是已付款待确认状态";
        }
        $new_stat = $current_stat.'-0';
        $origin_bill -> stat = $new_stat;
        $origin_bill -> confirm_date = date(DATE_TIME_FMT,time());
        $this -> db -> where ('id', $origin_bill_id);
        $this -> db -> update($this -> tbl_origin_res_bill,$origin_bill);
        //更新买单状态
        $this -> db -> set ('stat', $new_stat);
        $this -> db -> where ('id', $origin_bill -> buy_bill_id);
        $this -> db -> update($this -> tbl_origin_res_buy_bill);
        //更新卖单状态
        $this -> db -> set ('stat', $new_stat);
        $this -> db -> where ('id', $origin_bill -> sale_bill_id);
        $this -> db -> update($this -> tbl_origin_res_sale_bill);
        // TODO:可售额度倍数需要根据等级获得，更新买家资产,增加原始资产/可售额度=1.7*amount
        $buy_member_id = $origin_bill -> buy_member_id;
        $sql_update_buy = "update ".$this -> tbl_member_resouce." set origin_amount=origin_amount+".$origin_bill -> amount.",saleable_top=saleable_top+".SALEABLE_TOP_MUL_NUM * $origin_bill -> amount." where member_id=".$buy_member_id.";";
//echo $sql_update_buy;
        $this -> db -> query($sql_update_buy);
        //更新卖家资产,减少可交易资产(卖出数量+手续费)
        $sale_member_id = $origin_bill -> sale_member_id;
        $sql_update_sale = "update ".$this -> tbl_member_resouce." set tradeable_amount=tradeable_amount-".($origin_bill -> amount + $origin_bill -> tax)." where member_id=".$sale_member_id.";";
        $this -> db -> query($sql_update_sale);
//echo $sql_update_sale;
        //减除卖家冻结额度
$this -> delTradeableResOfForen($sale_member_id, ($origin_bill -> amount + $origin_bill -> tax));

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE)
        {
            return "事务执行失败";
        }else{
            return true;
        }
    }

    //获取买入原始资产订单列表
    public function all_buy_bill_origin_res_list($offset){
        return $this -> db -> order_by('modify_date DESC') -> limit(PAGESIZE,$offset) -> get_where($this -> tbl_origin_res_buy_bill, array('stat' => '0'))->result();
    }

    //获取买入原始资产订单详情
    public function buy_bill_origin_res_list($id){
        return $this -> db -> order_by('modify_date DESC') -> get_where($this -> tbl_origin_res_buy_bill, array('id' =>$id))->row();
    }


    //获取指定会员买入原始资产订单列表
    public function buy_origin_bill_res_list($offset, $member_id)
    {
        return $this -> db -> order_by('modify_date DESC') -> limit(PAGESIZE,$offset) -> get_where($this -> tbl_origin_res_buy_bill, array('buy_member_id' => $member_id, 'stat' => '0'))->result();
    }

    //获取卖出原始资产应付手续费
    public function origin_bill_res_tax($sale_member_id, $buy_amount){
        $member = $this->member_model->getwhereRow(['id' => $sale_member_id],'*');
        $profit_lvl = $member["profit_lvl"] ;
        //对照表
        $tax_row = $this -> db -> get_where($this -> tbl_key_val_params, ["params_type" => "1", "params_key" => $profit_lvl]) -> row_array();
        $params_val = (float)$tax_row["params_val"];
        return $buy_amount * $params_val;
    }

    //获取卖出用户的手续费率
    public function member_tax_percent($sale_member_id){
        $member = $this->member_model->getwhereRow(['id' => $sale_member_id],'*    ');
        $profit_lvl = $member["profit_lvl"] ;
        //对照表
        $tax_row = $this -> db -> get_where($this -> tbl_key_val_params, ["params_type" => "1", "params_key" => $profit_lvl]) -> row_array();
        $params_val = (float)$tax_row["params_val"];
        return $params_val;
    }


    //获取卖出原始资产表
    public function sale_origin_bill_res_list($offset, $member_id)
    {
        return $this -> db -> order_by('modify_date DESC') -> limit(PAGESIZE,$offset) -> get_where($this -> tbl_origin_res_sale_bill, array('sale_member_id' => $member_id, 'stat' => '0'))->result();
    }


    //买入用户获取成交的原始资产业务表
    public function origin_bill_res_list($type, $offset,$loginer_id)
    {
        $member_id = $loginer_id;
        if($type == '0'){
            //买家获取订单
            return $this -> db -> order_by('modify_date DESC') -> limit(PAGESIZE,$offset) -> get_where($this -> tbl_origin_res_bill, array('buy_member_id' => $member_id, 'stat' => '0-0')) -> result();
        }else if($type == '1'){
            //卖家获取订单
            return $this -> db -> order_by('modify_date DESC') -> limit(PAGESIZE,$offset) -> get_where($this -> tbl_origin_res_bill, array('sale_member_id' => $member_id, 'stat' => '0-0')) -> result();
        }else{
            return "缺少操作方式";
        }

    }

    //获取已匹配的业务订单详情
    public function origin_bill_res_detail($type, $loginer_id, $id)
    {
        $origin_bill = $this->db->get_where($this -> tbl_origin_res_bill,array('id' => $id))->row();
        if($origin_bill === null){
            return "当前订单不存在";
        }
        if($type == '0'){
            //买家获取订单
            //判断当前订单是否由登录用户所属
            if($origin_bill -> buy_member_id != $loginer_id){
                return "您无权处理该订单";
            }
            return $origin_bill;
        }else if($type == '1'){
            //卖家获取订单
            //判断当前订单是否由登录用户所属
            if($origin_bill -> sale_member_id != $loginer_id){
                return "您无权处理该订单";
            }
            return $origin_bill;
        }else{
            return "缺少操作方式";
        }
    }



    /** 处理原始资产交易 End**/

    
    /*** 处理获取个人资产汇总相关数据 **/

    //获取各项资产汇总 
    public function getBillOutline($member_id){
        return $this->db->get_where($this->tbl_member_resouce,array('member_id' => $member_id))->row();
    }

    //增加冻结资产
    public function addTradeableResOfForen($member_id, $amount){
        $sql_update = "update ".$this -> tbl_member_resouce." set tradeable_foren_amount=tradeable_foren_amount+".$amount." where member_id=".$member_id.";";
        $this -> db -> query($sql_update);
    }
    //减去冻结资产
    public function delTradeableResOfForen($member_id, $amount){
        $sql_update = "update ".$this -> tbl_member_resouce." set tradeable_foren_amount=tradeable_foren_amount-".$amount." where member_id=".$member_id.";";
        $this -> db -> query($sql_update);
    }

    //原始资产转为可售资产
    public function origin_2_totrade_res($loginer_id, $amount)
    {

        $res = $this -> getBillOutline($loginer_id);
        //判断原始资产是否足够
        if($res == null){
            return "缺少用户资产记录";
        }
        if($res -> origin_amount < $amount){
            return "需要:" . $amount . ";拥有:" . $res -> origin_amount . "!原始资产不足，请赶紧购买!";
        }
        $toTradeAmount = TO_TRADE_MUL_NUM * $amount;
        //更新资产
        $sql_update_sale = "update ".$this -> tbl_member_resouce." set origin_amount = origin_amount - ".$amount.",totrade_amount = totrade_amount + ".$toTradeAmount." where member_id=".$loginer_id.";";
        $this -> db -> query($sql_update_sale);
        return $this -> db -> affected_rows();
    }


    /** 释放可售资产为可交易资产,全体释放 暂时废弃
    public function toTradeRes2TradeableRes(){
        $this->db->trans_start();
        //插入明细记录
        //整数$query_i = "insert into totrade_2_trade_bill ( `realease_amount`, `totrade_amount_old`, `totrade_amount`, `trade_amount_old`, `trade_amount`, `member_id`) select   truncate(`totrade_amount` * 0.002,0), `totrade_amount`, `totrade_amount` -  truncate(`totrade_amount` * 0.002,0)  , `tradeable_amount`, `tradeable_amount` + truncate(`totrade_amount` * 0.002,0),  `member_id` from  member_resouce t where t.`totrade_amount` >= 500; ";
        $query_i = "insert into totrade_2_trade_bill ( `realease_amount`, `totrade_amount_old`, `totrade_amount`, `trade_amount_old`, `trade_amount`, `member_id`) select   `totrade_amount` * 0.002, `totrade_amount`, `totrade_amount` -  `totrade_amount` * 0.002, `tradeable_amount`, `tradeable_amount` + `totrade_amount` * 0.002,  `member_id` from  member_resouce t where t.`totrade_amount` >= 0.0005; ";
        $this -> db -> query($query_i);
        //更新资产记录
        //整数释放$query_u = "update member_resouce set `tradeable_amount` = `tradeable_amount` + truncate(`totrade_amount` * 0.002,0) , `totrade_amount`= `totrade_amount` - truncate(`totrade_amount` * 0.002,0) where `totrade_amount` >= 500;";
        $query_u = "update member_resouce set `tradeable_amount` = `tradeable_amount` + `totrade_amount` * 0.002 , `totrade_amount`= `totrade_amount` - `totrade_amount` * 0.002 where `totrade_amount` >= 0.0005;";
        $this -> db -> query($query_u);

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE)
        {
            return "事务执行失败";
        }else{
            //return $this -> db -> query("select `realease_amount`, `totrade_amount_old`, `totrade_amount`, `trade_amount_old`, `trade_amount`, `member_id` from totrade_2_trade_bill;") -> result();
            return true;
        }
    } **/

    //指定会员释放可售资产为可交易资产,千分之二的方式释放
    public function releaseTradeableRes($member_id){
        //获取用户资产
        $member_res = $this -> getBillOutline($member_id);
        //判断可售资产是否够0.0005
        if($member_res -> totrade_amount < 0.0005){
            return "可售资产不足，需要:0.0005以上;拥有".$member_res -> totrade_amount;
        }
        //TODO:判断和上次领取间隔时间24小时
        $query_diff_hour = $this -> db -> query("select  timestampdiff(HOUR,create_date,now()) diff_hour from `totrade_2_trade_bill` where member_id = ".$this->db->escape($member_id)."  order by diff_hour limit 0,1;");
        if($query_diff_hour -> row() != null){
            $diff_hour = $query_diff_hour -> row() -> diff_hour;
            if($diff_hour <= 24){
                return "每隔24小时领取一次,等待".(24 - $diff_hour)."小时";
            }
        }

        /**判断今天是否已经有释放,改为 判断和上次领取间隔时间24小时
        $today_data = $this -> db -> where("member_id", $member_id) -> where(" TO_DAYS(NOW()) = TO_DAYS(create_date) ") -> get($this -> tbl_totrade_2_trade_bill) -> row();
        if($today_data != null){
            return "每天一次机会,今天已经释放过了";
        }**/
        $this->db->trans_start();
        //插入明细记录
        $realease_amount = $member_res -> totrade_amount * 0.002;
        $data = array(
            "realease_amount" => $realease_amount,
            "totrade_amount_old" => $member_res -> totrade_amount,
            "totrade_amount" => $member_res -> totrade_amount - $realease_amount,
            "trade_amount_old" => $member_res -> tradeable_amount,
            "trade_amount" => $member_res -> tradeable_amount + $realease_amount,
            "member_id" => $member_id
        );
        $this -> db -> insert($this -> tbl_totrade_2_trade_bill, $data);
        $newid = $this -> db -> insert_id();
        //更新资产记录
        $this -> db -> set("totrade_amount", $member_res -> totrade_amount - $realease_amount);
        $this -> db -> set("tradeable_amount", $member_res -> tradeable_amount + $realease_amount);
        $this -> db -> where("member_id", $member_id);
        $this -> db -> update($this -> tbl_member_resouce);
        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE)
        {
            return "事务执行失败";
        }else{
            return true;
        }
    }


    //更新矿机订单执行次数,每小时会被调用一次
    public function machineProduct(){
        $query_u = "update member_machine_bill set prod_cnt = prod_cnt + 1, prod_amount = prod_cnt * bill_unit_produce where prod_cnt < bill_hour_amount; ";
        $this -> db ->query($query_u);
    }

    //批量领取矿机产出
    public function machine_prod_2_origin_res($member_id){
        $this->db->trans_start();
        //获取距离上次领取的时间间隔
        $query_diff_hour = $this -> db -> query("select  timestampdiff(HOUR,create_date,now()) diff_hour from `machineprod_2_origin_bill` where member_id = ".$this->db->escape($member_id)."  order by diff_hour limit 0,1;");
        if($query_diff_hour -> row() != null){
            $diff_hour = $query_diff_hour -> row() -> diff_hour;
            if($diff_hour <= 24){
                return "每隔24小时领取一次,等待".(24 - $diff_hour)."小时";
            }
        }
        //更改矿机订单表领取数据
        $this -> db -> query("update ".$this -> tbl_member_machine_bill." set last_gain = prod_amount - to_origin_amount, to_origin_amount = prod_amount where to_origin_amount < prod_amount and member_id=".$member_id.";");
//var_dump($query_diff_hour -> row());
        $affected_rows = $this -> db -> affected_rows();
        if($affected_rows <= 0){
            return "没有资源可领取";
        }
        //获取产出数量,可领取数量
        $query_ready_amount = $this -> db -> query("SELECT sum(last_gain) ready_amount FROM bwt.member_machine_bill  where last_gain > 0 and member_id =".$this->db->escape($member_id).";");
        if($query_ready_amount -> row() == null){
            return "没有资源可领取";
        }
        $ready_amount = $query_ready_amount -> row() -> ready_amount;
        if($ready_amount <= 0){
            return "没有资源可领取";
        }
        //用户资源记录
        $member_res = $this -> getBillOutline($member_id);
        if($member_res == null){
            return "会员资产记录未找到";
        }
        $origin_amount_new = $member_res -> origin_amount + $ready_amount;
        //添加领取记录
        $data_gain = array(
            "gain_amount" => $ready_amount,
            "origin_amount_old" => $member_res -> origin_amount,
            "origin_amount" => $origin_amount_new,
            "member_id" => $member_id
        );
        $this -> db -> insert($this -> tbl_machineprod_2_origin_bill, $data_gain);
        //更改会员资产记录(DTSC总量增加，原始资产数量增加)
        $this -> db -> query("update ".$this -> tbl_member_resouce." set origin_amount=origin_amount+".$ready_amount.", base_amount=base_amount+".$ready_amount." where member_id=".$member_id.";");
        //清空临时储存的各矿机订单领取数量
        $this -> db -> query("update ".$this -> tbl_member_machine_bill." set last_gain = 0 where  last_gain > 0 and member_id=".$member_id.";");

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE)
        {
            return "事务执行失败";
        }
        return $ready_amount;
    }


    /*** 处理获取个人资产汇总相关数据End **/

    //从键值对参数表中获取指定数据
    public function getKeyValFromParams($type,$key){
        $dict = $this -> db -> get_where($this -> tbl_key_val_params, ["params_type" => $type, "params_key" => $key]) -> row_array();
        return $dict;
    }
    //获取网体买入的成交量
    public function getBuyAmountSum($where,$dbArray=[],$where_in=[]){
        $this->db->where($where);
        $this->db->select_sum('amount');

        if($where_in){
            if(isset($where_in['field'])&&isset($where_in['data'])){
                $this->db->where_in($where_in['field'],$where_in['data']);
            }else{
                echo 'where_in参数输入不正确';exit();
            }
        }

        return $this->db->get($this->tbl_origin_res_bill)->select_sum();
    }


    /** 接受私募的会员商操作区域 **/
        
    //获取私募剩余天数
    public function getFundingRec(){
        return $this -> db -> query("SELECT *, to_days(stop_date) - to_days(start_date) total_days , to_days(stop_date) - to_days(now()) cnt_days FROM ".$this -> tbl_funding_rec." where stop_date > start_date order by start_date desc limit 0,1; ") -> row();
        
    }

    //买家获取当前私募订单
    public function getCurrentBuyFundingBill($member_id){
        return $this -> db -> query("SELECT * FROM ".$this -> tbl_funding_bill." where stat != 'S' and stat != 'X' and buy_member_id=".$member_id." limit 0,1; ") -> row();
        
    }

    //通过id获取订单
    public function getFundingBillById($id){
        return $this -> db -> get_where($this -> tbl_funding_bill, array("id" => $id)) -> row();
    }



    //运营商获取当前私募订单列表
    public function getFundingBillList($member_id, $offset){
        return $this -> db -> query("SELECT a.*, b.mobile buy_member_mobile ,b.user_name buy_member_username FROM bwt.funding_bill a, member b where a.buy_member_id = b.id  and a.sale_member_id = ".$member_id." order by modify_date desc limit ".$offset.",".PAGESIZE.";") -> result();
    }

    //根据id 获取私募订单详情
    public function getFundingBillDetail($id){
        return $this -> db -> query("SELECT a.*, b.mobile buy_member_mobile ,b.user_name buy_member_username, c.mobile sale_member_mobile ,c.user_name sale_member_username FROM bwt.funding_bill a, member b, member c where a.buy_member_id = b.id and a.sale_member_id = c.id  and a.id = ".$id.";") -> row();
    }

    //处于冻结状态的运营商原始资产
    public function getForenOriginAmountByFunding($member_id){
        $row =  $this -> db -> query("SELECT  sum(`dtsc_amount`) forenAmount FROM ".$this -> tbl_funding_bill." where stat = '0' or stat = '1' and sale_member_id=".$this -> db -> escape($member_id)."  limit 0,1; ") -> row();
        return $row -> forenAmount;
    }

    //保存兑换订单
    public function create_funding_bill($data){
        $this -> db -> insert($this -> tbl_funding_bill, $data);
        return $this -> db -> insert_id();
    }

    //更改订单状态
    public function update_funding_bill($loginer_id, $id, $stat){
        $bill = $this -> db -> get_where($this -> tbl_funding_bill, array("id" => $id)) -> row();
        if($bill == null){
            return "订单不存在";
        }
        $this->db->trans_start();
        switch($stat){
            case "1":
                //买家完成支付
                if($bill -> stat != "0"){
                    return "不是申请状态订单,无法执行完成支付";
                }
                if($loginer_id != $bill -> buy_member_id){
                    return "不是买家,无权操作订单";
                }
                $this -> db -> set("pay_date", date(DATE_TIME_FMT, time()));
                break;
            case "S":
                //运营方确认收款并放币,增加买家原始资产，减少卖家原始资产
                if($bill -> stat != "1"){
                    return "不是已付款状态订单,无法执行支付确认";
                }
                if($loginer_id != $bill -> sale_member_id){
                    return "不是运营商,无权操作订单";
                }
                //更新买家资产,增加原始资产
                $buy_member_id = $bill -> buy_member_id;
                $sql_update_buy = "update ".$this -> tbl_member_resouce." set origin_amount=origin_amount+".$bill -> dtsc_amount." where member_id=".$buy_member_id.";";
//echo $sql_update_buy;
                $this -> db -> query($sql_update_buy);
                //更新运营商资产,减少原始资产
                $sale_member_id = $bill -> sale_member_id;
                $sql_update_sale = "update ".$this -> tbl_member_resouce." set origin_amount=origin_amount-".($bill -> dtsc_amount)." where member_id=".$sale_member_id.";";
                $this -> db -> query($sql_update_sale);
                $this -> db -> set("confirm_date", date(DATE_TIME_FMT, time()));
                break;
            case "X":
                if($bill -> stat != "0"){
                    return "不是申请状态订单,无法执行撤销";
                }
                //买家撤销订单，活动结束作废
                if($bill -> sale_member_id != $loginer_id && $bill -> buy_member_id != $loginer_id){
                    return "不是运营商,也不是买家,无权操作订单";
                }
                break;
            default:
                break;
        }
        $this -> db -> set("stat", $stat);
        $this -> db -> where("id", $id);
        $this -> db -> update($this -> tbl_funding_bill);

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE)
        {
            return "事务执行失败";
        }

        return true;

    }

    /** 接受私募的会员商操作区域 End**/

}
?>
