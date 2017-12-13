<?php
namespace app\mobile\controller;
use think\AjaxPage;
use think\Page;
use think\Db;
class Freetrial extends MobileBase {
    /**
     * 析构流函数
     */
     public function  __construct() {   
        parent::__construct();                
        $this->cartLogic = new \app\home\logic\CartLogic();
        if(session('?user'))
        {
        	$user = session('user');
                $user = M('users')->where("user_id", $user['user_id'])->find();
                session('user',$user);  //覆盖session 中的 user               			                
        	$this->user = $user;
        	$this->user_id = $user['user_id'];
        	$this->assign('user',$user); //存储用户信息
                // 给用户计算会员价 登录前后不一样
                if($user){
                    $user[discount] = (empty($user[discount])) ? 1 : $user[discount];
                    DB::execute("update `__PREFIX__cart` set member_goods_price = goods_price * {$user[discount]} where (user_id ={$user[user_id]} or session_id = '{$this->session_id}') and prom_type = 0");
                }                 
         }            
    }
    public function index(){
        $time=date("Y-m-d H:i:s");
        $sql="SELECT * FROM `lt_freetrial` where '$time' BETWEEN start_time and end_time ORDER BY id desc LIMIT 1;";
        $data = Db::query($sql);
        if(count($data)==0){
            $this->error('暂时没有试用活动', U('index/index'));
        }
        $sql2="SELECT fg.*,g.shop_price,g.original_img FROM `lt_freetrial_goods` fg,`lt_goods` g where fid=".$data[0]['id']." AND g.goods_id=fg.gid ORDER BY fg.sort;";
        $list = Db::query($sql2);
        $this->assign('info',$data[0]); 
        $this->assign('list',$list); 
        return $this->fetch();
    }
    public function info(){
        if($this->user_id == 0)
            $this->error('请先登陆',U('Mobile/User/login'));
        $time = date("Y-m-d H:i:s");
        $gid=I("gid");
        $fid=I("fid");
        $fre = Db::name('freetrial')->where('id',$fid)->find();
        $t1 = strtotime($time) > strtotime($fre["start_time"]);
        $t2 = strtotime($time) < strtotime($fre["end_time"]);
        if(!($t1 && $t2)){
            $this->error('试用活动已结束', U('Goods/goodsInfo',array("id"=>$gid)));
        }
        C('TOKEN_ON',true);
        //$goodsLogic = new \app\home\logic\GoodsLogic();
        $filter_spec = [];//$goodsLogic->get_spec($gid); 
        $info = Db::name('freetrial_goods')->where(array('fid'=>$fid,'gid'=>$gid))->find();
        $address = M('user_address')->where(['user_id'=>$this->user_id])->select();//,'is_default'=>1
        //dump($address);die;
        $data=array(
            "goods"=>$info,
            "spec"=>$filter_spec
        );
        if(empty($address)){
        	header("Location: ".U('Mobile/User/add_address',array('source'=>'cart2')));
                exit;
        }else{
            for($i=0;$i<count($address);$i++){
                $address[$i]["province"]=$this->readAddress($address[$i]["province"]);
                $address[$i]["city"]=$this->readAddress($address[$i]["city"]);
                $address[$i]["district"]=$this->readAddress($address[$i]["district"]);
                $address[$i]["twon"]=$this->readAddress($address[$i]["twon"]);
            }
            $data["address"]=$address;
        }
        return json($data);
    }
    function readAddress($id){
        $name = Db::name('region ')->where(array('id'=>$id))->find();
        if($name){
            return $name["name"];
        }
        return "";
    }
    public function form(){
        if($this->user_id == 0)
            $this->error('请先登陆',U('Mobile/User/login'));
        $pushData=input("post.");
        $fre = Db::name('freetrial')->where('id',$pushData["fid"])->find();
        $time = date("Y-m-d H:i:s");
        
        $t1 = strtotime($time) > strtotime($fre["start_time"]);
        $t2 = strtotime($time) < strtotime($fre["end_time"]);
        
        if(!($t1 && $t2)){
            $this->error('试用活动已结束', U('Goods/goodsInfo',array("id"=>$gid)));
        }
        $arr=array(
            "uid"=>$this->user_id,
            "fid"=>$pushData["fid"]
        );
        $od = Db::name('freetrial_dd')->where($arr)->find();
        
        if($od!=null){
            $this->error('本期试用您已申请，活动限一个ID申请一次!',U('Mobile/Freetrial/index'));
        }
        for($i=0;$i<count($pushData["spec"]);$i++){
            if($i!=0){
                $spec.="|";
            }
            $spec.=$pushData["spec"][$i];
        }
        $data = array(
            "orderid"=>date('YmdHis').rand(1000,9999),
            "uid"=>$this->user_id,
            "gid"=>$pushData["id"],
            "goodid"=>$pushData["goodid"],
            "fid"=>$pushData["fid"],
            "spec"=>$spec,
            "name"=>$pushData["name"],
            "address"=>$pushData["address"],
            "zt"=>-1,
            "addtime"=>date("Y-m-d H:i:s")
        );
        $i= Db::name('freetrial_dd')->insert($data);
        if($i){
            Db::name('freetrial_goods')->where('id', $pushData["id"])->setInc('ysq');
           $this->success('已提交申请', U('Mobile/Freetrial/index'));
        }else{
           $this->error('申请失败', U('Mobile/Freetrial/index'));
        }
    }
}