<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Bill extends CI_Controller 
{
    private static $data = array();
    public function __construct()
    {
        parent::__construct();  
	$this->load->model(array('bill_model'));		
    }
	
	
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
     * @output {"name":"data.create_date","require":"true","type":"date","desc":"创建时间"}	
     * @output {"name":"data.modify_date","require":"true","type":"date","desc":"更新时间"}	
     **/
    public function bill_outline()
    {
        if(empty($this->input->post('id')))
        {
            show401('缺少id');
        }
        $id = $this->input->post('id');
        $data = $this -> bill_model -> getBillOutline($id);
        //$data = array("base_amount"=>100)
        //$data["base_amount"] = 200;
        show200($data);
    }
	
}
