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
 * 2015-11-21
 */
namespace app\mobile\controller;
use app\home\logic\UsersLogic;
use think\Page;
use think\Verify;
use think\Db;

class Distribut extends MobileBase {
        /*
        * 初始化操作
        */
    public function _initialize() {
        parent::_initialize();
        if(session('?user'))
        {
        	$user = session('user');
        	$this->user = $user;
        	$this->user_id = $user['user_id'];
        	$this->assign('user',$user); //存储用户信息
        }        
        $nologin = array(
        	'login','pop_login','do_login','logout','verify','set_pwd','finished',
        	'verifyHandle','reg','send_sms_reg_code','find_pwd','check_validate_code',
        	'forget_pwd','check_captcha','check_username','send_validate_code',
        );
        if(!$this->user_id && !in_array(ACTION_NAME,$nologin)){
        	header("location:".U('Mobile/User/login'));
        	exit;
        }
        if($user['is_distribut'] == 1){ //是分销商才查找用户店铺信息
            $user_store = Db::name('user_store')->where("user_id", $this->user_id)->find();
            $this->userStore=$user_store;
            $this->assign('store',$user_store);
        }

        $order_count = Db::name('order')->where("user_id", $this->user_id)->count(); // 我的订单数
        $goods_collect_count = Db::name('goods_collect')->where("user_id", $this->user_id)->count(); // 我的商品收藏
        $comment_count = Db::name('comment')->where("user_id", $this->user_id)->count();//  我的评论数
        $coupon_count = Db::name('coupon_list')->where("uid", $this->user_id)->count(); // 我的优惠券数量
        $first_nickname = Db::name('users')->where("user_id", $this->user['first_leader'])->getField('nickname');
        $level_name = Db::name('user_level')->where("level_id", $this->user['level'])->getField('level_name'); // 等级名称
        $this->assign('level_name',$level_name);        
        $this->assign('first_nickname',$first_nickname);        
        $this->assign('order_count',$order_count);
        $this->assign('goods_collect_count',$goods_collect_count);
        $this->assign('comment_count',$comment_count);
        $this->assign('coupon_count',$coupon_count);

    }
  
    /**
     * 分销用户中心首页（分销中心）
     */
    public function index(){
        // 销售额 和 我的奖励
        $result = DB::query("select sum(goods_price) as goods_price, sum(money) as money from __PREFIX__rebate_log where user_id = {$this->user_id}");
        $result = $result[0];
        $result['goods_price'] = $result['goods_price'] ? $result['goods_price'] : 0;
        $result['money'] = $result['money'] ? $result['money'] : 0;        
                
         $lower_count[1] = Db::name('users')->where("first_leader", $this->user_id)->count();
         $lower_count[2] = Db::name('users')->where("second_leader", $this->user_id)->count();
         $lower_count[3] = Db::name('users')->where("third_leader", $this->user_id)->count();

        /**
         * 我的下线 订单数（我的团队）
         */
        $result2 = DB::query("select status,count(1) as c , sum(goods_price) as goods_price from `__PREFIX__rebate_log` where user_id = :user_id group by status",['user_id'=>$this->user_id]);
        $level_order = convert_arr_key($result2, 'status');
        for($i = 0; $i <= 5; $i++)
        {
            $level_order[$i]['c'] = $level_order[$i]['c'] ? $level_order[$i]['c'] : 0;
            $level_order[$i]['goods_price'] = $level_order[$i]['goods_price'] ? $level_order[$i]['goods_price'] : 0;
        }

        $money['withdrawals_money'] = Db::name('withdrawals')->where(['user_id'=>$this->user_id,'status'=>1])->sum('money'); // 已提现财富
        $money['achieve_money'] = Db::name('rebate_log')->where(['user_id'=>$this->user_id,'status'=>3])->sum('money');  //累计获得佣金
        $time=strtotime(date("Y-m-d"));
        $money['today_money'] = Db::name('rebate_log')->where("user_id=$this->user_id and status=3 and create_time>$time")->sum('money');    //今日收入

        $this->assign('level_order',$level_order); // 下线订单        
        $this->assign('lower_count',$lower_count); // 下线人数        
        $this->assign('sales_volume',$result['goods_price']); // 销售额
        $this->assign('reward',$result['money']);// 奖励
        $this->assign('money',$money);
        return $this->fetch();
    }
    
    /**
     * 下线列表(我的团队)
     */
    public function lower_list(){
        $user =$this->user;
        if($user['is_distribut'] != 1)
            $this->error('您还不是分销商');
        $level = I('get.level',1);         
        $q = I('post.q','','trim');
        $condition = array(1=>'first_leader',2=>'second_leader',3=>'third_leader');

        $where = "{$condition[$level]} = {$this->user_id}";
        $bind = array();
        if($q){
            $where .= " and (nickname like :q1 or user_id = :q2 or mobile = :q3)";
            $bind['q1'] = "%$q%";
            $bind['q2'] = $q;
            $bind['q3'] = $q;
        }

        $count = Db::name('users')->where($where)->bind($bind)->count();
        $page = new Page($count,C('PAGESIZE'));
        $lists = Db::name('users')->where($where)->bind($bind)->limit("{$page->firstRow},{$page->listRows}")->order('user_id desc')->select();
        
        $this->assign('count', $count);// 总人数
        $this->assign('page', $page->show());// 赋值分页输出
        $this->assign('lists',$lists); // 下线
        if($_GET['is_ajax'])
        {
            return $this->fetch('ajax_lower_list');
            exit;
        }                
        return $this->fetch();
    }    
    
    /**
     * 下线订单列表（分销订单）
     */
    public function order_list(){
       $status = I('get.status',0);
       $where = ['user_id'=>$this->user_id,'status'=>['in',$status]];
       $count = M('rebate_log')->where($where)->count();
       $page = new Page($count,C('PAGESIZE'));
       $list = M('rebate_log')->where($where)->order('id desc')->limit("{$page->firstRow},{$page->listRows}")->select();
       $user_id_list = get_arr_column($list, 'buy_user_id');
       if(!empty($user_id_list))
           $userList = M('users')->where("user_id", "in", implode(',', $user_id_list))->getField('user_id,nickname,mobile,head_pic');

       $this->assign('count', $count);// 总人数
       $this->assign('page', $page->show());// 赋值分页输出
       $this->assign('userList',$userList); //
       $this->assign('list',$list); // 下线
       if($_GET['is_ajax'])
       {
           return $this->fetch('ajax_order_list');
           exit;
       }
       return $this->fetch();

/*lxl 2017-4-6 ****************************************/
        $user =$this->user;
        if($user['is_distribut'] != 1)
            $this->error('您还不是分销商');
        $model = M("rebate_log");
        $status = I('get.status',0);
        $where = ['user_id'=>$this->user_id,'status'=>['in',$status]];
        $count = $model->where($where)->count();
        $Page  = new Page($count,C('PAGESIZE'));
        $list = $model->where($where)->order("`id` desc")->limit($Page->firstRow.','.$Page->listRows)->select();
        /*获取订单商品*/
        $model = new UsersLogic();
        foreach ($list as $k => $v) {
            $data = $model->get_order_goods($v['order_id']);
            $list[$k]['goods_list'] = $data['result'];
        }
        $show  = $Page->show();
        $this->assign('show',$show);
        $this->assign('list',$list);
        return $this->fetch();
    } 


    /**
     * 验证码验证
     * $id 验证码标示
     */
    private function verifyHandle($id)
    {
        $verify = new Verify();
        if (!$verify->check(I('post.verify_code'), $id ? $id : 'user_login')) {
            $this->error("验证码错误");
        }
    }

    /**
     * 验证码获取
     */
    public function verify()
    {
        //验证码类型
        $type = I('get.type') ? I('get.type') : 'user_login';
        $config = array(
            'fontSize' => 40,
            'length' => 4,
            'useCurve' => true,
            'useNoise' => false,
        );
        $Verify = new Verify($config);
        $Verify->entry($type);
    }    
    
    /**
     * 个人推广二维码 （我的名片）
     */
    public function qr_code(){
        $ShareLink = urlencode("http://{$_SERVER[HTTP_HOST]}/index.php?m=Mobile&c=Index&a=index&first_leader={$this->user_id}"); //默认分享链接
        if($this->user['is_distribut'] == 1)
            $this->assign('ShareLink',$ShareLink);
        return $this->fetch();
    }

    /**
     * 个人推广链接
     */
    public function my_link(){
        $link = "http://{$_SERVER[HTTP_HOST]}/index.php?m=Mobile&c=Index&a=index&first_leader={$this->user_id}";
        // $this->link=$link;
        if($this->user['is_distribut'] == 1)
        $this->assign('link', $link);
        return $this->fetch();
    }

    /**
     * 平台分销商品列表
     * $author  lxl
     * $time 2017-4-6
     */
    public function goods_list(){
        $user =$this->user;
        if($user['is_distribut'] != 1)
            $this->error('您还不是分销商');
        $GoodsLogic = new \app\admin\logic\GoodsLogic();
        $brandList = $GoodsLogic->getSortBrands();
        $categoryList = $GoodsLogic->getSortCategory();
        $ids1 = array_multi2single(Db::name('user_distribution')->field('goods_id')->where("user_id",$this->user_id)->select());    //查找用户已添加的商品ID
        $ids= implode(',',$ids1)=='' ? 0:implode(',',$ids1);  //解决没添加时会报错
        $sort = I('sort','goods_id'); // 排序
        $sort_asc = I('sort_asc','asc'); // 排序
        $where = ' commission > 0 ';
        $cat_id = I('cat_id/d');
        $bind = array();
        if($cat_id > 0)
        {
            $grandson_ids = getCatGrandson($cat_id);
            $where .= " and cat_id in(".  implode(',', $grandson_ids).") "; // 初始化搜索条件
        }
        $key_word = I('key_word') ? trim(I('key_word')) : '';
        if($key_word)   //搜索
        {
            $where = "$where and (goods_name like :key_word1 or goods_sn like :key_word2)" ;
            $bind['key_word1'] = "%$key_word%";
            $bind['key_word2'] = "%$key_word%";
        }
        $brand_id = I('brand_id');      //品牌
        if($brand_id){
            $where = "$where and brand_id = :brand_id";
            $bind['brand_id'] = $brand_id;
        }
        $model = Db::name('Goods');
        $count = $model->where($where)->bind($bind)->count();
        $Page  = new Page($count,C('PAGESIZE'));
        $show = $Page->show();
        $goodsList = $model->field('goods_name,goods_id,commission,shop_price,cat_id,brand_id')->where("$where and goods_id not in($ids)")->order("$sort $sort_asc")->limit($Page->firstRow.','.$Page->listRows)->cache(true)->select();
        $this->assign('categoryList',$categoryList);    //品牌
        $this->assign('brandList',$brandList);  //分类
        $this->assign('goodsList',$goodsList);
        $this->assign('page',$show);
        $this->assign('pager',$Page);
        if(I('is_ajax')){
            return $this->fetch('ajax_goods_list');
        }
        return $this->fetch();
    }

    /**
     * 添加分销商品
     * $author  lxl
     * $time 2017-4-6
     */
    public function add_goods(){
        $user =$this->user;
        if($this->user_id == 0){  //判断登录是否有效
            $this->redirect('Mobile/User/index');
        }
        $data=I('post.');
        foreach($data as $k=>$v){
            $data[$k]['user_id']= $this->user_id;
            $data[$k]['user_name']= $user['nickname'] ;
            $data[$k]['addtime']= time();
        }
        $result=Db::name('user_distribution')->insertAll($data); //添加
        if($result){
            $this->success('成功',U('Mobile/Distribut/goods_list'));
        }else{
            $this->error('失败');
        }
    }

    /**
     * 店铺设置
     * $author  lxl
     * $time 2017-4-6
     */
    public function set_store(){
        $user =$this->user;
        if($user['is_distribut'] != 1)
            $this->error('您还不是分销商');
        if(IS_POST){

            // 上传图片
            if (!empty($_FILES['store_img']['tmp_name'])) {
                $files = request()->file('store_img');
                $save_url = 'public/upload/user_tore';
                // 移动到框架应用根目录/public/uploads/ 目录下
                $info = $files->rule('uniqid')->validate(['size' => 1024 * 1024 * 3, 'ext' => 'jpg,png,gif,jpeg'])->move($save_url);
                if ($info) {
                    // 成功上传后 获取上传信息
                    $return_imgs[] = '/'.$save_url . '/' . $info->getFilename();
                } else {
                    // 上传失败获取错误信息
                    $this->error($files->getError());
                }
            }
                if (!empty($return_imgs)) {
                    $data['store_img'] = implode(',', $return_imgs);
                }
                $data['store_name'] = I('store_name','');
                $data['true_name'] = I('true_name','');
                $data['mobile'] = I('mobile','');
                $data['qq'] = I('qq/d');
                if($this->userStore == null){ //添加
                    $data['user_id'] = $this->user_id;
                    $addres = Db::name('user_store')->add($data);
                    if($addres)
                        $this->success('添加店铺信息成功');
                        $this->error('添加店铺信息失败');
                }else{ //修改
                    $upres = Db::name('user_store')->where("user_id=$this->user_id")->update($data);
                    if($upres)
                        $this->success('修改店铺信息成功');
                    $this->error('修改店铺信息失败');
                }
            }
        return $this->fetch();
    }

    /**
     * 用户分销商品
     * $author  lxl
     * $time 2017-4-6
     */
    public function my_store(){
        $user =$this->user;
        if($user['is_distribut'] != 1)
            $this->error('您还不是分销商');
        $count = Db::name('user_distribution')->where("user_id = $this->user_id")->count();
        $Page  = new Page($count,C('PAGESIZE'));
        $lists = Db::name('user_distribution')->where("user_id = $this->user_id")->limit($Page->firstRow.','.$Page->listRows)->select(); //用户分销商品

        $countWhere = ' is_on_sale =1 and commission > 0 '; //公共统计条件
        $goods = Db::name('goods')->where($countWhere)->count('goods_id'); //全部
        $promotion = Db::name('goods')->where("prom_type=1 and $countWhere")->count();  //促销
        $new = Db::name('goods')->where("is_new=1 and $countWhere")->count();  //新品

        $show  = $Page->show();
        $this->assign('show',$show);
        $this->assign('lists', $lists);
        $this->assign('goods',$goods);
        $this->assign('promotion',$promotion);
        $this->assign('new',$new);
        if(I('is_ajax')){
            return $this->fetch('ajax_my_store');
        }
        return $this->fetch();
    }


    /**
     * 新手必看
     * $author  lxl
     * $time 2017-4-6
     */
    public function must_see(){
        return $this->fetch();
    }

    /**
     *分销排行
     * $author  lxl
     * $time 2017-4-6
     */
    public function rankings(){
        $user = $this->user;
        $sort= I('sort','distribut_money');
//        $count = Db::name('users')->where("is_distribut = 1")->count(); //以获得佣金排行
        $Page = new Page(200,C('PAGESIZE'));  //考虑用户不会看那么下去，不找那么多了
        $show = $Page->show();
        $lists = Db::name('users')->where("is_distribut = 1")->order("$sort desc")->limit($Page->firstRow.','.$Page->listRows)->cache(true)->select(); //以获得佣金排行
        if($sort=='distribut_money'){  //用户佣金排行条件
            $distribut_money = $user['distribut_money'] ;
            $placewhere = " distribut_money > $distribut_money ";
        }else{
            $underling_number = $user['underling_number'] ;
            $placewhere = " underling_number > $underling_number ";
        }

        $place = Db::name('users')->where($placewhere)->count('user_id'); //以获得佣金排行
        $this->assign('lists',$lists);
        $this->assign('page',$show);
        $this->assign('firsRrow',$Page->firstRow);  //当前分页开始数
        $this->assign('place',$place+1);  //当前分页开始数
        if(I('is_ajax')){
            return $this->fetch('ajax_rankings');
        }
        return $this->fetch();
    }

    /**
     * 分成记录
     * $author  lxl
     * $time 2017-4-6
     */
    public function rebate_log(){
        $user =$this->user;
        if($user['is_distribut'] != 1)
            $this->error('您还不是分销商');
        $status = I('status',''); //日志状态
        $sort_asc = I('sort_asc','desc');  //排序
        $sort  = I('sort','create_time'); //排序条件
        $where = "user_id =175 ";
        if($status!=''){
            $where .= " and status=$status ";
        }
        $count = Db::name('rebate_log')->where($where)->count(); //统计符合条件的数量
        $Page = new Page($count,C('PAGESIZE'));
        $lists = Db::name('rebate_log')->where($where)->order("$sort  $sort_asc")->limit($Page->firstRow.','.$Page->listRows)->cache(true)->select(); //查询日志
        $this->assign('lists',$lists);
        if(I('is_ajax')){
            return $this->fetch('ajax_rebate_log');
        }
        return $this->fetch();
    }
}