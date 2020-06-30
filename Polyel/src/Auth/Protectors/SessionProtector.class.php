<?php

namespace Polyel\Auth\Protectors;

use Polyel\Http\Request;
use Polyel\Session\Session;
use Polyel\Auth\AuthenticatedUser;
use Polyel\Auth\SourceDrivers\Database;

class SessionProtector
{
    private $session;

    // Where the authenticated user is stored during a request
    private $user;

    private $users;

    public function __construct(Database $users, Session $session)
    {
        $this->session = $session;
        $this->users = $users;
    }

    public function user()
    {
        if($this->user instanceof AuthenticatedUser)
        {
            return $this->user;
        }

        return false;
    }

    public function check(Request $request)
    {
        // Get the user ID from the session system
        $user = $this->session->user();

        // Make sure the user ID exists in the session
        if(!is_null($user))
        {
            // Load the user if not already loaded for this request...
            if(!exists($this->user))
            {
                // Called when the user has not yet been loaded but has a ID in the session
                $this->user = $this->load($user);
            }

            // If an AuthenticatedUser exists, it means we have a valid authenticated user
            if($this->user instanceof AuthenticatedUser)
            {
                return true;
            }
        }

        // Invalid, the user is not properly authenticated...
        return false;
    }

    private function load($user)
    {
        // Try and find the user by the ID from the session...
        $user = $this->users->getUserById($user);

        // If we find the user, create a new AuthenticatedUser instance
        if(exists($user))
        {
            // Return a newly authenticated user and their data...
            return new AuthenticatedUser($user);
        }

        // The user could not be found by their ID, invalid user, not authenticated...
        return false;
    }

    public function loginById($userId)
    {
        // Try and load the user from the database
        $this->user = $this->load($userId);

        // By performing this check, it also confirms the user was actually loaded using the ID
        if(exists($this->user))
        {
            // Now we can set the user ID inside the session, making them authenticated
            $this->session->setUser($userId);

            return true;
        }

        return false;
    }
}