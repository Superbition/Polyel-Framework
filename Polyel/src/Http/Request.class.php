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

    private $getQueries;

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
        $this->getQueries = $request->get;
    }
}