<?php

namespace Polyel\View;

use Polyel\Storage\Facade\Storage;

class View
{
    // Holds the template name and eventually the file path
    private $resource;

    // Holds the template data to be added
    private $data;

    private $resourceDir = ROOT_DIR . "/app/resources";

    public function __construct()
    {

    }

    // The main function used to perform the view rendering and data to template exchange
    public function render(ViewBuilder $resource)
    {
        // Give the class access to both resource and data
        $this->resource = $resource->resource;
        $this->data = $resource->data;

        // If the resource file was found and is valid...
        if($resource->isValid())
        {
            // Set the template type
            $type = $resource->type;

            // Format the resource file path and get the resource from the local disk
            $viewLocation = $this->resourceDir . "/${type}s/" . $this->resource . ".${type}.html";
            $this->resource = Storage::access('local')->read($viewLocation);

            return $this->resource;
        }

        // Return because the resource was not found or is invalid from the ViewBuilder
        return null;
    }
}