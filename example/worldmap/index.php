<?php

use Example\Worldmap\Application;
use Example\Worldmap\Config;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;
use Workerman\Protocols\Http\Response;

set_time_limit(-1);
ini_set('memory_limit', '1G');

require_once __DIR__.'/vendor/autoload.php';

$app = new Application(Config::fromEnv());

if (($argv[1] ?? null) === 'warmup') {
    $app->warmup();
    exit(0);
}

$worker = new \Workerman\Worker('http://0.0.0.0:80');
$worker->count = 16;

$worker->onMessage = function (TcpConnection $connection, Request $request) use ($app) {
    $app->run($request->path(), $request->get(), function (string $data, array $headers = []) use ($connection) {
        $response = new Response(200, $headers, $data);
        $connection->send($response);
    });
};

\Workerman\Worker::runAll();
