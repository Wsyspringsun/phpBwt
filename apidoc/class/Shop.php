<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
header('Access-Control-Allow-Origin:*');
/**
 * 登录管理
 * @author lxn
 */
class Shop {
    private static $data = array();
    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('user_model','wx_user_model','shop_model','coupon_model','coupon_claim_real_model','item_model','wx_user_staff_relation_model'));
    }

    /**
     * @title 店铺首页
     * @desc  (用户所关注得店铺和推荐的店铺)
     * @input {"name":"user_id","require":"true","type":"string","desc":"用户id"}
     *
     * @output {"name":"code","type":"int","desc":"200:成功,300各种提示信息"}
     * @output {"name":"msg","type":"string","desc":"信息说明"}
     * @output {"name":"data.shop_id","type":"int","desc":"店铺id"}
     * @output {"name":"data.shop_name","type":"string","desc":"店铺名称"}
     * @output {"name":"data.lat_lng","type":"string","desc":"店铺经纬度，先经度，后纬度"}
     * @output {"name":"data.telphone","type":"string","desc":"店铺座机号，可能会多个"}
     * @output {"name":"data.addr","type":"string","desc":"店铺详细地址"}
     * @output {"name":"data.city","type":"string","desc":"店铺所在城市"}
     * @output {"name":"data.pictures","type":"string","desc":"店铺图片"}
     * @output {"name":"data.rooms","type":"int","desc":"店铺的房间数"}
     * @output {"name":"data.tech_num","type":"int","desc":"店铺所有技师数量"}
     * @output {"name":"data.zanNum","type":"int","desc":"店铺点赞数"}
     * @output {"name":"data.collectNum","type":"int","desc":"店铺收藏数"}
     * @output {"name":"data.commentNum","type":"int","desc":"店铺评论数"}
     * @output {"name":"data.reportNum","type":"int","desc":"店铺举报数"}
     * @output {"name":"data.consume","type":"float","desc":"店铺平均消费"}
     * @output {"name":"data.judge","type":"float","desc":"店铺评分数值"}
     * @output {"name":"data.serviceTypeIds","type":"string","desc":"店铺硬件设施"}
     * @output {"name":"data.isGz","type":"int","desc":"用户是否关注店铺,0未关注,1已关注"}
     *
     */
    public function getShopList(){
        //$user_id=$this->input->post('user_id');
        $user_id=1;
        if(!$user_id){
            show300('用户id不能为空');
        }
        $serch['shop_name']="测试";
        //$serch['province']="浙江省2";
        $shopIds_arr= $this->getGzShops($user_id,$serch);
        $where=['shop.isRecommetn'=>1];
        $tj=$this->shop_model->getRecShop($where,$serch);
        if (!empty($shopIds_arr)){
            if (!empty($tj)){
                foreach ($tj as $kk=>$vv){
                    if (in_array($vv['shop_id'],$shopIds_arr['ids'])){
                        $shop_data[$kk]=$vv;
                        $shop_data[$kk]['lat_lng'] = explode(',',$vv['lat_lng']);
                        $shop_data[$kk]['telphone'] = explode(';',$vv['telphone']);
                        $shop_data[$kk]['isGz'] = 1;
                    }else{
                        $shop_data[$kk]=$vv;
                        $shop_data[$kk]['lat_lng'] = explode(',',$vv['lat_lng']);
                        $shop_data[$kk]['telphone'] = explode(';',$vv['telphone']);
                        $shop_data[$kk]['isGz'] = 0;
                    }

                }
            }else{
                foreach ($shopIds_arr['data'] as $k=>$v){
                    $shop_data[$k]=$v;
                    $shop_data[$k]['isGz'] = 1;
                }
            }
        }else{
            if(!empty($tj)){
                foreach ($tj as $k=>$v){
                    $shop_data[$k]=$v;
                    $shop_data[$k]['lat_lng'] = explode(',',$v['lat_lng']);
                    $shop_data[$k]['telphone'] = explode(';',$v['telphone']);
                    $shop_data[$k]['isGz'] = 0;
                }
            }else{
                $shop_data=array();
            }
        }
        show200($shop_data);
    }

    /**
     * @title 店铺详情
     * @desc  (用户所关注得店铺和推荐的店铺)
     * @input {"name":"user_id","require":"true","type":"string","desc":"用户id"}
     *
     * @output {"name":"code","type":"int","desc":"200:成功,300各种提示信息"}
     * @output {"name":"msg","type":"string","desc":"信息说明"}
     * @output {"name":"data.shop.shop_id","type":"int","desc":"店铺id"}
     * @output {"name":"data.shop.shop_name","type":"string","desc":"店铺名称"}
     * @output {"name":"data.shop.lat_lng","type":"string","desc":"店铺经纬度，先经度，后纬度"}
     * @output {"name":"data.shop.telphone","type":"string","desc":"店铺座机号，可能会多个"}
     * @output {"name":"data.shop.addr","type":"string","desc":"店铺详细地址"}
     * @output {"name":"data.shop.city","type":"string","desc":"店铺所在城市"}
     * @output {"name":"data.shop.pictures","type":"string","desc":"店铺图片"}
     * @output {"name":"data.shop.rooms","type":"int","desc":"店铺的房间数"}
     * @output {"name":"data.shop.tech_num","type":"int","desc":"店铺所有技师数量"}
     * @output {"name":"data.shop.zanNum","type":"int","desc":"店铺点赞数"}
     * @output {"name":"data.shop.collectNum","type":"int","desc":"店铺收藏数"}
     * @output {"name":"data.shop.commentNum","type":"int","desc":"店铺评论数"}
     * @output {"name":"data.shop.reportNum","type":"int","desc":"店铺举报数"}
     * @output {"name":"data.shop.consume","type":"float","desc":"店铺平均消费"}
     * @output {"name":"data.shop.judge","type":"float","desc":"店铺评分数值"}
     * @output {"name":"data.shop.serviceTypeIds","type":"string","desc":"店铺硬件设施"}
     *
     * @output {"name":"data.tech.technician_id","type":"int","desc":"技师id"}
     * @output {"name":"data.tech.user_name","type":"string","desc":"技师名称"}
     * @output {"name":"data.tech.photos","type":"string","desc":"技师图片"}
     * @output {"name":"data.tech.zanNum","type":"int","desc":"技师点赞数"}
     * @output {"name":"data.tech.giftNum","type":"int","desc":"技师送花数量"}
     * @output {"name":"data.tech.workStatus","type":"int","desc":"技师工作状态,1上钟,空闲）"}
     * @output {"name":"data.tech.skill","type":"string","desc":"技师会的技能"}
     * @output {"name":"data.tech.judge","type":"float","desc":"技师评分数值"}
     * @output {"name":"data.tech.collectNum","type":"int","desc":"技师收藏数量"}
     * @output {"name":"data.tech.job_num","type":"int","desc":"技师工号"}
     * @output {"name":"data.tech.sub_role_name","type":"string","desc":"技师的职称"}
     *
     *
     * @output {"name":"data.item.item_name","type":"string","desc":"项目名称"}
     * @output {"name":"data.item.item_time","type":"int","desc":"项目时长"}
     * @output {"name":"data.item.item_price","type":"float","desc":"项目价格"}
     * @output {"name":"data.item.description","type":"string","desc":"项目描述"}
     * @output {"name":"data.item.photo1","type":"string","desc":"项目图片一"}
     * @output {"name":"data.item.photo2","type":"string","desc":"项目图片二"}
     * @output {"name":"data.item.photo3","type":"string","desc":"项目图片三"}
     *
     */
    public function getShopDetail(){
        //$user_id=$this->input->post('user_id');
        //$shop_id=$this->input->post('shop_id');
        $shop_id=1;
        $user_id=1;
        if (!$shop_id){
            show300('店铺id不正确');
        }
        //店铺详情
        // 店铺
        $where_shop=['shop.shop_id'=>$shop_id];
        $shop=$this->shop_model->getUserShop($where_shop);
        if (empty($shop)){
            $data['shop']=$shop;
        }else{
            $data['shop']=$shop[0];
        }
        //技师总数和详情
        //技师 todo  人气，排名， 预计等的时间
        $where_tech=['reg_staff.shop_id'=>$shop_id];
        $data['tech']=$this->user_model->getTech($where_tech);
        $techNum=$data['tech']['count'];
        if($data['tech']['count']!=0){
            $gzTechs=$this->getGzTechs($user_id,$shop_id);
            unset($data['tech']['count']);
            foreach ($data['tech'] as $k=>$v){
                if (in_array($v['technician_id'],$gzTechs)){
                    $data['tech'][$k]['isGz']=1;
                }else{
                    $data['tech'][$k]['isGz']=0;
                }
            }
            $data['tech']['count']=$techNum;
        }else{
            $data['tech']=array();

        }
        //echo "<pre>";
        //print_r($gzTechs);exit;
        //项目详情
        //项目 todo    热销项目    根据 平均值查询  项目评价数值（4.9分）评论数  月销量
        $where_item=['item.shop_id'=>$shop_id,'shelves'=>1];
        $data['item']=$this->item_model->getItem($where_item);
        show200($data);
        //
    }

    /**
     * 根据店铺id获取此店铺全部技师
     */
    public function getShopTech(){
        //$user_id=$this->input->post('user_id');
        //$shop_id=$this->input->post('shop_id');
        $shop_id=2;
        $user_id=1;
        $gzTechs=$this->getGzTechs($user_id,$shop_id);

        $where_tech=['reg_staff.shop_id'=>$shop_id];
        $data['tech']=$this->user_model->getTech($where_tech);




        $this->a($res);



        





    }













}
/* End of file Login.php */
