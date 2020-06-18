<?php

namespace Polyel\Http;

use Polyel;
use Polyel\Router\Router;
use Polyel\Config\Config;
use Swoole\Coroutine as Swoole;
use Polyel\View\Element\Element;
use Polyel\Controller\Controller;
use Polyel\Middleware\Middleware;
use Polyel\Session\SessionManager;
use Polyel\Database\DatabaseManager;
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

    private $middleware;

    private $databaseManager;
  
    private $sessionManager;

    public function __construct(Config $config, Router $router, Controller $controller, Middleware $middleware, DatabaseManager $databaseManager, SessionManager $sessionManager)
    {
        cli_set_process_title("Polyel-HTTP-Server");

        $this->config = $config;
        $this->router = $router;
        $this->controller = $controller;
        $this->middleware = $middleware;
        $this->databaseManager = $databaseManager;
        $this->sessionManager = $sessionManager;
    }

    public function boot()
    {
        $this->config->load();

        $this->router->loadRoutes();

        $this->controller->loadAllControllers();

        $this->middleware->loadAllMiddleware();

        $this->sessionManager->setDriver(config('session.driver'));

        Element::loadClassElements();

        \Swoole\Runtime::enableCoroutine();

        $this->server = new SwooleHTTPServer(
            $this->config->get("main.serverIP"),
            $this->config->get("main.serverPort"),
            SWOOLE_PROCESS
        );

        $this->server->set([
            'worker_num' => swoole_cpu_num(),
            'package_max_length' => config("server.maxUploadSize"),
            'document_root' => config("server.publicRoot"),
            'enable_static_handler' => true,
            'upload_tmp_dir' => config("server.uploadDir"),
            ]);
    }

    public function registerReactors()
    {
        $this->server->on("WorkerStart", function($server, $workerId)
        {
            $this->databaseManager->createWorkerPool();
        });

        $this->server->on("start", function($server)
        {
            echo "\n";

            echo "------------------------------------------------------------------------\n";
            echo " Swoole: " . swoole_version() . "\n";
            echo " PHP Version: " . phpversion() . "\n";
            echo " \e[36mPolyel HTTP server started at http://" .
                $this->config->get("main.serverIP") . ":" .
                $this->config->get("main.serverPort") . "\e[30m\e[0m";
            echo "\n------------------------------------------------------------------------\n";
        });

        $this->server->on("request", function($HttpRequest, $HttpResponse)
        {
            $this->setDefaultResponseHeaders($HttpResponse);

            $this->runDebug();

            $HttpKernel = Polyel::newHttpKernel();

            $HttpKernel->request->capture($HttpRequest);

            $response = $this->router->handle(
                $HttpKernel->request, $HttpKernel
            );

            $response->send($HttpResponse);
        });

        $this->server->on("WorkerStop", function($server, $workerId)
        {
            $this->databaseManager->closeWorkerPool();
        });
    }

    public function run()
    {
        $this->server->start();
    }

    private function runDebug()
    {
        Swoole::create(function ()
        {
            $debugFile = __DIR__ . "/../../../debug.php";

            if(file_exists($debugFile))
            {
                require $debugFile;
            }
        });
    }

    private function setDefaultResponseHeaders($response)
    {
        $response->header("Server", "Polyel/Swoole-HTTP-Server");
        $response->header("X-Powered-By", "Polyel-PHP");
        $response->header("Content-Type", "text/html; charset=utf-8");
    }
}