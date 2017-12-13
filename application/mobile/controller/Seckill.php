<?php

namespace app\mobile\controller;

use app\home\logic\UsersLogic;
use Think\Db;

class Seckill extends MobileBase
{

    public function index()
    {
        return $this->fetch();
    }

    public function getDateList($isArray = false)
    {
        $morning = date('Y-m-d') . "00:00:00";
        $night   = date('Y-m-d') . "23:59:59";
        $map     = [
            'start_time' => [
                ['egt', strtotime($morning)],
                ['elt', strtotime($night)]
            ]
        ];

        $list = Db::name('seckill_sale')->field('start_time,period_time')->order('period_time ASC')->group('period_time')->where($map)->select();

        if ($isArray)
        {
            return is_array($list) ? $list : [];
        }
        else if ( ! is_array($list) || empty($list))
        {
            $this->ajaxReturn(['code' => 404, 'data' => []]);
        }

        if ($isArray)
        {
            $timeArr = array_column($list, 'period_time');
            return $timeArr;
        }

        $dateArr = array();
        $nowHour = date('H');
        foreach ($list as $item)
        {
            $h         = $item['period_time'];
            $text      = $h > $nowHour ? '预热中' : '抢购中';//已过期
            $dateArr[] = [
                'time' => $h,
                'date' => date('Y-m-d', $item['start_time']),
                'text' => $h == $nowHour ? '抢购中' : $text
            ];
            unset($item, $h);
        }

        //明天预告
        $tomorrow     = date('Y-m-d', strtotime('+ 1 day'));
        $tMap         = [
            'start_time' => [
                ['egt', strtotime($tomorrow . ' 00:00:00')],
                ['elt', strtotime($tomorrow . ' 23:59:59')]
            ]
        ];
        $tomorrowList = Db::name('seckill_sale')->field('start_time,period_time')->where($tMap)->order('period_time ASC')->group('period_time')->limit(3)->select();
        foreach ($tomorrowList as $item)
        {
            $dateArr[] = [
                'time' => $item['period_time'],
                'date' => date('Y-m-d', $item['start_time']),
                'text' => '明天开始'
            ];
            unset($item);
        }

        $response = [
            'code' => 200,
            'data' => $dateArr
        ];

        $this->ajaxReturn($response);
    }


    public function getGoods()
    {

        //检查商品,过期了则更改过期状态
        self::validGoodsExpire();

        $nowHour  = date('H');
        $today    = date('Y-m-d');
        $date     = I('date', $today);
        $page     = I('page', 1, 'intval');
        $limit    = I('limit', 20, 'intval');
        $hour     = I('hour', $nowHour, 'intval');
        $inBanner = I('banner', '-1', 'intval');
        $morning  = $date . " 00:00:00";
        $night    = $date . " 23:59:59";
        $morning  = strtotime($morning);
        $night    = strtotime($night);

        $start  = ($page - 1) * $limit;
        $prefix = C('database.prefix');
        $where  = "start_time >= '{$morning}' AND start_time <= '{$night}' AND period_time = {$hour}";

        if ($inBanner >= 0)
        {
            $where .= " AND banner = {$inBanner}";
        }
        $sql = "SELECT f.*,g.`market_price`,g.`shop_price`,g.`sales_sum`,c.`name`,c.`mobile_name` FROM {$prefix}seckill_sale f,{$prefix}goods g,{$prefix}goods_category c WHERE f.goods_id=g.goods_id AND g.cat_id=c.id AND {$where} LIMIT {$start},{$limit}";

        $list     = Db::query($sql);
        $countSql = "SELECT count(id) as t FROM {$prefix}seckill_sale WHERE {$where}";
        $count    = Db::query($countSql);

        foreach ($list as &$item)
        {
            $item['thumb'] = goods_thum_images($item['goods_id'], 400, 400);
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
        $data          = Db::name('seckill_sale')->field('id,title,goods_id,period_time,banner,banner_img,FROM_UNIXTIME(start_time,"%Y-%m-%d %H:%i:%h") as start_time')->where($map)->limit(10)->select();
        $response      = [
            'code' => 200,
            'data' => $data
        ];

        $this->ajaxReturn($response);
    }

    public function tomorrow()
    {
        $tomorrow = strtotime("+1 day");

        $page  = I('page', 1, 'intval');
        $limit = I('limit', 20, 'intval');

        $morning = date('Y-m-d', $tomorrow) . " 00:00:00";
        $night   = date('Y-m-d', $tomorrow) . " 23:59:59";
        $morning = strtotime($morning);
        $night   = strtotime($night);

        $start  = ($page - 1) * $limit;
        $prefix = C('database.prefix');
        $where  = "start_time >= '{$morning}' AND start_time <= '{$night}'";

        $sql = "SELECT f.*,g.`market_price`,g.`shop_price`,c.`name`,c.`mobile_name` FROM {$prefix}seckill_sale f,{$prefix}goods g,{$prefix}goods_category c WHERE f.goods_id=g.goods_id AND g.cat_id=c.id AND {$where} LIMIT {$start},{$limit}";

        $list     = Db::query($sql);
        $countSql = "SELECT count(id) as t FROM {$prefix}seckill_sale WHERE {$where}";
        $count    = Db::query($countSql);

        foreach ($list as &$item)
        {
            $item['thumb'] = goods_thum_images($item['goods_id'], 400, 400);
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
        $count     = Db::name('seckill_sale')->where($map)->count();
        if ($count > 0)
        {
            Db::name('seckill_sale')->where($map)->update(['is_end' => 1]);
        }
    }


}
