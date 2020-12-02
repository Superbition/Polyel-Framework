<?php

namespace Polyel\Model;

use Polyel\Auth\AuthManager;
use Polyel\Auth\VerifiesUserEmail;

class User extends Model
{
    use VerifiesUserEmail;

    private $auth;

    public function __construct(AuthManager $auth)
    {
        $this->auth = $auth;
    }
}