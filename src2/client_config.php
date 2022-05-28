<?php

//客户端配置
return [
    //驱动方式
    'type'               => 'workerman',
    //服务端连接池
    'rpc_server_address' => [
        'tcp://127.0.0.1:2015'
    ],
    //重连次数
    'reconnect_count'    => 1
];
