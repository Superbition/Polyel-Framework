<?php

namespace Polyel\Http;

class Request
{
    private $headers;

    private $hostIP;

    private $clientIP;

    private $userAgent;

    private $serverPort;

    private $serverProtocol;

    private $uri;

    private $path;

    private $method;

    private $fullQueryString;

    private $queries;

    private $cookies;

    private $files;

    private $postData;

    private $postRawContent;

    public function __construct()
    {

    }

    public function capture($request)
    {
        $this->headers = $request->header;
        $this->hostIP = $this->headers["host"];
        $this->userAgent = $this->headers["user-agent"];

        $this->clientIP = $request->server["remote_addr"];
        $this->serverPort = $request->server["server_port"];
        $this->serverProtocol = $request->server["server_protocol"];
        $this->uri = $request->server["request_uri"];
        $this->path = $request->server["path_info"];
        $this->method = $request->server["request_method"];
        $this->fullQueryString = $request->server["query_string"] ?? null;

        $this->queries = $request->get;

        $this->cookies = $request->cookie;

        $this->files = $request->files;

        $this->postData = $request->post;

        $this->postRawContent = $request->rawContent();
    }

    public function data($inputName = null, $default = null)
    {
        // If not exists, then return either normal $POST or rawContent()...
        if(exists($inputName))
        {
            // Check for a single key value pair first
            if(exists($this->postData) && array_key_exists($inputName, $this->postData))
            {
                // Return a value from a single input name
                return $this->postData[$inputName];
            }


            /*
             * This if block is used to allow for accessing array values from
             * normal form POST requests or JSON inputs from rawContent(). Arrays are accessed
             * via dot syntax and the JSON header must be set to decode JSON objs.
             * If the input given is using dot syntax, we continue checking for finding an array key/value...
             */
            $inputArray = explode(".", $inputName);
            if(is_array($inputArray) && exists($inputArray))
            {
                // Get the normal POST form data as a starting point
                $postDataArr = $this->postData;

                // Then check for the JSON header, to know when to decode JSON requests
                if($this->hasHeader("content-type", "application/json"))
                {
                    /*
                     * Decode and convert JSON object to PHP array
                     * If this block is reached, only JSON data is used for array access...
                     */
                    $postDataArr = json_decode($this->postRawContent, true);
                }

                // If POST data is null and no JSON data is found, return false
                if(!exists($postDataArr))
                {
                    // No form data or JSON data was found...
                    return false;
                }

                // The main loop for finding
                foreach ($inputArray as $inputItem)
                {
                    // Loop until we get a final value based on the dot syntax
                    $postDataArr = $postDataArr[$inputItem];
                }

                // Return data selected from the main loop based on the input name to search with
                return $postDataArr;
            }

            // No data was found, return the default if it was set
            if(exists($default))
            {
                return $default;
            }

            // Return false if all else fails, no data found
            return false;
        }

        // Return normal $POST data if it is not null
        if(exists($this->postData))
        {
            // Normal form POST data, whole array returned
            return $this->postData;
        }
        else
        {
            // Else return rawContent if it is not null
            if(exists($this->postRawContent))
            {
                // raw request content from body of request
                return $this->postRawContent;
            }
        }

        // Fallback for when normal POST and rawContent data are both null
        return false;
    }

    public function query($queryName = null, $queryDefault = null)
    {
        // Proceed to find query if a name to search for is set...
        if(exists($queryName))
        {
            // Check to see if the query name given exists
            if(exists($this->queries[$queryName]))
            {
                // Return thr query value
                return $this->queries[$queryName];
            }

            // Return a default if no query was found
            if(exists($queryDefault))
            {
                return $queryDefault;
            }

            // No query or default set
            return false;
        }

        // No query name set to search for, return the whole query...
        return $this->queries;
    }

    public function path()
    {
        return $this->path;
    }

    public function url()
    {
        return $this->uri . "?" . $this->fullQueryString;
    }

    public function method()
    {
        return $this->method;
    }

    public function isMethod($isMethodType)
    {
        if($this->method === $isMethodType)
        {
            return true;
        }

        return false;
    }

    public function headers($header = null)
    {
        if(exists($header))
        {
            if(exists($this->headers[$header]))
            {
                return $this->headers[$header];
            }

            return false;
        }

        return $this->headers;
    }

    public function hasHeader($headerToFind, $headerEquals = null)
    {
        if(exists($this->headers[$headerToFind]))
        {
            if(exists($headerEquals))
            {
                if($this->headers[$headerToFind] === $headerEquals)
                {
                    return true;
                }
                else
                {
                    return false;
                }
            }

            return true;
        }

        return false;
    }
}