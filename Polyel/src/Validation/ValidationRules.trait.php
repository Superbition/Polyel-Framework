<?php

namespace Polyel\Validation;

use Spoofchecker;
use Polyel\Http\File\UploadedFile;

trait ValidationRules
{
    protected function validateAccepted($field, $value)
    {
        return $this->validateRequired($field, $value) &&
            in_array($value, ['yes', 'on', '1', 1, true, 'true'], true);
    }

    protected function validateActiveUrl($field, $value)
    {
        if(!is_string($value))
        {
            return false;
        }

        if(\Swoole\Coroutine\System::gethostbyname($value, AF_INET, 1))
        {
            return true;
        }

        if(\Swoole\Coroutine\System::gethostbyname($value, AF_INET6, 1))
        {
            return true;
        }

        return false;
    }

    protected function validateEmail($field, $value, $parameters)
    {
        if(!is_string($value) && empty($value))
        {
            return false;
        }

        if(filter_var($value, FILTER_VALIDATE_EMAIL) === false)
        {
            return false;
        }

        if(in_array('dns', $parameters))
        {
            if(checkdnsrr(explode('@', $value)[1], 'MX') === false)
            {
                return false;
            }
        }

        if(in_array('spoof', $parameters))
        {
            $spoofChecker = new Spoofchecker();
            $spoofChecker->setChecks(Spoofchecker::SINGLE_SCRIPT);

            if($spoofChecker->isSuspicious($value))
            {
                return false;
            }
        }

        return true;
    }

    protected function validateRequired($field, $value)
    {
        if(is_null($value))
        {
            return false;
        }
        else if(is_string($value) && trim($value) === '')
        {
            return false;
        }
        else if((is_array($value) || is_countable($value)) && count($value) < 1)
        {
            return false;
        }
        else if($value instanceof UploadedFile)
        {
            return (string) $value->path() !== '';
        }

        return true;
    }

    protected function validateRequiredWithAny($field, $value, $parameters)
    {
        if($this->allParametersFailBeingRequired($parameters) === false)
        {
            return $this->validateRequired($field, $value);
        }

        return true;
    }

    protected function allParametersFailBeingRequired(array $parameters)
    {
        foreach($parameters as $parameter)
        {
            if($this->validateRequired($parameter, $this->getValue($parameter)))
            {
                return false;
            }
        }

        return true;
    }

    public function validateString($field, $value)
    {
        return is_string($value);
    }
}