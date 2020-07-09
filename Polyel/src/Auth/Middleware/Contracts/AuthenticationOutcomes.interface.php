<?php

namespace Polyel\Auth\Middleware\Contracts;

interface AuthenticationOutcomes
{
    public function unauthenticated();

    public function authenticated();
}