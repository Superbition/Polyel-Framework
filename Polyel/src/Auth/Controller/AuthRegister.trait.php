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
        $id = $this->create($request->data());

        $this->auth->protector('session')->loginById($id);

        if($response = $this->registered($id))
        {
            return $response;
        }

        return response('', 201);
    }
}