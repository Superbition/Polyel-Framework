<?php

cli_set_process_title("Phase");

require __DIR__ . "/core/autoload.php";
require __DIR__ . "/core/functions/helperFunctions.php";

Phase_Config::load();

$server = new swoole_http_server(Phase_Config::get("main.serverIP"), Phase_Config::get("main.serverPort"));

$server->on("start", function($server)
{
    echo "Phase HTTP server started at http://" . Phase_Config::get("main.serverIP") . ":" . Phase_Config::get("main.serverPort");
});

$server->on("request", function($request, $response)
{
    $response->header("Server", "Swoole-Phase");
    $response->header("X-Powered-By", "Passion");
    $response->header("Content-Type", "text/html; charset=utf-8");

    Phase_Route::handle($request);
    Phase_Route::deliver($response);
});

$server->start();