<?php

namespace Polyel\Auth\Protectors;

use Polyel\Http\Request;
use Polyel\Auth\GenericUser;
use Polyel\Auth\Drivers\Database;
use Polyel\Encryption\Facade\Crypt;

class TokenProtector
{
    // The authenticated user during a request cycle
    private $user;

    // THe users we have access to in the database
    private $users;

    public function __construct(Database $users)
    {
        $this->users = $users;
    }

    public function user()
    {
        if($this->user instanceof GenericUser)
        {
            return $this->user;
        }

        return false;
    }

    public function check(Request $request = null)
    {
        if($this->user instanceof GenericUser)
        {
            return true;
        }
        else if(is_null($request))
        {
            return false;
        }

        $clientId = $request->data('ClientID') ?: $request->headers('ClientID');
        $authorization = $request->headers('Authorization');

        if($this->attemptTokenAuthentication($clientId, $authorization))
        {
            return true;
        }

        return false;
    }

    public function attemptTokenAuthentication(string $clientId, string $authorization, array $conditions  = null)
    {
        if(exists($clientId))
        {
            $user = $this->users->getUserByToken($clientId, $conditions);

            if($user instanceof GenericUser)
            {
                $this->user = $user;

                if($this->hasValidApiToken($this->user, $authorization))
                {
                    $this->users->updateWhenTokenWasLastActive($clientId);

                    return true;
                }
            }
        }

        return false;
    }

    public function hasValidApiToken(GenericUser $user, string $authorization)
    {
        $authorization = hash_hmac('sha512', $authorization, Crypt::getEncryptionKey());

        return hash_equals($authorization, $user->get('token_hashed'));
    }
}