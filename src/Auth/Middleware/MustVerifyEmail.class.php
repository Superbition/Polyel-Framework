<?php

namespace Polyel\Auth\Middleware;

use Closure;
use App\Models\User;
use Polyel\Http\Request;
use Polyel\Auth\AuthManager as Auth;
use Polyel\Auth\Middleware\Contracts\VerificationOutcomes;

abstract class MustVerifyEmail implements VerificationOutcomes
{
    protected $auth;

    protected $user;

    public function __construct(Auth $auth, User $user)
    {
        $this->auth = $auth;
        $this->user = $user;
    }

    public function process(Request $request, Closure $nextMiddleware)
    {
        if($this->user->hasVerifiedEmail() === false)
        {
            if($response = $this->verificationFailed($request))
            {
                return $response;
            }

            return $request->expectsJson()
                ? response('', 403)
                : redirect('/email/verify');
        }

        return $nextMiddleware($request);
    }
}