<?php

namespace Polyel\Http;

use Polyel\Config\Config;
use Swoole\HTTP\Server as SwooleHTTPServer;

class Server
{
    // The Swoole server class variable
    private $server;

    public function __construct()
    {
        cli_set_process_title("Polyel");
    }

    public function boot()
    {
        Config::load();

        $this->server = new swoole_http_server(
            Config::get("main.serverIP"),
            Config::get("main.serverPort")
        );
    }

    public function registerReactors()
    {
        $this->server->on("start", function($server)
        {
            echo "Polyel HTTP server started at http://" .
                Config::get("main.serverIP") . ":" .
                Config::get("main.serverPort");
        });

        $this->server->on("request", function($request, $response)
        {
            Polyel_Server::setRequestHeaders($response);

            $this->runDebug();

            Route::handle($request);
            Route::deliver($response);
        });
    }

    public function run()
    {
        $this->server->start();
    }

    private function runDebug()
    {
        require __DIR__ . "/../../../debug.php";
    }

    private static function setRequestHeaders(&$response)
    {
        $response->header("Server", "Polyel-Swoole");
        $response->header("X-Powered-By", "Passion");
        $response->header("Content-Type", "text/html; charset=utf-8");
    }
}