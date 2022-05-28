<?php

return [
    //服务端配置
    'rpc_server' => [
        //worker进程数
        'processes'         => 1,
        //通信协议
        'protocol'          => '\streetlamp\rpcServer\JsonNL',
        //地址
        'host'              => '0.0.0.0',
        //端口
        'port'              => 2015,
        'socket'            => '',
        //统计数据的协议地址
        'statistic_address' => 'udp://127.0.0.1:9200',
        'worker_name'       => 'vitec',
        'log_file'          => RUNTIME_PATH . 'workerman/log.log',
        //服务
        'service'           => [
            'User' => \app\test\logic\User::class
        ]
    ]
];
