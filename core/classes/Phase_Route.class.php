<?php

class Phase_Route
{

    // The URI pattern the route responds to.
    private static $uri;

    // Holds the main route name/ page
    private static $requestedRoute;

    private static $getRoutes = [];

    // Holds the requested view template file name
    private static $requestedView;

    public static function handle(&$request)
    {
        // Get the full URL from the clients request
        self::$requestedRoute = self::$uri = $request->server["request_uri"];

        // Split the URI into an array based on the delimiter
        self::$uri = explode("/", $request->server["request_uri"]);

        // Remove empty array values from the URI because of the delimiters
        self::$uri = array_filter(self::$uri);

        // Reindex the array back to 0
        self::$uri = array_values(self::$uri);

        self::loadRoutes();

        // Continuing routing if there is a URL
        if(!empty(self::$requestedRoute))
        {
            if(self::$getRoutes[self::$requestedRoute])
            {
                $controller = self::$getRoutes[self::$requestedRoute];

                $controller = __DIR__ . "/../../app/controllers/" . $controller . ".php";

                if(file_exists($controller))
                {
                    require $controller;
                }
            }
        }
        else
        {
            self::$requestedView = __DIR__ . "/../../app/views/errors/404.html";
        }
    }

    public static function deliver(&$response)
    {
        if(Phase_Debug::doDumpsExist())
        {
            // The rendered response but with the debug dumps at the start.
            $response->end(Phase_Debug::getDumps() . "<br>" . Phase_Template::render(self::$requestedView));

            // Resets the last amount of dumps so duplicates are not shown upon next request.
            Phase_Debug::cleanup();
        }
        else
        {
            $response->end(Phase_Template::render(self::$requestedView));
        }
    }

    public static function get($route, $action)
    {
        self::$getRoutes[$route] = $action;
    }

    private static function loadRoutes()
    {
        require __DIR__ . "/../../app/routes.php";
    }
}