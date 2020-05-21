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

    // Used to store the file write mode, default is overwrite
    private $writeMode = "w+";

    public function __construct()
    {

    }

    // Used to start reading from a common directory link
    public function from($fromLink)
    {
        $this->fromLink = $fromLink;
        return $this;
    }

    // Send back the file size in a human readable format
    public function size($filePath)
    {
        $filePath = ROOT_DIR . $filePath;

        // Don't continue if the file does not exist
        if(!file_exists($filePath))
        {
            throw new Exception("ERROR: Cannot get file size, file does not exist: " . $filePath);
        }

        // Remove cached information so that filesize() is accurate
        clearstatcache();
        $bytes = filesize($filePath);

        // Convert bytes to a human readable format and return the final value with its unit
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        for ($i = 0; $bytes > 1024; $i++) $bytes /= 1024;

        return round($bytes, 2) . $units[$i];
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
        $file = Swoole::fread($handle, 0);
        fclose($handle);

        // Reset the from link
        $this->fromLink = null;

        // Return the file contents as a string
        return $file;
    }

    // Prepend to a file using php:://temp
    public function prepend($filePath, $contents)
    {
        $filePath = ROOT_DIR . $filePath;

        // Make sure the file we want to write to exists beforehand
        if(!file_exists($filePath))
        {
            // Create the file because the read and write modes later will not do it for us
            touch($filePath);
        }

        /*
         * Create both the source and destination handles for our file and temp buffer
         * https://www.php.net/manual/en/wrappers.php.php
         */
        $srcHandle = fopen($filePath, "r+");
        $destHandle = fopen("php://temp", "w");

        // Using a Swoole Coroutine to defer blocking I/O
        Swoole::create(function() use($srcHandle, $destHandle, $contents)
        {
            // Write the contents we want to prepend first into the php://temp stream
            Swoole::fwrite($destHandle, $contents);

            /*
             * First copy the source contents into the end of the destination stream.
             * Then set both the source and destination file pointers to the beginning so
             * that the prepended stream can be copied to the source file.
             */
            stream_copy_to_stream($srcHandle, $destHandle);
            rewind($destHandle);
            rewind($srcHandle);
            stream_copy_to_stream($destHandle, $srcHandle);

            // Finally close both resource handles.
            fclose($srcHandle);
            fclose($destHandle);
        });
    }

    public function append($filePath, $contents)
    {
        // Set the write mode to append to the end of the file
        $this->writeMode = "a+";
        $this->write($filePath, $contents);
    }

    // Main writing function for overwrite and appending
    public function write($filePath, $contents = "")
    {
        $filePath = ROOT_DIR . $filePath;

        // Open a resource handle and use a Swoole Coroutine to defer blocking I/O
        $handle = fopen($filePath, $this->writeMode);
        Swoole::create(function() use ($handle, $contents)
        {
            Swoole::fwrite($handle, $contents);
            fclose($handle);
        });

        // Reset the write mode back to the default
        $this->writeMode = "w+";
    }

    public function copy($source, $dest)
    {
        $source = ROOT_DIR . $source;
        $dest = ROOT_DIR . $dest;

        Swoole::create(function() use ($source, $dest)
        {
            copy($source, $dest);
        });
    }

    public function move($oldName, $newName, $absoluteOldPath = false)
    {
        if($absoluteOldPath === false)
        {
            $oldName = ROOT_DIR . $oldName;
        }

        $newName = ROOT_DIR . $newName;

        Swoole::create(function() use ($oldName, $newName)
        {
            rename($oldName, $newName);
        });

        return $newName;
    }

    public function delete($filePath)
    {
        // Defer the delete process
        Swoole::create(function() use ($filePath)
        {
            // When the filePath is a single string
            if(!is_array($filePath))
            {
                unlink(ROOT_DIR . $filePath);
            }
            else
            {
                // For when an array of filePaths are passed in for deletion
                foreach ($filePath as $path)
                {
                    unlink(ROOT_DIR . $path);
                }
            }
        });
    }

    public function makeDir($dirPath, $mode = 0777)
    {
        // Recursively create the directory path given using the mode that was set
        return mkdir(ROOT_DIR . $dirPath, $mode, true);
    }

    public function removeDir($dirPath)
    {
        // Only deletes a directory that is empty
        return rmdir(ROOT_DIR . $dirPath);
    }
}