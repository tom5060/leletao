<?php
/**
 * tpshop
 * ============================================================================
 * * 版权所有 2015-2027 深圳搜豹网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.tp-shop.cn
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: 当燃 2016-01-09
 */
namespace app\mobile\controller;
use app\home\logic\UsersLogic;
use Think\Db;
class Index extends MobileBase {

    public function index(){
        /*
            //获取微信配置
            $wechat_list = M('wx_user')->select();
            $wechat_config = $wechat_list[0];
            $this->weixin_config = $wechat_config;        
            // 微信Jssdk 操作类 用分享朋友圈 JS            
            $jssdk = new \Mobile\Logic\Jssdk($this->weixin_config['appid'], $this->weixin_config['appsecret']);
            $signPackage = $jssdk->GetSignPackage();              
            print_r($signPackage);
        */
        $hot_goods = M('goods')->where("is_hot=1 and is_on_sale=1")->order('goods_id DESC')->limit(20)->cache(true,TPSHOP_CACHE_TIME)->select();//首页热卖商品
        $thems = M('goods_category')->where('level=1')->order('sort_order')->limit(9)->cache(true,TPSHOP_CACHE_TIME)->select();
        $this->assign('thems',$thems);
        $this->assign('hot_goods',$hot_goods);
        $favourite_goods = M('goods')->where("is_recommend=1 and is_on_sale=1")->order('goods_id DESC')->limit(20)->cache(true,TPSHOP_CACHE_TIME)->select();//首页推荐商品

        //秒杀商品
        $now_time = time();  //当前时间
        if(is_int($now_time/7200)){      //双整点时间，如：10:00, 12:00
            $start_time = $now_time;
        }else{
            $start_time = floor($now_time/7200)*7200; //取得前一个双整点时间
        }
        $end_time = $start_time+7200;   //结束时间
        $seckill_list=DB::query("select * from __PREFIX__goods as g inner join __PREFIX__flash_sale as f on g.goods_id = f.goods_id where start_time = $start_time and end_time = $end_time limit 3");     //获取秒杀商品

        $this->assign('seckill_list',$seckill_list);
        $this->assign('start_time',$start_time);
        $this->assign('end_time',$end_time);
        $this->assign('favourite_goods',$favourite_goods);
        return $this->fetch();
    }

    // public function demo(){
    //     $openid="oT4Oo06Hh_yH4yn37ML8pkUaHuLA";
    //     $data=array(
    //         'first'=>array(
    //             "value"=>"团购测试"
    //         ),
    //         'delivername'=>array(
    //             "value"=>"韵达快递"
    //         ),
    //         'ordername'=>array(
    //             "value"=>"123123123"
    //         ),
    //         'remark'=>array(
    //             "value"=>"测试信息 \n asdf"
    //         )
    //     );
    //     $Tmplmsg = new \app\common\logic\Tmplmsg();
    //     $return = $Tmplmsg->FHMsg($openid,$data);
    //     dump($return);die;
    // }

    /**
     * 分类列表显示
     */
    public function categoryList(){
        return $this->fetch();
    }

    /**
     * 模板列表
     */
    public function mobanlist(){
        $arr = glob("D:/wamp/www/svn_tpshop/mobile--html/*.html");
        foreach($arr as $key => $val)
        {
            $html = end(explode('/', $val));
            echo "<a href='http://www.php.com/svn_tpshop/mobile--html/{$html}' target='_blank'>{$html}</a> <br/>";
        }
    }

    /**
     * 商品列表页
     */
    public function goodsList(){
        $id = I('get.id/d',0); // 当前分类id
        $lists = getCatGrandson($id);
        $this->assign('lists',$lists);
        return $this->fetch();
    }

    public function ajaxGetMore(){
    	$p = I('p/d',1);
    	$favourite_goods = M('goods')->where("is_recommend=1 and is_on_sale=1")->order('goods_id DESC')->page($p,10)->cache(true,TPSHOP_CACHE_TIME)->select();//首页推荐商品
    	$this->assign('favourite_goods',$favourite_goods);
    	return $this->fetch();
    }

    // 促销活动页面
    public function promoteList()
    {
        $period_time = I("period_time");

        $goodsList = DB::query("select * from __PREFIX__goods as g inner join __PREFIX__flash_sale as f on g.goods_id = f.goods_id   where ".time()." > start_time  and ".time()." < end_time and period_time = $period_time ORDER BY f.sort desc");
        $brandList = M('brand')->getField("id,name,logo");
        $this->assign('brandList',$brandList);
        $this->assign('goodsList',$goodsList);
        return $this->fetch();
    }

    /**
     * 类目首页
     */
    public function index_leimu(){
        // $cat_id = I('cat_id');
        // $map['is_hot'] = 1;
        // $data_arr = [];
        // $cat_list =  M('goods_category')->where($map)->order('sort_order')->getField('id,parent_id,level,name,image,sort_order');
        // $arr = $this->getMenu($cat_list,"child",$cat_id);

        // $this->assign('index_leimu', $arr);
        // return $this->fetch();

        $map['cat_group'] = I('cat_group');
        $cat_list =  M('goods_category')->where($map)->order('sort_order')->limit(7)->getField('id,parent_id,level,name,image,sort_order,cat_group');

        $this->assign('cat_list', $cat_list);
        return $this->fetch();
    }

    Public function getMenu($cate, $name = 'child', $menuId = 0) {
        $arr = array();
        foreach ($cate as $v) {
            if ($v['parent_id'] == $menuId) {
                $v[$name] = self::getMenu($cate, $name, $v['id']);
                $arr[] = $v;
            }
        }
        return $arr;
    }

    // 热门分类
    public function ajaxGetMoreHot(){
        $p = I('p/d',1);
        $cat_id = I('cat_id');
        if($cat_id > 0)
        {
            $grandson_ids = getCatGrandson($cat_id);

            $where['is_hot'] = array('eq', '1');
            $where['is_on_sale'] = array('eq','1');
            $where['cat_id'] = array('in',implode(',', $grandson_ids));
        }
        $favourite_goods = M('goods')->where($where)->order('sort DESC')->page($p,10)->cache(true,TPSHOP_CACHE_TIME)->select();//二级栏目推荐商品
        $this->assign('favourite_goods',$favourite_goods);
        return $this->fetch();
    }

    // 品牌团首页
    public function index_brand(){
        return $this->fetch();
    }

    // 品牌团ajax
    public function ajaxGetMoreBrand(){
        $p = I('p/d',1);
        $brand_id = I('brand_id');
        if($brand_id > 0)
        {
            $where['is_recommend'] = array('eq', '1');
            $where['is_on_sale'] = array('eq','1');
            $where['brand_id'] = $brand_id;
        }
        $favourite_goods = M('goods')->where($where)->order('sort DESC')->page($p,10)->cache(true,TPSHOP_CACHE_TIME)->select();//二级栏目推荐商品
        $this->assign('favourite_goods',$favourite_goods);
        return $this->fetch();
    }

    public function brand_goods(){
        return $this->fetch();
    }

    // 获取该品牌（id） 所有商品
    public function ajaxGetAllBrand(){
        $p = I('p/d',1);
        $brand_id = I('brand_id');
        if($brand_id > 0)
        {
            $where['brand_id'] = $brand_id;
            $where['is_on_sale'] = array('eq','1');
        }
        $favourite_goods = M('goods')->where($where)->order('shop_price ASC')->page($p,10)->cache(true,TPSHOP_CACHE_TIME)->select();//二级栏目推荐商品
        $this->assign('favourite_goods',$favourite_goods);
        return $this->fetch();
    }

    public function all_nine_goods(){
        return $this->fetch();
    }

    // 获取所有价格为9.9 19.9 29.9 39.9的商品
    public function ajaxAllNineGoods(){
        $p = I('p/d',1);

        $where['shop_price'] = array('in','9.9,19.9,29.9,39.9,49.9,59.9,69.9,79.9,89.9,99.9');
        $where['is_on_sale'] = array('eq','1');

        $favourite_goods = M('goods')->where($where)->order('shop_price ASC')->page($p,10)->cache(true,TPSHOP_CACHE_TIME)->select();//二级栏目推荐商品
        $this->assign('favourite_goods',$favourite_goods);
        return $this->fetch();
    }

    /**
     * 首页广告 粽子
     */
    public function zongzi(){
        return $this->fetch();
    }

    /**
     * 首页广告 骆驼
     */
    public function luotuo(){
        return $this->fetch();
    }

    /**
     * 品牌团 曼美琪
     */
    public function manmeiqi(){
        return $this->fetch();
    }

    /**
     * 品牌团 达芙妮
     */
    public function dafuni(){
        return $this->fetch();
    }

    /**
     * 品牌团 家电
     */
    public function jiadian(){
        return $this->fetch();
    }

    /**
     * 品牌团 美妆
     */
    public function meizhuang(){
        return $this->fetch();
    }

    /**
     * 品牌团 恬贝儿
     */
    public function tianbeier(){
        return $this->fetch();
    }

    /**
     * 品牌团 生活百货
     */
    public function department(){
        return $this->fetch();
    }

}