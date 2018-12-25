<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 登录管理
 * @author lxn
 */
//自定义父类(后台用)
class Admin_Controller extends Base_Controller
{
	private static  $adminId;
	public function __construct()
	{
		parent::__construct();	
		//自定义加载模型
		
		$this->load->config('menu');       //配置数据中的菜单(所有菜单在内)
		$adminId = $this->session->userdata('adminId');
		
		if (empty($adminId)){
		    redirect('admin/login');        //检查登录状态
		}
		self::$adminId=$adminId;
		$this->checkAuth();
	}
	//判断用户是否有当前路径的权限
	public function checkAuth(){
	    $uri1=$this->uri->slash_segment(1);
	    $uri2=$this->uri->segment(2);
	    $uri=$uri1.$uri2;
	    $adminAuth=$this->getAdminAuth();
	    
	    if(!in_array($uri,$adminAuth)){
	        show_error('没有权限');
	        exit();
	    }
	    
	}
	
	//获取用户权限值
	public function getAdminAuth(){
	    $authMenu=$this->config->item('auth');
	    $adminAuth=$this->session->userdata('adminAuth');
	    if(!@$adminAuth){   
	        $this->db->select('adminId,adminName,roleName,auth');
	        $this->db->where('adminId',self::$adminId);
	        $this->db->join('role','admin.roleId=role.roleId');
	        $admin=$this->db->get('admin')->row_array();
	        $adminAuth=@$admin['auth'];
	        $authUriArray=['admin/','admin/index'];     //用户初始权限,即任何人都可以访问主页
	        if($adminAuth){
	            $authArray=[];
	            $auth=explode('|', $adminAuth);
	            if(is_array($auth)){
	                foreach ($authMenu as $key=>$val){
	                    if(in_array($val['authId'], $auth)){
	                        $authArray[]=$authMenu[$key];
	                    }
	                }
	                //var_dump($auth);
	                foreach ($authArray as $key=>$val){
	                    $authUriArray[]=$val['uri'];
	                }
	            }
	        }
	        
	        $adminAuth=$authUriArray;
	        $this->session->set_userdata('adminAuth',$adminAuth); //这里只处理权限值,不处理名称角色等,这些数据可以在登录的时候存入session
	    }    
	    return $adminAuth;
	}	
	
	//获取目录
	public function get_menu(){
	    $adminAuth=$this->getAdminAuth();      //管理员二进制权限值
	    
	    $this->load->config('menu');       //配置数据中的菜单(所有菜单在内)
	    $menu=$this->config->item('menu');
	    $menuList=[];
	    	    
	    foreach ($adminAuth as $key=>$val){
	        if(isset($menu[$val]))
	        $menuList[]=$menu[$val];
	    }
	    return $menuList;      //返回管理员所属权限的菜单
	   
	}
}
//扩展了模型加载的基类
class Base_Controller extends CI_Controller{
    protected $models=[];      //需要加载的模型变量
    public function __construct() {
        parent::__construct();
        //统一加载模型，并自动转成小写
        $models=$this->models;
        if($models){
            foreach ($models as $key=>$val){
                $this->load->model(ucfirst($val),strtolower($val));     //第一个参数首字母大写，第二个参数全部小写
            }
        }        
    }
}
//扩展卖家后台基类
class Seller_Controller extends Base_Controller{
    public function __construct() {
        parent::__construct();      
        $sellerId = $this->getSellerId();
        
        if (empty($sellerId)){
            redirect('seller/login');        //检查登录状态
        }
    }
    //获取卖家端登录用户userId
    public function getSellerId(){
        $sellerId=$this->session->userdata('sellerId');
        return $sellerId;
    }
    //检查用户标记位,看用户是否通过认证
    public function checkUserFlag(){
        $this->load->model('user_model');
        $sellerId=$this->getSellerId();
        $user=$this->user_model->getWhereRow(['userId'=>$sellerId]);
        $userFlag=$user['userFlag'];
        if($user['is_delete']==1){
            echo  '您已被管理员拉入黑名单,暂时不能进行该项操作';
            exit();
        }
        if(in_array($userFlag, [1,2,4])){
            if($userFlag!=2){
                if($userFlag==4){
                    echo '您的认证请求被拒绝,不能进行该操作';
                }else{
                    echo '您还未通过企业认证,不能进行该操作';
                }
                exit();
            }
            
        }else{
            echo'您不是企业用户无权进行该操作';exit();
        }
    }
    //判断某企业用户是否创建了店铺/公司
    public function checkCompany(){
        $this->load->model('company_model');
        $sellerId=$this->getSellerId();
        $company=$this->company_model->getWhereRow(['userId'=>$sellerId]);
        if(!$company){
            echo '您还没有创建店铺,请先完善店铺信息后才能进行该项操作';exit();
        }else{
            return $company['companyId'];
        }
    }

    public function checkM2(){
        $this->load->model('company_model');
        $sellerId=$this->getSellerId();
        $company=$this->company_model->getWhereRow(['userId'=>$sellerId]);
        if($company['typeFlag'] == 'Common'){
            echo '您还没有成为M2用户,请先成为M2用户后才能进行该项操作';exit();
        }else{
            return $company['companyId'];
        }
    }
}




/* End of file Login.php */