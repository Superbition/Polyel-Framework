<?php

namespace App\Http\Middleware;

use Polyel\Auth\Middleware\Authenticate as PolyelAuthenticationMiddleware;

class AuthenticateMiddleware extends PolyelAuthenticationMiddleware
{
    /*
     * Called when a web request is not authenticated, the user is not logged in
     */
    public function unauthenticated()
    {
        return redirect('/login');
    }

    /*
     * Called when a user is authenticated and is valid during a web request
     */
    public function authenticated()
    {
        // ...
    }

    /*
     * Called when an API request is not authorized due to invalid client ID or API token
     */
    public function unauthorized()
    {
        return response([
            'error' => [
                'status' => 401,
                'message' => 'Authorization failed'
            ]
        ], 401);
    }

    /*
     * Called when an API request has a valid client ID and API token
     */
    public function authorized()
    {
        // ...
    }
}