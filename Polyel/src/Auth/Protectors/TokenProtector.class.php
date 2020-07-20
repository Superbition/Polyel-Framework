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

    // The users we have access to in the database
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

        $credentials = $this->getApiCredentials($request);

        if(is_array($credentials) && $this->attemptTokenAuthentication($credentials))
        {
            return true;
        }

        return false;
    }

    private function getApiCredentials(Request $request)
    {
        if($request->method === 'GET')
        {
            // Try to find the client ID and API token from either query parameters or headers
            $clientId = $request->query('client_id') ?: $request->headers('ClientID');
            $authorization = $request->query('api_token') ?: $request->headers('Authorization');

            // Add on the auth type when using query parameters to send in the API token
            if(strpos($authorization, 'Bearer') !== 0 && exists($request->query('api_token')))
            {
                $authorization = 'Bearer ' . $authorization;
            }

            if(exists($clientId) && exists($authorization))
            {
                return ['ClientID' => $clientId, 'Authorization' => $authorization];
            }
        }

        if(in_array($request->method, ['POST', 'PUT', 'PATCH', 'DELETE']))
        {
            // Try and find the client ID and API token from the request body or headers
            $clientId = $request->data('client_id') ?: $request->headers('ClientID');
            $authorization = $request->data('api_token') ?: $request->headers('Authorization');

            // Add the auth type to the start of the token only when using the request body to set the token
            if(strpos($authorization, 'Bearer') !== 0 && exists($request->data('api_token')))
            {
                $authorization = 'Bearer ' . $authorization;
            }

            if(exists($clientId) && exists($authorization))
            {
                return ['ClientID' => $clientId, 'Authorization' => $authorization];
            }
        }

        return null;
    }

    public function attemptTokenAuthentication(array $credentials, array $conditions  = null)
    {
        if(array_key_exists('ClientID', $credentials) && array_key_exists('Authorization', $credentials))
        {
            $user = $this->users->getUserByToken($credentials['ClientID'], $conditions);

            if($user instanceof GenericUser)
            {
                $this->user = $user;

                $tokenExpirationDate = $this->user->get('token_expires_at');

                if($this->hasValidApiToken($this->user, $credentials['Authorization']) && $this->tokenHasNotExpired($tokenExpirationDate))
                {
                    $this->users->updateWhenTokenWasLastActive($credentials['ClientID']);

                    return true;
                }
            }
        }

        return false;
    }

    public function hasValidApiToken(GenericUser $user, string $authorization)
    {
        // Split the bearer type and token
        $bearerToken  = explode(' ', $authorization);

        // Two elements means we have Bearer + Token
        if(count($bearerToken) !== 2)
        {
            return false;
        }

        // Check that the auth type is Bearer token
        if($bearerToken[0] !== 'Bearer')
        {
            return false;
        }

        // Get the token from the Bearer token
        $authorization = $bearerToken[1];

        // A valid token will be 160 in length
        if(strlen($authorization) !== 160)
        {
            return false;
        }

        $authorization = hash_hmac('sha512', $authorization, Crypt::getEncryptionKey());

        return hash_equals($authorization, $user->get('token_hashed'));
    }

    private function tokenHasNotExpired($tokenExpirationDate)
    {
        return time() < strtotime($tokenExpirationDate);
    }
}