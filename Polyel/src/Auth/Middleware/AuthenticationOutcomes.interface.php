<?php

namespace Polyel\Auth\Middleware;

interface AuthenticationOutcomes
{
    public function unauthenticated();

    public function authenticated();
}