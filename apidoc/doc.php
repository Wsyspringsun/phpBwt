<?php
    $service = $_GET['m'];

    if (empty($service)) {
        die('缺少参数:m');
    }

    include_once('config/config.php'); //加载配置文件

    list($className, $methodName) = explode('.', $service);

    include_once(CURRENT_CLASS_DIR.$className.'.php');

    //获取返回结果
    $rMethod = new ReflectionMethod($className, $methodName);
    $docComment = $rMethod->getDocComment();
    $docCommentArr = explode("\n", $docComment);

//     //获取接口参数
//     $rules = [];
//     if (method_exists($className,'getRules')) {
//         $classObj = new $className();
//         $rulesArr = $classObj::getRules();
//         $rules    = $rulesArr[$methodName];
//     }

    //定义类型
    $typeMaps = array(
        'string'  => '字符串',
        'int'     => '整型',
        'float'   => '浮点型',
        'boolean' => '布尔型',
        'date'    => '日期',
        'array'   => '数组',
        'fixed'   => '固定值',
        'enum'    => '枚举类型',
        'object'  => '对象',
    );

    $description  = '//请检测函数标题描述';
    $descComment  = '//请使用@desc 注释';
    if (!empty($docCommentArr)) {
        foreach ($docCommentArr as $comment) {
            $comment = trim($comment);
            
            //标题描述
            $pos = stripos($comment, '@title');
            if ($pos !== false) {
                $titleComment = substr($comment, $pos + 7);
                continue;
            }

            //@desc注释
            $pos = stripos($comment, '@desc');
            if ($pos !== false) {
                $descComment = substr($comment, $pos + 5);
                continue;
            }
            
            //@output注释 (输出数据)
            $pos = stripos($comment, '@output');
            if ($pos !== false) {
                //continue;
                $returnCommentArr = explode(' ', substr($comment, $pos + 8));
                
                $outputs[] = json_decode($returnCommentArr[0]);;
                /* //将数组中的空值过滤掉，同时将需要展示的值返回
                $returnCommentArr = array_values(array_filter($returnCommentArr));
                if (count($returnCommentArr) < 2) {
                    continue;
                }
                if (!isset($returnCommentArr[2])) {
                    $returnCommentArr[2] = '';	//可选的字段说明
                } else {
                    //兼容处理有空格的注释
                    $returnCommentArr[2] = implode(' ', array_slice($returnCommentArr, 2));
                } */
                
                
            }
            
            //@input注释      输入参数
            $pos = stripos($comment, '@input');
            if ($pos !== false) {
                $inputComment= explode(' ', substr($comment, $pos + 7));
                
                $inputs[] = json_decode($inputComment[0]);
            }
            
        }
    }

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?php echo $service?> <?php echo ' | '.PRODUCT_NAME;?></title>
    <link rel="stylesheet" href="assets/css/semantic.min.css">
</head>
<body>
<div class="ui large top fixed menu transition visible" style="display: flex !important;">
    <div class="ui container">
        <div class="header item">API_DOC<code>(1.0)</code></div>
        <a class="item" href="list_class.php">文件列表</a>
        <a class="item" href="list_method.php?f=<?php echo $_GET['f'];?>">接口列表</a>
        <a class="active item">文档详情</a>
        <a class="item" href="wiki.php">使用说明</a>
    </div>
</div>

<div class="ui text container" style="max-width: none !important; margin-top: 50px;">
    <div class="ui floating message">
        <h2 class='ui header'>接口：<?php echo ($service ? '<a href="list_method.php?m='.$service.'&f='.$_GET['f'].'&reload=1">'.str_replace('.', '/', $service).'</a>' : '--'); ?></h2>
        <br/>
        <span class='ui teal tag label'>
            <?php echo ($titleComment ? $titleComment: '//请检测函数标题描述');?>
        </span>

        <div class="ui raised segment">
            <span class="ui red ribbon label">接口说明</span>
            <div class="ui message">
                <p> <?php echo ($descComment ? $descComment : '//请使用@desc 注释');?></p>
            </div>
        </div>

        <h3>接口参数</h3>
        <table class="ui red celled striped table" >
            <thead>
                <tr>
                    <th>参数名字</th>
                    <th>类型</th>
                    <th>是否必须</th>
                    <th>默认值</th>
                    <th>其他</th>
                    <th>说明</th>
                </tr>
            </thead>
            <tbody>
            <?php
                
                if (!empty($inputs)) {
                    foreach ($inputs as $key => $val) {
                        $name = $val->name;
                        if (!isset($val->type)) {
                            $val->type = 'string';
                        }
                        //var_dump($val);
                        $type    = isset($typeMaps[$val->type]) ? $typeMaps[$val->type] : $val->type;
                        $require = isset($val->require) && $val->require ? '<font color="red">必须</font>' : '可选';
                        $default = isset($val->default) ? $val->default : '';
                        if ($default === NULL) {
                            $default = 'NULL';
                        } else if (is_array($default)) {
                            $default = json_encode($default);
                        } else if (!is_string($default)) {
                            $default = var_export($default, true);
                        }

                        
                        if (isset($val->other)){
                            $other=$val->other;
                        }else{
                            $other='';
                        }
                        
                        $desc = isset($val->desc) ? trim($val->desc) : '';

                        echo '<tr>';
                        echo '<td>'.$name.'</td>';
                        echo '<td>'.$type.'</td>';
                        echo '<td>'.$require.'</td>';
                        echo '<td>'.$default.'</td>';
                        echo '<td>'.$other.'</td>';
                        echo '<td>'.$desc.'</td>';
                        echo '</tr>';
                    }
                }
            ?>
            </tbody>
        </table>

        <h3>返回结果</h3>
        <table class="ui green celled striped table" >
            <thead>
                <tr>
                    <th>返回字段</th>
                    <th>类型</th>
                    <th>说明</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (!empty($outputs)) {
                    foreach ($outputs as $key => $val) {
                        if(isset($val->child)){
                            $child=$val->child;                            
                            $name='';
                            for ($i=0;$i<$child;$i++){                                
                                $name.='——';                                
                            }                           
                            $name.= $val->name;
                        }else{
                            $name= $val->name;
                        }
                        
                        if (!isset($val->type)) {
                            $val->type = 'string';
                        }
                        $type    = isset($typeMaps[$val->type]) ? $typeMaps[$val->type] : $val->type;                        
                        
                        $desc = isset($val->desc) ? trim($val->desc) : '';
                        
                        echo '<tr>';
                        echo '<td>'.$name.'</td>';
                        echo '<td>'.$type.'</td>';                        
                        echo '<td>'.$desc.'</td>';
                        echo '</tr>';
                    }
                    }
                ?>
            </tbody>
        </table>

        <div class="ui blue message">
            <strong>温馨提示：</strong> 此接口参数列表根据后台代码自动生成。
        </div>

    </div>

    <p><?php echo COPYRIGHT?><p>

</div>
</body>
</html>