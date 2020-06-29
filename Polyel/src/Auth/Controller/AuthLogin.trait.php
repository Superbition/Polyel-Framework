<?php

namespace Polyel\Auth\Controller;

use Polyel\Http\Request;

trait AuthLogin
{
    public function displayLoginView()
    {
        return response(view('auth.login:view'));
    }

    public function login(Request $request)
    {

    }

    private function attemptLogin()
    {

    }

    private function loginSuccessful()
    {

    }

    private function loginNotSuccessful()
    {

    }

    private function logout()
    {

    }
}