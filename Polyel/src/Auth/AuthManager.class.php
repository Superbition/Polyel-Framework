<?php

namespace Polyel\Auth;

use RuntimeException;
use Polyel\Auth\Drivers\Database;
use Polyel\Encryption\Facade\Crypt;
use Polyel\Auth\Protectors\TokenProtector;
use Polyel\Auth\Protectors\SessionProtector;

class AuthManager
{
    private $HttpKernel;

    private $protectors;

    private $users;

    public function __construct(Database $users, SessionProtector $sessionProtector, TokenProtector $tokenProtector)
    {
        $this->users = $users;

        $this->protectors['session'] = $sessionProtector;
        $this->protectors['token'] = $tokenProtector;
    }

    public function initialise($HttpKernel)
    {
        $this->HttpKernel = $HttpKernel;
    }

    public function setSource($source)
    {
        // The table where the users are located
        $this->users->setTable($source);
    }

    public function protector($protector)
    {
        if(array_key_exists($protector, $this->protectors))
        {
            return $this->protectors[$protector];
        }

        throw new RuntimeException('Invalid protector requested: ' . $protector);
    }

    public function check()
    {
        $requestType = $this->HttpKernel->request->type;

        // Gets the protector based on the request type of either web or api
        $protector = config("auth.protectors.$requestType");

        return $this->protector($protector['driver'])->check();
    }

    public function user()
    {
        $requestType = $this->HttpKernel->request->type;

        // Gets the protector based on the request type of either web or api
        $protector = config("auth.protectors.$requestType");

        if($this->check())
        {
            return $this->protector($protector['driver'])->user();
        }

        return false;
    }

    public function userId()
    {
        $user = $this->user();

        if($user instanceof GenericUser)
        {
            return $user->get('id');
        }

        return false;
    }

    public function logout()
    {
        $this->protector('session')->logout();
    }

    public function generateApiClientId()
    {
        do {

            $clientId = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex(random_bytes(16)), 4));

        } while($this->users->doesApiClientIdExist($clientId) === true);


        return $clientId;
    }

    public function generateApiToken($saveToDatabase = true, $userId = null)
    {
        $token = bin2hex(random_bytes(80));
        $hash = hash_hmac('sha512', $token, Crypt::getEncryptionKey());
        $clientId = $this->generateApiClientId();

        // Either use the passed in id or get the id from the current authed user through the protector in use
        $userId = $userId ?: $this->userId();

        // After creating a new API token, save the API user to the database
        if($saveToDatabase)
        {
            if($this->users->createNewApiToken($clientId, $hash, $userId) === 0)
            {
                // Failed while trying to save new API user to the database
                return false;
            }
        }

        return [
            'clientId' => $clientId,
            'token' => $token,
            'hash' => $hash,
        ];
    }

    public function generateApiTokenOnly()
    {
        $token = $this->generateApiToken(false);

        return $token['token'];
    }

    public function revokeApiToken($token)
    {
        $this->users->deleteApiToken($token);
    }

    public function revokeApiTokenUsingClientId($clientId)
    {
        $this->users->deleteApiTokenByClientId($clientId);
    }

    public function revokeAllApiTokens($userId = null)
    {
        if(is_null($userId))
        {
            $userId = $this->userId();
        }

        $this->users->deleteAllApiTokensByUserId($userId);
    }
}