<?php

namespace Polyel\Http;

use Polyel\Config\Config;
use Swoole\HTTP\Server as SwooleHTTPServer;

class Server
{
    // The Swoole server class variable
    private $server;

    private $config;

    public function __construct(Config $config)
    {
        cli_set_process_title("Polyel");

        $this->config = $config;
    }

    public function boot()
    {
        $this->config->load();

        $this->server = new SwooleHTTPServer(
            $this->config->get("main.serverIP"),
            $this->config->get("main.serverPort")
        );
    }

    public function registerReactors()
    {
        $this->server->on("start", function($server)
        {
            echo "Polyel HTTP server started at http://" .
                $this->config->get("main.serverIP") . ":" .
                $this->config->get("main.serverPort");
        });

        $this->server->on("request", function($request, $response)
        {
            $this->setRequestHeaders($response);

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
        $debugFile = __DIR__ . "/../../../debug.php";

        if(file_exists($debugFile))
        {
            require $debugFile;
        }
    }

    private function setRequestHeaders(&$response)
    {
        $response->header("Server", "Polyel-Swoole");
        $response->header("X-Powered-By", "Passion");
        $response->header("Content-Type", "text/html; charset=utf-8");
    }
}