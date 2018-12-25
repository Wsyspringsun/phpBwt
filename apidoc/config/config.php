<?php
//开启报错
ini_set('display_errors', 1);

//因CI需定义变量.
define('BASEPATH', 'API_DOC');

//设置项目根目录
define('API_DOC_PATH', dirname(dirname(dirname(__FILE__))));

//设置项目API目录
define('SYSTEM_CLASS_DIR', API_DOC_PATH.'/application/controllers/');

//设置当前API目录
define('CURRENT_CLASS_DIR', API_DOC_PATH.'/apidoc/class/');

//设置版权
define('COPYRIGHT', '');

//设置产品名称
define('PRODUCT_NAME', '');