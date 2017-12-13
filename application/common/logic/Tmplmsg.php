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
class Tmplmsg //extends Model
{
    private function get_access_token(){
        //判断是否过了缓存期
        $wechat = M('wx_user')->find();
        $expire_time = $wechat['web_expires'];
        if($expire_time > time()){
           return $wechat['web_access_token'];
        }
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$wechat['appid']}&secret={$wechat['appsecret']}";
        $return = httpRequest($url,'GET');
        $return = json_decode($return,1);
        $web_expires = time() + 7000; // 提前200秒过期
        M('wx_user')->where(array('id'=>$wechat['id']))->save(array('web_access_token'=>$return['access_token'],'web_expires'=>$web_expires));
        return $return['access_token'];
    }

    public function tmsg($openid,$templateID,$data){
        $access_token=$this->get_access_token();
        $url ="https://api.weixin.qq.com/cgi-bin/message/template/send?access_token={$access_token}";
        $post_arr = array(
            'touser'=>$openid,
            'template_id'=>$templateID,
            'data'=>$data
            );
        $post_str = json_encode($post_arr,JSON_UNESCAPED_UNICODE);        
        $return = httpRequest($url,'POST',$post_str);
        $return = json_decode($return,true); 
    }
    /*
     * 购买成功通知
     * $openid 用户 opid
     * 'data'=>array(
            'keynote1'=>$name,
            'remark'=>$remark
            )
            您好，您已购买成功。

商品信息：{{name.DATA}}
{{remark.DATA}}
    */
    public function BuyOkMsg($openid,$data){
        $templateID="eynkTiuOvhVy_81TJzNbEisWwtPB83Cf3TsfevFf6lU";
        $this->tmsg($openid,$templateID,$data);
    }
    /*
     * 退款通知
     * $openid 用户 opid
     * $data=array(
            "first"=>"",
            "keynote1"=>"",
            "keynote2"=>"",
            "remark"=>""
        );
        {{first.DATA}}

退款原因：{{reason.DATA}}
退款金额：{{refund.DATA}}
{{remark.DATA}}
    */
    public function Refund($openid,$data){
        $templateID="EJvb6XBC4USimrmitk9Kb3qY894icvS55fD-6wpthAQ";
        $this->tmsg($openid,$templateID,$data);
    }
    /*
     * 订单包裹跟踪通知
     * $openid 用户 opid
     * $data=array(
            "first"=>"",
            "keynote1"=>"",
            "keynote2"=>"",
            "remark"=>""
        );
        {{first.DATA}}

订单号：{{order_id.DATA}}
包裹单号：{{package_id.DATA}}
{{remark.DATA}}
    */
    public function Express($openid,$data){
        $templateID="K477yftPCn3awbyT3GPBoRUuKS6vHqlFActZVs7tvAI";
        $this->tmsg($openid,$templateID,$data);
    }
    /*
     * 积分提醒
     * $openid 用户 opid
     * $data=array(
            "first"=>"",
            "keynote1"=>"",
            "keynote2"=>"",
            "keynote3"=>"",
            "keynote4"=>"",
            "keynote5"=>"",
            "remark"=>""
        );
        {{first.DATA}}

账户：{{account.DATA}}
时间：{{time.DATA}}
类型：{{type.DATA}}
{{creditChange.DATA}}积分：{{number.DATA}}
{{creditName.DATA}}余额：{{amount.DATA}}
{{remark.DATA}}
    */
    public function JFMsg($openid,$data){
        $templateID="mbs4syJc1aMHADKGDY75Cw6llNUPNtC9aLc-y6VGfvI";
        $this->tmsg($openid,$templateID,$data);
    }
    /*
     * 团购成功通知
     * $openid 用户 opid
     * $data=array(
            "first"=>"",
            "keynote1"=>"",
            "keynote2"=>"",
            "remark"=>""
        );
        {{first.DATA}}

产品名称：{{hotelName.DATA}}
团购券号:{{voucher number.DATA}}
{{remark.DATA}}
    */
    public function TGMsg($openid,$data){
        $templateID="oa9zB5wWKf3utH33P-9M9KZV0VKl0rmhPE1QtmURyX0";
        $this->tmsg($openid,$templateID,$data);
    }
/*
     * 商品已发出通知
     * $openid 用户 opid
     * $data=array(
            "first"=>"",
            "keynote1"=>"",
            "keynote2"=>"",
            "remark"=>""
        );
        {{first.DATA}} 

快递公司：{{delivername.DATA}}
快递单号：{{ordername.DATA}}
 {{remark.DATA}}  
    */
    public function FHMsg($openid,$data){
        $templateID="y4_zS4EVpQtt8Nst0HIoAAyF3lgHB_Da3D0ARvNhGbs";
        $this->tmsg($openid,$templateID,$data);
    }
}