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
    * @title 购买矿机
    * @desc  执行矿机购买流程
    * @input {"name":"member_id","require":"true","type":"int","desc":"会员id"}	
    * @input {"name":"machine_id","require":"true","type":"int","desc":"矿机id"}	
    * @input {"name":"bill_hour_amount","require":"true","type":"int","desc":"租用时长"}	
    * @output {"name":"code","type":"int","desc":"200:成功,300各种提示信息"}
    * @output {"name":"msg","type":"string","desc":"信息说明"}
    **/
    public function buy_machine()
    {
        //$id = $this->input->post('id');
        $requires = array("member_id"=>"缺少会员id","machine_id"=>"缺少矿机id","bill_hour_amount"=>"缺少租用时长");
        $params = array();
        foreach($requires as $k => $v)
        {
            if($this->input->post($k) == null){
                show300($v);
            }
            $params[$k] = $this -> input -> post($k);
        }
        //var_dump($_POST);
        $data = $this -> bill_model -> buyMachine($params);
        if($data == true){
            show200($data);
        }else{
            show300($data);
        }
    }

    /**
    * @title 购买矿机清单列表
    * @desc  执行矿机购买流程
    * @input {"name":"page","require":"true","type":"int","desc":"页码，1开始"}	
    * @input {"name":"member_id","require":"true","type":"int","desc":"会员id"}	
    * @output {"name":"code","type":"int","desc":"200:成功,300各种提示信息"}
    * @output {"name":"msg","type":"string","desc":"信息说明"}
    * @output {"name":'create_date',"type":'timestamp',"desc":'创建时间'}
    * @output {"name":'modify_date',"type":'timestamp',"desc":'更新时间'}
    * @output {"name":'member_id',"type":'int(11)',"desc":'会员id'}
    * @output {"name":'machine_id',"type":'int(11)',"desc":'矿机id'}
    * @output {"name":'machine_title',"type":'varchar(45)',"desc":'矿机名称'}
    * @output {"name":'bill_unit_produce',"type":'double(16,6)',"desc":'每小时产量'}
    * @output {"name":'bill_price',"type":'double(16,6)',"desc":'单位租金'}
    * @output {"name":'bill_hour_amount',"type":'int(11)',"desc":'租用时长'}
    * @output {"name":'bill_real_pay',"type":'double(16,6)',"desc":'花费金额'}
    * @output {"name":'bill_date_start',"type":'datetime',"desc":'租用起始时间'}
    * @output {"name":'bill_date_end',"type":'datetime',"desc":'租用截止时间'}
    **/

    public function bill_machine_list()
    {
        if(empty($this->input->post('id')))
        {
            show300('缺少id');
        }
        $id = $this->input->post('id');
        $data = $this -> bill_model -> getBillOutline($id);
        //$data = array("base_amount"=>100)
        //$data["base_amount"] = 200;
        show200($data);
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
            show300('缺少id');
        }
        $id = $this->input->post('id');
        $data = $this -> bill_model -> getBillOutline($id);
        show200($data);
    }
	
}
