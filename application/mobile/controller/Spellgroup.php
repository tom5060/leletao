<?php

namespace app\mobile\controller;

use app\home\logic\UsersLogic;
use think\console\command\make\Model;
use Think\Db;

class Spellgroup extends MobileBase
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

        $list = Db::name('flash_sale')->field('period_time')->where($map)->select();

        if ($isArray)
        {
            return is_array($list) ? $list : [];
        }
        else if ( ! is_array($list) || empty($list))
        {
            $this->ajaxReturn(['code' => 404, 'data' => []]);
        }

        $timeArr = array_column($list, 'period_time');

        $timeArr = array_unique($timeArr);

        sort($timeArr, SORT_ASC);

        if ($isArray)
        {
            return $timeArr;
        }

        $dateArr = array();
        $nowHour = date('H');
        foreach ($timeArr as $h)
        {
            $text      = $h > $nowHour ? '预热中' : '抢购中';//已过期
            $dateArr[] = [
                'time' => $h,
                'text' => $h == $nowHour ? '抢购中' : $text
            ];
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

        $page     = I('page', 1, 'intval');
        $limit    = I('limit', 20, 'intval');
        $hour     = I('hour', 0, 'intval');
        $inBanner = I('banner', '-1', 'intval');
        $morning  = date('Y-m-d') . " 00:00:00";
        $night    = date('Y-m-d') . " 23:59:59";
        $morning  = strtotime($morning);
        $night    = strtotime($night);

        /**
         * 如果没传时间，则检查当前时间是否在抢购时间段里面
         * 不在的话，则取最近的一个时间段
         */
        if ( ! $hour)
        {
            $timeArr = $this->getDateList(true);
            $nowHour = date('H');
            if ( ! in_array($nowHour, $timeArr))
            {
                $timeArr = array_column($timeArr, 'period_time');
                rsort($timeArr);
                foreach ($timeArr as $k => $d)
                {
                    if ($d < $nowHour)
                    {
                        $hour = $d;
                        break;
                    }
                }
            }
        }

        $start  = ($page - 1) * $limit;
        $prefix = C('database.prefix');
        $where  = "start_time >= '{$morning}' AND start_time <= '{$night}' AND period_time = {$hour}";

        if ($inBanner >= 0)
        {
            $where .= " AND banner = {$inBanner}";
        }
        $sql = "SELECT f.*,g.`market_price`,c.`name`,c.`mobile_name` FROM {$prefix}flash_sale f,{$prefix}goods g,{$prefix}goods_category c WHERE f.goods_id=g.goods_id AND g.cat_id=c.id AND {$where} LIMIT {$start},{$limit}";

        $list     = Db::query($sql);
        $countSql = "SELECT count(id) as t FROM {$prefix}flash_sale WHERE {$where}";
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
        $data          = Db::name('flash_sale')->field('id,title,goods_id,period_time,banner,banner_img,FROM_UNIXTIME(start_time,"%Y-%m-%d %H:%i:%h") as start_time')->where($map)->select();
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
        $count     = Db::name('flash_sale')->where($map)->count();
        if ($count > 0)
        {
            Db::name('flash_sale')->where($map)->update(['is_end' => 1]);
        }
    }

}