<?php

namespace Polyel\Storage;

use Exception;
use Swoole\Coroutine as Swoole;

class LocalStorage
{
    // Default links to some important directories
    private $directoryLinks = [
        "app" => ROOT_DIR . "/app",
        "controllers" => ROOT_DIR . "/app/Controllers",
        "views" => ROOT_DIR . "/app/views",
        "public" => ROOT_DIR . "/public",
        "config" => ROOT_DIR . "/config",
        "storage" => ROOT_DIR . "/storage",
        "src" => ROOT_DIR . "/Polyel/src",
    ];

    // Used to set a common directory link
    private $fromLink;

    public function __construct()
    {

    }

    // Used to start reading from a common directory link
    public function from($fromLink)
    {
        $this->fromLink = $fromLink;
        return $this;
    }

    // Read a file and return the raw string content
    public function read($filePath)
    {
        // Check if a from link has been set
        if(isset($this->fromLink))
        {
            $filePath = $this->directoryLinks[strtolower($this->fromLink)] . $filePath;
        }

        if(!file_exists($filePath))
        {
            throw new Exception("Read Error: File not found at " . $filePath);
        }

        // Open a resource handle
        $handle = fopen(realpath($filePath), "rb");

        // Read the entire file and close the handle afterwards
        $file = Swoole::fread($handle, filesize($filePath));
        fclose($handle);

        // Reset the from link
        $this->fromLink = null;

        // Return the file contents as a string
        return $file;
    }
}