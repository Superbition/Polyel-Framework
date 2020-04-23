<?php


namespace Polyel\Http;

class ResponseBuilder
{
    private $content;

    private $status;

    private $headers;

    public function __construct($content, $status = 200)
    {
        $this->content = $content;
        $this->status = $status;
    }

    public function status(int $code)
    {
        $this->status = $code;
    }

    public function header($name, $value)
    {
        $this->headers[$name] = $value;
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
    }
}