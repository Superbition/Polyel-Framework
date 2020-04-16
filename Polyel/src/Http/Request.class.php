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
            if(exists($this->postData) && array_key_exists($inputName, $this->postData))
            {
                return $this->postData[$inputName];
            }

            $inputArray = explode(".", $inputName);
            if(is_array($inputArray) && exists($inputArray))
            {
                $postDataArr = $this->postData;

                if($this->hasHeader("content-type", "application/json"))
                {
                    $postDataArr = json_decode($this->postRawContent, true);
                }

                if(!exists($postDataArr))
                {
                    return false;
                }

                foreach ($inputArray as $inputItem)
                {
                    // Loop until we get a final value based on the dot syntax
                    $postDataArr = $postDataArr[$inputItem];
                }

                return $postDataArr;
            }

            if(exists($default))
            {
                return $default;
            }

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
        return null;
    }

    public function query($queryName = null, $queryDefault = null)
    {
        if(exists($queryName))
        {
            if(exists($this->queries[$queryName]))
            {
                return $this->queries[$queryName];
            }

            if(exists($queryDefault))
            {
                return $queryDefault;
            }

            return false;
        }

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