<?php

namespace Polyel\Auth;

class GenericUser
{
    private $id;

    private $user;

    public function __construct(array $user)
    {
        $this->user = $user;
    }

    public function get($key)
    {
        return $this->user[$key];
    }
}