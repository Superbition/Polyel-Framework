<?php

namespace Polyel\Http;

use Closure;
use Polyel\View\View;
use Polyel\Session\Session;
use Polyel\Auth\AuthManager;
use Polyel\Container\Container;
use App\Http\Controllers\Controller;
use Polyel\Validation\ValidationException;
use Polyel\Http\Middleware\Facade\MiddlewareManager;

class Kernel
{
    // The kernel service container for this HTTP request
    public $container;

    // Session service for the HttpKernel
    public $session;

    // The request service for the duration of this HTTP request
    public $request;

    // The response service for the duration of this HTTP request
    public $response;

    // The AuthManager service
    public $auth;

    protected array $globalMiddlewareStack = [];

    protected array $middlewareGroups = [];

    protected array $routeMiddlewareAliases = [];

    public function __construct(Session $session, Request $request, Response $response, AuthManager $auth)
    {
        $this->session = $session;
        $this->request = $request;
        $this->response = $response;
        $this->auth = $auth;
    }

    public function setup()
    {
        $this->auth->initialise($this);

        $view = $this->container->get(View::class);
        $view->setHttpKernel($this);

        $this->request->setAuthManager($this->auth);
    }

    public function setContainer(Container $container)
    {
        $this->container = $container;
    }

    public function setSessionID($sessionID)
    {
        $this->session->setID($sessionID);
    }

    public function executeMiddlewareWithCoreAction($requestMethod, $matchedRoute)
    {
        // Create a middleware stack to execute against the request
        $middlewareStack = MiddlewareManager::prepareStack(
            $this,
            MiddlewareManager::generateStackForRoute($requestMethod, $matchedRoute['url']),
            $this->routeMiddlewareAliases,
            $this->globalMiddlewareStack,
            $this->middlewareGroups,
        );

        // A core action would be either a closure or a controller
        $coreAction = $this->prepareCoreAction($matchedRoute);

        // Execute a middleware stack with the prepared core action: closure or controller and get the response
        $response = MiddlewareManager::executeStackWithCoreAction($this->request, $middlewareStack, $coreAction);

        // Build the response returned by either middleware or the core action
        $this->response->build($response);

        return $this->response;
    }

    private function prepareCoreAction($matchedRoute)
    {
        return function() use($matchedRoute)
        {
            /*
             * The core route action can either be Closure or Controller
             */
            if($matchedRoute['action'] instanceof Closure)
            {
                // Get the Closure and set it as the route action
                $coreAction = $matchedRoute['action'];
            }
            else if(is_string($matchedRoute['action']))
            {
                // Extract the controller and action and set them so the class has access to them
                $matchedRoute['action'] = explode("@", $matchedRoute['action']);

                // Get the current matched controller and route action
                list($controller, $controllerAction) = $matchedRoute['action'];

                // The controller namespace and getting its instance from the container using ::call
                $controllerName = "App\Http\Controllers\\" . $controller;
                $controller = $this->container->resolveClass($controllerName);

                // Set the route action to the resolved controller
                $coreAction = $controller;
            }

            // URL route parameters from request
            $routeParams = $matchedRoute['params'];

            /*
             * The route action is either a a Closure or Controller
             */
            if($coreAction instanceof Closure)
            {
                // Resolve and perform method injection when calling the Closure
                $closureDependencies = $this->container->resolveClosureDependencies($coreAction);

                // Method injection for any services first, then route parameters and get the Closure response
                $coreActionResponse = $coreAction(...$closureDependencies, ...$routeParams);
            }
            else if($coreAction instanceof Controller)
            {
                // Resolve and perform method injection when calling the controller action
                $methodDependencies = $this->container->resolveMethodInjection($controllerName, $controllerAction);

                try
                {
                    // Method injection for any services first, then route parameters and get the Controller response
                    $coreActionResponse = $controller->$controllerAction(...$methodDependencies, ...$routeParams);
                }
                catch(ValidationException $validator)
                {
                    if($this->request->expectsJson())
                    {
                        // Return the response from the validation service
                        return $validator->response(422);

                    }

                    // Return the response from the validation service
                    return $validator->session($this->session)
                                     ->response(302, $this->request->uri);
                }
            }

            // return the built core action response
            return $coreActionResponse;
        };
    }

    public function getMiddlewareGroups()
    {
        return $this->middlewareGroups;
    }

    public function getRouteMiddlewareAliases()
    {
        return $this->routeMiddlewareAliases;
    }
}