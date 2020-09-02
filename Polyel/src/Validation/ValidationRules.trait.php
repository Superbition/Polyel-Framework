<?php

namespace Polyel\Validation;

use DateTime;
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

                case '<':
                    return $firstDate < $secondDate;
                break;

                case '<=':
                    return $firstDate <= $secondDate;
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

        return false;
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

    protected function validateArray($field, $value)
    {
        return is_array($value);
    }

    protected function validateBreak()
    {
        // Always return true, allowing us to just use Break as a rule
        return true;
    }

    protected function validateBefore($field, $value, $parameters)
    {
        return $this->dateComparison($field, $value, $parameters, '<');
    }

    protected function validateBeforeOrEqual($field, $value, $parameters)
    {
        return $this->dateComparison($field, $value, $parameters, '<=');
    }

    protected function validateBetween($field, $value, $parameters)
    {
        $size = $this->getFieldSize($field, $value);

        if($size !== false)
        {
            return $size >= $parameters[0] && $size <= $parameters[1];
        }

        return false;
    }

    protected function getFieldSize($field, $value)
    {
        if(is_numeric($value) && $this->hasRule($field, $this->numericRules))
        {
            $this->lastSizeType = 'Numeric';
            return $value;
        }
        else if(is_array($value))
        {
            $this->lastSizeType = 'Array';
            return count($value);
        }
        else if($value instanceof UploadedFile)
        {
            $this->lastSizeType = 'File';
            return $value->getSize() / 1024;
        }
        else if(is_string($value))
        {
            $this->lastSizeType = 'String';
            return mb_strlen($value);
        }

        return false;
    }

    protected function validateBool($field, $value)
    {
        return in_array($value, [true, false, 'true', 'false', 0, 1, '0', '1'], true);
    }

    protected function validateConfirmed($field, $value)
    {
        $otherField = $this->getValue("${field}_confirmed");

        return $this->validateMatch($field, $value, [$otherField]);
    }

    protected function validateMatch($field, $value, $parameters)
    {
        return $value === $parameters[0];
    }

    protected function validateDate($field, $value)
    {
        if((!is_string($value) && !is_numeric($value)) || strtotime($value) === false)
        {
            return false;
        }

        $date = date_parse($value);

        return checkdate($date['month'], $date['day'], $date['year']);
    }

    protected function validateDateFormat($field, $value, $parameters)
    {
        $dateFormat = $parameters[0];

        $date = DateTime::createFromFormat('!' . $dateFormat, $value);

        return $date && $date->format($dateFormat) === $value;
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

    protected function validateNumeric($field, $value)
    {
        return is_numeric($value);
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
            if($this->validateRequired(null, $parameter))
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