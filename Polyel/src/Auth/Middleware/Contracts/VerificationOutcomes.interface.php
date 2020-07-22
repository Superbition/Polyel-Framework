<?php

namespace Polyel\Auth\Middleware\Contracts;

use Polyel\Http\Request;

interface VerificationOutcomes
{
    public function additionalVerification(Request $request);

    public function verificationFailed(Request $request);
}