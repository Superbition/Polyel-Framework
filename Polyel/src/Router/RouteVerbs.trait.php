<?php

namespace Polyel\Router;

trait RouteVerbs
{
    public function initialiseHttpVerbs()
    {
        $this->routes["GET"] = [];
        $this->routes["POST"] = [];
        $this->routes["PUT"] = [];
        $this->routes["PATCH"] = [];
        $this->routes["DELETE"] = [];

        $this->listOfAddedRoutes["GET"] = [];
        $this->listOfAddedRoutes["POST"] = [];
        $this->listOfAddedRoutes["PUT"] = [];
        $this->listOfAddedRoutes["PATCH"] = [];
        $this->listOfAddedRoutes["DELETE"] = [];
    }

    public function get($route, $action)
    {
        $this->addRoute("GET", $route, $action);

        return $this;
    }

    public function post($route, $action)
    {
        $this->addRoute("POST", $route, $action);

        return $this;
    }

    public function put($route, $action)
    {
        $this->addRoute("PUT", $route, $action);

        return $this;
    }

    public function patch($route, $action)
    {
        $this->addRoute("PATCH", $route, $action);

        return $this;
    }

    public function delete($route, $action)
    {
        $this->addRoute("DELETE", $route, $action);

        return $this;
    }
}