<?php

namespace Polyel\Validation;

use Polyel\View\ViewTools;

trait ValidationErrorMessages
{
    use ViewTools;

    private $errorMessages = [
        'Accepted' => '{field} must be accepted.',
        'Email' => 'Your {field} must be a valid email address.',
        'Required' => 'The {field} field is required.',
        'RequiredWithAny' => 'The {field} field is required when {values} is present.',
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

        if(count($placeholders) !== count($parameters))
        {
            $parameters = array_combine($placeholders, $this->reduceParametersToString($parameters));
        }

        foreach($placeholders as $placeholder)
        {
            if(array_key_exists($placeholder, $parameters))
            {
                return str_replace('{' . $placeholder . '}', $parameters[$placeholder], $errorMessage);
            }
        }

        /*
         * If no placeholders can be matched and replaced, return false, placeholder replacement failed.
         * Only applies when placeholders are found but no matching parameters can take the placeholder.
         */
        return false;
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

    public function errors()
    {
        return $this->errors;
    }
}