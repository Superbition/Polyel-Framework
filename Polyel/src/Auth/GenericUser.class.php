<?php

namespace Polyel\Auth;

class GenericUser
{
    private $id;

    private $user;

    public function __construct(array $user)
    {
        $this->id = $user['id'];
        $this->user = array_shift($user);
    }

    public function get($key)
    {
        return $this->user[$key];
    }
}