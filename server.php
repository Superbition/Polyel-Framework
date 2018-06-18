<?php

require __DIR__ . "/core/autoload.php";

$server = new swoole_http_server("192.168.1.182", 9501);

$server->on("start", function ($server)
{
    echo "Swoole http server is started at http://127.0.0.1:9501\n";
});

$server->on("request", function ($request, $response)
{
    $response->header("Server", "Swoole-Phase");
    $response->header("X-Powered-By", "Passion");
    $response->header("Content-Type", "text/html");
    $response->end("Hello World\n");
});

$server->start();