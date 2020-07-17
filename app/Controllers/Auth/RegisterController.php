<?php

namespace App\Controllers\Auth;

use App\Models\User;
use Polyel\Auth\AuthManager;
use App\Controllers\Controller;
use Polyel\Hashing\Facade\Hash;
use Polyel\Auth\Controller\AuthRegister;

class RegisterController extends Controller
{
    /*
    │------------------------------------------------------------------------------
    │ Register Controller
    │------------------------------------------------------------------------------
    │ This controller processes user registration requests, handling validation
    | and the creation of new users to your application. Most of the functionality
    | for user registration is already provided for you by the register trait
    | included with this controller. You may use the create and registered methods
    | to alter what happens during these events before the default Polyel outcome
    | is used if you don’t provide a response.
    │
    */

    use AuthRegister;

    private $auth;

    private $user;

    public function __construct(AuthManager $auth, User $user)
    {
        $this->auth = $auth;
        $this->user = $user;
    }

    public function validation()
    {

    }

    private function create(array $data)
    {
        return $this->user->create([
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => Hash::create($data['password']),
        ]);
    }

    private function registered($request, $id)
    {
        return redirect('/');
    }
}