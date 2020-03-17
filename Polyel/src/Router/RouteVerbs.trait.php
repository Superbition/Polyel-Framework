<?php

namespace Polyel\Router;

trait RouteVerbs
{
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