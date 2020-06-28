<?php

namespace Polyel\Auth;

class AuthenticatedUser
{
    private $id;

    private $user;

    public function __construct(array $user)
    {
        $this->id = $user['id'];
        $this->user = array_shift($user);
    }

    public function getUserId()
    {
        return $this->id;
    }

    public function getData($key)
    {
        return $this->user[$key];
    }
}