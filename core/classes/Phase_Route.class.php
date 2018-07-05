<?php

class Phase_Route
{

    // The URI pattern the route responds to.
    private static $uri;

    // Holds the main route name/ page
    private static $route;

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
            }

            if(file_exists(__DIR__ . "/../../app/views/" . self::$route . ".html"))
            {

            }
        }
        else
        {

        }
    }

    public static function deliver($response)
    {
        $response->end("Hello World!");
    }
}