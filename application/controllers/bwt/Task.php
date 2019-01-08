<?php
/**
* @title 处理定时任务
* @desc  处理定时任务
**/
class Task extends CI_Controller 
{
    public function __construct()
    {
        parent::__construct();  
	$this->load->model(array('bill_model','member_model'));		
    }

    /**
    private function write_file(){
        $count++;
        if(file_exists('lock.txt'))
        {
        }   

        // 写文件操作开始
        $fp= fopen("test".$count.".txt","w");
        if($fp)
        {
            for($i=0;$i<5;$i++)
            {
                $flag=fwrite($fp,$i."这里是文件内容www.uacool.com\r\n");
                if(!$flag)
                {
                    echo"写入文件失败";
                    break;
                }
            }
        }
        fclose($fp);
        // 写文件操作结束

    }
    **/


    //开启每小时执行一次的任务
    public function hour_task()
    {
        // 设定定时任务启动条件
        $dict = $this -> bill_model -> getKeyValFromParams("3","flg");
        if($dict["params_val"] == 'off'){
            show300("定时任务指令处于关闭状态");
        }
        
        ignore_user_abort(TRUE);// 设定关闭浏览器也执行程序
        set_time_limit(0);     // 设定响应时间不限制，默认为30秒
        while(TRUE)
        {
            // 设定定时任务终止条件
            $dict = $this -> bill_model -> getKeyValFromParams("3","flg");
            if($dict["params_val"] == 'off'){
                break;
            }
            //矿机产出
            $this -> bill_model -> machineProduct();
            /**  释放改为手动释放
            //每天凌晨1点进行每日计算
            $now = time();
            $hour = date('H', $now);
            if($hour == '01'){
                //释放可售资产为可交易资产
                $this -> bill_model -> toTradeRes2TradeableRes();
            }
            **/
            
            sleep(3600);          // 每小时钟执行一次
            
        }
        echo "定时任务执行停止..".date("Y-m-d H:i:s",time());
    }

}

