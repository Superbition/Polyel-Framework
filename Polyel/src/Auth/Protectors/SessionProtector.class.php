<?php

namespace Polyel\Auth\Protectors;

use Polyel\Session\Session;

class SessionProtector
{
    private $session;

    public function __construct(Session $session)
    {
        $this->session = $session;
    }
}