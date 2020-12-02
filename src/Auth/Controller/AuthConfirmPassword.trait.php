<?php

namespace Polyel\Auth\Controller;

use Polyel\Http\Request;
use Polyel\Session\Session;
use Polyel\Auth\Drivers\Database;

trait AuthConfirmPassword
{
    public function displayConfirmView(Session $session)
    {
        // Return a 404 response if the password is not expected to be confirmed...
        if(!$session->exists('intendedConfirmURL'))
        {
            // The intendedConfirmURL has not been set, so we act like this page does not exist
            return response(view('404:error'));
        }

        return response(view('auth.confirmPassword:view'));
    }

    public function confirmPassword(Request $request, Session $session, Database $users)
    {
        if($this->attemptPasswordConfirmation($request, $session, $users))
        {
            // Password confirmation was successful, reset the timestamp in the session
            $this->resetPasswordConfirmationTimeout($session);

            // Redirect to the intended URL and remove it from the session because we've used it here
            return redirect($session->pull('intendedConfirmURL'));
        }

        return redirect('/password/confirm')->withErrors([
            'password' => 'Your password was incorrect']);
    }

    private function resetPasswordConfirmationTimeout(Session $session)
    {
        $session->store('lastPasswordConfirmation', time());
    }

    private function attemptPasswordConfirmation(Request $request, Session $session, Database $users)
    {
        $data = $request->validate($this->validation());

        $userId = $session->user();

        if(exists($userId))
        {
            $user = $users->getUserById($userId);

            $PasswordToConfirm['password'] = $data['password'];

            $confirmed = $this->auth->protector('session')->hasValidCredentials($user, $PasswordToConfirm);

            if($confirmed)
            {
                return true;
            }
        }

        return false;
    }
}