<?php

namespace Polyel\Auth\Middleware;

use App\Models\User;
use Polyel\Auth\AuthManager as Auth;
use Polyel\Auth\Middleware\Contracts\VerificationOutcomes;

abstract class MustVerifyEmail implements VerificationOutcomes
{
    public $middlewareType = "before";

    protected $auth;

    protected $user;

    public function __construct(Auth $auth, User $user)
    {
        $this->auth = $auth;
        $this->user = $user;
    }

    public function process($request)
    {
        if($this->user->hasVerifiedEmail() === false)
        {
            if($response = $this->additionalVerification($request))
            {
                return $response;
            }

            if($response = $this->verificationFailed($request))
            {
                return $response;
            }

            // TODO: If Json is expected return a 403: Your email address is not verified.

            // TODO: Add validation msg to the redirect here

            return redirect('/email/verify');
        }
    }
}