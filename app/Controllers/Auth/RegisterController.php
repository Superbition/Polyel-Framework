<?php

namespace App\Controllers\Auth;

use App\Models\User;
use Polyel\Auth\AuthManager;
use App\Controllers\Controller;
use Polyel\Hashing\Facade\Hash;
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