<?php

namespace Polyel\Router;

trait RouteUtilities
{
    // Pack a route into a single multidimensional array making it easier to handle parameters
    private function packRoute($routeToPack, $finalValue)
    {
        /*
         * Split the route into segments based on a '/'
         * The URL is split into segments so route params can be processed.
         * array_filter is used to remove the first empty '/'
         * array_values is used to reindex the array back to 0 from previous step
         * array_pack, a Polyel helper, is used to pack the segments into one single multidimensional array
         * The outcome of array_pack is for example: /blog/user/{post_id} = controller@Action
         */
        $routeToPack = explode("/", $routeToPack);
        $routeToPack = array_filter($routeToPack);
        $routeToPack = array_values($routeToPack);
        $routeToPack = array_pack($routeToPack, $finalValue);

        return $routeToPack;
    }
}