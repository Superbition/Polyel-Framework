<?php

namespace Polyel\Auth;

use RuntimeException;
use Polyel\Auth\Drivers\Database;
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

    public function check($protector = 'web')
    {
        $protector = config("auth.protectors.$protector");

        return $this->protector($protector['driver'])->check();
    }

    public function user($protector = 'web')
    {
        $protector = config("auth.protectors.$protector");

        if($this->check())
        {
            return $this->protector($protector['driver'])->user();
        }

        return false;
    }

    public function userId($protector = 'web')
    {
        $user = $this->user($protector);

        if($user instanceof GenericUser)
        {
            return $user->get('id');
        }

        return false;
    }
}