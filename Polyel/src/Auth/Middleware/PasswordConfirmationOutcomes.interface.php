<?php

namespace Polyel\Auth\Middleware;

use Polyel\Http\Request;

interface PasswordConfirmationOutcomes
{
    public function passwordConfirmationRequired(Request $request);

    public function passwordConfirmationNotRequired(Request $request);
}