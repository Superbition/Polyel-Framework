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
    }

    public function data($inputName = null, $default = null)
    {
        if(exists($inputName))
        {
            if(exists($this->postData[$inputName]))
            {
                return $this->postData[$inputName];
            }

            if(exists($default))
            {
                return $default;
            }

            return false;
        }

        return $this->postData;
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