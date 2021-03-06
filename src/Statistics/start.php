<?php

/**
 * This file is part of workerman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link http://www.workerman.net/
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */
require_once __DIR__ . '/Config/Config.php';
require_once __DIR__ . '/Protocols/Statistic.php';
require_once __DIR__ . '/Bootstrap/StatisticProvider.php';
require_once __DIR__ . '/Bootstrap/StatisticWorker.php';
// StatisticProvider
$statistic_provider       = new \streetlamp\Statistics\Bootstrap\StatisticProvider("Text://0.0.0.0:55858");
$statistic_provider->name = 'StatisticProvider';
// StatisticWorker
$statistic_worker            = new \streetlamp\Statistics\Bootstrap\StatisticWorker("\streetlamp\Statistics\Protocols\Statistic://0.0.0.0:9200");
$statistic_worker->transport = 'udp';
$statistic_worker->name      = 'StatisticWorker';
// WebServer
$web       = new \Workerman\WebServer("http://0.0.0.0:55757");
$web->name = 'StatisticWeb';
$web->addRoot('www.your_domain.com', __DIR__ . '/Web');
// recv udp broadcast
$udp_finder            = new \Workerman\Worker("Text://0.0.0.0:55858");
$udp_finder->name      = 'StatisticFinder';
$udp_finder->transport = 'udp';
$udp_finder->onMessage = function ($connection, $data) {
    $data = json_decode($data, true);
    var_dump($data);
    if (empty($data)) {
        return false;
    }
    // 无法解析的包
    if (empty($data['cmd']) || $data['cmd'] != 'REPORT_IP') {
        return false;
    }
    // response
    return $connection->send(json_encode(array('result' => 'ok')));
};
// 如果不是在根目录启动，则运行runAll方法
if (!defined('GLOBAL_START')) {
    \Workerman\Worker::runAll();
}
