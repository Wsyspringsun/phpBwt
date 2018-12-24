<?php

if (is_file(__DIR__ . '/../autoload.php')) {
    require_once __DIR__ . '/../autoload.php';
}
if (is_file(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}
require_once __DIR__ . '/Config.php';


use OSS\OssClient;
use OSS\Core\OssUtil;
use OSS\Core\OssException;
/**
 * Class Common
 *
 * The Common class for 【Samples/*.php】 used to obtain OssClient instance and other common functions
 */
class Common
{
    const endpoint = Config::OSS_ENDPOINT;
    const accessKeyId = Config::OSS_ACCESS_ID;
    const accessKeySecret = Config::OSS_ACCESS_KEY;
    const bucket = Config::OSS_TEST_BUCKET;

    /**
     * Get an OSSClient instance according to config.
     *
     * @return OssClient An OssClient instance
     */
    private static function getOssClient()
    {
        try {
            $ossClient = new OssClient(self::accessKeyId, self::accessKeySecret, self::endpoint, false);
        } catch (OssException $e) {
            printf(__FUNCTION__ . "creating OssClient instance: FAILED\n");
            printf($e->getMessage() . "\n");
            return null;
        }
        return $ossClient;
    }

    private static function getBucketName()
    {
        return self::bucket;
    }

    /**
     * A tool function which creates a bucket and exists the process if there are exceptions
     */
    public static function createBucket()
    {
        $ossClient = self::getOssClient();
        if (is_null($ossClient)) exit(1);
        $bucket = self::getBucketName();
        $acl = OssClient::OSS_ACL_TYPE_PUBLIC_READ;
        try {
            $ossClient->createBucket($bucket, $acl);
        } catch (OssException $e) {

            $message = $e->getMessage();
            if (\OSS\Core\OssUtil::startsWith($message, 'http status: 403')) {
                echo "Please Check your AccessKeyId and AccessKeySecret" . "\n";
                exit(0);
            } elseif (strpos($message, "BucketAlreadyExists") !== false) {
                echo "Bucket already exists. Please check whether the bucket belongs to you, or it was visited with correct endpoint. " . "\n";
                exit(0);
            }
            printf(__FUNCTION__ . ": FAILED\n");
            printf($e->getMessage() . "\n");
            return;
        }
        print(__FUNCTION__ . ": OK" . "\n");
    }

    public static function println($message)
    {
        if (!empty($message)) {
            echo strval($message) . "\n";
        }
    }
    //多文件上传
    private function multiuploadFile($fileName,$file,$dir='')        //$fileName,文件名称,可不填,$file,要上传的文件资源,$dir目录
    {
        $object = $dir.uniqid().'_'.$fileName;      //创建唯一的文件名称
        
        $options = array();
        $ossClient=$this->getOssClient();
        $bucket=$this->getBucketName();
        try {
            $ossClient->multiuploadFile($bucket, $object, $file, $options);
            $out=[
                'code'=>200,
                'smg'=>'上传成功',
                'content'=>['url'=>$object]
            ];
        } catch (OssException $e) {
            $msg=$e->getMessage();
            $out=[
                'code'=>400,
                'msg'=>$msg,                
            ];
        }
        return $out;
    }
    
    public function do_upload($dir='',$fileKey='file')
    {       //上传文件的键名,和目录(目录要以/结尾)
        $file=@$_FILES[$fileKey];
        if (!$file){
            $out['code']=400;
            $out['msg']='上传文件不存在';
            $out['content']=['url'=>''];
            return $out;
        }
        if (is_array($file['tmp_name'])){
            $num=count(array_filter($file['tmp_name']));      //要上传的文件总数    array_filter()过滤掉空的数据组
            
            $successNum=0;
            $faileNum=0;
            $content=[];
            $id=0;
            foreach (array_filter($file['tmp_name']) as $key=>$val){
                $res=$this->multiuploadFile($file['name'][$key], $val,$dir);
                if ($res['code']==200){     //上传成功
                    $url=$res['content']['url'];
                    $content[]=[
                        'id'=>$id++,
                        'code'=>200,
                        'msg'=>'上传成功',
                        'url'=>$url
                    ];
                    $successNum++;         //上传成功 则成功数加1
                }else{
                    $content[]=[
                        'id'=>$id++,
                        'code'=>$res['code'],
                        'msg'=>$res['msg'],
                        'url'=>''
                    ];
                    $faileNum++;         //上传失败 则失败数加1
                }
            }
            if ($successNum==0){
                $code=400;
            }else{
                $code=200;
            }
            $msg='共上传'.$num.'个文件,'.$successNum.'个文件上传成功,'.$faileNum.'个文件上传失败';
            $out=[
                'code'=>$code,
                'msg'=>$msg,
                'content'=>$content
            ];
            
        }else{
            $out=$this->multiuploadFile($file['name'], $file['tmp_name'],$dir);
            
        }
        return $out;
    }
    //单个删除
    public function del($object){
        $ossClient=$this->getOssClient();
        $bucket=$this->getBucketName();
        try
        {
            $ossClient-> deleteObject($bucket,$object);
           
            return true;
        } catch ( OssException $e ) {
            //printf($e->getMessage() . "\n");
            return;
        }
    }
    
    //批量删除
    public function del_mult($objects){
        $ossClient=$this->getOssClient();
        $bucket=$this->getBucketName();
        try
        {
            $ossClient-> deleteObject($bucket,$objects);            
            return true;
        } catch ( OssException $e ) {
            //printf($e->getMessage() . "\n");
            return;
        }
    }

}

# Common::createBucket();
