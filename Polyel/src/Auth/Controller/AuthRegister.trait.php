<?php

namespace Polyel\Auth\Controller;

use Polyel\Http\Request;

trait AuthRegister
{
    public function displayRegistrationView()
    {
        return response(view('auth.register:view'));
    }

    public function register(Request $request)
    {
        // Let the main Register method actually create the user, just pass the request data over
        $id = $this->create($request->data());

        // Login the newly created user by their ID
        $this->auth->protector('session')->loginById($id);

        /*
         * Once the user is created and logged in, run the completed registration
         * function and get the response is one is returned, if one is we use the devs
         * provided response.
         */
        if($response = $this->registered($request, $id))
        {
            return $response;
        }

        // If no response is provided, we send back a normal 201 response to indicate a user was created
        return response('', 201);
    }
}