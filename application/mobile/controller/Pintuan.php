<?php

/**
 * 拼团
 *
 * 新增字段：
 * ALTER TABLE `lt_pintuan_infos` ADD COLUMN `m_orderid` varchar(50) NULL DEFAULT '' COMMENT '建团用户订单号' AFTER `sign`, ADD COLUMN `s_orderid` varchar(50) NULL DEFAULT '' COMMENT '拼团用户订单号' AFTER `m_orderid`;
 */

namespace app\mobile\controller;


use Think\Db;

class Pintuan extends MobileBase
{
    public $cartLogic;
    public $user_id = 0;
    public $user = array();

    /**
     * 析构流函数
     */
    public function __construct()
    {
        parent::__construct();
        $this->cartLogic = new \app\home\logic\CartLogic();
        if (session('?user'))
        {
            $user = session('user');
            $user = M('users')->where("user_id", $user['user_id'])->find();
            session('user', $user);  //覆盖session 中的 user
            $this->user    = $user;
            $this->user_id = $user['user_id'];
            $this->assign('user', $user); //存储用户信息
        }
    }

    public function index()
    {
        return $this->fetch();
    }

    public function getGoods()
    {

        //检查商品,过期了则更改过期状态
        self::validGoodsExpire();

        $page     = I('page', 1, 'intval');
        $limit    = I('limit', 20, 'intval');
        $inBanner = I('banner', '-1', 'intval');
        $expire   = time();

        $start  = ($page - 1) * $limit;
        $prefix = C('database.prefix');
        $where  = "end_time >= {$expire}";

        if ($inBanner >= 0)
        {
            $where .= " AND banner = {$inBanner}";
        }
        $sql = "SELECT f.*,g.`market_price`,c.`name`,c.`mobile_name` FROM {$prefix}pintuan_sale f,{$prefix}goods g,{$prefix}goods_category c WHERE f.goods_id=g.goods_id AND g.cat_id=c.id AND {$where} LIMIT {$start},{$limit}";

        $list     = Db::query($sql);
        $countSql = "SELECT count(id) as t FROM {$prefix}pintuan_sale WHERE {$where}";
        $count    = Db::query($countSql);
        foreach ($list as &$item)
        {
            $item['thumb'] = goods_thum_images($item['goods_id'], 400, 400);
            $groupInfos    = D('pintuan_infos')->where([
                    'goods_id' => $item['goods_id'],
                    'status'   => 1
                ]
            )->limit(2)->order('start_time ASC')->select();

            //获取拼团用户信息
            if ($groupInfos)
            {
                $uids  = array_column($groupInfos, 'm_uid');
                $users = Db::name('users')->where([
                        'user_id' => ['in', $uids]
                    ]
                )->field('user_id,nickname,head_pic')->select();

                $userList = [];
                foreach ($users as $user)
                {
                    $userList[$user['user_id']] = $user;
                    unset($user);
                }
                unset($users);
                foreach ($groupInfos as $uItem)
                {
                    $user             = isset($userList[$uItem['m_uid']]) ? $userList[$uItem['m_uid']] : [];
                    $item['groups'][] = [
                        'id'         => $uItem['id'],
                        'm_uid'      => $uItem['m_uid'],
                        'm_avatar'   => isset($user['head_pic']) ? $user['head_pic'] : '',
                        'm_nickname' => isset($user['nickname']) ? $user['nickname'] : ''
                    ];
                }
            }

            //$oMap = "goods_id = {$item['goods_id']} and status = 1";
            $oMap = [
                "goods_id" => $item['goods_id'],
                'status' => 1
            ];
            $item['order_num'] = D('goods')->where("goods_id = ".$item['goods_id'])->getField('sales_sum');
            unset($oMap);
        }

        $response = [
            'code'         => 200,
            'total'        => isset($count[0]['t']) ? $count[0]['t'] : 0,
            'per_page'     => 20,
            'current_page' => $page,
            'data'         => $list
        ];

        $this->ajaxReturn($response);
    }

    public function getSlider()
    {
        $map['banner'] = ['eq', 1];
        $data          = Db::name('pintuan_sale')->field('id,title,goods_id,period_time,banner,banner_img,FROM_UNIXTIME(start_time,"%Y-%m-%d %H:%i:%h") as start_time')->where($map)->limit(10)->select();
        $response      = [
            'code' => 200,
            'data' => $data
        ];

        $this->ajaxReturn($response);
    }

    /**
     * 如果商品已经过期则吧商品标注为过期状态
     */
    private static function validGoodsExpire()
    {
        $today     = date('Y-m-d') . " 00:00:00";
        $timestamp = strtotime($today);
        $map       = [
            'start_time' => ['lt', $timestamp]
        ];
        $count     = Db::name('pintuan_sale')->where($map)->count();
        if ($count > 0)
        {
            Db::name('pintuan_sale')->where($map)->update(['is_end' => 1]);
        }
    }

    public function goods()
    {
        return $this->fetch();
    }

    public function getGroups()
    {
        $goods_id = I("get.goods_id/d");
        if (empty($goods_id))
        {
            $this->ajaxReturn(['code' => 404, 'msg' => '商品ID不能为空']);
        }
        $infos = Db::name('pintuan_infos')->where(['goods_id' => $goods_id,'status' => 0])->paginate(10)->toArray();

        //获取用户信息
        $uids = [];
        if (isset($infos['data']) && ! empty($infos['data']))
        {
            $uids = array_column($infos['data'], 'm_uid');
        }
        $users    = Db::name('users')->where([
                'user_id' => [
                    'in',
                    $uids
                ]
            ]
        )->field('user_id,nickname,head_pic')->select();
        $userList = [];
        foreach ($users as $user)
        {
            $userList[$user['user_id']] = $user;
            unset($user);
        }
        unset($users);

        foreach ($infos['data'] as &$info)
        {
            $user               = isset($userList[$info['m_uid']]) ? $userList[$info['m_uid']] : [];
            $info['m_avatar']   = isset($user['head_pic']) && ! empty($user['head_pic']) ? $user['head_pic'] : '/template/mobile/new/static/images/user68.jpg';
            $info['m_nickname'] = isset($user['nickname']) ? $user['nickname'] : '';
            unset($user);
        }

        $this->ajaxReturn($infos);
    }

    public function getAddress()
    {
        if ($this->user_id == 0)
        {
            $this->ajaxReturn(array('status' => -999, 'msg' => '请先登陆', 'result' => U('Mobile/User/login')));
        }
        $list      = D('user_address')->where(['user_id' => $this->user_id])->select();
        $idArr     = [];
        $addrField = ['province', 'city', 'district', 'twon'];
        foreach ($list as $addr)
        {
            foreach ($addrField as $k)
            {
                if (isset($addr[$k]))
                {
                    $idArr[] = $addr[$k];
                }
            }
        }
        $regionArr = [];
        if ( ! empty($idArr))
        {
            $region = D('region')->where(['id' => ['in', $idArr]])->select();
            foreach ($region as $rg)
            {
                $regionArr[$rg['id']] = $rg['name'];
            }
        }

        foreach ($list as &$item)
        {
            foreach ($addrField as $k)
            {
                $item[$k] = $regionArr[$item[$k]];
            }
        }

        $response = [
            'code' => 200,
            'data' => $list
        ];

        $this->ajaxReturn($response);
    }

    public function confirm()
    {
        $goods_id = I("get.goods_id/d");
        if ($this->user_id == 0)
        {
            $this->error('商品ID不能为空', U('Mobile/User/login'));
        }
        $goods               = D('goods')->where(['goods_id' => $goods_id])->find();
        $tuanGoods           = D('pintuan_sale')->where(['goods_id' => $goods_id])->find();
        $goods['shop_price'] = $tuanGoods['price'];

        //支付方式
        if (strstr($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger'))
        {
            //微信浏览器
            $payment_where['code'] = array('in', array('weixin', 'cod'));
        }
        else
        {
            $payment_where['scene'] = array('in', array('0', '1'));
        }
        $payment_where['status'] = 1;

        $paymentList = M('Plugin')->where($payment_where)->select();
        $paymentList = convert_arr_key($paymentList, 'code');

        $bankCodeList = [];
        foreach ($paymentList as $key => $val)
        {
            $val['config_value'] = unserialize($val['config_value']);
            if ($val['config_value']['is_bank'] == 2)
            {
                $bankCodeList[$val['code']] = unserialize($val['bank_code']);
            }
            //判断当前浏览器显示支付方式
            if (($key == 'weixin' && ! is_weixin()) || ($key == 'alipayMobile' && is_weixin()))
            {
                unset($paymentList[$key]);
            }
        }

        $this->assign('paymentList', $paymentList);
        $this->assign('goods', $goods);
        return $this->fetch();
    }

    public function addGroups()
    {
        if ($this->user_id == 0)
        {
            $this->ajaxReturn(array('status' => -999, 'msg' => '请先登陆', 'result' => U('Mobile/User/login')));
        }
        $act           = I("act"); //  拼团行为，建团参数是：add ,拼团参数是：join
        $address_id    = I("address_id/d"); //  收货地址id
        $buy_num       = I("buy_num/d", 1); //  购买数量
        $t_id          = I("t_id/d", 0); //  拼团ID
        $shipping_code = I("shipping_code", 'shentong'); //  物流编号
        $invoice_title = I('invoice_title', ''); // 发票
        $user_note     = trim(I('user_note'));   //买家留言
        $goods_id      = I('goods_id/d');   //商品ID

        //参数检查
        if ( ! $address_id)
        {
            $this->ajaxReturn(array('status' => -3, 'msg' => '请先填写收货人信息', 'result' => null));
        }

        if ( ! $shipping_code)
        {
            $this->ajaxReturn(array('status' => -4, 'msg' => '请选择物流信息', 'result' => null));
        }
        if ( ! in_array($act, ['add', 'join']) || $act == 'join' && $t_id <= 0)
        {
            $this->ajaxReturn(array('status' => -5, 'msg' => '参数值不正确,请检查参数', 'result' => null));
        }

        //拼团检查
        if ($act == 'join')
        {
            $gMap      = [
                'id'       => $t_id,
                "goods_id" => $goods_id,
                "status"   => 0,
            ];
            $tuanCount = M('pintuan_infos')->where($gMap)->count();
            if ( ! $tuanCount)
            {
                $this->ajaxReturn(array('status' => -5, 'msg' => '拼团失败，找不到指定的团', 'result' => null));
            }

            //不允许自己建团自己拼团
            $tuanInfo = M('pintuan_infos')->where($gMap)->find();
            if ($tuanInfo['uid'] == $this->user_id)
            {
                $this->ajaxReturn(array('status' => -6, 'msg' => '很抱歉，不允许拼团自己建的团。', 'result' => null));
            }
        }

        //$address      = M('UserAddress')->where("address_id", $address_id)->find();
        $tuanGoods    = D('pintuan_sale')->where(['goods_id' => $goods_id])->find();
        $goods_price  = $tuanGoods['price'];
        $total_amount = $goods_price * $buy_num;
        $result       = array(
            'total_amount'   => $total_amount, // 商品总价
            'order_amount'   => $total_amount, // 应付金额
            'shipping_price' => 0, // 物流费
            'goods_price'    => $goods_price, // 商品总价
            'cut_fee'        => 0, // 共节约多少钱
            'anum'           => $buy_num, // 商品总共数量
            'integral_money' => 0,  // 积分抵消金额
            'user_money'     => 0, // 使用余额
            'coupon_price'   => 0,// 优惠券抵消金额
            'order_goods'    => [], // 商品列表 多加几个字段原样返回
        );

        //价格计算失败
        if ($result['status'] < 0)
        {
            $this->ajaxReturn($result);
        }
        // 订单满额优惠活动
        $order_prom                  = get_order_promotion($result['order_amount']);
        $result['order_amount']      = $order_prom['order_amount'];
        $result['order_prom_id']     = $order_prom['order_prom_id'];
        $result['order_prom_amount'] = $order_prom['order_prom_amount'];

        $car_price = array(
            'postFee'           => $result['shipping_price'], // 物流费
            'couponFee'         => $result['coupon_price'], // 优惠券
            'balance'           => $result['user_money'], // 使用用户余额
            'pointsFee'         => $result['integral_money'], // 积分支付
            'payables'          => $result['order_amount'], // 应付金额
            'goodsFee'          => $result['goods_price'],// 商品价格
            'order_prom_id'     => $result['order_prom_id'], // 订单优惠活动id
            'order_prom_amount' => $result['order_prom_amount'], // 订单优惠活动优惠了多少钱
        );
        unset($result);

        // 添加订单
        $result   = $this->cartLogic->addTuanOrder($goods_id, $total_amount, $buy_num, $this->user_id, $address_id, $shipping_code, $invoice_title, 0, $car_price, $user_note, 1);
        $order_id = $result['result'];
        //订单生成失败
        if ($result['status'] < 0)
        {
            $this->ajaxReturn($result);
        }

        //写入拼团信息
        if ($act == 'add')
        {
            $data = [
                'goods_id'   => $goods_id,
                'm_uid'      => $this->user_id,
                'm_orderid'  => $order_id,
                'start_time' => time(),
                'end_time'   => strtotime("+1 day"),
                'status'     => 0,
            ];
            Db::name('pintuan_infos')->insert($data);
        }
        else if ($act == 'join')
        {
            $data = [
                's_uid'     => $this->user_id,
                's_orderid' => $order_id,
                'status'    => 1
            ];
            Db::name('pintuan_infos')->where(['id' => $t_id])->update($data);
        }

        $this->ajaxReturn($result);
    }

    public function homeTips()
    {
        $model      = M('pintuan_infos');
        $tuan_count = $model->count();
        $tuanList   = $model->where(['status' => 0])->order('id DESC')->limit(2)->select();

        //获取用户信息
        $uids     = [];
        $users    = [];
        $userList = [];
        if ( ! empty($tuanList))
        {
            $uids  = array_column($tuanList, 'm_uid');
            $users = Db::name('users')->where([
                'user_id' => [
                    'in',
                    $uids
                ]
            ]
            )->field('user_id,nickname,head_pic')->select();
        }

        foreach ($users as $user)
        {
            $userList[$user['user_id']] = $user;
            unset($user);
        }
        unset($users);

        foreach ($tuanList as &$info)
        {
            $user               = isset($userList[$info['m_uid']]) ? $userList[$info['m_uid']] : [];
            $info['m_avatar']   = isset($user['head_pic']) && ! empty($user['head_pic']) ? $user['head_pic'] : '/template/mobile/new/static/images/user68.jpg';
            $info['m_nickname'] = isset($user['nickname']) ? $user['nickname'] : '';
            unset($user);
        }

        $data = [
            'tuan_count' => $tuan_count,
            'user'       => $tuanList
        ];
        $this->ajaxReturn($data);
    }

}
