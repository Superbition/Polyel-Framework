<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Polyel\Http\Request;
use Polyel\Auth\AuthManager;
use App\Http\Controllers\Controller;
use Polyel\Auth\Controller\AuthVerifyEmail;

class VerificationController extends Controller
{
    /*
    │------------------------------------------------------------------------------
    │ Verification Controller
    │------------------------------------------------------------------------------
    │ This controller is responsible for processing email verifications for when a
    | user needs to verify their email address after registration or altering
    | their email. The verification process is based on a signed URL which is
    | matched against the logged in user, their ID, their email and an expiration
    | timestamp, the expiration timeout can be changed in the auth config file.
    | A user may also request a verification email resend if they did not receive
    | the original email.
    │
    */

    use AuthVerifyEmail;

    /*
     * Controls where to redirect the user after their
     * email is successfully verified, default is the home page.
     */
    private $redirectTo = '/';

    private $auth;

    private $user;

    public function __construct(AuthManager $auth, User $user)
    {
        $this->auth = $auth;
        $this->user = $user;
    }

    /*
     * Called when a user is successfully verified, allows you to run other
     * tasks or return a custom response.
     */
    public function verified(Request $request)
    {
        // ...
    }
}