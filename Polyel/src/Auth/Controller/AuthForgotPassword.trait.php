<?php

namespace Polyel\Auth\Controller;

use Polyel\Http\Request;
use Polyel\Database\Facade\DB;
use Polyel\Auth\SourceDrivers\Database;

trait AuthForgotPassword
{
    public function displayForgotPasswordView()
    {
        return response(view('auth.forgotpassword:view'));
    }

    public function sendPasswordResetEmail(Request $request, Database $users)
    {
        // TODO: Validate request email here

        // Try to find the user by the provided email
        $user = $users->getUserByCredentials($this->credentials($request));

        // If the user exists, we continue validating the request further...
        if(exists($user))
        {
            // Create as unique password reset token, 64 bytes turns into a 128 hex string
            $resetToken = bin2hex(random_bytes(64));

            $email = $user->get('email');

            $resetConfig = $this->auth->getResetConfig();

            // Stop tokens from being re-created too often...
            if($this->tokenRecentlyCreated($email, $resetConfig['table'], $resetConfig['timeout']))
            {
                // TODO: Return msg that token throttle is active still
                return;
            }
            else
            {
                // If the user is allowed to create another token, delete the existing one first
                $this->removeExistingToken($email, $resetConfig['table']);
            }

            // Save the password reset data to the database
            DB::table($resetConfig['table'])->insert([
               'email' => $user->get('email'),
               'token' => $resetToken,
               'created_at' => date("Y-m-d H:i:s"),
            ]);

            // TODO: Send reset email here
            // TODO: Send back msg that password reset was sent...
            return redirect('/password/reset');
        }
    }

    public function validateEmail(Request $request)
    {

    }

    private function credentials(Request $request)
    {
        return ['email' => $request->data('email')];
    }

    private function tokenRecentlyCreated(string $email, string $table, int $limit = 15)
    {
        $token = DB::table($table)->where('email', '=', $email)->first();

        if(exists($token))
        {
            $created_at = strtotime($token['created_at']);

            // Only allow the user to create another token if they have passed the limit, default is 15 minutes
            if((time() - $created_at) < $limit * 60)
            {
                return true;
            }
        }

        // The user has passed the token creation limit, allow them to create another...
        return false;
    }

    private function removeExistingToken(string $email, string $table)
    {
        DB::table($table)->where('email', '=', $email)->delete();
    }
}