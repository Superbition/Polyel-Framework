<?php

namespace Polyel\Auth\Contracts;

interface EmailVerification
{
    public function hasVerifiedEmail();

    public function markEmailAsVerified();
}