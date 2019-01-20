<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Upload extends CI_Controller 
{
    private static $data = array();
    public function __construct()
    {
        parent::__construct();  
		$this->load->model(array());		
    }
		/**
     * @title 图片上传接口
     * @desc  (图片上传接口)
     * @output {"name":"code","type":"int","desc":"200:成功,300各种提示信息"}
     * @output {"name":"data.picPath","require":"true","type":"string","desc":"图片路径"}
     */
	
	public function uploadPicc(){
		//print_r(333);exit;		
			if($_FILES["file"]["error"]){
				echo $_FILES["file"]["error"];    
			}else{
					if(($_FILES["file"]["type"]=="image/png"||$_FILES["file"]["type"]=="image/jpeg")&&$_FILES["file"]["size"]<1024000){
							 //$filename ="./img/".time().$_FILES["file"]["name"];
							 //$filename =iconv("UTF-8","gb2312",$filename);
							 $filename =date('YmdHis').rand(1000,9999).'.jpg';
							if(file_exists($filename)){
								show300('该文件已存在');
							}else{  
									$config['upload_path']='./upload/';
									$config['allowed_types']='gif|jpg|png';
									$config['file_name']=$filename;
									$this->load->library('upload', $config);
									$this->upload->do_upload('file');
									$data['picPath']=PHOTOPATH.$filename;
									show200($data);
							}        
					}else{
						show300('文件类型不对');
					}
				}
	}
	
}
