<?php

namespace Polyel\Router;

use Polyel;
use Polyel\View\View;
use Polyel\Debug\Debug;
use Polyel\Middleware\Middleware;

class Router
{
    use RouteVerbs;

    // The URI pattern the route responds to.
    private $uri;

    // Holds the main route name/page
    private $requestedRoute;

    // Holds the request method sent by the client
    private $requestMethod;

    // Holds all the request routes to respond to
    private $routes;

    private $lastAddedRoute;

    // Holds the requested view template file name
    private $requestedView;

    private $view;

    private $debug;

    private $middleware;

    public function __construct(View $view, Debug $debug, Middleware $middleware)
    {
        $this->view = $view;
        $this->debug = $debug;
        $this->middleware = $middleware;
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

        // Detect the request method: GET, POST, PUT etc.
        $this->requestMethod = $request->server["request_method"];

        // Continue routing if there is a URL
        if(!empty($this->requestedRoute))
        {
            // Check if the route matches any registered routes
            if($this->routeExists($this->requestMethod, $this->requestedRoute))
            {
                $this->requestedView = null;

                // Get the action of the route split based on controller@Action
                $routeAction = $this->getRouteAction($this->requestMethod, $this->requestedRoute);

                // Split both the controller and func into separate vars from controller@Action
                $controller = $routeAction[0];
                $controllerAction = $routeAction[1];

                //The controller namespace and getting its instance from the container using ::call
                $controllerName = "App\Controllers\\" . $controller;
                $controller = Polyel::call($controllerName);

                // Check that the controller exists
                if(isset($controller) && !empty($controller))
                {
                    // Resolve and perform method injection when calling the controller action
                    $methodDependencies = Polyel::resolveMethod($controllerName, $controllerAction);
                    $controller->$controllerAction(...$methodDependencies);
                }
            }
            else
            {
                // Error 404 route not found
                $this->requestedView = __DIR__ . "/../../../app/views/errors/404.html";
            }
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

    public function addRoute($requestMethod, $uri, $action)
    {
        $this->routes[$requestMethod][$uri] = $action;
        $this->lastAddedRoute[$requestMethod] = $uri;
    }

    public function routeExists($requestMethod, $requestedRoute)
    {
        if(array_key_exists($requestedRoute, $this->routes[$requestMethod]))
        {
            return true;
        }

        return false;
    }

    public function getRouteAction($requestMethod, $requestedRoute)
    {
        // Each route will have a controller and func it wants to call
        return $routeAction = explode("@", $this->routes[$requestMethod][$requestedRoute]);
    }

    public function middleware($middlewareKey)
    {
        $requestMethod = array_key_first($this->lastAddedRoute);
        $routeUri = $this->lastAddedRoute[$requestMethod];
        $this->middleware->register($requestMethod, $routeUri, $middlewareKey);
    }

    public function loadRoutes()
    {
        require __DIR__ . "/../../../app/routes.php";
    }
}