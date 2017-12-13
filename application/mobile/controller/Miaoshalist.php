<?php

namespace app\mobile\controller;

use app\home\logic\UsersLogic;
use Think\Db;

class Miaoshalist extends MobileBase
{

    public function index()
    {
        return $this->fetch();
    }

}