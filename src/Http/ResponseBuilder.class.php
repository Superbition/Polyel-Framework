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

    // Holds the path to a file which will get sent back to the client if set
    public $file;

    // Used to set when to show a file instead of force a download to client
    private $showFileFlag;

    public function __construct($content = "", $status = 200)
    {
        $this->content = $content;
        $this->status = $status;

        // Default is to not show files without using showFile()
        $this->showFileFlag = false;
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

    public function withCookie($name, $value, $expire = 86400, $path = "/", $domain = "", $secure = false, $httpOnly = true, $sameSite = "None")
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

        if($contentType === "ico")
        {
            $this->contentType = "ico";
            $this->header("Content-Type", "image/x-icon");
        }

        if($contentType === "svg")
        {
            $this->contentType = "svg";
            $this->header("Content-Type", "image/svg+xml");
        }

        return $this;
    }

    public function sendFile($filePath, $name = null, $type = null)
    {
        // Only send a file if no main content has been set
        if(!exists($this->content))
        {
            // If no given name is set, use the file name and extension from the file path given
            if(!exists($name))
            {
                // Split based on '/' and use the last element to get the file name and ext: example.txt
                $name = explode("/", $filePath);
                $name = end($name);
            }

            // Trim off any slashes to prevent file path errors
            $filePath = ltrim($filePath, "/");
            $filePath = rtrim($filePath, "/");

            // Build up the file path
            $filePath = APP_DIR . "/storage/" . $filePath;

            // Perform an early return if the file cannot be found
            if(!file_exists($filePath))
            {
                return $this;
            }

            // If a file type is set...
            if(exists($type))
            {
                $this->setContentType($type);
            }

            // When show file is set, don't force a download, instead just show the file
            if(!$this->showFileFlag)
            {
                // Set the appropriate header needed to force a client download
                $this->header("Content-Disposition", "attachment;filename=$name");

                // Reset show file flag, ready for the next request
                $this->showFileFlag = false;
            }

            // Set the file property to indicate a file should be sent with the response
            $this->file = $filePath;
        }

        // Return back the ResponseBuilder instance
        return $this;
    }

    public function showFile($filePath, $type)
    {
        // Setting show file flag enables the response to display the file rather than force a download
        $this->showFileFlag = true;

        // Using the normal send file function but with the show file flag set...
        return $this->sendFile($filePath, null, $type);
    }
}