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

    protected function validateAfter($field, $value, $parameters)
    {
        return $this->dateComparison($field, $value, $parameters, '>');
    }

    protected function validateAfterOrEqual($field, $value, $parameters)
    {
        return $this->dateComparison($field, $value, $parameters, '>=');
    }

    protected function dateComparison($field, $value, $parameters, $operator)
    {
        if(!is_string($value) && !is_numeric($value))
        {
            return false;
        }

        $firstDate = $this->parseDate($value);
        $secondDate = $this->parseDate($parameters[0]);

        if(is_numeric($firstDate) && is_numeric($secondDate))
        {
            switch($operator)
            {
                case '>':
                    return $firstDate > $secondDate;
                break;

                case '>=':
                    return $firstDate >= $secondDate;
                break;
            }
        }

        return false;
    }

    protected function parseDate($value)
    {
        // Try by using the format given first to get a timestamp
        if($date = strtotime($value))
        {
            return $date;
        }

        // Convert for a valid European datetime format as strtotime uses - for dd/mm/yyy
        $date = str_replace('/', '-', $value);

        // Return the result for a European formatted datetime
        return strtotime($date);
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

    protected function validateAlpha($field, $value)
    {
        // Match any character from any language with unicode support
        return is_string($value) && preg_match('/^[\pL\pM]+$/u', $value);
    }

    protected function validateAlphaDash($field, $value)
    {
        // Match any character from any language with unicode support, dashes or underscores
        return is_string($value) && preg_match('/^[\pL\pM_-]+$/u', $value);
    }

    protected function validateAlphaNumeric($field, $value)
    {
        if(!is_string($value) && !is_numeric($value))
        {
            return false;
        }

        // More than 0 because it can be classed as true
        return preg_match('/^[\pL\pM\pN]+$/u', $value) > 0;
    }

    protected function validateAlphaNumericDash($field, $value)
    {
        if(!is_string($value) && !is_numeric($value))
        {
            return false;
        }

        // More than 0 because it can be classed as true
        return preg_match('/^[\pL\pM\pN_-]+$/u', $value) > 0;
    }

    protected function validateBreak()
    {
        // Always return true, allowing us to just use Break as a rule
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