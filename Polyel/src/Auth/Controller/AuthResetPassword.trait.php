<?php

namespace Polyel\Auth\Controller;

use Polyel\Http\Request;
use Polyel\Database\Facade\DB;
use Polyel\Hashing\Facade\Hash;

trait AuthResetPassword
{
    public function displayPasswordResetView($token)
    {
        return response(view('auth.resetPassword:view', ['token' => $token]));
    }

    public function resetPassword(Request $request)
    {
        // TODO: Validate reset data here

        // Get the email, new password and token from the request
        $credentials = $this->credentials($request);

        $resetConfig = $this->auth->getResetConfig();

        // See if a reset exists for the given email...
        $reset = $this->getResetFromDatabase($credentials['email'], $resetConfig['table']);

        // If a reset actually exists for the given email, we continue to validate the reset request
        if(exists($reset))
        {
            $tokenTimeout = $resetConfig['expire'];
            $tokenCreatedAt = strtotime($reset['created_at']);

            // Check to see if the token has not expired yet, that it is within the time limit
            if((time() - $tokenCreatedAt) < $tokenTimeout * 60)
            {
                if($this->tokensMatch($credentials['token'], $reset['token']))
                {
                    $this->changeUserPassword($credentials['email'], $credentials['password'], $resetConfig['source']);

                    $this->deleteToken($credentials['email'], $resetConfig['table']);

                    return $this->sendSuccessfulResetResponse('Your password has been reset, please login');
                }
                else
                {
                    $error = 'Invalid reset token';
                }
            }
            else
            {
                $error = 'Password reset has expired already';
            }
        }
        else
        {
            $error = 'Incorrect email address';
        }

        return $this->sendFailedResetResponse($credentials['token'], $error);
    }

    private function credentials(Request $request)
    {
        return [
            'email' => $request->data('email'),
            'password' => $request->data('password'),
            'token' => $request->data('token'),
        ];
    }

    private function getResetFromDatabase($email, $table)
    {
        return DB::table($table)->where('email', '=', $email)->first();
    }

    private function tokensMatch($token1, $token2)
    {
        return hash_equals($token1, $token2);
    }

    private function changeUserPassword($email, $newPassword, $table)
    {
        $newPassword = Hash::create($newPassword);

        DB::table($table)->where('email', '=', $email)
            ->update(['password' => $newPassword]);
    }

    private function deleteToken($email, $table)
    {
        DB::table($table)->where('email', '=', $email)->delete();
    }

    private function sendFailedResetResponse($token, $error)
    {
        return redirect('/password/reset/' . $token);
    }

    private function sendSuccessfulResetResponse($message)
    {
        return redirect('/login');
    }
}