<?php

class Phase_Route
{

    // The URI pattern the route responds to.
    private static $uri;

    // Holds the main route name/ page
    private static $route;

    // Holds the requested view template file name
    private static $requestedView;

    public static function handle($request)
    {
        // Get the full URL from the clients request
        self::$uri = $request->server["request_uri"];

        // Split the URI into an array based on the delimiter
        self::$uri = explode("/", $request->server["request_uri"]);

        // Remove empty array values from the URI because of the delimiters
        self::$uri = array_filter(self::$uri);

        // Reindex the array back to 0
        self::$uri = array_values(self::$uri);

        // Continuing routing if there is a URL
        if(!empty(self::$uri))
        {
            // Get the main page/ route name from the URI
            self::$route = strtolower(self::$uri[0]);

            if(file_exists(__DIR__ . "/../../app/controllers/" . self::$route . ".php"))
            {
                require __DIR__ . "/../../app/controllers/" . self::$route . ".php";

                $controllerFound = true;
            }

            if(file_exists(__DIR__ . "/../../app/views/" . self::$route . ".html"))
            {
                self::$requestedView = __DIR__ . "/../../app/views/" . self::$route . ".html";
            }
            else if(!isset($controllerFound))
            {
                self::$requestedView = __DIR__ . "/../../app/views/errors/404.html";
            }
        }
        else
        {
            // Else the user has requested the home page.
            self::$requestedView = __DIR__ . "/../../app/views/index.html";
        }
    }

    public static function deliver($response)
    {
        if(Phase_Debug::doDumpsExist())
        {
            $response->end(Phase_Debug::getDumps() . Phase_Template::render(self::$requestedView));
        }
        else
        {
            $response->end(Phase_Template::render(self::$requestedView));
        }
    }
}