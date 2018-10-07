<?php

class Phase_Server
{
    // The Swoole server class variable
    private $server;

    public function __construct()
    {
        cli_set_process_title("Phase");
    }

    public function boot()
    {
        Phase_Config::load();

        $this->server = new swoole_http_server(
            Phase_Config::get("main.serverIP"),
            Phase_Config::get("main.serverPort")
        );
    }

    public function setupReactors()
    {
        $this->server->on("start", function($server)
        {
            echo "Phase HTTP server started at http://" .
                Phase_Config::get("main.serverIP") . ":" .
                Phase_Config::get("main.serverPort");
        });

        $this->server->on("request", function($request, $response)
        {
            Phase_Server::setRequestHeaders($response);

            Phase_Route::handle($request);
            Phase_Route::deliver($response);
        });
    }

    public function run()
    {
        $this->server->start();
    }

    private static function setRequestHeaders(&$response)
    {
        $response->header("Server", "Swoole-Phase");
        $response->header("X-Powered-By", "Passion");
        $response->header("Content-Type", "text/html; charset=utf-8");
    }
}