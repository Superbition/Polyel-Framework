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
        $this->users->setTable($source);
    }

    public function getResetConfig($config = null)
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

        $protector = config("auth.protectors.$requestType");

        return $this->protector($protector['driver'])->check();
    }

    public function user()
    {
        $requestType = $this->HttpKernel->request->type;

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

    public function generateApiClientId()
    {
        do {

            $clientId = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex(random_bytes(16)), 4));

        } while($this->users->doesApiClientIdExist($clientId) === true);


        return $clientId;
    }

    public function generateApiToken($saveToDatabase = true, $userId = null)
    {
        $token = bin2hex(random_bytes(64));
        $hash = hash_hmac('sha512', $token, Crypt::getEncryptionKey());
        $clientId = $this->generateApiClientId();

        $userId = $userId ?: $this->userId();

        if($saveToDatabase)
        {
            if($this->users->createNewApiToken($clientId, $hash, $userId) === 0)
            {
                return false;
            }
        }

        return [
            'clientId' => $clientId,
            'token' => $token,
            'hash' => $hash,
        ];
    }
}