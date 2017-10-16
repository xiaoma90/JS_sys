<?php


return [
    'url_route_on' => true,
    'trace' => [
        'type' => 'html', // 支持 socket trace file
    ],
    //各模块公用配置
    'extra_config_list' => ['database', 'route', 'validate'],
    //临时关闭日志写入
    'log' => [
        'type' => 'test',
    ],

    'app_debug' => true,
    'default_filter' => ['strip_tags', 'htmlspecialchars'],

    // +----------------------------------------------------------------------
    // | 缓存设置
    // +----------------------------------------------------------------------
    'cache' => [
        // 驱动方式
        'type' => 'file',
        // 缓存保存目录
        'path' => CACHE_PATH,
        // 缓存前缀
        'prefix' => '',
        // 缓存有效期 0表示永久缓存
        'expire' => 0,
        'host' => '192.168.6.55',
        'port' => 11211,
    ],

    //加密串
    'salt' => 'wZPb~yxvA!ir38&Z',

    //备份数据地址
    'back_path' => APP_PATH .'../back/',

    'account_type' => [
        '1' => '购买项目',
        '2' => '充值',
    ],
    'auth_status' => [
        '0' => '未认证',
        '1' => '待审核',
        '2' => '未通过',
        '3' => '已通过'
    ],

];