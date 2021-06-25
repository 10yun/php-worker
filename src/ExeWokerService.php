<?php

namespace shiyun\worker;

use think\Service as BaseService;

class ExeWokerService extends BaseService
{
    public function register()
    {
        $this->commands([
            'worker'         => '\\shiyun\\worker\\command\\Worker',
            'worker:server'  => '\\shiyun\\worker\\command\\Server',
            'worker:gateway' => '\\shiyun\\worker\\command\\GatewayWorker',
        ]);
    }
}
