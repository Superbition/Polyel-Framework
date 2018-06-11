<?php

$server = new swoole_http_server("127.0.0.1", 9501);

$server->on("start", function ($server)
{
    echo "Swoole http server is started at http://127.0.0.1:9501\n";
});

$server->on("request", function ($request, $response)
{
    $response->header("Content-Type", "text/html");
    $response->end("Hello World\n");
});

$server->start();