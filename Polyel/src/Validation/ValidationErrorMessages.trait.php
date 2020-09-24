<?php

namespace Polyel\Validation;

use Polyel\View\ViewTools;

trait ValidationErrorMessages
{
    use ViewTools;

    private $errorMessages = [
        'Accepted' => '{field} must be accepted.',
        'ActiveURL' => '{field} is an invalid URL.',
        'After' => '{field} must be a date after {date}',
        'AfterOrEqual' => '{field} must be after or equal to {date}',
        'Alpha' => '{field} must be only alphabetical characters.',
        'AlphaDash' => '{field} must be only alphabetical characters with dashes or underscores.',
        'AlphaNumeric' => '{field} must be only alpha-numeric characters.',
        'AlphaNumericDash' => '{field} must be only alpha-numeric characters with dashes or underscores.',
        'Array' => '{field} must be an array.',
        'Before' => '{field} must be a date before {date}',
        'BeforeOrEqual' => '{field} must be a date before or equal to {date}',
        'Between' => [
            'Numeric' => '{field} must be between {min} and {max}',
            'Array' => '{field} must have between {min} and {max} items',
            'File' => '{field} must be between {min} and {max} kilobytes',
            'String' => '{field} must be between {min} and {max} characters',
        ],
        'Bool' => '{field} must be either true or false',
        'Confirmed' => '{field} confirmation does not match',
        'Match' => '{field} must be the same as {other}',
        'Date' => '{field} must be a valid date',
        'DateFormat' => '{field} must use the date format {format}',
        'DateEquals' => '{field} must be a date equal to {date}',
        'DistinctFrom' => '{field} must be different from {other}',
        'Digits' => '{field} must be exactly {digits} digits',
        'DigitsBetween' => '{field} must be between {min} and {max} digits',
        'Dimensions' => 'The {field} has invalid dimensions',
        'UniqueArray' => '{field} cannot have duplicate values',
        'Email' => 'Your {field} must be a valid email address.',
        'StartsWith' => '{field} must start with one of the following: {values}',
        'EndsWith' => '{field} must end with one of the following: {values}',
        'Exists' => '{field} does not exist',
        'Unique' => '{field} has already been taken',
        'File' => '{field} must be a valid file',
        'Populated' => '{field} must be populated when given',
        'GreaterThan' => [
            'Numeric' => '{field} must be greater than {value}',
            'Array' => '{field} must have more than {value} items',
            'File' => '{field} must be greater than {value} kilobytes',
            'String' => '{field} must be greater than {value} characters',
        ],
        'GreaterThanOrEqual' => [
            'Numeric' => '{field} must be more than or equal to {value}',
            'Array' => '{field} must have more than or equal to {value} items',
            'File' => '{field} must be more than or equal to {value} kilobytes',
            'String' => '{field} must be more than or equal to {value} characters',
        ],
        'LessThan' => [
            'Numeric' => '{field} must be less than {value}',
            'Array' => '{field} must have less than {value} items',
            'File' => '{field} must be less than {value} kilobytes',
            'String' => '{field} must be less than {value} characters',
        ],
        'LessThanOrEqual' => [
            'Numeric' => '{field} must be less than or equal to {value}',
            'Array' => '{field} must have less than or equal to {value} items',
            'File' => '{field} must be less than or equal to {value} kilobytes',
            'String' => '{field} must be less than or equal to {value} characters',
        ],
        'Image' => '{field} must be a valid image',
        'Within' => 'The selected {field} is invalid',
        'WithinArray' => '{field} must exist within {other}',
        'Integer' => '{field} must be a valid integer',
        'IP' => '{field} must be a valid IP address',
        'IPv4' => '{field} must be a valid IPv4 address',
        'IPv6' => '{field} must be a valid IPv6 address',
        'IPNotPriv' => '{field} must not be a private IP address',
        'IPNotRes' => '{field} must not be a reserved IP address',
        'JSON' => '{field} must be valid JSON input',
        'Max' => [
            'Numeric' => '{field} must not be more than {value}',
            'Array' => '{field} must not have more than {value} items',
            'File' => '{field} must not be more than {value} kilobytes',
            'String' => '{field} must not be more than {value} characters',
        ],
        'MimesAllowed' => '{field} must match one of the following file types: {types}',
        'Min' => [
            'Numeric' => '{field} must not be less than {value}',
            'Array' => '{field} must not have less than {value} items',
            'File' => '{field} must not be less than {value} kilobytes',
            'String' => '{field} must not be less than {value} characters',
        ],
        'Numeric' => '{field} must be a numeric value',
        'Required' => 'The {field} field is required.',
        'RequiredWithAny' => 'The {field} field is required when {values} is present.',
        'Uploaded' => 'The {field} file failed to upload',
    ];

    protected function getRuleErrorMessage(string $rule)
    {
        if(array_key_exists($rule, $this->errorMessages))
        {
            return $this->errorMessages[$rule];
        }

        return null;
    }

    protected function replaceErrorMessagePlaceholders(string $errorMessage, string $field, array $parameters)
    {
        $errorMessage = str_replace('{field}', $field, $errorMessage);

        $placeholders = $this->getStringsBetween($errorMessage,'{', '}');

        if(count($placeholders) === 0)
        {
            // No placeholders, so we return the original error message with its field name replaced only
            return $errorMessage;
        }

        // Remove any duplicate placeholders where they could be used more than once
        $placeholders = array_unique($placeholders);

        // For when the number of placeholders or parameters don't match
        if(count($placeholders) !== count($parameters))
        {
            // Combine all parameters as a string to one single placeholder, due to unequal elements
            $parameters = array_combine($placeholders, $this->reduceParametersToString($parameters));
        }
        else
        {
            // Combine the found placeholders together with the parameters from the rule
            $parameters = array_combine($placeholders, $parameters);
        }

        foreach($placeholders as $placeholder)
        {
            if(array_key_exists($placeholder, $parameters))
            {
                $errorMessage = str_replace('{' . $placeholder . '}', $parameters[$placeholder], $errorMessage);
            }
        }

        return $errorMessage;
    }

    protected function reduceParametersToString(array $parameters)
    {
        $values = '';

        foreach($parameters as $parameter)
        {
            $values .= $parameter . ', ';
        }

        return [rtrim($values, ', ')];
    }

    protected function getSizeErrorMessage(array $sizeErrorMessages)
    {
        return $sizeErrorMessages[$this->lastSizeType];
    }

    public function errors()
    {
        return $this->errors;
    }
}