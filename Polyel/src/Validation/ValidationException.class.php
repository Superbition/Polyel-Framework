<?php

namespace Polyel\Validation;

use Exception;
use Polyel\Session\Session;

class ValidationException extends Exception
{
    private $vlaidator;

    private $session;

    public function __construct(Validator $validator)
    {
        parent::__construct('The request data was invalid.');

        $this->vlaidator = $validator;
    }

    public function session(Session $session)
    {
        $this->session = $session;

        return $this;
    }

    public function response($status)
    {
        if($this->session instanceof Session)
        {
            return $this->sessionResponse($status);
        }

        return $this->jsonResponse($status);
    }

    protected function sessionResponse($status)
    {
        $invalidFields = $this->vlaidator->errors();

        $this->session->remove('errors');

        foreach($invalidFields as $field => $errors)
        {
            foreach($errors as $error)
            {
                $this->session->push("errors.$field", $error);
            }
        }

        return redirect($this->session->get('previousUrl'), $status);
    }

    protected function jsonResponse($status)
    {
        return response($this->errors(), $status);
    }

    public function errors()
    {
        return $this->vlaidator->errors();
    }
}