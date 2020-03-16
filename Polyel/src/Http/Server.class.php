<?php

namespace Polyel\Http;

use Polyel\Router\Router;
use Polyel\Config\Config;
use Polyel\Controller\Controller;
use Swoole\HTTP\Server as SwooleHTTPServer;

class Server
{
    // The Swoole server class variable
    private $server;

    // The config instance from the container
    private $config;

    // The Route instance from the container
    private $router;

    // The Controller instance from the container
    private $controller;

    public function __construct(Config $config, Router $router, Controller $controller)
    {
        cli_set_process_title("Polyel");

        $this->config = $config;
        $this->router = $router;
        $this->controller = $controller;
    }

    public function boot()
    {
        // Load all configuration files
        $this->config->load();

        // Preload all application routes
        $this->router->loadRoutes();

        // Preload all applications Controllers
        $this->controller->loadAllControllers();

        // Create a new Swoole HTTP server and set server IP and listening port
        $this->server = new SwooleHTTPServer(
            $this->config->get("main.serverIP"),
            $this->config->get("main.serverPort")
        );

        $this->server->set([
            'worker_num' => swoole_cpu_num(),
            ]);
    }

    public function registerReactors()
    {
        $this->server->on("start", function($server)
        {
            echo "\n";

            echo "Polyel HTTP server started at http://" .
                $this->config->get("main.serverIP") . ":" .
                $this->config->get("main.serverPort");

            echo "\n\n";
        });

        $this->server->on("request", function($request, $response)
        {
            $this->setRequestHeaders($response);

            $this->runDebug();

            $this->router->handle($request);
            $this->router->deliver($response);
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