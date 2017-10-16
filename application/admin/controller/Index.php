<?php

namespace app\admin\controller;
use app\admin\model\NodeModel;

class Index extends Base
{
    public function index()
    {
        // 获取权限菜单
        $node = new NodeModel();
        $this->assign([
            'menu' => $node->getMenu(session('rule'))
        ]);

        return $this->fetch('/index');
    }

    /**
     * 后台默认首页
     * @return mixed
     */
    public function indexPage()
    {
        return $this->fetch('index');
    }
}
