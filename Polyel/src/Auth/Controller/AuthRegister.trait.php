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

    }
}