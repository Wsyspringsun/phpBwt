<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Machine extends CI_Controller 
{
    private static $data = array();
    public function __construct()
    {
        parent::__construct();  
		$this->load->model(array('machine_model'));		
    }
	
	
	 /**
	 * @title 矿机列表
     * @desc  (矿机列表，赠送除外)
	 * @input {"name":"page","require":"true","type":"int","desc":"页数"}	
	 
	 * @output {"name":"code","type":"int","desc":"200:成功,300各种提示信息"}
     * @output {"name":"msg","type":"string","desc":"信息说明"}
	 * @output {"name":"data.id","require":"true","type":"int","desc":"矿机id"}	
	 * @output {"name":"data.title","require":"true","type":"string","desc":"名称"}	
	 * @output {"name":"data.unit_produce","require":"true","type":"float","desc":"产量/小时"}	
	 * @output {"name":"data.price","require":"true","type":"float","desc":"价格"}	
	 * @output {"name":"data.picture","require":"true","type":"string","desc":"图片"}	
	 * @output {"name":"data.create_date","require":"true","type":"date","desc":"创建时间"}	
	 * @output {"name":"data.modify_date","require":"true","type":"date","desc":"更新时间"}	
	 */
    public function machineList()
    {
		$page=empty($this->input->post('page'))?1:($this->input->post('page'));//页数
		//$page=2;
		$offset=$this->getPage($page,HOMEMACHINE);//偏移量
		$data['data']=$this->machine_model->getMachineList(HOMEMACHINE,$offset);
		$data['count']=$this->machine_model->getMachineCount();
		show200($data);
    }
	
	 /**
	 * @title 矿机详细查看
     * @desc  (矿机详细查看)
	 * @input {"name":"id","require":"true","type":"int","desc":"id"}	
	 
	 * @output {"name":"code","type":"int","desc":"200:成功,300各种提示信息"}
     * @output {"name":"msg","type":"string","desc":"信息说明"}
	 * @output {"name":"data.id","require":"true","type":"int","desc":"矿机id"}	
	 * @output {"name":"data.title","require":"true","type":"string","desc":"名称"}	
	 * @output {"name":"data.unit_produce","require":"true","type":"float","desc":"产量/小时"}	
	 * @output {"name":"data.price","require":"true","type":"float","desc":"价格"}	
	 * @output {"name":"data.picture","require":"true","type":"string","desc":"图片"}	
	 * @output {"name":"data.create_date","require":"true","type":"date","desc":"创建时间"}	
	 * @output {"name":"data.modify_date","require":"true","type":"date","desc":"更新时间"}	
	 */
    public function machineDetail()
    {
		$this->getId();
		$id=trim($this->input->post('id'));
		//$id=1;
		if(!$id){
			show300('矿机id不能为空');
		}
		$data=$this->machine_model->getwhere(['id'=>$id,'*']);
		show200($data);
    }
	
	
}
