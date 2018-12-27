<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 截取字符串
 * @param $str string 要截取的字符串
 * @param $limit integer 要截取的长度,尽量用3的倍数
 * @param $end_char string 超长后补充字符
 */
if ( ! function_exists('cut_string')){
	function cut_string($str, $limit = 100, $end_char = '…'){
	    if (extension_loaded('mbstring') == TRUE){//开启了mbstring
	        if(mb_strlen($str) > $limit){
	            return mb_substr($str, 0, $limit).$end_char;
	        } else {
	            return $str;
	        }
	    }

		$pa = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|\xe0[\xa0-\xbf][\x80-\xbf]|[\xe1-\xef][\x80-\xbf][\x80-\xbf]|\xf0[\x90-\xbf][\x80-\xbf][\x80-\xbf]|[\xf1-\xf7][\x80-\xbf][\x80-\xbf][\x80-\xbf]/";
        preg_match_all($pa, $str, $t_string);       
        if(count($t_string[0]) > $limit) return join('', array_slice($t_string[0], 0, $limit)).$end_char;
        return join('', array_slice($t_string[0], 0, $limit));
    }
}

/**
 * 返回时间格式，当天的时间没有年月日
 * @param $date integer 时间戳
 */
if ( ! function_exists('bd_date')){
	function bd_date($date){
	    $date = intval($date);
		return date("Y-m-d", $date) != date("Y-m-d") ? date("Y-m-d H:i", $date) : date("H:i", $date);
	}
}

/**
 * 判断是否是图片格式
 * @param $suffix 文件后缀字符
 */
if ( ! function_exists('bd_is_image')){
    function bd_is_image($suffix){
        $image_arr = array('jpg','jpeg','gif','png');
        return in_array(strtolower($suffix), $image_arr);
    }
}

/**
 * 编辑器入库前替换
 * @param $suffix 文件后缀字符
 */
if ( ! function_exists('bd_delete_prefix')){
	function bd_delete_prefix($content){
	    $search = array('src="/'.PROJECT_NAME.'/ueditor/', 'href="/'.PROJECT_NAME.'/ueditor/');
	    $replace = array('src="/ueditor/', 'href="/ueditor/');
		return (PROJECT_NAME != '') ? str_replace($search, $replace, $content) : $content;
	}
}

/**
 * 编辑器出库前替换
 * @param $suffix 文件后缀字符
 */
if ( ! function_exists('bd_add_prefix')){
	function bd_add_prefix($content){
	    $search = array('src="/ueditor/', 'href="/ueditor/');
		$replace = array('src="/'.PROJECT_NAME.'/ueditor/', 'href="/'.PROJECT_NAME.'/ueditor/');
		return (PROJECT_NAME != '') ? str_replace($search, $replace, $content) : $content;
	}
}

/**
 * 验证客户端是mobile还是pc
 * @return boolean
 */
function dstrpos($string, &$arr, $returnvalue = false) {
	if(empty($string)) return false;
	foreach((array)$arr as $v) {
		if(strpos($string, $v) !== false) {
			$return = $returnvalue ? $v : true;
			return $return;
		}
	}
	return false;
}

/**
 * 格式化时间
 * */
function format_time($time)
{
    $now_time = date("Y-m-d H:i:s",time());
    $now_time = strtotime($now_time);
    $show_time = $time;
    $dur = $now_time - $show_time;
    if($dur < 0)
    {
        return date('Y-m-d',$time);
    }
    else
    {
        if($dur ==0)
        {
            return '刚刚';
        }
        else if($dur < 60)
        {
            return $dur.'秒前';
        }
        else
        {
            if($dur < 3600)
            {
                return floor($dur/60).'分钟前';
            }
            else
            {
                if($dur < 86400)
                {
                    return floor($dur/3600).'小时前';
                }
                else
                {
                    if($dur < 259200)
                    {//3天内
                        return floor($dur/86400).'天前';
                    }
                    else
                    {
                        return date('Y-m-d',$time);
                    }
                }
            }
        }
    }
}

/**
 * 格式化时间
 * */
function format_week($time)
{
    $now_time = date("Y-m-d H:i:s",time());
    $now_time = strtotime($now_time);
    $show_time = $time;
    $dur = $now_time - $show_time;

    if($dur < 0)
    {
        return date('Y-m-d H:i',$time);
    }
    else
    {
        if($dur < 60)
        {
            return '';
        }
        else
        {
           
            if($dur < 86400)
            {
                return date('H:i',$time);
            }
            else
            {  
                     
                if($dur < 604800)
                {//3天内
                  
                    $a =date('N',$time);
                  
                    $week ='';
                    switch ($a)
                    {
                        case '1':
                            $week ='星期一';
                        break;
                        case '2':
                            $week ='星期二';
                            break;
                        case '3':
                            $week ='星期三';
                            break;
                        case '4':
                            $week ='星期四';
                            break;
                        case '5':
                            $week ='星期五';
                            break;
                        case '6':
                            $week ='星期六';
                            break;
                        case '7':
                            $week ='星期日';
                            break;                        
                    }
                   
                    $b =date('H:i',$time);
                    return $week."&nbsp;&nbsp;&nbsp;".$b;
                }
                else
                {
                    return date('Y-m-d H:i',$time);
                }
                
            }
        }
    }
}

/**
 * 返回指定时间的星期数
 * @return 
 */
if ( ! function_exists('get_week')){
    function get_week($time) {
        if (empty($time)) $time = time();
        $week = date('l', $time);
        $str = '';
        switch ($week)
        {
            case 'Monday':
                $str = '星期一';
            break;
            case 'Tuesday':
                $str = '星期二';
            break;
            case 'Wednesday':
                $str = '星期三';
            break;
            case 'Thursday':
                $str = '星期四';
            break;
            case 'Friday':
                $str = '星期五';
            break;
            case 'Saturday':
                $str = '星期六';
            break;
            case 'Sunday':
                $str = '星期日';
            break;
        }
        return $str;
    }
}

/**
 * 验证客户端是mobile还是pc
 * @return boolean TRUE=手机 FALSE=pc
 */
function checkmobile() {
	global $_G;
	$mobile = array();
	static $mobilebrowser_list =array('iphone', 'android', 'phone', 'mobile', 'wap', 'netfront', 'java', 'opera mobi', 'opera mini',
			'ucweb', 'windows ce', 'symbian', 'series', 'webos', 'sony', 'blackberry', 'dopod', 'nokia', 'samsung',
			'palmsource', 'xda', 'pieplus', 'meizu', 'midp', 'cldc', 'motorola', 'foma', 'docomo', 'up.browser',
			'up.link', 'blazer', 'helio', 'hosin', 'huawei', 'novarra', 'coolpad', 'webos', 'techfaith', 'palmsource',
			'alcatel', 'amoi', 'ktouch', 'nexian', 'ericsson', 'philips', 'sagem', 'wellcom', 'bunjalloo', 'maui', 'smartphone',
			'iemobile', 'spice', 'bird', 'zte-', 'longcos', 'pantech', 'gionee', 'portalmmm', 'jig browser', 'hiptop',
			'benq', 'haier', '^lct', '320x320', '240x320', '176x220','ipad');
	$useragent = strtolower($_SERVER['HTTP_USER_AGENT']);
	if(($v = dstrpos($useragent, $mobilebrowser_list, TRUE))) {
		$_G['mobile'] = $v;
		return TRUE;
	}
	$brower = array('mozilla', 'chrome', 'safari', 'opera', 'm3gate', 'winwap', 'openwave', 'myop');
	if(dstrpos($useragent, $brower)) return FALSE;

	$_G['mobile'] = 'unknown';
	if($_GET['mobile'] === 'yes') {
		return TRUE;
	} else {
		return FALSE;
	}
}

/**
 * web/wap切换
 */
function bd_switch_web_wap($flag,$url){
	if($flag=='wap'){
		if(!checkmobile()){
			redirect(site_url($url), 'refresh');
		}	
	}
	
	if($flag=='web'){
		if(checkmobile()){
			redirect(site_url($url), 'refresh');
		}
	}
}

//得到相应后缀的小图标
function bd_fileext_png($filepath){
	return strtolower(trim(substr(strrchr($filepath, '.'), 1))).'.png';
}

/**
 * 获取文件名后缀
 * @author dyb 2012-12-12
 * @param $filename
 * @return string
 */
function bd_get_fileext($filename) {
	return strtolower(trim(substr(strrchr($filename, '.'), 1)));
}

/**
 * 文件size换算
 * $size 单位 为 B
 * @param unknown_type $size
 */
function bd_get_file_size($size = 0){
	$rzt = '0KB';
	if($size > 0 && $size < 1024){
		$rzt = $size . 'B';
	}else if($size > 1024 && $size < (1024 * 1024)){
		$rzt = number_format($size / 1024 , 2) . 'KB';
	}else if($size > (1024 * 1024) && $size < (1024 * 1024 * 1024)){
		$rzt = number_format(($size / 1024) / 1024 , 2) . 'M';
	}else if($size > (1024 * 1024 * 1024)){
		$rzt = number_format((($size / 1024) / 1024) / 1024 , 2) . 'G';
	}
	return $rzt;
}

function get_weibo_vip($weibo_id = 0){
	if($weibo_id == 0) return false;
	$vip_b = array('1937649537','2073102055');
	$vip_y = array('1500998893','1834790933');
	if(@in_array($weibo_id,$vip_b)){
		return 'blue';
	}else if(@in_array($weibo_id,$vip_y)){
		return 'yellow';
	}else{
		return 'black';
	}
}



function jam_format_time($time,$flag = ''){
	$times=intval((time()-$time)/60);

	if($flag == 'index'){
		if($times<60){
			if($times == 0) return '1分钟';
			else return $times.'分钟';
		}else if($times>60){
			return intval($times/60).'小时';
		}
	}else{
		if($times<=2){
			return '刚刚';
		}else if($times<60){
			return $times.'分钟前';
		}else if($times>60){
			return intval($times/60).'小时前';
		}
	}
}

//设置连线大数据cookie
function call_set_cookie($call_id){
	$CI =& get_instance();
	$CI->load->helper('cookie');
	
	set_cookie("bd_call_cookie[".$call_id."]",$call_id, 3600*24*30*3);
// 	if (!isset($_COOKIE["bd_call_cookie[".$call_id."]"])){
// 		setcookie("bd_call_cookie[".$call_id."]",$call_id, time()+3600*24*30*3);
// 	}
}

//获取连线大数据cookie
function call_get_cookie($call_id){
	$result =  0;
	
	$CI =& get_instance();
	$CI->load->helper('cookie');
	$bd_call_cookie = get_cookie("bd_call_cookie");
	if((!empty($bd_call_cookie)) && (!empty($bd_call_cookie[$call_id]))){
		$result = intval($bd_call_cookie[$call_id]);
	}
	return $result;
// 	$result =  0 ;
// 	if (isset($_COOKIE["bd_call_cookie"])){
// 		$bd_call_cookie = $_COOKIE["bd_call_cookie"];
// 		if(!empty($bd_call_cookie[$call_id])){
// 			$result = $call_id;
// 		}
// 	}
// 	return $result;
}

//获取连线已读数量
function call_cookie_count(){
	$CI =& get_instance();
	$CI->load->helper('cookie');
	
	$result =  0 ;
	$bd_call_cookie = get_cookie("bd_call_cookie");
	if(!empty($bd_call_cookie)){
		$result = count($bd_call_cookie);
	}
	return $result;
	//var_dump($bd_call_cookie);
// 	$result =  0 ;
// 	if (isset($_COOKIE["bd_call_cookie"])){
// 		$result = count($_COOKIE["bd_call_cookie"]);
// 	}
// 	return $result;
}

//使用毫秒级别时间数字串和id组合唯一订单编号@input  member_id:会员唯一id-type:int
function get_bill_unique_id($member_id){
    list($t1, $t2) = explode(' ', microtime());
    $val =  date("YmdHis",floatval($t2)).''.intval(floatval($t1) * 1000);
    $val = intval($val);
    $val += $member_id;
    $val *= 11;
    return $val;
}


/* End */
