<?php

namespace streetlamp\rpcClient\driver\workerman;

use streetlamp\rpcClient\Driver;
use Exception;

/**
 *
 *  RpcClient Rpc客户端
 *
 *
 *  示例
 *  // 服务端列表
 * $address_array = array(
 * 'tcp://127.0.0.1:2015',
 * 'tcp://127.0.0.1:2015'
 * );
 * // 配置服务端列表
 * RpcClient::config($address_array);
 *
 * $uid = 567;
 * $user_client = RpcClient::instance('User');
 * // ==同步调用==
 * $ret_sync = $user_client->getInfoByUid($uid);
 *
 * // ==异步调用==
 * // 异步发送数据
 * $user_client->asend_getInfoByUid($uid);
 * $user_client->asend_getEmail($uid);
 *
 * 这里是其它的业务代码
 * ..............................................
 *
 * // 异步接收数据
 * $ret_async1 = $user_client->arecv_getEmail($uid);
 * $ret_async2 = $user_client->arecv_getInfoByUid($uid);
 *
 * @author walkor <worker-man@qq.com>
 */
class Workerman implements Driver
{
    /**
     * 发送数据和接收数据的超时时间  单位S
     * @var integer
     */
    const TIME_OUT = 5;

    /**
     * 异步调用发送数据前缀
     * @var string
     */
    const ASYNC_SEND_PREFIX = 'asend_';

    /**
     * 异步调用接收数据
     * @var string
     */
    const ASYNC_RECV_PREFIX = 'arecv_';

    protected $reconnectCount = 1;

    /**
     * 服务端地址
     * @var array
     */
    protected $addressArray = array();

    /**
     * 异步调用实例
     * @var string
     */
    protected $asyncInstances = array();

    /**
     * 到服务端的socket连接
     * @var resource
     */
    protected $connection = null;

    /**
     * 实例的服务名
     * @var string
     */
    protected $serviceName = '';

    protected $config = [];

    public function init($config)
    {
        $this->config = $config;
        if (!empty($config['rpc_server_address'])) {
            $this->addressArray = $config['rpc_server_address'];
        }
        $this->serviceName    = $config['service_name'];
        $this->reconnectCount = $config['reconnect_count'] ?? 1;
    }

    /**
     * 调用
     * @param string $method
     * @param array  $arguments
     * @return
     * @throws Exception
     */
    public function __call($method, $arguments)
    {
        // 判断是否是异步发送
        if (0 === strpos($method, self::ASYNC_SEND_PREFIX)) {
            $real_method  = substr($method, strlen(self::ASYNC_SEND_PREFIX));
            $instance_key = $real_method . serialize($arguments);
            if (isset($this->asyncInstances[$instance_key])) {
                throw new Exception(
                    $this->serviceName . "->$method(" . implode(',', $arguments) . ") have already been called"
                );
            }
            $this->asyncInstances[$instance_key] = (new self());
            $this->asyncInstances[$instance_key]->init($this->config);
            return $this->asyncInstances[$instance_key]->sendData($real_method, $arguments);
        }
        // 如果是异步接受数据
        if (0 === strpos($method, self::ASYNC_RECV_PREFIX)) {
            $real_method  = substr($method, strlen(self::ASYNC_RECV_PREFIX));
            $instance_key = $real_method . serialize($arguments);
            if (!isset($this->asyncInstances[$instance_key])) {
                throw new Exception(
                    $this->serviceName . "->asend_$real_method(" . implode(',', $arguments) . ") have not been called"
                );
            }
            $tmp = $this->asyncInstances[$instance_key];
            unset($this->asyncInstances[$instance_key]);
            return $tmp->recvData();
        }
        // 同步发送接收

        $this->sendData($method, $arguments);
        $res = $this->recvData();
        return $res;
    }

    /**
     * 发送数据给服务端
     * @param string $method
     * @param array  $arguments
     */
    public function sendData($method, $arguments)
    {
        $this->openConnection();
        $bin_data = JsonProtocol::encode(array(
            'class'       => $this->serviceName,
            'method'      => $method,
            'param_array' => $arguments,
        ));
        if (fwrite($this->connection, $bin_data) !== strlen($bin_data)) {
            throw new \Exception('Can not send data');
        }
        return true;
    }

    /**
     * 从服务端接收数据
     * @throws Exception
     */
    public function recvData()
    {
        $ret = fgets($this->connection);
        $this->closeConnection();
        if (!$ret) {
            throw new Exception("recvData empty");
        }
        return JsonProtocol::decode($ret);
    }

    /**
     * 打开到服务端的连接
     * @return void
     */
    protected function openConnection()
    {
        $address      = $this->addressArray[array_rand($this->addressArray)];
        $connectCount = 0;

        while (!$this->connection && $connectCount < $this->reconnectCount) {
            try {
                $this->connection = stream_socket_client($address, $err_no, $err_msg);
            } catch (\Throwable $e) {
                $connectCount++;
            }
        }
        if (!$this->connection) {
            throw new Exception("can not connect to $address , $err_no:$err_msg");
        }
        stream_set_blocking($this->connection, true);
        stream_set_timeout($this->connection, self::TIME_OUT);
    }

    /**
     * 关闭到服务端的连接
     * @return void
     */
    protected function closeConnection()
    {
        fclose($this->connection);
        $this->connection = null;
    }
}