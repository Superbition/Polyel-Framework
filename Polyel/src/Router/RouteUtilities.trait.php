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

    /*
     * Used to shift all parameter routes to the end of the their array, vertically.
     * This is done because it means each route parameter will be processed last, giving static
     * routes the chance to have priority as they define how unique a route can be.
     */
    private function shiftAllParamsToTheEnd(&$routes)
    {
        // Loop through the array vertically
        foreach($routes as $routeKey => $routeValue)
        {
            /*
               If the key is a parameter and isn't the last element in the array.
               If it's the last element it doesn't need to be moved to the end.
             */
            if(preg_match_all("/(\{[a-zA-Z_0-9]*\})/", $routeKey) && array_key_last($routes) !== $routeKey)
            {
                // Remove the parameter from the array
                unset($routes[$routeKey]);

                // Then, re-add the parameter to the back of the array
                $routes[$routeKey] = $routeValue;

                // Remove the empty space in the array
                $routes = array_filter($routes);

                // If the value is an array
                if(is_array($routeValue))
                {
                    // Call the function on itself again to process the value and go to the next level...
                    $this->shiftAllParamsToTheEnd($routeValue);
                }
            }
            else
            {
                /*
                 * Else the value is static or the parameter is already at the end of the array
                 *
                 * If the value is an array, move on to the next level...
                 */
                if(is_array($routeValue))
                {
                    // Call the function on itself again to process the value, moving on to the next level...
                    $this->shiftAllParamsToTheEnd($routeValue);
                }
            }
        }
    }
}