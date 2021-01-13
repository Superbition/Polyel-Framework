<?php

namespace Polyel\View\Element;

use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class Element
{
    public function __construct()
    {

    }

    public function processElementsFor(&$mainResource, $elementTags, $HttpKernel)
    {
        if(exists($elementTags))
        {
            foreach($elementTags as $element)
            {
                $elementClass = $HttpKernel->container->resolveClass("App\View\Elements\\" . $element);

                $renderedElement = $elementClass->build();

                // If the build method returns false, it means we don't want this element in our final View
                if($renderedElement === false)
                {
                    // The element has returned without content, replace the element tag with nothing...
                    $renderedElement = "";
                }

                $mainResource = str_replace("{{ @addElement($element) }}", $renderedElement, $mainResource);
            }
        }
    }
}