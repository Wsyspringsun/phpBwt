<?php

/**
 * wechat php test
 */

//define your token
//define("TOKEN", "shadow2016 ");
//$wechatObj = new wechatCallbackapiTest();
//$wechatObj->valid();

class WechatCallbackapiTest
{
    public function valid()
    {
        $echoStr = $_GET["echostr"];

        //valid signature , option
        if($this->checkSignature()){
            echo $echoStr;
            exit;
        }
    }

    public function responseMsg()
    {
        //get post data, May be due to the different environments
        $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];

        //extract post data
        if (!empty($postStr)){
            /* libxml_disable_entity_loader is to prevent XML eXternal Entity Injection,
               the best way is to check the validity of xml by yourself */
            libxml_disable_entity_loader(true);
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $fromUsername = $postObj->FromUserName;
            $toUsername = $postObj->ToUserName;
            $keyword = trim($postObj->Content);
            $time = time();
            $textTpl = "<xml>
							<ToUserName><![CDATA[%s]]></ToUserName>
							<FromUserName><![CDATA[%s]]></FromUserName>
							<CreateTime>%s</CreateTime>
							<MsgType><![CDATA[%s]]></MsgType>
							<Content><![CDATA[%s]]></Content>
							<FuncFlag>0</FuncFlag>
							</xml>";
            if(!empty( $keyword ))
            {
                $msgType = "text";
                $contentStr = "Welcome to wechat world!";
                $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
                echo $resultStr;
            }else{
                echo "Input something...";
            }

        }else {
            echo "";
            exit;
        }
    }
    public function checkSignature()
    {
        // you must define TOKEN by yourself
        if (!defined("TOKEN")) {
            throw new Exception('TOKEN is not defined!');
        }

        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];

        $token = TOKEN;
        $tmpArr = array($token, $timestamp, $nonce);
        // use SORT_STRING rule
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );

        if( $tmpStr == $signature ){
            return true;
        }else{
            return false;
        }
    }

    public function _config($url){
        $jsapiTicket = $this->getJsApiTicket();
        //echo "<br/>";
        // 注意 URL 一定要动态获取，不能 hardcode.
        //$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        //$url = "$protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
       // $url = "$protocol$_SERVER[HTTP_HOST]/application/views/weixin/page2.html";
        //$url=urlencode($url);
        $timestamp = time();
        $nonceStr = $this->createNonceStr();
        // 这里参数的顺序要按照 key 值 ASCII 码升序排序
        $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";
        $signature = sha1($string);
        $data=array(
            'debug'=>true,//开启调用模式
            'appId'=>WXAPPID,
            'timestamp'=>$timestamp,//签名的时间戳
            'nonceStr'=>$nonceStr,//生成签名的随机串
            'signature'=>$signature,//签名
            'jsApiList'=>['openLocation','updateAppMessageShareData','previewImage','closeWindow','onMenuShareTimeline','onMenuShareAppMessage'
            ],//需要使用的js接口列表

        );
        $data=json_encode($data);
        return $data;

    }

    public function createNonceStr($length = 16) {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }
    /**
     * @return mixed
     *  获取jsapi_ticket
     */
    public function getJsApiTicket() {
        $accessToken = $this->getAccessToken();
        $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=$accessToken";
        $res = json_decode($this->curl_request($url),true);
        return $res['ticket'];
    }
    /**
     * @return mixed
     * 获取access_Token
     */
    public function getAccessToken() {
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".WXAPPID."&secret=".WXMY;
        $res = json_decode($this->curl_request($url),true);
        return $res['access_token'];
    }

    /**
     *  网页授权登录获取微信信息
     */
    public function _getWxIfo($code){
        //3.通过code换取网页授权access_token
        $curl = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid='.WXAPPID.'&secret='.WXMY.'&code='.$code.'&grant_type=authorization_code';
        $content=$this->curl_request($curl);
        $result = json_decode($content);
        //4.通过access_token和openid拉取用户信息
        $webAccess_token = $result->access_token;
        $openid = $result->openid;
        // 5获取用户信息
        $userInfourl = 'https://api.weixin.qq.com/sns/userinfo?access_token='.$webAccess_token.'&openid='.$openid.'&lang=zh_CN';
        $recontent = $this->curl_request($userInfourl);
        return json_decode($recontent,true);


    }

    /**
     * @param $url
     * @return mixed
     */
   /* public function httpGet($url) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 500);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_URL, $url);
        $res = curl_exec($curl);
        curl_close($curl);
        return $res;
    }*/
    /**
     * @param $url
     * @param int $timeout
     * @param array $header
     * @return mixed
     * @throws Exception
     */

    public function curl_request($url,$timeout=30,$header=array()){
        if (!function_exists('curl_init')) {
            throw new Exception('server not install curl');
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        if (!empty($header)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }
        $data = curl_exec($ch);
        @curl_close($ch);
        return $data;exit();

    }



}

?>
