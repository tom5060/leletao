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
 * Author: 当燃
 * 专题管理
 * Date: 2016-03-09
 */

namespace app\admin\controller;

use think\AjaxPage;
use think\Db;
use think\Page;
use app\admin\logic\GoodsLogic;

class Seckill extends Base
{

    //限时抢购
    public function seckill_sale()
    {
        $condition = array();
        $model     = M('seckill_sale');
        $count     = $model->where($condition)->count();
        $Page      = new Page($count, 10);
        $show      = $Page->show();
        $prom_list = $model->where($condition)->order("sort desc")->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('prom_list', $prom_list);
        $this->assign('page', $show);// 赋值分页输出
        $this->assign('pager', $Page);
        return $this->fetch();
    }

    public function seckill_sale_info()
    {
        if (IS_POST)
        {
            $data               = I('post.');
            $data['start_time'] = strtotime($data['start_time']);
            $data['end_time']   = strtotime($data['end_time']);
            if (empty($data['id']))
            {
                $r = M('seckill_sale')->add($data);
                M('goods')->where("goods_id=" . $data['goods_id'])->save(array('seckill_id' => $r));
                adminLog("管理员添加抢购活动 " . $data['name']);
            }
            else
            {
                $r = M('seckill_sale')->where("id=" . $data['id'])->save($data);
                M('goods')->where("prom_id=" . $data['id'])->save(array(
                        'seckill_id'   => 0
                    )
                );
                M('goods')->where("goods_id=" . $data['goods_id'])->save(array(
                        'seckill_id'   => $data['id']
                    )
                );
            }
            if ($r)
            {
                $this->success('编辑抢购活动成功', U('Seckill/seckill_sale'));
                exit;
            }
            else
            {
                $this->error('编辑抢购活动失败', U('Seckill/seckill_sale'));
            }
        }
        $id                 = I('id');
        $info['start_time'] = date('Y-m-d H:i:s');
        $info['end_time']   = date('Y-m-d 23:59:59', time() + 3600 * 24 * 60);
        if ($id > 0)
        {
            $info               = M('seckill_sale')->where("id=$id")->find();
            $info['start_time'] = date('Y-m-d', $info['start_time']);
            $info['end_time']   = date('Y-m-d', $info['end_time']);
        }
        $this->assign('info', $info);
        $this->assign('min_date', date('Y-m-d'));
        return $this->fetch();
    }

    public function seckill_del()
    {
        $id = I('del_id');
        if ($id)
        {
            M('seckill_sale')->where("id=$id")->delete();
            M('goods')->where("prom_type=1 and prom_id=$id")->save(array('prom_id' => 0, 'prom_type' => 0));
            M('cart')->where("prom_type=1 and prom_id=$id")->save(array('prom_id' => 0, 'prom_type' => 0));
            exit(json_encode(1));
        }
        else
        {
            exit(json_encode(0));
        }
    }


}