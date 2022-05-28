<?php

namespace streetlamp\rpcServer;

use streetlamp\MdgApp;
use streetlamp\rpcServer\statistics\StatisticClient;
use Workerman\Worker;

class RpcServer extends Server
{
    protected $statisticAddress = 'udp://127.0.0.1:55656';
    protected $workerName       = 'vitec';
    protected $logFile          = '';
    protected $service          = [];

    public function __construct($config = '')
    {
        if (is_string($config)) {
            if ($config == '') {
                $config = include_once __DIR__ . '/../server_config.php';
            } else {
                $config = include_once $config;
            }
        }
        $this->processes        = $config['rpc_server']['processes'] ?? 4;
        $this->protocol         = $config['rpc_server']['protocol'] ?? 'http';
        $this->host             = $config['rpc_server']['host'] ?? '0.0.0.0';
        $this->port             = $config['rpc_server']['port'] ?? '2346';
        $this->socket           = $config['rpc_server']['socket'] ?? '';
        $this->statisticAddress = $config['rpc_server']['statisticAddress'] ?? 'udp://127.0.0.1:55656';
        $this->workerName       = $config['rpc_server']['worker_name'] ?? 'streetlamp';
        $this->logFile          = $config['rpc_server']['log_file'] ?? '';
        $this->service          = $config['rpc_server']['service'] ?? [];
        parent::__construct();
    }

    protected function init()
    {
        $this->worker->name = $this->workerName;
        if (!$this->logFile) {
            Worker::$logFile = $this->logFile;
        }
    }

    /**
     * 收到信息
     * @param $connection
     * @param $data
     */
    public function onMessage($connection, $data)
    {
        $statistic_address = $this->statisticAddress;
        // 判断数据是否正确
        if (empty($data['class']) || empty($data['method']) || !isset($data['param_array'])) {
            // 发送数据给客户端，请求包错误
            return $connection->send(array('code' => 400, 'msg' => 'bad request', 'data' => null));
        }
        // 获得要调用的类、方法、及参数
        $class       = $data['class'];
        $method      = $data['method'];
        $param_array = $data['param_array'];
        StatisticClient::tick($class, $method);
        $success = false;
        // 调用类的方法
        try {
            $ret = MdgApp::getInstance()->$class->$method(...$param_array);
            StatisticClient::report($class, $method, 1, 0, '', $statistic_address);
            return $connection->send(array('code' => 200, 'msg' => 'ok', 'data' => $ret));
        } // 有异常
        catch (\Throwable $e) {
            // 发送数据给客户端，发生异常，调用失败
            $code = $e->getCode() ? $e->getCode() : 500;
            StatisticClient::report($class, $method, $success, $code, $e, $statistic_address);
            return $connection->send(array('code' => $code, 'msg' => $e->getMessage(), 'data' => $e));
        }
    }

    /**
     * 当连接建立时触发的回调函数
     * @param $connection
     */
    public function onConnect($connection)
    {
    }

    /**
     * 当连接断开时触发的回调函数
     * @param $connection
     */
    public function onClose($connection)
    {
    }

    /**
     * 当客户端的连接上发生错误时触发
     * @param $connection
     * @param $code
     * @param $msg
     */
    public function onError($connection, $code, $msg)
    {
        //StatisticClient::report($class, $method, $success, $code, $e, $statistic_address);
        echo "error $code $msg\n";
    }

    /**
     * 每个进程启动
     * @param $worker
     */
    public function onWorkerStart($worker)
    {
        if (!empty($this->service)) {
            foreach ($this->service as $key => $class) {
                MdgApp::getInstance()->bindTo([$key => $class]);
            }
        }
    }
}
