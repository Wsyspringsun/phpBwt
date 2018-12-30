<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');
class Bill_model extends MY_Model
{
    private $tbl_member_resouce ='member_resouce'; //会员资产汇总
    private $tbl_member_machine_bill ='member_machine_bill'; //会员矿机租用表
    private $tbl_origin_res_bill ='origin_res_bill';//原始资产交易表
    private $tbl_origin_res_buy_bill ='origin_res_buy_bill';//原始资产买入下单表
    private $tbl_origin_res_sale_bill ='origin_res_sale_bill';//原始资产卖出下单表
    private $tbl_origin_res_pay_bill ='tbl_origin_res_pay_bill';//原始资产卖出下单表

    public function __construct()
    {
        parent::__construct($this->tbl_member_resouce);
        parent::__construct($this->tbl_member_machine_bill);
        parent::__construct($this->tbl_origin_res_bill);
        parent::__construct($this->tbl_origin_res_buy_bill);
        parent::__construct($this->tbl_origin_res_sale_bill);
        parent::__construct($this->tbl_origin_res_pay_bill);
    }

    /** 处理矿机订单Start **/

    //购买矿机
    public function buyMachine($params)
    {
        $this->db->trans_start();
        $machine_id = $params["machine_id"];
        var_dump($machine_id);
        //获取矿机，判断矿机是否存在
        $m =  $this->db->get_where('machine',array('id' => $machine_id))->row();
        if($m == null)
            return "当前矿机已经不存在,请刷新数据";
        $bill_hour_amount = $params["bill_hour_amount"];
        $dtfmt = "Y-m-d H:i:s";
        //矿机租用起始时间
        $bill_date_start = time();
        //矿机租用截止时间
        $bill_date_end = time() + $bill_hour_amount * 60 * 60;
        //单价
        $bill_price = $m -> price;
        //花费金额
        $bill_real_pay = $bill_price * $bill_hour_amount;
        $member_id = $params["member_id"];
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
            "bill_date_end" => date($dtfmt,$bill_date_end),
            "bill_date_end" => date($dtfmt,$bill_date_end)
        );
        //减少用户原始资产
        $mem_bill_outline = $this -> getBillOutline($member_id);
var_dump($mem_bill_outline);
var_dump($bill_real_pay);
        if($mem_bill_outline == null)
        {
            return "当前用户已经不存在";
        }
        $avaliable_origin_res = $mem_bill_outline -> origin_amount - $this -> getOriginResOfForen($member_id) ;
        if($avaliable_origin_res < $bill_real_pay)
        {
            return "用户可用原始资源不足，需要".$bill_real_pay.",拥有:".$avaliable_origin_res;
        }
        $mem_bill_outline -> origin_amount = $mem_bill_outline -> origin_amount - $bill_real_pay;

        //添加矿机购买数据
        $this -> db -> insert($this -> tbl_member_machine_bill,$bill_data);
        //更新会员资源数据
        $this -> db -> where("id",$member_id);
        $this -> db -> update($this -> tbl_member_resouce,$mem_bill_outline);
        $this->db->trans_commit();

        if ($this->db->trans_status() === FALSE)
        {
            return "事务执行失败";
        }
        return true;
    }

    //获取矿机租用明细
    public function machine_bill_list($member_id,$offset)
    {
        return $this -> db -> order_by('create_date DESC') -> limit(PAGESIZE,$offset) -> get_where($this->tbl_member_machine_bill,array('member_id' => $member_id))->result();
    }
    /** 处理矿机订单 End **/

    /** 处理原始资产交易 **/

    //原始资产购买下单
    public function buyOriginRes($params){
        //当天下单不得超过5笔?

        $member_id = $params['buy_member_id'];
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
    }

    //卖给确定的买单
    public function sale2BuyBillOriginRes($params){
        $this->db->trans_start();
        //买单
        $buy_id = $params['buy_id'];
        $buy_bill = $this -> db -> get_where($this -> tbl_origin_res_buy_bill,array('id' => $buy_id)) -> row();
        if($buy_bill === null){
            return "买入单不存在";
        }

        $amount = $buy_bill -> amount;
        $unit_price = $buy_bill -> unit_price;
        $pay_amount = $buy_bill -> pay_amount;

        //TODO:卖出的额度限制

        $stat = '0-0' ;
        //更新买单状态
        $buy_bill -> stat = $stat;
        $this -> db -> where ('id', $buy_id);
        $this -> db -> update($this -> tbl_origin_res_buy_bill,$buy_bill);
        //创建卖单
$sale_member_id = $params['sale_member_id'];
        $sale_data = array(
            'sale_member_id' => $sale_member_id,
            'sale_bill_no' => get_bill_unique_id($sale_member_id),
            'amount' => $amount,
            'unit_price' => $unit_price,
            'pay_amount' => $pay_amount,
            'stat' => $stat //状态：匹配:0
        );
        $this -> db -> insert($this -> tbl_origin_res_sale_bill,$sale_data);
        $sale_id =  $this->db->insert_id();
        //新增业务订单数据
        $data = array(
            'buy_bill_id' => $buy_id,
            'sale_bill_id' => $sale_id,
            'origin_bill_no' => get_bill_unique_id($buy_id + $sale_id),
            'amount' => $amount,
            'unit_price' => $unit_price,
            'pay_amount' => $pay_amount,
            'stat' => $stat//状态：匹配成功
        );
        //业务订单入库
        $this -> db -> insert($this -> tbl_origin_res_bill,$data);
        $origin_res_bill_id = $this->db->insert_id();

        $this->db->trans_commit();//提交事务

        if ($this->db->trans_status() === FALSE)
        {
            return "事务执行失败";
        }
        return $origin_res_bill_id;
    }

    //付款完成业务处理
    public function payed4BillOriginRes($params){
        $this->db->trans_start();
        $origin_bill_id = $params["origin_bill_id"];
        $origin_bill = $this->db->get_where($this -> tbl_origin_res_bill,array('id' => $origin_bill_id))->row();
        if($origin_bill === null){
            return "当前订单不存在";
        }
        $pic_dir = $params["pic_dir"];
        $thirdpart_bill_no = $params["thirdpart_bill_no"];
        $data = array(
            "origin_bill_id" => $origin_bill_id ,
            "pay_amount" => $origin_bill -> pay_amount,
            "pic_dir" => $pic_dir,
            "thirdpart_bill_no" => $thirdpart_bill_no
        );
        $this -> db -> insert($tbl_origin_res_pay_bill, $data);
        $origin_bill -> stat = $origin_bill -> stat.'-0';
        $this -> db -> where ('id', $origin_bill_id);
        $this -> db -> update($this -> tbl_origin_res_bill,$origin_bill);

        $this->db->trans_commit();
        if ($this->db->trans_status() === FALSE)
        {
            return "事务执行失败";
        }
        return true;
    }


    /** 处理原始资产交易 End**/



    /*** 处理获取个人资产汇总相关数据 **/

    //获取各项资产汇总
    public function getBillOutline($id){
        return $this->db->get_where($this->tbl_member_resouce,array('id' => $id))->row();
    }

    //获取个人冻结得原始资产数量
    public function getOriginResOfForen($id){
        return 0;
    }

    //获取个人可用的原始资产数量
    public function getOriginResOfAvaliable($id){
            return 0;
    }

    /*** 处理获取个人资产汇总相关数据End **/

}
?>
