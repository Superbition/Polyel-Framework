<?php

namespace Polyel\Auth\Controller;

use Polyel\Http\Request;
use Polyel\Database\Facade\DB;
use Polyel\Auth\Drivers\Database;

trait AuthForgotPassword
{
    public function displayForgotPasswordView()
    {
        return response(view('auth.forgotPassword:view'));
    }

    public function sendPasswordResetEmail(Request $request, Database $users)
    {
        $data = $request->validate($this->validation());

        // Try to find the user by the provided email
        $user = $users->getUserByCredentials($this->credentials($data));

        // If the user exists, we continue validating the request further...
        if(exists($user))
        {
            // Create as unique password reset token, 64 bytes turns into a 128 hex string
            $resetToken = bin2hex(random_bytes(64));

            $email = $user->get('email');

            $resetConfig = $this->getPasswordResetConfig();

            // Stop tokens from being re-created too often...
            if($this->tokenRecentlyCreated($email, $resetConfig['table'], $resetConfig['timeout']))
            {
                return redirect('/password/reset')->withErrors([
                        'throttle' => 'You can only request a password reset every ' . $resetConfig['timeout'] . ' minutes']);
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
        }

        // TODO: Send back flash msg that password reset was sent/or not...
        // If the email provided links to an account, a password reset email will be sent
    }

    private function credentials(array $data)
    {
        return ['email' => $data['email']];
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

    public function getPasswordResetConfig($config = null)
    {
        if(is_null($config))
        {
            $defaultResetKey = config('auth.defaults.reset');
            $config = config("auth.resets.passwords.$defaultResetKey");
        }
        else
        {
            $config = config("auth.resets.passwords.$config");
        }

        return $config;
    }
}