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
            if($error = $this->additionalVerification($request))
            {
                // TODO: Decide what to do with the returned error
            }

            if($response = $this->verificationFailed($request))
            {
                return $response;
            }

            return $request->expectsJson()
                ? response('', 403)
                : redirect('/email/verify');
        }
    }
}