<?php
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2014 - 2017, British Columbia Institute of Technology
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package	CodeIgniter
 * @author	EllisLab Dev Team
 * @copyright	Copyright (c) 2008 - 2014, EllisLab, Inc. (https://ellislab.com/)
 * @copyright	Copyright (c) 2014 - 2017, British Columbia Institute of Technology (http://bcit.ca/)
 * @license	http://opensource.org/licenses/MIT	MIT License
 * @link	https://codeigniter.com
 * @since	Version 1.0.0
 * @filesource
 */
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Application Controller Class
 *
 * This class object is the super class that every library in
 * CodeIgniter will be assigned to.
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	Libraries
 * @author		EllisLab Dev Team
 * @link		https://codeigniter.com/user_guide/general/controllers.html
 */
class CI_Controller {

	/**
	 * Reference to the CI singleton
	 *
	 * @var	object
	 */
	private static $instance;

	/**
	 * Class constructor
	 *
	 * @return	void
	 */
	public function __construct()
	{
		self::$instance =& $this;

		// Assign all the class objects that were instantiated by the
		// bootstrap file (CodeIgniter.php) to local class variables
		// so that CI can run as one big super object.
		foreach (is_loaded() as $var => $class)
		{
			$this->$var =& load_class($class);
		}

		$this->load =& load_class('Loader', 'core');
		$this->load->initialize();
		log_message('info', 'Controller Class Initialized');
	}

	// --------------------------------------------------------------------

	/**
	 * Get the CI singleton
	 *
	 * @static
	 * @return	object
	 */
	public static function &get_instance()
	{
		return self::$instance;
	}
//根据页码获取偏移量
	public function getPage($page,$limit){
		$offset=($page-1)*$limit;
		return $offset;
	}
		/**
     * @title 图片上传base64接口
     * @desc  (图片上传base64接口)
     * @output {"name":"code","type":"int","desc":"200:成功,300各种提示信息"}
     * @output {"name":"data.picPath","require":"true","type":"string","desc":"图片路径"}
     */
	public function base64_upload($base64_image){   
	//$base64_image="data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDABALDA4MChAODQ4SERATGCgaGBYWGDEjJR0oOjM9PDkzODdASFxOQERXRTc4UG1RV19iZ2hnPk1xeXBkeFxlZ2P/2wBDARESEhgVGC8aGi9jQjhCY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2P/wAARCAAcAHQDASIAAhEBAxEB/8QAHwAAAQUBAQEBAQEAAAAAAAAAAAECAwQFBgcICQoL/8QAtRAAAgEDAwIEAwUFBAQAAAF9AQIDAAQRBRIhMUEGE1FhByJxFDKBkaEII0KxwRVS0fAkM2JyggkKFhcYGRolJicoKSo0NTY3ODk6Q0RFRkdISUpTVFVWV1hZWmNkZWZnaGlqc3R1dnd4eXqDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uHi4+Tl5ufo6erx8vP09fb3+Pn6/8QAHwEAAwEBAQEBAQEBAQAAAAAAAAECAwQFBgcICQoL/8QAtREAAgECBAQDBAcFBAQAAQJ3AAECAxEEBSExBhJBUQdhcRMiMoEIFEKRobHBCSMzUvAVYnLRChYkNOEl8RcYGRomJygpKjU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6goOEhYaHiImKkpOUlZaXmJmaoqOkpaanqKmqsrO0tba3uLm6wsPExcbHyMnK0tPU1dbX2Nna4uPk5ebn6Onq8vP09fb3+Pn6/9oADAMBAAIRAxEAPwDz+t7SfDkt7bpPIDslPyAHk1hxDMqD1YV694bt0TA4PlIFXHT8KAMq38EW4gVWtkJ65Zjk1z+t+FRZE+UrqT0BOQPxr1Ws/WYUlsHLgHbyM0AeIyI0blHBBHY02vRLzQtPvtrvBOrdyneqv/CKaZ/cu/zoA4Wiu7/4RPTP+ed3+dH/AAiemf8APO7/ADoA4Siu7/4RPTP+ed3+dH/CJ6Z/zzu/zoA5LStPN/MRnCL1rvdJ8MRi1TbaoSP+WknJP4U/TdMtLSSG3hhZQW5Zxya7FQFAAGAKAOH1HwnbLbt51tzztljONp+nSuE1OyayuTHtbZ/CT3r3J1DqVYZBGCK8u8cRhDCAv3XYZoA5KiiigBVOGB9DXo/hjXUe3jlbarKoSRAew6GvN6fFNJCcxuVz6UAe5pqFq6bhMuPesHxFr0KQmKJuOrNXmP8AaV6f+XmT86jlu55gRLKzZ9TQBo6l4gv7q73x3c0cacIqOVA/Kq6a1qS7v9NnORjlycVQooA0DrmqHGb+c44GWoGuaopyt/OPo9Z9FAF/+29T/wCf6b/vqj+29T/5/pv++qoUUAdd4f1+4lKxXMu9ozlWY8mvQrPVbe4iUs4Rscg14ijsjBkJBHcVvaZrN3GkaEq6jswPP60Aen3uqwQQt5bb3xxjtXmPivUxdvHbKCPLYsx9SaXU9dvZIZIgyRoeyLg/n1rn3dnYs5JJ7mgBtFFFAH//2Q==";
		$file_size=strlen($base64_image);    //4.8kb 4925  8k
        // 验证文件条件是否符合
        if($file_size<= 0){ # 未上传文件
              show300('未上传文件');
        }
        if($file_size>= 4088000){ # 图片大于400KB，结束传输
              show300('图片大小不符（不大于400k）');
        }
        $base64_image = str_replace(' ', '+',$base64_image);//post
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64_image, $result)){
               //匹配成功
               if($result[2] == 'jpeg'){
                     //$pri=date(YmdHis).$name;
                    $image_name = date('YmdHis').rand(1000,9999).'.jpg';
               }else{
                    $image_name = date('YmdHis').rand(1000,9999).'.'.$result[2];
               }
               $image_file = "./upload/".$image_name;
               //服务器文件存储路径
               //$image_file_1  要写入数据的文件。如果文件不存在，则创建一个新文件。
               //base64_decode(str_replace($result[1], '', $base64_image)) 要写入文件的数据。可以是字符串、数组或数据流。
               if(file_put_contents($image_file, base64_decode(str_replace($result[1], '', $base64_image)))){
                     $data['picPath'] = PHOTOPATH.$image_name;
                     return $data['picPath'];
               }else{
                    show300('上传失败');
               }                
        }else{
              show300('上传失败');
        }
    }
		/**
     * @title 图片上传接口
     * @desc  (图片上传接口)
     * @output {"name":"code","type":"int","desc":"200:成功,300各种提示信息"}
     * @output {"name":"data.picPath","require":"true","type":"string","desc":"图片路径"}
     */
	
	public function uploadPic(){		
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
	/**
	*
	* 判断是否登录
	*
	**/
	public function getId(){
		$id = $this->session->tempdata('id');
        if (empty($id)) {
            show888();
        }
		return  $id;
	}
	
}
