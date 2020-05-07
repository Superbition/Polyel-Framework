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

            /*
             * Format the resource file path and get the resource from the local disk
             * NOTE: The $this->resource file/path name is already converted from dot syntax in ViewBuilder
             */
            $viewLocation = $this->resourceDir . "/${type}s/" . $this->resource . ".${type}.html";
            $this->resource = Storage::access('local')->read($viewLocation);

            // Get all the tags from the resource template
            $this->resourceTags = $this->getStringBetween($this->resource, "{{", "}}");

            // Process @include() calls for the main request view
            $this->processIncludes($this->resource, $this->resourceTags);

            if(exists($this->data))
            {
                // If data has been passed in, inject that into the resource
                $this->injectDataToView($this->resource, $this->resourceTags, $this->data);
            }

            return $this->resource;
        }

        // Return because the resource was not found or is invalid from the ViewBuilder
        return null;
    }

    private function processIncludes(&$resourceContent, &$resourceTags)
    {
        // Sort all the @includes into a single string for processing
        $includesAsString = '';
        foreach($resourceTags as $key => $tag)
        {
            // Grab only the @include tags
            if(strpos($tag, '@include') !== false)
            {
                // Sort the @include tags into a single string for easier processing
                $includesAsString .= ' ' . $tag;

                // Delete the @include tag because it will be processed here
                unset($resourceTags[$key]);
            }
        }

        if(!exists($includesAsString))
        {
            // Return early if there are no includes to process
            return;
        }

        // Reset the array index for resource tags
        $resourceTags = array_values($resourceTags);

        // Based on the string of includes, grab all the values from the include call between the ()
        $includes = $this->getStringBetween($includesAsString, '@include(', ')');

        // Process each include value and inject any resource into the main template
        foreach($includes as $include)
        {
            // Split based on the resource name/path and the type, for example: header:view or common.header:view
            $resourceAndType = explode(":", $include);
            $resourceName = $resourceAndType[0];
            $includeType = $resourceAndType[1];

            // Using the dot notation convert dots to directory slashes in the resource name
            $resourceFileNamePath = str_replace('.', '/', $resourceName);

            // Build the include file location to check...
            $includeLocation = $this->resourceDir . "/${includeType}s/" . $resourceFileNamePath . '.view.html';

            // Check if the include exists on local disk
            if(file_exists($includeLocation))
            {
                // Get the resource content include from file and inject it into the main template
                $includeContent = Storage::access('local')->read($includeLocation);
                $resourceContent = str_replace("{{ @include($resourceName:$includeType) }}", $includeContent, $resourceContent);
            }
            else
            {
                // Else if no include resource is found, remove the include tags and replace them with nothing
                $resourceContent = str_replace("{{ @include($resourceName:$includeType) }}", '', $resourceContent);
            }
        }
    }

    private function injectDataToView(&$resourceContent, &$resourceTags, $data)
    {
        if(!exists($resourceTags))
        {
            // Return early if no tags are found in the resource
            return;
        }

        foreach($data as $key => $value)
        {
            if(in_array($key, $resourceTags, true))
            {
                $resourceContent = str_replace("{{ $key }}", $value, $resourceContent);
            }
        }
    }

    private function getStringBetween($string, $startDelimiter, $endDelimiter): array
    {
        $matches = [];
        $startDelimiterLength = strlen($startDelimiter);
        $endDelimiterLength = strlen($endDelimiter);
        $startFrom = $stringStart = $stringEnd = 0;

        while (false !== ($stringStart = strpos($string, $startDelimiter, $startFrom)))
        {
            $stringStart += $startDelimiterLength;
            $stringEnd = strpos($string, $endDelimiter, $stringStart);

            if (false === $stringEnd)
            {
                break;
            }

            $matches[] = trim(substr($string, $stringStart, $stringEnd - $stringStart));
            $startFrom = $stringEnd + $endDelimiterLength;
        }

        return $matches;
    }
}