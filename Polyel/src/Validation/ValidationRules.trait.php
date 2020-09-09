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

        // Check if the date parameter is a name for another field
        if($otherFieldValue = $this->getValue($parameters[0]))
        {
            // If so get the value from the other field
            $parameters[0] = $otherFieldValue;
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

                case '===':
                    return $firstDate === $secondDate;
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

    protected function validateDateEquals($field, $value, $parameters)
    {
        return $this->dateComparison($field, $value, $parameters, '===');
    }

    protected function validateDistinctFrom($field, $value, $parameters)
    {
        foreach($parameters as $parameter)
        {
            $other = $this->getValue($parameter) ?? $parameter;

            if($value === $other)
            {
                return false;
            }
        }

        return true;
    }

    protected function validateDigits($field, $value, $parameters)
    {
        return !preg_match('/\D/', $value) && strlen((string) $value) == $parameters[0];
    }

    protected function validateDigitsBetween($field, $value, $parameters)
    {
        $length = strlen((string) $value);

        return !preg_match('/\D/', $value) && $length >= $parameters[0] && $length <= $parameters[1];
    }

    protected function validateDimensions($field, $value, $parameters)
    {
        // Making sure we have a valid uploaded file
        if($value instanceof UploadedFile && $value->isValid() === false)
        {
            return false;
        }

        if(in_array($value->getMimeType(), ['image/svg+xml', 'image/svg']))
        {
            return true;
        }

        // Make sure we can get the image dimensions
        if(!$dimensions = getimagesize($value->fullPath()))
        {
            return false;
        }

        [$width, $height] = $dimensions;

        // Convert named parameters where parameters are the array index with their values...
        $parameters = $this->parseNamedParameters($parameters);

        // Perform a image dimensions check based on the named parameters
        if($this->imageFailsDimensionChecks($parameters, $width, $height))
        {
            return false;
        }

        return true;
    }

    protected function imageFailsDimensionChecks($dimensions, $imgWidth, $imgHeight)
    {
        return (isset($dimensions['width']) && $dimensions['width'] != $imgWidth) ||
               (isset($dimensions['minWidth']) && $dimensions['minWidth'] > $imgWidth) ||
               (isset($dimensions['maxWidth']) && $dimensions['maxWidth'] < $imgWidth) ||
               (isset($dimensions['height']) && $dimensions['height'] != $imgHeight) ||
               (isset($dimensions['minHeight']) && $dimensions['minHeight'] > $imgHeight) ||
               (isset($dimensions['maxHeight']) && $dimensions['maxHeight'] < $imgHeight);
    }

    protected function parseNamedParameters(array $parameters)
    {
        $parametersParsed = [];

        // Converts named parameters to be used as the array index with their values
        foreach($parameters as $parameter)
        {
            $parameter = explode('=', $parameter);

            $parametersParsed[$parameter[0]] = $parameter[1];
        }

        return $parametersParsed;
    }

    protected function validateUniqueArray($field, $value, $parameters)
    {
        // Get the original field name, so person.luke.email would become person.*.email etc.
        $originalFieldName = $this->getOriginalField($field);

        // Based on the original field name, get all the data related to that field
        $data = $this->getUniqueArrayValues($originalFieldName);

        // We don't want to validate data against the actual field we are checking...
        unset($data[$field]);

        if(in_array('IgnoreCase', $parameters))
        {
            // Use grep to perform a case insensitive check
            return empty(preg_grep('/^'.preg_quote($value, '/').'$/iu', $data));
        }

        // Check if there are any duplicate values within the data array...
        return !in_array($value, array_values($data));
    }

    protected function getUniqueArrayValues($originalFieldName)
    {
        // If the data has not already previously been checked, we need to gather it...
        if(!array_key_exists($originalFieldName, $this->uniqueArrayValueCache))
        {
            /*
             * The leading data path is the path before the wildcard, so job.name.*.id would give job.name
             * This means we don't have to bother searching through extra data to get to our desired array
             * level.
             */
            $leadingFieldDataPath = rtrim(explode('*', $originalFieldName)[0], '.') ?: null;

            // Based on the leading data path, get a flattered version of the data array
            $flatteredFieldData = $this->flatternData($this->getValue($leadingFieldDataPath), $leadingFieldDataPath . '.');

            // Prepare the pattern to search for matching keys which match the wildcard field name
            $fieldNamePattern = str_replace('\*', '[^.]+', preg_quote($originalFieldName, '#'));

            $results = [];

            foreach($flatteredFieldData as $key => $value)
            {
                /*
                 * If a match is found, we add that to our results array as
                 * it will be apart of the wildcard field name related data we
                 * want to check for duplicate values... The # delimiter is used
                 * just in case the ignore case parameter is set and that the
                 */
                if(preg_match('#^' . $fieldNamePattern . '\z#u', $key))
                {
                    $results[$key] = $value;
                }
            }

            // Add the built up data results to the cache, so we don't have to gather the data again
            $this->uniqueArrayValueCache[$originalFieldName] = $results;
        }

        // Return the cached unique array data from previous unique validations...
        return $this->uniqueArrayValueCache[$originalFieldName];
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