<?php

namespace Polyel\Auth\Middleware\Contracts;

use Polyel\Http\Request;

interface PasswordConfirmationOutcomes
{
    public function passwordConfirmationRequired(Request $request);

    public function passwordConfirmationNotRequired(Request $request);
}