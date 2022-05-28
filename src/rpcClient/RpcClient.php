<?php

namespace streetlamp\rpcClient;

class RpcClient
{
    /**
     * 同步调用实例
     * @var string
     */
    protected static $instances = array();

    /**
     * 获取一个实例
     * @param string $service_name 服务名
     * @param mixed  $config 配置支持数组或配置文件
     * @return mixed|string
     */
    public static function instance(
        $service_name,
        $config = ''
    ) {
        if (is_string($config)) {
            if ($config == '') {
                $config = include_once __DIR__ . '/../client_config.php';
            } else {
                $config = include_once $config;
            }
        }
        $config['service_name'] = $service_name;
        $type                   = $config['type'] ?? 'workerman';
        if (!isset(self::$instances[$service_name])) {
            $class                          = false === strpos($type, '\\') ?
                '\\streetlamp\\rpcClient\\driver\\' . $type . '\\' . ucwords($type) :
                $type;
            self::$instances[$service_name] = new $class();
        }
        self::$instances[$service_name]->init($config);
        return self::$instances[$service_name];
    }
}