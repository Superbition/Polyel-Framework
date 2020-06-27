<?php

namespace App\Controllers\Auth;

use App\Controllers\Controller;
use Polyel\Auth\Controller\AuthLogin;

class LoginController extends Controller
{
    use AuthLogin;

    public function __construct()
    {

    }


}