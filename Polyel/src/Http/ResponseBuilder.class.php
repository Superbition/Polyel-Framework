<?php


namespace Polyel\Http;

class ResponseBuilder
{
    public $content;

    public $contentType;

    public $status;

    public $headers;

    // Holds cookies that need to be added to the final response
    public $cookies;

    public function __construct($content, $status = 200)
    {
        $this->content = $content;
        $this->status = $status;
    }

    public function status(int $code)
    {
        $this->status = $code;

        return $this;
    }

    public function header($name, $value)
    {
        $this->headers[$name] = $value;

        return $this;
    }

    public function usingHeaders($headers)
    {
        if(is_array($headers))
        {
            foreach($headers as $key => $value)
            {
                $this->header($key, $value);
            }
        }

        return $this;
    }

    public function addCookie($name, $value, $expire = 86400, $path = "/", $domain = "", $secure = false, $httpOnly = true, $sameSite = "None")
    {
        // Setup the cookie array and store it in $cookies, ready to be attached to the response later
        $this->cookies[] = [
            $name,
            $value,
            $expire = (time() + $expire),
            $path,
            $domain,
            $secure,
            $httpOnly,
            $sameSite
        ];

        // Return back the ResponseBuilder instance
        return $this;
    }

    public function setContentType($contentType)
    {
        if($contentType === "json")
        {
            $this->contentType = "json";
            $this->header("Content-Type", "application/json");
        }

        if($contentType === "xml")
        {
            $this->contentType = "xml";
            $this->header("Content-Type", "application/xml");
        }

        if($contentType === "text")
        {
            $this->contentType = "text";
            $this->header("Content-Type", "text/plain");
        }

        if($contentType === "pdf")
        {
            $this->contentType = "pdf";
            $this->header("Content-Type", "application/pdf");
        }

        if($contentType === "zip")
        {
            $this->contentType = "zip";
            $this->header("Content-Type", "application/zip");
        }

        if($contentType === "jpeg")
        {
            $this->contentType = "jpeg";
            $this->header("Content-Type", "image/jpeg");
        }

        if($contentType === "png")
        {
            $this->contentType = "png";
            $this->header("Content-Type", "image/png");
        }

        if($contentType === "gif")
        {
            $this->contentType = "gif";
            $this->header("Content-Type", "image/gif");
        }

        return $this;
    }
}