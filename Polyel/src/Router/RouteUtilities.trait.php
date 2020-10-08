<?php

namespace Polyel\Router;

trait RouteUtilities
{
    // The regex used to check if a route key is a parameter like {param}
    private $routeParamPattern = "/({[a-zA-Z_0-9]*})/";

    /*
     * Used to find and make a match with a registered route from Route::<method>
     * Processes static and dynamic route requests recursively and collects any URL route parameters to send back
     * once and if a route match is found.
     *
     * When no route match can be found, false is returned. This function expects an array of routes to be
     * passed in, only the actual routes from the method type of GET, POST etc. And with the requested route in
     * a segmented format.
     */
    private function matchRoute($routes, $requestedSegmentedRoute, $currentDepth = null, $maxDepth = null, $params = null, $regURL = "")
    {
        // Don't want to overwrite the depth variables...
        if(!isset($currentDepth) && !isset($maxDepth))
        {
            // Keeps track of the current depth when processing the segmented route
            $currentDepth = 1;
            $maxDepth = count($requestedSegmentedRoute);
        }

        // Don't want to overwrite the parameter collection array...
        if(!isset($params))
        {
            // Store collected parameters from the route URI
            $params = [];
        }

        // Loop through all the routes found in this level of the $routes array
        foreach($routes as $routeKey => $routeValue)
        {
            // Parameters have to be matched by their surrounding characters like {param}...
            $paramFound = false;
            if(preg_match($this->routeParamPattern, $routeKey))
            {
                /*
                 * If a parameter is found at the current depth, we set the paramFound flag to true
                 * Also collect the parameters so we can return them later, if a match is found
                 */
                $params[] = $requestedSegmentedRoute[$currentDepth - 1];
                $paramFound = true;
            }

            // If the route key matches the requested route key or if a parameter was found
            if($routeKey === $requestedSegmentedRoute[$currentDepth - 1] || $paramFound)
            {
                /*
                 * Build up the registered URL which gets sent back if a match is found
                 * Here we only add the route key if it is not the index route as we
                 * don't want to end up with '//' when matching the '/' index route
                 */
                $regURL .= "/" . (($routeKey !== '/') ? ($routeKey) : (''));

                // And if the current array depth matches the desired depth
                if($currentDepth === $maxDepth)
                {
                    // And the route is valid and has a value
                    if(isset($routeValue))
                    {
                        /*
                         * And if the route value is an array, it means multiple routes exist
                         * inside the same route tree at this level but a matching action will always be the
                         * first indexed array if the route does exist at this tree level.
                         */
                        if(is_array($routeValue))
                        {
                            /*
                             * And if the route value has a dead end route, which will always be at
                             * array level [0], it means that an route action is located here. But there
                             * are other routes within the same tree level.
                             */
                            if(isset($routeValue[0]))
                            {
                                // The first index will always be the route action
                                $routeAction = $routeValue[0];

                                // If [1] exists, it means the route is an API registered route...
                                if(isset($routeValue[1]) && $routeValue[1] === 'API')
                                {
                                    /*
                                     * So we attach that info to the route action, indicating
                                     * this is an API route, allowing the Router to know that it is
                                     * handling an API request for this matched route.
                                     */
                                    $routeAction = [$routeAction, 'API'];
                                }
                            }
                            else
                            {
                                // Else it does not, and the route doesn't exist
                                return false;
                            }
                        }
                        else
                        {
                            $routeAction = $routeValue;
                        }

                        /*
                         * Add an ending slash on the URI so that middleware
                         * routes will match with registered routes because
                         * not knowing if the end slash is there or not is
                         * ambiguous.
                         */
                        if($regURL[-1] !== '/')
                        {
                            $regURL .= '/';
                        }

                        /*
                         * Else the route value is not an array, because this is a single tree with no
                         * other routes at the same level, a match was found, return the action and parameters.
                         */
                        $routeMatched["action"] = $routeAction;
                        $routeMatched["params"] = $params;
                        $routeMatched["regURL"] = $regURL;
                        return $routeMatched;
                    }
                }
                else
                {
                    // Else the desired route depth has not been reached yet
                    if(is_array($routeValue))
                    {
                        /*
                         * Go through the next level of the array looking for the next element of requested route
                         * Passing through the depth levels and parameters if any were collected.
                         */
                        return $this->matchRoute($routeValue, $requestedSegmentedRoute, ++$currentDepth, $maxDepth, $params, $regURL);
                    }
                }
            }
        }

        /*
         * If the code reaches this stage, false is returned due to no other arrays being found, likely because the
         * requested route was deeper than the maximum depth of the routes array
         */
        return false;
    }

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
            if(preg_match_all($this->routeParamPattern, $routeKey) && array_key_last($routes) !== $routeKey)
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