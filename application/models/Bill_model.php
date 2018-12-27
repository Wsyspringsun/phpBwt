<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');
class Bill_model extends MY_Model
{
    private $tbl_member_resouce ='member_resouce';
    private $tbl_member_machine_bill ='member_machine_bill';
    public function __construct()
    {
        parent::__construct($this->tbl_member_resouce);
        parent::__construct($this->tbl_member_machine_bill);
    }

    /** 处理矿机订单Start **/

    //购买矿机
    public function buyMachine($params)
    {
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
            "id" => get_bill_unique_id(0),
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
        if($mem_bill_outline -> origin_amount < $bill_real_pay)
        {
            return "用户原始资源不足，需要".$bill_real_pay.",拥有:".$mem_bill_outline -> origin_amount;
        }
        $mem_bill_outline -> origin_amount = $mem_bill_outline -> origin_amount - $bill_real_pay;

        $this->db->trans_start();
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
    /** 处理矿机订单 End **/



    /***
    *获取个人钱包资产汇总
    **/
    public function getBillOutline($id){
            return $this->db->get_where($this->tbl_member_resouce,array('id' => $id))->row();
    }
}
?>
