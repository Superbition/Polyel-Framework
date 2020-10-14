<?php

namespace App\Http\Controllers;

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