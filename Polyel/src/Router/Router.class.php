<?php

namespace Polyel\Router;

use Polyel\View\View;
use Polyel\Debug\Debug;

class Router
{
    // The URI pattern the route responds to.
    private $uri;

    // Holds the main route name/page
    private $requestedRoute;

    private $getRoutes = [];

    // Holds the requested view template file name
    private $requestedView;

    private $view;

    private $debug;

    public function __construct(View $view, Debug $debug)
    {
        $this->view = $view;
        $this->debug = $debug;
    }

    public function handle(&$request)
    {
        // Get the full URL from the clients request
        $this->requestedRoute = $this->uri = $request->server["request_uri"];

        // Split the URI into an array based on the delimiter
        $this->uri = explode("/", $request->server["request_uri"]);

        // Remove empty array values from the URI because of the delimiters
        $this->uri = array_filter($this->uri);

        // Reindex the array back to 0
        $this->uri = array_values($this->uri);

        $this->loadRoutes();

        // Continue routing if there is a URL
        if(!empty($this->requestedRoute))
        {
            // Check if the route matches any registered routes
            if(array_key_exists($this->requestedRoute, $this->getRoutes))
            {
                // Each route will have a controller and func it wants to call
                $routeAction = explode("@", $this->getRoutes[$this->requestedRoute]);

                // Split both the controller and func into separate vars
                $controller = $routeAction[0];
                $controllerFunc = $routeAction[1];

                // The path to the requested routes controller...
                $controller = __DIR__ . "/../../../app/controllers/" . $controller . ".php";

                if(file_exists($controller))
                {
                    require_once $controller;
                }
            }
        }
        else
        {
            $this->requestedView = __DIR__ . "/../../../app/views/errors/404.html";
        }
    }

    public function deliver(&$response)
    {
        if($this->debug->doDumpsExist())
        {
            // The rendered response but with the debug dumps at the start.
            $response->end($this->debug->getDumps() . "<br>" . Template::render($this->requestedView));

            // Resets the last amount of dumps so duplicates are not shown upon next request.
            $this->debug->cleanup();
        }
        else
        {
            $response->end($this->view->render($this->requestedView));
        }
    }

    public function get($route, $action)
    {
        $this->getRoutes[$route] = $action;
    }

    private function loadRoutes()
    {
        require __DIR__ . "/../../../app/routes.php";
    }
}