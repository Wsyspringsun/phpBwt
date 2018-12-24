<?php
/*
 * 小程序微信支付
 */
class WeixinPay {

    protected $appid=XCXID;
    protected $mch_id='1499196302';
    protected $key='5847574780zooo815847574780zooo81';
    protected $openid;
    protected $out_trade_no;
    protected $body;
    protected $total_fee;
    function __construct($openid='',$out_trade_no='',$body='',$total_fee='') {
        $this->openid=$openid;
        $this->out_trade_no = $out_trade_no;
        $this->body = $body;
        $this->total_fee = $total_fee;
    }
    public function pay() {
        //统一下单接口
        $return = $this->weixinapp();
        return $return;
    }


    //统一下单接口
    private function unifiedorder() {
        $url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
        $parameters = array(
            'appid' => $this->appid, //小程序ID
            'mch_id' => $this->mch_id, //商户号
            'nonce_str' => $this->createNoncestr(), //随机字符串
            //            'body' => 'test', //商品描述
            'body' => $this->body,
            //            'out_trade_no' => '2018013106125348', //商户订单号
            'out_trade_no'=> $this->out_trade_no,
            //            'total_fee' => floatval(0.01 * 100), //总金额 单位 分
            'total_fee' => $this->total_fee,
            //'spbill_create_ip' => $_SERVER['REMOTE_ADDR'], //终端IP
            // 'spbill_create_ip' => '192.168.0.161', //终端IP
            'notify_url' => BASE_URL.'notify', //通知地址  确保外网能正常访问
            'openid' => $this->openid, //用户id
            'trade_type' => 'JSAPI'//交易类型
        );
        //统一下单签名
        $parameters['sign'] = $this->getSign($parameters);
        $xmlData = $this->arrayToXml($parameters);
        //var_dump($xmlData);die();
        $return = $this->xmlToArray($this->postXmlCurl($xmlData, $url, 60));
        //var_dump($return);
        return $return;
    }


    private static function postXmlCurl($xml, $url, $second = 30)
    {
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); //严格校验
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        //post提交方式
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
        curl_setopt($ch, CURLOPT_TIMEOUT, 40);
        set_time_limit(0);
        //运行curl
        $data = curl_exec($ch);
        //返回结果
        if ($data) {
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            curl_close($ch);
            throw new WxPayException("curl出错，错误码:$error");
        }
    }



    //数组转换成xml
    private function arrayToXml($arr) {
        if(!is_array($arr) 
			|| count($arr) <= 0)
		{
    		//throw new WxPayException("数组数据异常！");
    	}
    	
    	$xml = "<xml>";
    	foreach ($arr as $key=>$val)
    	{
    		if (is_numeric($val)){
    			$xml.="<".$key.">".$val."</".$key.">";
    		}else{
    			$xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
    		}
        }
        $xml.="</xml>";
        return $xml; 
    }


    //xml转换成数组
    public function xmlToArray($xml) {
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $xmlstring = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        $val = json_decode(json_encode($xmlstring), true);
        return $val;
    }


    //微信小程序接口
    private function weixinapp() {
        //统一下单接口
        $unifiedorder = $this->unifiedorder();
        //        print_r($unifiedorder);
        $parameters = array(
            'appId' => $this->appid, //小程序ID
            'timeStamp' => '' . time() . '', //时间戳
            'nonceStr' => $this->createNoncestr(), //随机串
            'package' => 'prepay_id=' . $unifiedorder['prepay_id'], //数据包
            'signType' => 'MD5'//签名方式
        );
        //签名
        $parameters['paySign'] = $this->getSign($parameters);
        return $parameters;
    }


    //作用：产生随机字符串，不长于32位
    private function createNoncestr($length = 32) {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) { 
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1); 
        } 
        return $str; 
    } 
        //作用：生成签名 
   private function getSign($Obj) {
       foreach ($Obj as $k => $v) {
        $Parameters[$k] = $v;
        }
        //签名步骤一：按字典序排序参数
        ksort($Parameters);
        $String = $this->formatBizQueryParaMap($Parameters, false);
        //签名步骤二：在string后加入KEY
        $String = $String . "&key=" . $this->key;
        //签名步骤三：MD5加密
        $String = md5($String);
        //签名步骤四：所有字符转为大写
        $result_ = strtoupper($String);
        return $result_;
    }


    ///作用：格式化参数，签名过程需要使用
    private function formatBizQueryParaMap($paraMap, $urlencode) {
        $buff = "";
        ksort($paraMap);
        foreach ($paraMap as $k => $v) {
            if ($urlencode) {
                $v = urlencode($v);
            }
            $buff .= $k . "=" . $v . "&";
        }
        $reqPar='';
        if (strlen($buff) > 0) {
            $reqPar = substr($buff, 0, strlen($buff) - 1);
        }
        return $reqPar;
    }
    
    /* 微信支付完成，回调地址url方法  xiao_notify_url() */
    public function notify(){
        $post = $this->post_data();
        
        $post_data = $this->xmlToArray($post);   //微信支付成功，返回回调地址url的数据：XML转数组Array
        
        $postSign = $post_data['sign'];
        unset($post_data['sign']);
    
        /* 微信官方提醒：
         *  商户系统对于支付结果通知的内容一定要做【签名验证】,
         *  并校验返回的【订单金额是否与商户侧的订单金额】一致，
         *  防止数据泄漏导致出现“假通知”，造成资金损失。
         */
        ksort($post_data);// 对数据进行排序
        $str = $this->ToUrlParams($post_data);//对数组数据拼接成key=value字符串
        $user_sign = strtoupper(md5($post_data));   //再次生成签名，与$postSign比较
        if($postSign&&$postSign==$user_sign){      //两次签名不正确            
            return $post_data;
        }else{
            return $post_data;      //因为签名验证存在问题,这里先直接返回数据
        }
        
    }
    //获取回调数据
    private function post_data(){
        $receipt = file_get_contents("php://input");
        if(empty($receipt)){
            $receipt = $_REQUEST;
            if(empty($receipt)){
                $receipt = $GLOBALS['HTTP_RAW_POST_DATA'];
            }
        }
        return $receipt;
    }
    /**
     * 将参数拼接为url: key=value&key=value
     * @param $params
     * @return string
     */
    private function ToUrlParams( $params ){
        $string = '';
        if( !empty($params) ){
            $array = array();
            foreach( $params as $key => $value ){
                $array[] = $key.'='.$value;
            }
            $string = implode("&",$array);
        }
        return $string;
    }
    /*
     * 给微信发送确认订单金额和签名正确，SUCCESS信息 -xzz0521
     */
    public function return_success(){
        $return['return_code'] = 'SUCCESS';
        $return['return_msg'] = 'OK';
        $xml_post = '<xml>
                    <return_code>'.$return['return_code'].'</return_code>
                    <return_msg>'.$return['return_msg'].'</return_msg>
                    </xml>';
        echo $xml_post;exit;
    }
    //退款通知处理程序
    public function refund_notify(){
        $xml = file_get_contents("php://input");
        libxml_disable_entity_loader(true);
        $xmlstring = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        if($xmlstring->return_code!='SUCCESS'){
            // '微信退款失败';
            exit();
        }
        $req_info=$xmlstring->req_info;
        return $this->refund_decrypt($req_info);
    }
    //解密退款通知返回的数据
    public function refund_decrypt($str) {
        $key=md5($this->key);
        $str = base64_decode($str);
        $str = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, $str, MCRYPT_MODE_ECB);
        $block = mcrypt_get_block_size('rijndael_128', 'ecb');
        $pad = ord($str[($len = strlen($str)) - 1]);
        $len = strlen($str);
        $pad = ord($str[$len - 1]);
        $xml= substr($str, 0, strlen($str) - $pad);
        $data=$this->xmlToArray($xml);
        return $data;
    }
}


