<?php

namespace Polyel\Http\Middleware;

use Closure;
use Polyel\Http\Request;
use Polyel\Session\Session;

class CsrfTokenVerifier
{
    /*
     * URIs that shall be excluded from CSRF Token verification.
     * Set within the Middleware at the App level.
     */
    protected array $except = [];

    // The session service from the request
    private $session;

    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    /*
     * The main Middleware process function which runs before the App
     */
    public function process(Request $request, Closure $nextMiddleware)
    {
        /*
         * First check the request is not a HTTP read verb and that the URI is not in the exception list.
         * If the request is not a read and the URI is not excluded, continue to validate the CSRF Token.
         */
        if($this->isNotReading($request) && $this->notInExceptList($request))
        {
            // Validate if the token from the session and request match-up...
            if($this->tokensMatch($request) === false)
            {
                // Tokens do not match-up from session and request, return a 401 error
                return response(view('401:error'), 401);
            }
        }

        return $nextMiddleware($request);
    }

    /*
     * Used to check if the request method is not a read verb.
     *
     * CSRF protection only runs on POST, PUT, PATCH and DELETE
     */
    protected function isNotReading($request): bool
    {
        // Check that the request method is not using a read verb
        if(in_array($request->method, ['HEAD', 'GET', 'OPTIONS']))
        {
            // The request is using a read verb, so it is reading...
            return false;
        }

        // The request is NOT reading...
        return true;
    }

    /*
     * Check if the request URI is not part of the excluded list of URIs
     * that are exempt from CSRF Protection verification.
     */
    protected function notInExceptList($request): bool
    {
        // Process each URI exception pattern...
        foreach($this->except as $except)
        {
            // Check for a static route match
            if($except === $request->uri)
            {
                // The URI is a static match, so its part of the excluded list
                return false;
            }


            // Escape regex special chars and the # because we use it as a start and end char
            $except = preg_quote($except, '#');

            // Convert asterisks to zero or more regex wildcards
            $except = str_replace('\*', '.*', $except);

            // Using regex, check for a pattern based on the $except
            if(preg_match('#^' . $except . '\z#u', $request->uri) === 1)
            {
                /*
                 * The URI is a pattern match, so its part of the excluded list
                 * For example: /route/* or /no-csrf/route/* etc.
                 */
                return false;
            }
        }

        // The URI is NOT part of the excluded CSRF list
        return true;
    }

    /*
     * Get the CSRF token from the request
     */
    protected function getTokenFromRequest($request)
    {
        // The token comes from the POST data or from the request headers.
        return $request->data('csrf_token') ?: $request->headers('X-CSRF-TOKEN');
    }

    /*
     * Process each token from the session and request to check them match or not
     */
    protected function tokensMatch($request)
    {
        // Get the token from the rquest...
        $token = $this->getTokenFromRequest($request);

        // Make sure the token is not null
        if(exists($token))
        {
            // Check the tokens match up or not
            if(hash_equals($this->session->get('CSRF-TOKEN'), $token))
            {
                return true;
            }
        }

        return false;
    }
}