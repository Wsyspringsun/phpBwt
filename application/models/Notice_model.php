<?php
/**
 * 公告管理model
 * @author lxn
 */
class Notice_model extends MY_Model
{
	private $table = 'notice';
	public function __construct()
	{
		parent::__construct($this->table);
	}
	
	/**
	 * 获取通知列表
	 */
	public function getNoticeList($limit = 10, $page = 0,$where)
	{
	    $this->db->select('*');
	    $this->db->where($where);
	    $this->db->order_by('create_time', 'desc');
		$this->db->limit($limit,$page);
	    return $this->db->get($this->table)->result_array();
	}

	/**
	 * @param $user_id
	 * @return mixed  根据用户id获取未读消息的计数
	 */
	public function getNoticeCount(){
		$this->db->from($this->table);
		$this->db->where(['is_show'=>1]);
		return $this->db->count_all_results();
}
	
	
}
/* End of file Linkadmin_model.php */