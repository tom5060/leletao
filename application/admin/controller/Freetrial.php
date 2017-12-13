<?php
namespace app\admin\controller;
use app\admin\logic\GoodsLogic;
use think\AjaxPage;
use think\Page;
use think\Db;

class Freetrial extends Base {
    public function index(){
        $sql="SELECT f.id,f.title,f.start_time,f.end_time,f.description,
        (select count(*) from `lt_freetrial_goods` g where f.id = g.fid) as sl
        FROM `lt_freetrial` f order by f.id desc;";
        $data =Db::query($sql);
        for($i=0;$i<count($data);$i++){
            $time = date("Y-m-d H:i:s");
            $t1 = strtotime($time) > strtotime($data[$i]["start_time"]);
            $t2 = strtotime($time) < strtotime($data[$i]["end_time"]);
            $data[$i]["zt"] = "<font>[已结束]</font>";
            if($t1 && $t2){
                $data[$i]["zt"] = "<font style='color:rgb(44, 188, 163)'>[活动中]</font>";
            }else if($t1==false && $t2==true){
                $data[$i]["zt"] = "<font style='color:#ff7b86'>[未开始]</font>";
            }
           // $data[$i]["zt"] = ($t1 && $t2) ? "<font style='color:rgb(44, 188, 163)'>[活动中]</font>" : "<font style='color:#ff7b86'>[已结束]</font>";
        }
        //$data =  Db::name('freetrial')->select();
        $this->assign('list',$data); 
        return $this->fetch();
    }
    public function add(){
        $this->assign('type',"add");
        return $this->fetch();
    }
    public function edit(){
        $id = I("id");
        $data = Db::name('freetrial')->where('id',$id)->find();
        $this->assign('info',$data);
        $this->assign('type',"edit");
        return $this->fetch("add");
    }
    // public function demo(){
    //     $data =  Db::name('freetrial_dd')->where("id<502")->select();
    //     for($i=0;$i<count($data);$i++){
    //         $dz = explode("|",$data[$i]["address"]);
    //         $add = Db::name("user_address")->where("user_id =".$data[$i]["uid"])->select();
    //         for($ii=0;$ii<count($add);$ii++){
    //             if($add[$ii]["address"]==$dz[0]){
    //                 //$data[$i]["dz"]=$add[$ii];
    //                 $d = $this->readAddress($add[$ii]["province"]).$this->readAddress($add[$ii]["city"]).$this->readAddress($add[$ii]["district"]).$this->readAddress($add[$ii]["twon"]);
    //                 $data[$i]["address"]=$d." ".$data[$i]["address"];
    //                 Db::name('freetrial_dd')->update($data[$i]);
    //             }
    //         }
    //     }
    //     dump("ok");die;
    // }
    function readAddress($id){
        $name = Db::name('region ')->where(array('id'=>$id))->find();
        if($name){
            return $name["name"];
        }
        return "";
    }
    public function order(){
        $key_word = I("key_word");
        $id = I("id");
        
        $where["fid"]=$id;
        if(!empty($key_word)){
            $where["address|orderid"]= array('like','%'.$key_word.'%');
        }
        $freetrial = Db::name('freetrial')->where('id',$id)->find();
        
        $all  = Db::name('freetrial_dd')->where($where)->count();
        $data =  Db::name('freetrial_dd')->where($where)->order(['zt','addtime'=>'desc'])->paginate(20);
        $this->assign('id',$id);
        $this->assign('info',$data);
        $this->assign('all',$all);
        $this->assign('freetrial',$freetrial);
        return $this->fetch();
    }
    public function exportReport(){
        $pushData=input("post.");
        if(count($pushData)>0){
            $ids=implode(",",$pushData["ids"]);
            $data =  Db::name('freetrial_dd')->where('id','in',$ids)->select();
        }else{
            $data =  Db::name('freetrial_dd')->select();
        }
        $strTable ='<table width="500" border="1">';
    	$strTable .= '<tr>';
    	$strTable .= '<td style="text-align:center;font-size:12px;width:120px;">订单编号</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="100">日期</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">收货人</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">收货地址</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">电话</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">发货状态</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">订单状态</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">商品编号</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">商品名称</td>';
    	$strTable .= '</tr>';
        foreach($data as $val){
            $users = explode("|",$val["address"]);
            $kd=$val['kuaidiname'].$val['kuaidi'];
            switch($val['zt']){
                case -1:
                    $zt="待处理";
                    break;
                case 0:
                    $zt="申请失败";
                    break;
                case 1:
                    $zt="申请成功";
                    break;
            }
            $strTable .= '<tr>';
            $strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$val['orderid'].'</td>';
            $strTable .= '<td style="text-align:center;font-size:12px;">'.$val['addtime'].'</td>';
            $strTable .= '<td style="text-align:center;font-size:12px;">'.$users[1].'</td>';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$users[0].'</td>';
            $strTable .= '<td style="text-align:center;font-size:12px;">'.$users[2].'</td>';
            $strTable .= '<td style="text-align:center;font-size:12px;">'.($kd==""?"未发货":$kd).'</td>';
            $strTable .= '<td style="text-align:center;font-size:12px;">'.$zt.'</td>';
            $strTable .= '<td style="text-align:center;font-size:12px;">'.$val['goodid'].'</td>';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['name'].'</td>';
            $strTable .= '</tr>';
        }
        $strTable .='</table>';
        unset($orderList);
    	downloadExcel($strTable,'order_shiyong');
    	exit();
    }
    public function orderinfo(){
        $id = I("id");
        $data =  Db::name('freetrial_dd')->where(array("id"=>$id))->find();
        $data["address"]=explode("|",$data["address"]);
        $data["spec"]=str_replace("|"," - ",$data["spec"]);
        $this->assign('order',$data);
        return $this->fetch();
    }
    public function orderForm(){
        $pushData=input("post.");
        $pushData["zt"]=1;
        $i=Db::name('freetrial_dd')->update($pushData);
        if(true){
            $dt=Db::name('freetrial_dd')->where(array("id"=> $pushData["id"]))->find();
            $this->SendWX($pushData);
            
            $this->success('操作成功', U('Freetrial/order',array("id"=>$pushData["fid"])));
        }else{
            $this->error('操作失败', U('Freetrial/order',array("id"=>$pushData["fid"])));
        }
    }
    public function SendWX($pushData){
        $wx_user = M('wx_user')->find();
        $jssdk = new \app\mobile\logic\Jssdk($wx_user['appid'],$wx_user['appsecret']);
        $tmp_user = M('users')->where("user_id", $pushData["uid"])->find();
        //dump($tmp_user);die;
        if($tmp_user['oauth']== 'weixin')
        {
            $kdname = explode("|",$pushData["kuaidiname"]);
            $data=array(
                'first'=>array(
                    "value"=>"试用产品发货通知"
                ),
                'delivername'=>array(
                    "value"=>$kdname[1]
                ),
                'ordername'=>array(
                    "value"=>$pushData["kuaidi"]
                ),
                'remark'=>array(
                    "value"=>"您申请的试用产品已发货，留意查收。感谢您对乐乐淘活动的参与和支持。"
                )
            );
            $Tmplmsg = new \app\common\logic\Tmplmsg();
            $return = $Tmplmsg->FHMsg($tmp_user['openid'],$data);
            $wx_content="恭喜id：".$tmp_user["nickname"]." 会员，您申请的试用产品成功通过了，请您关注个人中心订单后台，留意查收。感谢您对乐乐淘活动的参与和支持。";
           // dump($wx_content);die;
           $s= $jssdk->push_msg($tmp_user['openid'],$wx_content);
        } 
    }
    public function sqsb(){
        $pushData=input("post.");
        if(count($pushData)>0){
            $ids= $pushData["ids"];
            foreach($ids as $val){
                Db::name('freetrial_dd')->where('id',$val)->setField("zt",0);
            }
        }
        $this->success('操作成功');
    }
    public function orderJJ(){
        $id = I("id");
        $fid = I("fid");
        $data=array(
            "id"=>$id,
            "zt"=>0
        );
        $i = Db::name('freetrial_dd')->update($data);
        if($i){
            $this->success('操作成功', U('Freetrial/order',array("id"=>$fid)));
        }else{
            $this->error('操作失败', U('Freetrial/order',array("id"=>$fid)));
        }
    }
    public function Form(){
        $type=I("type");
        if($type=="add"){
            $data = array(
                "title"=>I("title"),
                "start_time"=>I("start_time"),
                "end_time"=>I("end_time"),
                "description"=>I("description")
            );
            $i= Db::name('freetrial')->insert($data);
            if($i){
               $this->success('编辑成功', U('Freetrial/index'));
            }else{
               $this->error('编辑失败', U('Freetrial/index'));
            }
        }else if($type =="edit"){
            $data = array(
                "id"=>I("id"),
                "title"=>I("title"),
                "start_time"=>I("start_time"),
                "end_time"=>I("end_time"),
                "description"=>I("description")
            );
            $i = Db::name('freetrial')->update($data);
            if($i){
                $this->success('编辑成功', U('Freetrial/index'));
            }else{
                $this->error('编辑失败', U('Freetrial/index'));
            }
        }else{
            $this->error('编辑失败', U('Freetrial/index'));
        }
    }

    //试用商品列表
    public function splist(){
        $id = I("id");
        $freetrial = Db::name('freetrial')->where(array("id"=>$id))->find();
        $data =  Db::name('freetrial_goods')->where(array("fid"=>$id))->order("sort")->select();
        $this->assign('freetrial',$freetrial); 
        $this->assign('list',$data); 
        return $this->fetch();
    }
    public function addGoods(){
        $this->assign('type',"add");
        $fid = I("id");
        $this->assign('fid',$fid); 
        return $this->fetch();
    }
    public function editGoods(){
        $id = I("id");
        $data =  Db::name('freetrial_goods')->where(array("id"=>$id))->find();
        $this->assign('info',$data);
        $this->assign('type',"edit");
        return $this->fetch("addGoods");
    }
    public function GoodsForm(){
        $type=I("type");
        $fid=I("fid");
        if($type=="add"){
            $data=array(
                "fid"=>$fid,
                "gid"=>I("gid"),
                "ysq"=>I("ysq"),
                "sysl"=>I("sysl"),
                "fname"=>I("fname"),
                "sort"=>I("sort")
            );
            $i= Db::name('freetrial_goods')->insert($data);
            if($i){
               $this->success('编辑成功', U('Freetrial/splist',array("id"=>$fid)));
            }else{
               $this->error('编辑失败', U('Freetrial/splist',array("id"=>$fid)));
            }
        }else if($type =="edit"){
            $data=array(
                "id"=>I("id"),
                "gid"=>I("gid"),
                "ysq"=>I("ysq"),
                "sysl"=>I("sysl"),
                "fname"=>I("fname"),
                "sort"=>I("sort")
            );
            $i = Db::name('freetrial_goods')->update($data);
            if($i){
                $this->success('编辑成功', U('Freetrial/splist',array("id"=>$fid)));
            }else{
                $this->error('编辑失败', U('Freetrial/splist',array("id"=>$fid)));
            }
        }else{
            $this->error('编辑失败', U('Freetrial/index'));
        }
    }
    public function del(){
        $pushData=input("post.");
        $id = $pushData["del_id"];
        $i =  Db::name('freetrial_goods')->delete($id);
        return json($i);
    }
}