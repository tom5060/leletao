<?php

namespace app\mobile\controller;

use app\home\logic\UsersLogic;
use Think\Db;

class Qianggoulist extends MobileBase
{

    public function index()
    {
        return $this->fetch();
    }

}