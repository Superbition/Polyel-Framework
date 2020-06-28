<?php

namespace Polyel\Auth\Protectors;

use Polyel\Session\Session;
use Polyel\Auth\SourceDrivers\Database;

class SessionProtector
{
    private $session;

    private $users;

    public function __construct(Database $users, Session $session)
    {
        $this->session = $session;
        $this->users = $users;
    }
}