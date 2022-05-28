# 简介

think-workerman-jsonrpc是基于workerman-json-rpc二次封装成一个Package，里面封装了客户端，服务端和统计分析服务

# 安装

## 通过 composer

```
composer require streetlamp/think-workerman-jsonrpc
```

# 服务端使用说明

需要在linux环境下启动

## 服务

在项目根目录新建server.php启动文件

```
#!/usr/bin/env php
<?php
require_once __DIR__.'/vendor/autoload.php';
new \streetlamp\rpcServer\RpcServer();
```

### 服务配置说明

服务配置文件为/src/server_config.php，内容如下：

```
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
        //服务进程名
        'worker_name'       => 'vitec',
        //日志路径
        'log_file'          => RUNTIME_PATH . 'workerman/log.log',
        //服务
        'service'           => [
            'User' => \app\test\logic\User::class
        ]
    ]
];
```

实例化new \streetlamp\rpcServer\RpcServer()对象时可以传入自定义的配置文件绝对路径，或者直接传入一个数组。

#### 一、传入绝对路径

```
#!/usr/bin/env php
<?php
require_once __DIR__.'/vendor/autoload.php';
new \streetlamp\rpcServer\RpcServer("/var/www/html/application/config.php");
```

#### 二、传入数组

```
#!/usr/bin/env php
<?php
require_once __DIR__.'/vendor/autoload.php';
$config = [
    'rpc_server' => [
        'processes'         => 1,
        'protocol'          => '\streetlamp\rpcServer\JsonNL',
        'host'              => '0.0.0.0',
        'port'              => 2015,
        'socket'            => '',
        'statistic_address' => 'udp://127.0.0.1:9200',
        'worker_name'       => 'vitec',
        'log_file'          => RUNTIME_PATH . 'workerman/log.log',
        'service'           => [
            'User' => \app\test\logic\User::class
        ]
    ]
];
new \streetlamp\rpcServer\RpcServer($config);
```

### 命令说明

#### 一、守护进程启动

php server.php start -d

#### 二、重启启动

php server.php restart

#### 三、平滑重启/重新加载配置

php server.php reload

#### 四、查看服务状态

php server.php status

#### 五、停止

php server.php stop

### thinkPHP5.0框架使用例子
新增启动服务文件server.php，在项目根目录
```
#!/usr/bin/env php
<?php
define('APP_PATH', __DIR__ . '/application/');
define('BIND_MODULE', 'rpc/RpcServer');
// 加载框架引导文件
require __DIR__ . '/thinkphp/start.php';
```
新增服务类
```
<?php

namespace app\rpc\controller;

class RpcServer extends \streetlamp\rpcServer\RpcServer
{
}
```
然后直接执行php server.php start则可开启服务。
# 客户端使用说明

## 客户端同步调用

```
<?php
// User对应服务端配置中service里面的映射类，$config为配置文件的绝对路径或数组

$config = 'client_config.php';

$user_client = streetlamp\rpcClient\RpcClient::instance('User',$config);

// getInfoByUid对应User类中的getInfoByUid方法

$ret_sync = $user_client->getInfoByUid($uid);
```

## 客户端异步调用

调用的方法添加"asend_"前缀，接收数据时添加"arecv_"前缀。

```
<?php
// User对应服务端配置中service里面的映射类，$config为配置文件的绝对路径或数组

$config = 'client_config.php';

$user_client = streetlamp\rpcClient\RpcClient::instance('User',$config);

// getInfoByUid对应User类中的getInfoByUid方法
//异步调用User::getInfoByUid方法
user_client = $user_client->asend_getInfoByUid($uid);

这里是其它的业务代码
....................
....................


// 需要数据的时候异步接收数据
$ret_async2 = $user_client->arecv_getInfoByUid($uid);
```

## 客户端配置说明

客户端配置文件为/src/client_config.php，内容如下：

```
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
```

$user_client = streetlamp\rpcClient\RpcClient::instance('User',$config);$config可以传入自定义的配置文件绝对路径，或者直接传入一个数组。

#### 一、传入绝对路径

```
<?php
$user_client = streetlamp\rpcClient\RpcClient::instance('User',"/var/www/html/application/config.php");

```

#### 二、传入数组

```
<?php
$user_client = streetlamp\rpcClient\RpcClient::instance(
    'User',
    [
        'type'               => 'workerman',
        'rpc_server_address' => [
            'tcp://127.0.0.1:2015'
        ],
        'reconnect_count'    => 1
    ]
);
```

# 统计服务端使用说明

需要在linux环境下启动

## 服务

在项目根目录新建statistic.php启动文件

```
#!/usr/bin/env php
<?php
require_once __DIR__.'/vendor/autoload.php';
new \streetlamp\Statistics\StatisticsServer();
```

### 统计服务端配置说明

统计服务端配置文件为/src/statistics_server_config.php，内容如下：

```
<?php
//统计服务端配置
return [
    //web页面端口
    'web_port'        => 55757,
    //接收统计数据端口
    'statistics_port' => 9200,
    //web页面域名配置
    'host'            => '',
];
```

实例化new \streetlamp\Statistics\StatisticsServer();对象时可以传入自定义的配置文件绝对路径，或者直接传入一个数组。

#### 一、传入绝对路径

```
#!/usr/bin/env php
<?php
require_once __DIR__.'/vendor/autoload.php';
new \streetlamp\Statistics\StatisticsServer("/var/www/html/application/config.php");
```

#### 二、传入数组

```
#!/usr/bin/env php
<?php
require_once __DIR__.'/vendor/autoload.php';
$config = [
    'rpc_server' =>[
    //web页面端口
    'web_port'        => 55757,
    //接收统计数据端口
    'statistics_port' => 9200,
    //web页面域名配置
    'host'            => '',
];
new \streetlamp\Statistics\StatisticsServer($config);
```

### 命令说明

#### 一、守护进程启动

php statistic.php start -d

#### 二、重启启动

php statistic.php restart

#### 三、平滑重启/重新加载配置

php statistic.php reload

#### 四、查看服务状态

php statistic.php status

#### 五、停止

php statistic.php stop

### 统计监控页面
访问地址：http://ip:55757(端口为配置文件中的web页面端口)

