<?php

namespace Polyel\View;

use Polyel\Storage\Facade\Storage;

class View
{
    // Holds the template name and eventually the file path
    private $resource;

    // Holds the found tags from a template
    private $resourceTags;

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

            // Get all the tags from the resource template
            $this->resourceTags = $this->getResourceTags($this->resource, "{{", "}}");

            if(exists($this->data))
            {
                // If data has been passed in, inject that into the resource
                $this->injectDataToView();
            }

            return $this->resource;
        }

        // Return because the resource was not found or is invalid from the ViewBuilder
        return null;
    }

    private function injectDataToView()
    {
        if(!exists($this->resourceTags))
        {
            // Return early if no tags are found in the resource
            return;
        }

        foreach($this->data as $key => $value)
        {
            if(in_array($key, $this->resourceTags, true))
            {
                $this->resource = str_replace("{{ $key }}", $value, $this->resource);
            }
        }
    }

    private function getResourceTags($resource, $startDelimiter, $endDelimiter): array
    {
        $tags = [];
        $startDelimiterLength = strlen($startDelimiter);
        $endDelimiterLength = strlen($endDelimiter);
        $startFrom = $resourceStart = $resourceEnd = 0;

        while (false !== ($resourceStart = strpos($resource, $startDelimiter, $startFrom)))
        {
            $resourceStart += $startDelimiterLength;
            $resourceEnd = strpos($resource, $endDelimiter, $resourceStart);

            if (false === $resourceEnd)
            {
                break;
            }

            $tags[] = trim(substr($resource, $resourceStart, $resourceEnd - $resourceStart));
            $startFrom = $resourceEnd + $endDelimiterLength;
        }

        return $tags;
    }
}