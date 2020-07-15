<?php

namespace Polyel\Auth\Controller;

use Polyel\Http\Request;
use Polyel\Encryption\Facade\Crypt;

trait AuthVerifyEmail
{
    public function displayEmailVerificationView()
    {
        // Don't show the email verification view if they are already verified
        if($this->user->hasVerifiedEmail())
        {
            return redirect($this->redirectTo);
        }

        return response(view('auth.verification:view'));
    }

    public function verify(Request $request, $id, $hash, $expiration)
    {
        // The signed URL signature must be valid and the expiration still within the timeout...
        if($this->signatureIsValid($request) && $this->signatureHasNotExpired($expiration))
        {
            // Checks that the user is verifying against the same ID and email from the URL
            if($this->verificationIsNotForThisUser($id, $hash))
            {
                // TODO: Add error msg to request here
                return redirect('/email/verify');
            }

            // Redirect if the user has already got a verified email
            if($this->user->hasVerifiedEmail())
            {
                // TODO: Add a flash msg to say their email is already verified...
                return redirect($this->redirectTo);
            }

            // TODO: Add response error if email send fails for some reason
            if($this->user->markEmailAsVerified())
            {
                if($response = $this->verified($request))
                {
                    return $response;
                }

                // TODO: Add msg to say email was verified, banner, flash msg?
                return redirect($this->redirectTo);
            }
        }

        // TODO: Add error info to the actual request
        return redirect('/email/verify');
    }

    private function signatureIsValid(Request $request)
    {
        // Construct the path to build up the original URL to compare it against the signature
        $url = $request->path();

        // Recreate the original signature to compare it and check that it is valid
        $urlSignature = hash_hmac('sha256', $url, Crypt::getEncryptionKey());

        $originalSignature = $request->query('sig', '');

        return hash_equals($urlSignature, (string) $originalSignature);
    }

    private function signatureHasNotExpired($expiration)
    {
        $timeout = config('auth.verification_expiration');

        // Validate that the expiration is within the timeout limit in minutes
        return (time() - $expiration) < $timeout * 60;
    }

    private function verificationIsNotForThisUser($id, $hash)
    {
        // The ID from the URL must be the same as the current logged in user
        if(!hash_equals((string) $id, (string) $this->auth->userId()))
        {
            return true;
        }

        // The email from the URL must be the same as the current logged in user
        if(!hash_equals((string) $hash, sha1($this->auth->user()->get('email'))))
        {
            return true;
        }

        return false;
    }

    public function resendVerifyEmail(Request $request)
    {
        // Don't resend verification URLs if they are already verified
        if($this->user->hasVerifiedEmail())
        {
            // TODO: Add msg to indicate their email is already verified
            return redirect($this->redirectTo);
        }

        // TODO: Check for email errors
        $this->sendVerificationEmail($this->auth->user()->get('email'));

        // TODO: Add resend message here
        return redirect('/email/verify');
    }
}