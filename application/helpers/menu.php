<?php
defined('BASEPATH') OR exit('No direct script access allowed');
//菜单
$config['menu']=[
    'admin/banner'=>[
        'title'=>'轮播图管理',
        'icon'=>'&#xe634',
        'href'=>'admin/banner',
        
    ], 
    'admin/company'=>[
        'title'=>'店铺管理',
        'icon'=>'&#xe634',
        'href'=>'',
        'children'=>[
            ['title'=>'店铺待审核','icon'=>'&#xe609;','href'=>'admin/company/issue0'],
            ['title'=>'店铺审核通过','icon'=>'&#xe609;','href'=>'admin/company/issue1'],
            ['title'=>'店铺审核未通过','icon'=>'&#xe609;','href'=>'admin/company/issue2'],
            
        ]
    ],  
    'admin/project'=>[
        'title'=>'项目管理',
        'icon'=>'&#xe634',
        'href'=>'admin/project',
        'children'=>[
            ['title'=>'项目待审核','icon'=>'&#xe609;','href'=>'admin/project/issue0'],
            ['title'=>'项目审核通过','icon'=>'&#xe609;','href'=>'admin/project/issue1'],
            ['title'=>'项目审核未通过','icon'=>'&#xe609;','href'=>'admin/project/issue2'],
            
        ]
    ], 
    'admin/technician'=>[
        'title'=>'技师管理',
        'icon'=>'&#xe634',
        'href'=>'admin/technician',
        'children'=>[
            ['title'=>'技师待审核','icon'=>'&#xe609;','href'=>'admin/technician/issue0'],
            ['title'=>'技师审核通过','icon'=>'&#xe609;','href'=>'admin/technician/issue1'],
            ['title'=>'技师审核未通过','icon'=>'&#xe609;','href'=>'admin/technician/issue2'],
            
        ]
    ], 
    'admin/gift'=>[
        'title'=>'礼物管理',
        'icon'=>'&#xe634',
        'href'=>'admin/gift',
        
    ], 
    'admin/type'=>[
        'title'=>'分类管理',
        'icon'=>'&#xe634',
        'href'=>'',
        'children'=>[
            ['title'=>'行业分类','icon'=>'&#xe609;','href'=>'admin/type/trade'],
            ['title'=>'店铺提供的服务分类','icon'=>'&#xe609;','href'=>'admin/type/service'],
            ['title'=>'项目分类','icon'=>'&#xe609;','href'=>'admin/type/projectType'],
            /* ['title'=>'职位分类','icon'=>'&#xe61c;','href'=>'admin/type/job'],
            ['title'=>'规模分类','icon'=>'&#xe61c;','href'=>'admin/type/scale'],
            ['title'=>'工作经验分类','icon'=>'&#xe61c;','href'=>'admin/type/exp'],
            ['title'=>'薪资分类','icon'=>'&#xe61c;','href'=>'admin/type/salary'],
            ['title'=>'认证分类','icon'=>'&#xe61c;','href'=>'admin/type/authen'], */
            ['title'=>'职业技能分类','icon'=>'&#xe61c;','href'=>'admin/type/skill'],
        ]
    ],    
    
    'admin/user'=>[
        'title'=>'用户管理',
        'icon'=>'&#xe634',
        'href'=>'',
        'children'=>[
            ['title'=>'普通用户列表','icon'=>'&#xe609;','href'=>'admin/user'],
            ['title'=>'企业用户列表','icon'=>'&#xe609;','href'=>'admin/user/company'],
            ['title'=>'黑名单','icon'=>'&#xe609;','href'=>'admin/user/blackList'],
        ]
    ],
    'admin/manager'=>[
        'title'=>'管理员管理',
        'icon'=>'&#xe634',
        'href'=>'',
        'children'=>[
            ['title'=>'角色管理','icon'=>'&#xe61c;','href'=>'admin/manager/role'],
            ['title'=>'管理员列表','icon'=>'&#xe609;','href'=>'admin/manager'],
        ]
    ],

    'admin/db'=>[
        'title'=>'数据库',
        'icon'=>'&#xe634',
        'href'=>'admin/db',
    ],

    'admin/order'=>[
        'title'=>'订单管理',
        'icon'=>'&#xe634',
        'href'=>'admin/order/index',
        /*'children'=>[
            ['title'=>'订单列表','icon'=>'&#xe609;','href'=>'admin/order/index'],
        ]*/
    ],
    'admin/finance'=>[
        'title'=>'财务管理',
        'icon'=>'&#xe634',
        'href'=>'admin/finance/index',
    ],
]; 

$config['auth']=[
    ['authId'=>7,'uri'=>'admin/banner','intro'=>'轮播图管理权限'],
    ['authId'=>1,'uri'=>'admin/company','intro'=>'企业管理权限'],
    ['authId'=>8,'uri'=>'admin/project','intro'=>'项目管理权限'],
    ['authId'=>9,'uri'=>'admin/technician','intro'=>'技师管理权限'],
    ['authId'=>10,'uri'=>'admin/gift','intro'=>'礼物管理权限'],
    ['authId'=>2,'uri'=>'admin/type','intro'=>'分类管理权限'],
    ['authId'=>3,'uri'=>'admin/user','intro'=>'用户管理权限'],    
    ['authId'=>4,'uri'=>'admin/manager','intro'=>'管理员管理权限'],
    ['authId'=>5,'uri'=>'admin/db','intro'=>'数据库管理权限'],
    ['authId'=>6,'uri'=>'admin/order','intro'=>'订单管理权限'],
    ['authId'=>11,'uri'=>'admin/finance','intro'=>'财务管理权限']
];

$config['seller']=[
    
    [
        'title'=>'企业用户认证信息',
        'icon'=>'&#xe634',
        'href'=>'seller/user',        
    ],      
    [
        'title'=>'店铺管理',
        'icon'=>'&#xe634',
        'href'=>'seller/company',
        
    ], 
    [
        'title'=>'店铺轮播图管理',
        'icon'=>'&#xe634',
        'href'=>'seller/banner',
    ], 
    [
        'title'=>'项目管理',
        'icon'=>'&#xe634',
        'href'=>'seller/project',        
    ],  
    [
        'title'=>'技师管理',
        'icon'=>'&#xe634',
        'href'=>'seller/technician',
    ],
    'admin/comment'=>[
        'title'=>'评论管理',
        'icon'=>'&#xe634',
        'href'=>'',
        'children'=>[
            ['title'=>'店铺评价','icon'=>'&#xe609;','href'=>'seller/comment/company'],
            ['title'=>'项目评价','icon'=>'&#xe609;','href'=>'seller/comment/project'],
            ['title'=>'技师评价','icon'=>'&#xe609;','href'=>'seller/comment/technician'],
        ]
    ],
    [
        'title'=>'财务管理',
        'icon'=>'&#xe634',
        'href'=>'seller/finance',
    ],  
];








