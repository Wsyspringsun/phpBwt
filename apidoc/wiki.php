<?php
    include_once('config/config.php'); //加载配置文件
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>WIKI<?php echo ' | '.PRODUCT_NAME;?></title>
    <link rel="stylesheet" href="assets/css/semantic.min.css">
</head>
<body>
    <div class="ui large top fixed menu transition visible" style="display: flex !important;">
        <div class="ui container">
            <div class="header item">API_DOC<code>(1.0)</code></div>
            <a class="item" href="list_class.php">文件列表</a>
            <a class="item">接口列表</a>
            <a class="item">文档详情</a>
            <a class="active item">使用说明</a>
        </div>
    </div>

    <div class="ui text container" style="max-width: none !important; margin-top: 50px;">
        <div class="ui floating message">
        	<span class='ui teal tag label'>接口说明</span>
            <div class="ui message">
                <p>1. 本接口前缀为'app',即接口url为:域名/app/接口api</p>
                <p>2. 所有接口均采用post方式传输数据</p>
                <p>3. 如果接口在文件夹下面,则需要将文件夹名写在接口api前面,比如'me'文件夹下面的user/weixin_login接口,完整url则是:域名/app/me/user/weixin_login</p>
            </div>
        
            <!-- <span class='ui teal tag label'>配置</span>
            <div class="ui message">
                <p>1. 将文件夹复制到到项目根目录即可。</p>
                <p>2. 设置 api_doc/config/config.php 中 SYSTEM_CLASS_DIR 。</p>
                <p>3. 赋予 api_doc/class 777 权限。</p>
            </div>

            <span class='ui teal tag label'>方法注释</span>
            <div class="ui message">
                事例一：
                <pre>
/**
     * 资讯列表
     *@desc 资讯列表接口
     *@input {"name":"typeId","type":"int","desc":"资讯分类的分类ID,如果不传此参数则表示获取全部资讯"}
     *@output {"name":"code","type":"int","desc":"200:获取成功,400获取失败,无数据"}
     *@output {"name":"msg","type":"string","desc":"获取成功/获取失败"}
     *@output {"name":"content","type":"array","desc":"资讯数组,下面是详细说明"}
     *@output {"name":"content[index].title","desc":"资讯标题","child":"1"}
     *@output {"name":"content[index].poster","desc":"资讯封面","child":"1"}
     *@output {"name":"content[index].zanNum","desc":"点赞数","child":"1"}
     *@output {"name":"content[index].collectNum","desc":"收藏数","child":"1"}
     *@output {"name":"content[index].viewNum","desc":"浏览量","child":"1"}
     * */
public function getMultiBaseInfo()
{
    return [];
}
                </pre>
                @desc:说明信息<br>
                @input:输入参数,name:参数名称,type:参数数据类型,default:默认值,reqiure:是否必须,desc:说明,other:其他<br>
				@output:输出数据参数,name:数据名称,type:数据类型,desc:说明,child:子项标记,1则为子项,2则为子项的子项,以此类推
            </div> -->
        </div>

        <p><?php echo COPYRIGHT?><p>

    </div>
</body>
</html>