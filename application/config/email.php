<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$config['protocol'] = 'smtp';
$config['smtp_host']='ssl://smtp.126.com';
//用户名
$config['smtp_user'] = 'iyavic@126.com';
//pop3授权码
$config['smtp_pass'] = "suhang123";
//服务器端口
$config['smtp_port'] = 465;
//邮件类型
$config['mailtype'] = 'html';
$config['smtp_timeout']='20';
$config['wordwrap'] = TRUE;
//$config['wordwrap'] = false;
$config['charset']="gbk";

