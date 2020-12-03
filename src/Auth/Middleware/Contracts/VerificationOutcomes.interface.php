<?php

namespace Polyel\Auth\Middleware\Contracts;

use Polyel\Http\Request;

interface VerificationOutcomes
{
    public function verificationFailed(Request $request);
}