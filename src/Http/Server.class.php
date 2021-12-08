<?php

namespace Polyel\Http;

use Polyel;
use Swoole\Runtime;
use Polyel\Router\Router;
use Polyel\Config\Config;
use Polyel\Storage\Storage;
use Swoole\Coroutine as Swoole;
use Polyel\Hashing\Facade\Hash;
use Polyel\System\ServiceManager;
use Polyel\Session\SessionManager;
use Polyel\Encryption\Facade\Crypt;
use Polyel\Database\DatabaseManager;
use Polyel\System\ApplicationLoader;
use Swoole\HTTP\Server as SwooleHTTPServer;

class Server
{
    // The Swoole server class variable
    private $server;

    // The config instance from the container
    private $config;

    // The Route instance from the container
    private $router;

    private $applicationLoader;

    private $serviceManager;

    private $databaseManager;
  
    private $sessionManager;

    public function __construct(Config $config, Router $router, ApplicationLoader $applicationLoader, serviceManager $serviceManager, DatabaseManager $databaseManager, SessionManager $sessionManager)
    {
        cli_set_process_title("Polyel-HTTP-Server");

        $this->config = $config;
        $this->router = $router;
        $this->applicationLoader = $applicationLoader;
        $this->serviceManager = $serviceManager;
        $this->databaseManager = $databaseManager;
        $this->sessionManager = $sessionManager;
    }

    public function boot()
    {
        $this->config->load();

        $this->applicationLoader->load();
        $this->applicationLoader->loadThirdPartyPackages();

        echo "\nPreparing to startup HTTP server\n";

        echo "Loading all registered routes\n";
        $this->router->loadRoutes();

        echo "Setting up the selected session driver\n";
        $this->sessionManager->setDriver(config('session.driver'));

        Crypt::setup();

        Hash::setup();

        Storage::setup();

        echo "Contacting the service manager, processing suppliers...\n";
        $this->serviceManager->processServiceSuppliers();

        echo "Enabling the Swoole RunTime Hook\n";
        Runtime::enableCoroutine();

        $this->server = new SwooleHTTPServer(
            $this->config->get("server.serverIP"),
            $this->config->get("server.serverPort"),
            SWOOLE_PROCESS
        );

        echo "Setting up the HTTP server configuration\n";
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
            echo " Polyel: " . Polyel::version() . "\n";
            echo " Swoole: " . swoole_version() . "\n";
            echo " PHP: " . phpversion() . "\n";
            echo " \e[36mPolyel HTTP server started at http://" .
                $this->config->get("server.serverIP") . ":" .
                $this->config->get("server.serverPort") . "\e[30m\e[0m";
            echo "\n------------------------------------------------------------------------\n";
        });

        $this->server->on("request", function($HttpRequest, $HttpResponse)
        {
            $this->setDefaultResponseHeaders($HttpResponse);

            $this->runDebug();

            // Retrieve service binds and singletons to make them available during the request life cycle
            $HttpKernel = Polyel::newHttpKernel(
                $this->serviceManager->getBinds(),
                $this->serviceManager->getLocalSingletons()
            );

            $HttpKernel->request->capture($HttpRequest);

            $response = $this->router->handle(
                $HttpKernel->request, $HttpKernel
            );

            $response->send($HttpResponse);
        });

        /*
         * Currently the Swoole task worker system is only used
         * to offload email transmission because SMTP connections that
         * use STARTTLS do not work under coroutines and the TCP hook.
         *
         * So this is the workaround for now until further releases.
         */
        $this->server->on('Task', function($server, $taskId, $reactorId, $email)
        {
            // Task system is only used to send emails...
            $email->send();
        });

        $this->server->on("WorkerStop", function($server, $workerId)
        {
            $this->databaseManager->closeWorkerPool();
        });
    }

    public function run()
    {
        echo "Starting the HTTP server...\n";
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