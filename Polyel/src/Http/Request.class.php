<?php

namespace Polyel\Http;

use Polyel\Http\File\UploadedFile;

class Request
{
    use CookieHandler;

    public $hostIP;

    public $clientIP;

    public $userAgent;

    public $serverPort;

    public $serverProtocol;

    public $uri;

    public $path;

    public $type;

    public $method;

    private $headers;

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
        $this->userAgent = $this->headers["user-agent"] ?? null;

        $this->clientIP = $request->server["remote_addr"];
        $this->serverPort = $request->server["server_port"];
        $this->serverProtocol = $request->server["server_protocol"];
        $this->uri = $request->server["request_uri"];
        $this->path = $request->server["path_info"];
        $this->method = $request->server["request_method"];
        $this->fullQueryString = $request->server["query_string"] ?? null;

        $this->queries = $request->get ?? [];

        $this->cookies = $request->cookie ?? [];

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
             * Make sure the input array is also more than 1 as well
             */
            $inputArray = explode(".", $inputName);
            if(is_array($inputArray) && exists($inputArray) && count($inputArray) > 1)
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
                    // Only continue if the dot syntax matches an array element
                    if(array_key_exists($inputItem, $postDataArr))
                    {
                        // Loop until we get a final value based on the dot syntax
                        $postDataArr = $postDataArr[$inputItem];
                    }
                    else
                    {
                        // Return when an undefined index is found
                        return false;
                    }
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

    public function has($inputToCheck)
    {
        // Detect an array of values that the POST request must have present
        if(is_array($inputToCheck))
        {
            // Check if all the values are present in the POST request data
            foreach($inputToCheck as $input)
            {
                // If one value is not found or is not set, return false
                if($this->data($input) === false)
                {
                    return false;
                }
            }

            // All data is present
            return true;
        }
        else
        {
            // For when only a single string value is sent in to check
            if($this->data($inputToCheck) !== false)
            {
                // Value was found and is set
                return true;
            }
        }

        // False, single value is not present in POST data
        return false;
    }

    public function hasAny($inputToCheck)
    {
        // Only processes an array to check for present values in a POST request
        if(is_array($inputToCheck))
        {
            // If any values are found in the POST data, return true
            foreach($inputToCheck as $input)
            {
                if($this->has($input) !== false)
                {
                    return true;
                }
            }
        }

        // Otherwise no keys were found...
        return false;
    }

    public function isFilled($inputToCheck)
    {
        $value = $this->data($inputToCheck);

        if(exists($value))
        {
            return true;
        }

        return false;
    }

    public function isMissing($inputToCheck)
    {
        if($this->has($inputToCheck))
        {
            return false;
        }

        return true;
    }

    public function query($queryName = null, $queryDefault = null)
    {
        // Proceed to find query if a name to search for is set...
        if(exists($queryName))
        {
            // Check to see if the query name given exists
            if(array_key_exists($queryName, $this->queries))
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

    public function bool($boolInputName)
    {
        // POST data is not null and the bool name exists inside the POST request data
        if(exists($this->postData) && array_key_exists($boolInputName, $this->postData))
        {
            // The bool value from the input element
            $boolToCheck = $this->postData[$boolInputName];

            $boolTruthList = [1, "1", true, "true", "on", "yes"];

            // Check that the bool value from the client matches something from the truth list
            if(in_array($boolToCheck, $boolTruthList))
            {
                // Input value is true...
                return true;
            }
        }

        // No data or no bool matches
        return false;
    }

    public function cookie($name)
    {
        return $this->requestGetCookie($name);
    }

    public function file($fileName)
    {
        return new UploadedFile($this->files, $fileName);
    }

    public function hasFile($fileName)
    {
        if(exists($this->files) && array_key_exists($fileName, $this->files))
        {
            return true;
        }

        return false;
    }
}