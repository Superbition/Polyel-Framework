<?php

namespace Polyel\Validation;

use Polyel\Http\File\UploadedFile;

class Validator
{
    use ValidationRules, ValidationErrorMessages;

    private array $data;

    private array $flattenedData;

    private array $rules;

    private string $group;

    /*
     * The validation rules which can be used with files
     */
    private array $fileRules = [
        'File', 'Image', 'Mimes', 'Mimetypes', 'Min',
        'Max', 'Size', 'Between', 'Dimensions',
    ];

    /*
     * The validation rules that imply a field is required
     */
    private array $implicitRules = [
        'Required', 'Filled', 'RequiredWithAny', 'RequiredWithAll',
        'RequiredWithoutAny', 'RequiredWithoutAll', 'RequiredIf',
        'RequiredUnless', 'Accepted',
    ];

    /*
     * The validation rules which depend on other fields for their parameters
     */
    private array $dependentRules = [
        'RequiredWithAny', 'RequiredWithAll', 'RequiredWithoutAny', 'RequiredWithoutAll',
        'RequiredIf', 'RequiredUnless', 'Confirmed', 'Same', 'Different', 'Unique',
        'Before', 'After', 'BeforeOrEqual', 'AfterOrEqual', 'GreaterThan', 'LessThan', 'Gte', 'Lte',
        'ExcludeIf', 'ExcludeUnless',
    ];

    /*
     * The array of error messages when validation fails for fields
     */
    private array $errors = [];

    public function __construct(array $data, array $rules, string $group = '')
    {
        $this->flattenedData = $this->flatternData($data);
        $this->data = $data;
        $this->rules = $this->prepareRules($rules);
        $this->group = $group;
    }

    protected function prepareRules(array $fieldsAndRules)
    {
        foreach($fieldsAndRules as $field => $rules)
        {
            // Expand any rules which are using * as a wildcard
            if(strpos($field, '*') !== false)
            {
                // Remove the field.*.rule as it will be replaced with the actual array path
                unset($fieldsAndRules[$field]);

                $fieldsAndRules = array_merge($fieldsAndRules, $this->explodeRulesWithWildcard($field, $rules));
            }
        }

        return $fieldsAndRules;
    }

    protected function explodeRulesWithWildcard($field, $rules)
    {
        $pattern = str_replace('\*', '([^\.]*)', preg_quote($field, '/'));

        $explodedWildcardRules = [];

        foreach($this->flattenedData as $key => $value)
        {
            if(preg_match('/^'. $pattern . '$/', $key))
            {
                $explodedWildcardRules[$key] = $rules;
            }
        }

        // Return the exploded rules or the given field & rules if no matching data is found
        return $explodedWildcardRules ?: [$field => $rules];
    }

    protected function flatternData($data, $prepend = '')
    {
        $results = [];

        foreach($data as $key => $value)
        {
            if(is_array($value) && !empty($value))
            {
                $results = array_merge($results, $this->flatternData($value, $prepend . $key . '.'));
            }
            else
            {
                $results[$prepend . $key] = $value;
            }
        }

        return $results;
    }

    public function validate()
    {
        if($this->validationFails())
        {
            throw new ValidationException($this);
        }

        // Validation has completed without any errors
        return true;
    }

    public function validationPasses()
    {
        // Returns true when validation passes
        return $this->processRulesAgainstData();
    }

    public function validationFails()
    {
        // Returns true when validation fails
        return !$this->processRulesAgainstData();
    }

    private function processRulesAgainstData()
    {
        foreach($this->rules as $field => $rules)
        {


            foreach($rules as $rule)
            {
                $value = $this->getValue($field);

                $this->processRule($field, $rule, $value);
            }


        }

        if(empty($this->errors))
        {
            // True, validation has passed
            return true;
        }

        // False, validation has failed
        return false;
    }

    private function getValue($field)
    {
        $keys = explode('.', $field);

        $data = $this->data;
        foreach($keys as $key)
        {
            if(array_key_exists($key, $data))
            {
                // Loop through until we get a final value based on the dot syntax
                $data = $data[$key];
            }
            else
            {
                return null;
            }
        }

        // Return the requested configuration level/value
        return $data;
    }

    private function processRule($field, $rule, $value)
    {
        [$rule, $parameters] = $this->parseRule($rule);

        if($this->dependentOnOtherFields($rule))
        {
            $parameters = $this->explodeWildcardParameters($parameters);
        }

        if(exists($parameters))
        {
            // Convert any parameters to values if they are a name for another field
            foreach($parameters as $key => $parameter)
            {
                if($anotherFieldValue = $this->getValue($parameters[$key]))
                {
                    $parameters[$key] = $anotherFieldValue;
                }
            }
        }

        if($value instanceof UploadedFile && $value->isValid() === false && $this->fileIsRequired($rule))
        {
            $this->addError($field, 'Uploaded');

            return;
        }

        $validationMethod = "validate{$rule}";

        if($this->$validationMethod($field, $value, $parameters) === false)
        {
            $this->addError($field, $rule, $parameters);
        }
    }

    private function parseRule(string $rule)
    {
        $parameters = [];

        if(strpos($rule, ':') !== false)
        {
            [$rule, $parameters] = explode(':', $rule);

            $parameters = str_getcsv($parameters);
        }

        return [ucwords($rule), $parameters];
    }

    protected function dependentOnOtherFields(string $rule)
    {
        return in_array($rule, $this->dependentRules, true);
    }

    protected function explodeWildcardParameters(array $parameters)
    {
        $parametersExploded = [];

        foreach($parameters as $parameterKey => $parameter)
        {
            if(strpos($parameter, '*') !== false)
            {
                $pattern = str_replace('\*', '([^\.]*)', preg_quote($parameter, '/'));

                foreach($this->flattenedData as $dataKey => $value)
                {
                    if(preg_match('/^'. $pattern . '$/', $dataKey))
                    {
                        unset($parameters[$parameterKey]);

                        $parametersExploded[] = $dataKey;
                    }
                }
            }
        }

        return array_merge($parameters, $parametersExploded);
    }

    protected function fileIsRequired($rule)
    {
        return in_array($rule, $this->implicitRules, true);
    }

    public function group()
    {
        return $this->group ?? null;
    }

    private function addError(string $field, string $rule, array $parameters = [])
    {
        $errorMessage = $this->getRuleErrorMessage($rule);

        if(exists($errorMessage))
        {
            $errorMessage = $this->replaceErrorMessagePlaceholders($errorMessage, $field, $parameters);

            if($errorMessage === false)
            {
                return false;
            }

            // Store an error message inside a named group if one is set
            if(exists($this->group))
            {
                // Add the error message to a named form group of fields and their error messages
                $this->errors[$this->group][$field][] = $errorMessage;
            }
            else
            {
                // Add the error message to an array of fields and their error messages
                $this->errors[$field][] = $errorMessage;
            }

            // The error has been successfully added to the list of error messages
            return true;
        }

        // No matching error message was found for the given rule
        return false;
    }
}