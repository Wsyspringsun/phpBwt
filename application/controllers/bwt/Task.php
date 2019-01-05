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

    public function test()
    {
        show200("OK");
    }

    private function write_file(){
        $count++;
        // 设定定时任务终止条件
        if(file_exists('lock.txt'))
        {
            //break;
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


    //开启每日执行一次的任务
    public function day_task()
    {
        $data = $this -> bill_model -> toTradeRes2TradeableRes();
        show200($data);
        //ignore_user_abort(TRUE);// 设定关闭浏览器也执行程序
        //set_time_limit(0);     // 设定响应时间不限制，默认为30秒
         
        //$count= 0;
        //echo "Strart.....";
        //while(TRUE)
        //{
            //sleep(20);          // 每5秒钟执行一次
        //}
    }

}

