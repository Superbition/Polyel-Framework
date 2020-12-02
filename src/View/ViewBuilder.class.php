<?php

namespace Polyel\View;

use Exception;

class ViewBuilder
{
    // The template name, which is also the location and type
    public $resource;

    private $resourceDir = ROOT_DIR . '/resources';

    // If set, will hold the extending view name
    public $extendingView = false;

    // Holds any extending view data waiting to be injected
    public $extendingViewData;

    // Used to set if the view requested is valid
    private $valid = false;

    // The template extension type
    public $extensionType;

    // The file path template direction name
    public $filePathDirName;

    // The data which needs to be exchanged with the template
    public $data;

    public function __construct($resource, $data = null)
    {
        // Get the type from the resource name and set the name and type to the class
        $resourceAndType = explode(":", $resource);

        try
        {
            if(!array_key_exists(1, $resourceAndType))
            {
                throw new Exception("\n \e[41mResource type not set when using view(), you need to set a type like :view or :error\e[0m\n");
            }

            $this->resource = $resourceAndType[0];
            $this->extensionType = $resourceAndType[1];
        }
        catch(Exception $e)
        {
            echo $e->getMessage();
        }

        // Using the dot notation convert dots to directory slashes in the resource name
        $this->resource = str_replace(".", "/", $this->resource);

        /*
         * The template is either a view or an error.
         * Work out based on the type if the resource is a view or and error and check if they exist on file.
         */
        if($this->extensionType === 'view' && file_exists($this->resourceDir . '/views/' . $this->resource . '.view.html'))
        {
            $this->filePathDirName = 'views';
            $this->valid = true;
        }
        else if($this->extensionType === 'error' && file_exists($this->resourceDir . '/errors/' . $this->resource . '.error.html'))
        {
            $this->filePathDirName = 'errors';
            $this->valid = true;
        }
        else if($this->extensionType === 'flash' && file_exists($this->resourceDir . '/flashes/' . $this->resource . '.flash.html'))
        {
            $this->filePathDirName = 'flashes';
            $this->valid = true;
        }

        // If data is passed and not empty and is of type array
        if(is_array($data) && exists($data))
        {
            $this->data = $data;
        }
    }

    public function __toString()
    {
        return (string) file_get_contents(
            $this->resourceDir .
            "/{$this->filePathDirName}/" .
            $this->resource .
            ".{$this->extensionType}.html"
        );
    }

    public function extending($extendingView, $extendingViewData = null)
    {
        if(exists($extendingView))
        {
            // Sort the extending view name and type as they are stored like viewName:viewType
            list($extViewName, $extType) = explode(":", $extendingView);

            // Convert dot syntax to file slashes, build a full file path to the extending view, using the type as well
            $extViewName = str_replace(".", "/", $extViewName);
            $extViewFilePath = $this->resourceDir . "/${extType}s/" . $extViewName . ".$extType.html";

            // Check if the extending view actually exists
            if(file_exists($extViewFilePath))
            {
                // The extending view exists, so we set the name and data
                $this->extendingView = $extendingView;
                $this->extendingViewData = $extendingViewData;
            }
        }

        // Return back the instance of this class
        return $this;
    }

    public function isValid(): bool
    {
        return $this->valid;
    }
}