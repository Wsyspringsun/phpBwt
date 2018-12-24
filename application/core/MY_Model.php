<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

//自定义模型父类
class MY_Model extends CI_Model
{
    private $table;
    public $join = array();
	public function __construct($table,$models=[])
	{
	    parent::__construct();
	    //统一加载模型，并自动转成小写
	    if($models){
	        foreach ($models as $key=>$val){
	            $this->load->model(ucfirst($val),strtolower($val));     //第一个参数首字母大写，第二个参数全部小写
	        }
	    }
		if(!$table){
		    echo '表名不能为空';exit();
		}
		$this->table=$table;		
	}
	//执行sql语句
	public final function getQuery($sql)
	{
		return $this->db->query($sql);
	}
	//自定义条件数组获取多条数据
	public function getWhere($where,$select='*',$dbArray=[],$where_in=[]){      
	    $this->format($dbArray);
	    $this->db->select($select);
	    $this->db->where($where);	        
	    if($where_in){
	        if(isset($where_in['field'])&&isset($where_in['data'])){
	            $this->db->where_in($where_in['field'],$where_in['data']);
	        }else{
	            echo 'where_in参数输入不正确';exit();
	        }	        
	    }
	    $res=$this->db->get($this->table)->result_array();
	    	    
	    return $res;
	}
	//自定义条件数组获取单条数据
	public function getWhereRow($where,$select="*",$dbArray=[]){
	    $this->format($dbArray);
	    $this->db->select($select);
	    $this->db->where($where);
	    $res=$this->db->get($this->table)->row_array();
	    return $res;
	}
	//自定义数组where_in(该项目中暂时使用此函数,在下一项目或版本中将弃用,统一用getWhere方法);
	public function getWhere_in($field,$where,$select='*',$dbArray=[]){
	    $this->format($dbArray);
	    $this->db->select($select);
	    $this->db->where_in($field,$where);
	    $res=$this->db->get($this->table)->result_array();	    
	    return $res;
	}
	//获取数据条数
	public function getWhere_num($where,$dbArray=[],$where_in=[]){
	    $this->db->where($where);	
	    if (isset($dbArray['like'])){      //like查询数组
	        if($dbArray['like']){
	            if(isset($dbArray['or_like'])&&!empty($dbArray['or_like'])){
	                $this->db->group_start();
	                
	                $this->db->like($dbArray['like']);
	                $this->db->or_like($dbArray['or_like']);
	                
	                $this->db->group_end();
	            }else{
	                $this->db->like($dbArray['like']);
	            }
	        }
	        
	    }
	    if($where_in){
	        if(isset($where_in['field'])&&isset($where_in['data'])){
	            $this->db->where_in($where_in['field'],$where_in['data']);
	        }else{
	            echo 'where_in参数输入不正确';exit();
	        }
	    }
	        
	    return $this->db->get($this->table)->num_rows();
	}
	//处理自定义数据库数组数据
	private function format($dbArray){
	    if ($dbArray){
	        if (isset($dbArray['like'])){      //like查询数组
	            if(isset($dbArray['or_like'])&&!empty($dbArray['or_like'])){
	                $this->db->group_start();
	                
	                $this->db->like($dbArray['like']);
	                $this->db->or_like($dbArray['or_like']);
	                
	                $this->db->group_end();
	            }else{
	                $this->db->like($dbArray['like']);
	            }
	            
	        }
	        if(isset($dbArray['order'])){
	            $orderArray=$dbArray['order'];
	            foreach ($orderArray as $key=>$val){
	                $this->db->order_by($key,$val);
	            }
	        }
	        if(isset($dbArray['page'])){       //分页数组
	            $pageArray=$dbArray['page'];
	            $limit=isset($pageArray['limit'])?$pageArray['limit']:20;
	            $offset=isset($pageArray['offset'])?$pageArray['offset']:0;
	            $this->db->limit($limit,$offset);
	        }
	        
	    }
	}
    protected function _get_db($data=array()) {
        if (isset($this->join['table'])) {
            for ($i = 0; $i < count($this->join['table']); $i++) {
                $this->db->join($this->join['table'][$i], $this->join['where'][$i], $this->join['add'][$i]);
            }
        }
        foreach($data as $key=>$v) {
            if($key == 'limit') {
                $v = $v ? $v : 20;
                $data['page'] = $data['page'] ? $data['page'] : 1;
                $this->db->limit($v, ($data['page'] - 1) * $v);
                continue;
            }
            if($key == 'page') continue;
            if(strpos($key, '_time')) {
                $v = explode('|', $v);
                if(isset($v[1])) {
                    $this->db->where($key . ' >', strtotime($v[0]));
                    $this->db->where($key . ' <', strtotime($v[1]));
                }
                continue;
            }
            if(strpos($key, '_in')) {
                if($v !== NULL) {
                    $v = explode(',', $v);
                    $this->db->where_in(substr($key, 0, -3), $v);
                }
                continue;
            }
            if(strpos($key, '_gte')) {
                if($v !== NULL) {
                    $this->db->where(substr($key, 0, -4) . ' >=', $v);
                }
                continue;
            }
            if(strpos($key, '_lt')) {
                if($v !== NULL) {
                    $this->db->where_in(substr($key, 0, -3) . ' <3', $v);
                }
                continue;
            }
            if($v === '' || $v === 'all' || $v === FALSE || $v === NULL) continue;
            $this->db->where($key, $v);
        }
    }

    protected function _join($table, $where, $add='inner') {
        $this->join['table'][] = $table;
        $this->join['where'][] = $where;
        $this->join['add'][] = $add;
    }

	//插入
	public function insert($data){
	    $this->db->insert($this->table,$data);
	    return $this->db->insert_id();
	}
	//批量插入
	public function insert_batch($data){       //批量插入数据,$data为二维数组
	    $this->db->insert_batch($this->table,$data);
	    return $this->db->affected_rows();
	}
	//带创建时间的插入
	public function insert_time($data){
	    $data['createTime']=time();
	    $this->db->insert($this->table,$data);
	    return $this->db->insert_id();
	}
	//带创建时间的批量插入
	public function insert_batch_time($data){       //批量插入数据,$data为二维数组
	    foreach ($data as $key=>$val){
	        $data[$key]['createTime']=time();
	    }
	    $this->db->insert_batch($this->table,$data);
	    return $this->db->affected_rows();
	}
	//自定义条件数据更新
	public function updateWhere($where,$data){
	    $this->db->where($where);
	    $this->db->update($this->table,$data);
	    return $this->db->affected_rows();
	}
	//自定义条件批量更新
	public function updateWhere_in($field,$where,$data){
	    $this->db->where_in($field,$where);
	    $this->db->update($this->table,$data);
	    return $this->db->affected_rows();
	}
	//更新点赞,评论,收藏,浏览量等数据自动加1
	public function updateNumPlus($where,$field){  //$where条件数组，$field需要自加的字段名
	    $this->db->set($field, $field.'+1', FALSE);	    
	    $this->db->where($where)->update($this->table);
	    return $this->db->affected_rows();
	}
	//更新点赞,评论,收藏,浏览量等数据自动减1
	public function updateNumPluss($where,$field){  //$where条件数组，$field需要自加的字段名
		$this->db->set($field, $field.'-1', FALSE);
		$this->db->where($where)->update($this->table);
		return $this->db->affected_rows();
	}
	//自定义条件数组删除数据
	public function delWhere($where){
	    $this->db->where($where);
	    $this->db->delete($this->table);
	    return $this->db->affected_rows();
	}
	//自客义条件数组批量更新数据
	public function delWhere_in($field,$fieldArray){
	    $this->db->where_in($field,$fieldArray);
	    $this->db->delete($this->table);
	    return $this->db->affected_rows();
	}
	//逻辑删除
	public function delWhere_logic($where){        //is_delete是数据库中的逻辑删除标记
	    return $this->updateWhere($where, ['is_delete'=>1]);
	}
	//指逻辑删除
	public function delWhere_in_logic($field,$where){
	    return $this->updateWhere_in($field, $where, ['is_delete'=>1]);
	}

	//事务提交
	public function commit()
	{
		return $this->db->trans_commit();
	}
	//事物回滚
	public function rollback()
	{
		return $this->db->trans_rollback();
	}
	//事务开始
	public function start()
	{
		return $this->db->trans_start();
	}
}
