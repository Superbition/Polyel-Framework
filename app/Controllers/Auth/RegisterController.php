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

    /*
     * Setup the rules which validate the
     * data provided during registration.
     */
    public function validation()
    {
        return [
            'Username' => ['Break:rule', 'Required', 'String', 'Between:3,32'],
            'Email' => ['Break:rule', 'Required', 'Email'],
            'Password' => ['Break:rule', 'Required', 'Confirmed', 'Min:6'],
        ];
    }

    /*
     * Once the registration data is valid, we can
     * create the new user and store their details in
     * the database.
     */
    private function create(array $data)
    {
        return $this->user->create([
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => Hash::create($data['password']),
        ]);
    }

    /*
     * A user has now been successfully created, here
     * we decide what to do once a new user has been
     * created. By default if JSON is expected then a
     * 201 response is sent back or the user is redirected
     * to the index route. But you may provide your own
     * custom redirect or response.
     */
    private function registered($request, $id)
    {
        // TODO: Send verification email here after user reg?
    }
}