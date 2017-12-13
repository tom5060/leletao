<?php
/**
 * tpshop
 * ============================================================================
 * 版权所有 2015-2027 深圳搜豹网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.tp-shop.cn
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * Author: IT宇宙人
 * Date: 2015-09-09
 * 
 * TPshop 公共逻辑类  将放到Application\Common\Logic\   由于很多模块公用 将不在放到某个单独模下面
 */

namespace app\common\logic;

use think\Model;
//use think\Page;

/**
 * 分销逻辑层
 * Class CatsLogic
 * @package Home\Logic
 */
class DistributLogic //extends Model
{
     public function hello(){
        echo 'function hello(){'; 
     }
     
     /**
      * 生成分销记录
      */

      public function rebate_log($order){
        // 计算返点 = 订单总价-邮费-优惠券金额
        $count_amount = $order['total_amount']-$order['shipping_price']-$order['coupon_price'];
        $arr=[];
        //获取该订单购买的用户id
        $uid =M("order")->where(array("order_id"=>$order["order_id"]))->getField("user_id");
        //购买用户信息
        $user=M("users")->where(array("user_id"=>$uid))->find();
         //是合伙人
        if($user["is_distribut"] == 1){
            //合伙人 10% 购买
            if($user["first_leader"] == 0 && $user["second_leader"] == 0 && $user["third_leader"] == 0){
                array_push($arr,array("uid"=>$uid,"money"=>($count_amount * 0.1),"lv"=>0,"i"=>true));
            }
            //二级 购买
            if($user["first_leader"] > 0 && $user["second_leader"] == 0 && $user["third_leader"] == 0){
                //合伙人 2%
                array_push($arr,array("uid"=>$user["first_leader"],"money"=>($count_amount * 0.02),"lv"=>1,"i"=>false));
                //自己 8%
                array_push($arr,array("uid"=>$uid,"money"=>($count_amount * 0.08),"lv"=>0,"i"=>true));
            }
            //三级
            if($user["first_leader"] > 0 && $user["second_leader"] > 0 && $user["third_leader"] == 0){
                //合伙人 2%
                array_push($arr,array("uid"=>$user["second_leader"],"money"=>($count_amount * 0.02),"lv"=>2,"i"=>false));
                //二级 2%
                array_push($arr,array("uid"=>$user["first_leader"],"money"=>($count_amount * 0.02),"lv"=>1,"i"=>false));
                //自己 6%
                array_push($arr,array("uid"=>$uid,"money"=>($count_amount * 0.06),"lv"=>0,"i"=>true));
            }
            if($user["first_leader"] > 0 && $user["second_leader"] > 0 && $user["third_leader"] > 0){
                //合伙人 2%
                array_push($arr,array("uid"=>$user["third_leader"],"money"=>($count_amount * 0.02),"lv"=>2,"i"=>false));
                //二级 2%
                array_push($arr,array("uid"=>$user["second_leader"],"money"=>($count_amount * 0.02),"lv"=>1,"i"=>false));
                //三级 6%
                array_push($arr,array("uid"=>$user["first_leader"],"money"=>($count_amount * 0.06),"lv"=>0,"i"=>false));
            }
        }else{//不是合伙人
             //二级 购买
            if($user["first_leader"] > 0 && $user["second_leader"] == 0 && $user["third_leader"] == 0){
                //合伙人 10%
                array_push($arr,array("uid"=>$user["first_leader"],"money"=>($count_amount * 0.1),"lv"=>0,"i"=>false));
            }
            //三级
            if($user["first_leader"] > 0 && $user["second_leader"] > 0 && $user["third_leader"] == 0){
                //合伙人 2%
                array_push($arr,array("uid"=>$user["second_leader"],"money"=>($count_amount * 0.02),"lv"=>1,"i"=>false));
                //二级 8%
                array_push($arr,array("uid"=>$user["first_leader"],"money"=>($count_amount * 0.08),"lv"=>0,"i"=>false));
            }
            if($user["first_leader"] > 0 && $user["second_leader"] > 0 && $user["third_leader"] > 0){
                //合伙人 2%
                array_push($arr,array("uid"=>$user["third_leader"],"money"=>($count_amount * 0.02),"lv"=>2,"i"=>false));
                //二级 2%
                array_push($arr,array("uid"=>$user["second_leader"],"money"=>($count_amount * 0.02),"lv"=>1,"i"=>false));
                //三级 6%
                array_push($arr,array("uid"=>$user["first_leader"],"money"=>($count_amount * 0.06),"lv"=>0,"i"=>false));
            }
        }
        if(empty($arr))
            return;
        //  微信消息推送
        $wx_user = M('wx_user')->find();
        $jssdk = new \app\mobile\logic\Jssdk($wx_user['appid'],$wx_user['appsecret']);
        foreach($arr as $k=>$v){
            $lv=$v["lv"] + 1;
            if($lv == 1){
                $Dlv="一";
            }else if($lv == 2){
                $Dlv="二";
            }else if($lv == 3){
                $Dlv="三";
            }
            $data = array(             
                'user_id' =>$v["uid"],
                'buy_user_id'=>$user['user_id'],
                'nickname'=>$user['nickname'],
                'order_sn' => $order['order_sn'],
                'order_id' => $order['order_id'],
                'goods_price' => $order['goods_price'],
                'money' => $v["money"],
                'level' => $lv,
                'create_time' => time(),             
            );                  
            M('rebate_log')->add($data);
            // 微信推送消息
            $tmp_user = M('users')->where("user_id", $v["uid"])->find();
            if($tmp_user['oauth']== 'weixin')
            {
                $wx_content = "你的".$Dlv."级下线,刚刚下了一笔订单:{$order['order_sn']} 如果交易成功你将获得 ￥".$v["money"]."奖励 !";
                if($v["i"]){
                    $wx_content = "你刚刚下了一笔订单:{$order['order_sn']} 如果交易成功你将获得 ￥".$v["money"]."奖励 !";
                }
                $jssdk->push_msg($tmp_user['openid'],$wx_content);
            }                       
        }
        M('order')->where("order_id", $order['order_id'])->save(array("is_distribut"=>1));  //修改订单为已经分成
      }
     
     
     /**
      * 自动分成 符合条件的 分成记录
      */
     function auto_confirm(){
         
         $switch = tpCache('distribut.switch');
         if($switch == 0)
             return false;
         
         $today_time = time();
         $distribut_date = tpCache('distribut.date');
         $distribut_time = $distribut_date * (60 * 60 * 24); // 计算天数 时间戳
         $rebate_log_arr = M('rebate_log')->where("status = 2 and ($today_time - confirm) >  $distribut_time")->select();
         foreach ($rebate_log_arr as $key => $val)
         {
             accountLog($val['user_id'], $val['money'], 0,"订单:{$val['order_sn']}分佣",$val['money']);             
             $val['status'] = 3;
             $val['confirm_time'] = $today_time;
             $val['remark'] = $val['remark']."满{$distribut_date}天,程序自动分成.";
             M("rebate_log")->where("id", $val['id'])->save($val);
         }
     }
}