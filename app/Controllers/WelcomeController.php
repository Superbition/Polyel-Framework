<?php

namespace App\Controllers;

class WelcomeController extends Controller
{
    public function __construct()
    {

    }

    public function welcome()
    {
        return response(view('welcome:view'));
    }
}