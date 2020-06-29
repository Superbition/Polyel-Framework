<?php

namespace App\Controllers\Auth;

use Polyel\Auth\AuthManager;
use App\Controllers\Controller;
use Polyel\Auth\Controller\AuthRegister;

class RegisterController extends Controller
{
    use AuthRegister;

    private $auth;

    private $user;

    public function __construct(AuthManager $auth, User $user)
    {
        $this->auth = $auth;
        $this->user = $user;
    }

    private function create()
    {

    }

    private function registered($id)
    {
        return redirect('/');
    }
}