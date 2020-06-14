<?php

namespace App\Controllers;

class WelcomeController extends Controller
{
    public function welcome()
    {
        return response(view('welcome:view'));
    }
}