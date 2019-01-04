<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Member_model extends MY_Model
{
    private $table = 'member';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function getMachineList($limit = 10, $page = 0)
    {
        $this->db->select();
        //$this->db->from($this->table);
        $this->db->from($this->table);
        $this->db->limit($limit, $page);
        $this->db->where('price !=', 0);
        $result = $this->db->get()->result_array();
        return $result;
    }

    public function getMax($parm)
    {
        $this->db->select_max($parm);
        return $this->db->get($this->table)->row_array();
    }

    public function getMin($parm)
    {
        $this->db->select_min($parm);
        return $this->db->get($this->table)->row_array();
    }

    public function gRefNum($id)
    {
        $this->db->from($this->table);
        $this->db->where('referee_id', $id);
        return $this->db->count_all_results();
    }
 /*
  * 获取会员信息链表查等级
  * @param $id  会员id
  */
	public function getMyInfo($id){
		$this->db->from($this->table);
		$this->db->select('member.id,member.real_name,member.head_icon,lev.name');
		$this->db->join('member_level as lev','lev.id=member.member_lvl','left');
		$this->db->where('member.id',$id);
		return $this->db->get()->row_array();	
	}
	
 /*
  * 获取会员等级
  * @param $member_lvl  等级id
  */
    public function getLevel($member_lvl)
    {
        $query = $this->db->get_where('member_level', ['id' => $member_lvl], 1)->row_array();
        if ($query && isset($query['name'])) {
            $level = $query['name'];
        } else {
            $level = 0;
        }
        return $level;

    }

    /*
  * 获取所有上级
  * @param $id String 待查找的id
  * @return String | NULL 失败返回null
  */
    public function getSup($id, $n = 0)
    {
        $res = $this->db->select('referee_id')->get_where($this->table, ['id' => $id], 1)->row()->referee_id;
		//echo "<rpe>";
		//print_r($res);exit;

        $ids = '';
        if ($res) {
            if ($n) {
                $ids .= "," . $res;
            } else {
                $ids = $res;
            }
            //return $ids;
            $n++;
            $ids .= $this->getSup($res, $n);
        }
		//echo "<pre>";
		//print_r($ids);exit;
        return $ids;
    }

    /*
     * 获取所有下级
     * @param $id String 待查找的id
     * @return String | NULL 失败返回null
     */
    public function getChild($id)
    {
        $res = $this->db->select('id')->get_where($this->table, ['referee_id' => $id])->result_array();
        $ids = $id;
        if (!empty($res)) {
            foreach ($res as $key => $val) {
                $ids .= "," . $this->getChild($val['id']);
            }
        }
        return $ids;

    }

    //获取数据条数
    public function getRefereeNum($where,$dbArray=[],$where_in=[],$groupBy='referee_id'){
        $this->db->where($where);
        $this->db->group_by($groupBy);

        if($where_in){
            if(isset($where_in['field'])&&isset($where_in['data'])){
                $this->db->where_in($where_in['field'],$where_in['data']);
            }else{
                echo 'where_in参数输入不正确';exit();
            }
        }

        return $this->db->get($this->table)->num_rows();
    }


}

?>
