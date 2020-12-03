<?php

namespace Polyel\Validation;

use Polyel\Auth\AuthManager;
use Polyel\Http\File\UploadedFile;

class Validator
{
    use ValidationRules, ValidationErrorMessages;

    private array $data;

    private array $flattenedData;

    private array $rules;

    private array $expandedFields = [];

    private array $fieldsToBeRemoved = [];

    private array $failedRules;

    private array $uniqueArrayValueCache = [];

    private string $group;

    private array $customErrorMessages;

    private object $auth;

    private string $lastSizeType;

    private int $lastSizeMetric;

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
        'RequiredIf', 'RequiredUnless', 'Confirmed', 'Match', 'Different', 'Before', 'After',
        'BeforeOrEqual', 'AfterOrEqual', 'GreaterThan', 'GreaterThanOrEqual', 'LessThan',
        'LessThanOrEqual', 'RemoveIf', 'RemoveUnless',
    ];

    /*
     * The validation rules which can be used to remove a field and its data
     */
    private array $removalRules = ['RemoveIf', 'RemoveUnless',];

    /*
     * The Numeric validation rules
     */
    private $numericRules = ['Numeric', 'Integer'];

    /*
     * The Size based validation rules
     */
    private $sizeRules = [
        'Size', 'Between', 'Min', 'Max', 'GreaterThan',
        'GreaterThanOrEqual', 'LessThan', 'LessThanOrEqual'
    ];

    /*
     * The array of error messages when validation fails for fields
     */
    private array $errors = [];

    public function __construct(array $data, array $rules, string $group = '', $customErrorMessages = [])
    {
        $this->flattenedData = $this->flatternData($data);
        $this->data = $data;
        $this->rules = $this->prepareRules($rules);
        $this->group = $group;
        $this->customErrorMessages = $customErrorMessages;
    }

    /*
     * Some rules require use of the AuthManager
     */
    public function setAuthManager(AuthManager $auth)
    {
        $this->auth = $auth;
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

                // Store a list of fields and their expanded keys
                $this->expandedFields[$field][] = $key;
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
        return $this;
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
                if($rule === '')
                {
                    continue;
                }

                $value = $this->getValue($field);

                $this->processRule($field, $rule, $value);

                // Determine if a field should be removed if it fails any removal rule
                if($this->shouldBeRemoved($field))
                {
                    // Remove the field data and its rule
                    $this->removeField($field);

                    // Continue on to the next field to validate
                    break;
                }

                if($breakPoint = $this->shouldBreakFromValidating($field, $rules))
                {
                    if($breakPoint === 'field')
                    {
                        // Break on the field and out of the main loop, stopping validation
                        break 2;
                    }

                    if($breakPoint === 'rule')
                    {
                        // Break on the rule and continue onto the next field to validate
                        break 1;
                    }
                }
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

    private function getValue($field, $default = null)
    {
        $keys = explode('.', $field);

        $data = $this->data;
        foreach($keys as $key)
        {
            if(is_array($data) && array_key_exists($key, $data))
            {
                // Loop through until we get a final value based on the dot syntax
                $data = $data[$key];
            }
            else
            {
                return $default;
            }
        }

        // Return the requested configuration level/value
        return $data;
    }

    public function data()
    {
        return $this->data;
    }

    protected function shouldBeRemoved(string $field)
    {
        return in_array($field, $this->fieldsToBeRemoved);
    }

    protected function removeField(string $field)
    {
        unset($this->data[$field], $this->rules[$field]);
    }

    protected function getOriginalField($field)
    {
        foreach($this->expandedFields as $unexpanded => $expanded)
        {
            /*
             * Return the unexpanded field name if a match is found within the expanded list
             *
             * For Example: person.luke.id would become person.*.id
             */
            if (in_array($field, $expanded))
            {
                return $unexpanded;
            }
        }

        return $field;
    }

    protected function shouldBreakFromValidating($field, $rules)
    {
        // Available Break rule variations
        $breakRules = ['Break', 'Break:rule', 'Break:field'];

        // Check if the field is using a Break rule and has an error
        if(!empty($breakPoint = array_intersect($breakRules, $rules)) && $this->hasError($field))
        {
            // Return the breakpoint of either rule or field, default to rule is one is not set
            return explode(':', current($breakPoint))[1] ?? 'rule';
        }

        /*
         * Break at the rule level if the field is using a rule that implies it
         * is required and that the field has already failed the requirement validation.
         * We do this because there is no point in continuing validating when the
         * requirement rule has failed and trying to execute another rule on an empty or
         * missing field.
         */
        if($this->hasRule($field, $this->implicitRules) && $this->hasError($field))
        {
            // Only break when the failed rule is actually a implicit rule
            if($this->hasFailedRule($field, $this->implicitRules))
            {
                return 'rule';
            }
        }

        // False, we should continue validating rules or onto the next field...
        return false;
    }

    private function processRule($field, $rule, $value)
    {
        [$rule, $parameters] = $this->parseRule($rule);

        // Original rule parameters that are set from the rule definitions
        $originalParameters = $parameters;

        if(exists($parameters) && $this->dependentOnOtherFields($rule))
        {
            $parameters = $this->explodeWildcardParameters($parameters);

            // Update original parameters with any expanded wildcard parameters
            $originalParameters = $parameters;

            // Convert any parameters to values if they are a name for another field
            foreach($parameters as $key => $parameter)
            {
                $anotherFieldValue = $this->getValue($parameters[$key]);

                if(exists($anotherFieldValue) || $anotherFieldValue === '')
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

        /*
         * See if the field and its value can be validated, allowing the validateRule
         * method to be called. This check is in place to make sure the field is not
         * marked as optional or null, and performing any pre-validation checks to make
         * sure the field and its value are usable for any of the validateRule methods.
         * A true response means the field is validatable and a false means it is not.
         */
        $fieldCanBeValidated = $this->canFieldBeValidated($field, $value, $rule);

        $validationMethod = "validate{$rule}";

        if($fieldCanBeValidated && $this->$validationMethod($field, $value, $parameters) === false)
        {
            // By default error messages use the original parameters from the rule definitions
            $parameters = $originalParameters;

            // Use the true size values for parameters when adding a new size dependent rule error
            if(in_array($rule, $this->sizeRules) && in_array($rule, $this->dependentRules))
            {
                // Uses the true size value instead of a field name in the error message
                $parameters = [$this->lastSizeMetric];
            }

            $this->addError($field, $rule, $parameters);
        }
    }

    private function parseRule(string $rule)
    {
        $parameters = [];

        if(strpos($rule, ':') !== false)
        {
            [$rule, $parameters] = explode(':', $rule);

            // Regex rules might contain a ',' which would impact the way parameters are captured...
            if(!in_array($rule, ['Regex', 'RegexNot']))
            {
                // Store parameters based on a list split up by ',' into an array
                $parameters = str_getcsv($parameters);
            }
            else
            {
                /*
                 * Some rules are not compatible with parameter splitting because they could contain a ','.
                 * So we only need to use the parameter string value directly
                 */
                $parameters = [$parameters];
            }
        }

        return [ucwords($rule), $parameters];
    }

    protected function canFieldBeValidated($field, $value, $rule)
    {
        // Must be validated if the current rule is a removal rule as the field may need to be excluded
        if(in_array($rule, $this->removalRules, true))
        {
            return true;
        }

        return $this->canBeOptionalIfMarkedAsOptional($field, $rule);
    }

    protected function canBeOptionalIfMarkedAsOptional($field, $rule)
    {
        // Must be validated if the current rule deems the field as "Required" or when the field is not Optional
        if(in_array($rule, $this->implicitRules) || !$this->hasRule($field, ['Optional']))
        {
            return true;
        }

        $data = $this->getValue($field);

        return !is_null($data) && $data !== '';
    }

    protected function hasRule($field, $rules)
    {
        // Matches a fields rules against the rules provided, checking if any are present
        if(!empty(array_intersect($this->rules[$field], $rules)))
        {
            return true;
        }

        return false;
    }

    protected function hasFailedRule($field, $rules)
    {
        // Uses the failedRules array keys to check if a rule has already failed on a field
        if(!empty(array_intersect(array_keys($this->failedRules[$field]), $rules)))
        {
            return true;
        }

        return false;
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
        if(in_array($rule, $this->removalRules))
        {
            $this->addFieldToRemovalArray($field);

            return null;
        }

        // First check for any custom message, if no custom message is found, use the rule default
        $errorMessage = $this->checkForCustomErrorMessage($field, $rule) ?: $this->getRuleErrorMessage($rule);

        // Used to convert a size rule into its error message...
        if(is_array($errorMessage) && in_array($rule, $this->sizeRules))
        {
            $errorMessage = $this->getSizeErrorMessage($errorMessage);
        }

        if(exists($errorMessage))
        {
            $errorMessage = $this->replaceErrorMessagePlaceholders($errorMessage, $field, $parameters);

            if($errorMessage === false)
            {
                return false;
            }

            // Store which rules have failed for each rule and their parameters
            $this->failedRules[$field][$rule] = $parameters;

            // Store an error message inside a named group if one is set
            if(exists($this->group))
            {
                if($this->checkForDuplicateErrorMessage($errorMessage, $this->group))
                {
                    // Still add an error for the field but add no error, so we don'get a static duplicate error
                    $errorMessage = null;
                }

                // Add the error message to a named form group of fields and their error messages
                $this->errors[$this->group][$field][] = $errorMessage;
            }
            else
            {
                if($this->checkForDuplicateErrorMessage($errorMessage))
                {
                    // Still add an error for the field but add no error, so we don'get a static duplicate error
                    $errorMessage = null;
                }

                // Add the error message to an array of fields and their error messages
                $this->errors[$field][] = $errorMessage;
            }

            // The error has been successfully added to the list of error messages
            return true;
        }

        // No matching error message was found for the given rule
        return false;
    }

    protected function addFieldToRemovalArray($field)
    {
        $this->fieldsToBeRemoved[] = $field;
    }

    protected function checkForDuplicateErrorMessage($newErrorMessage, $group = null)
    {
        if($group && array_key_exists($group, $this->errors))
        {
            // Select only messages from a group if one was set
            $errorMessages = $this->errors[$group];
        }
        else
        {
            $errorMessages = $this->errors;
        }

        foreach($errorMessages as $fieldErrors)
        {
            if(in_array($newErrorMessage, $fieldErrors))
            {
                // A duplicate error message was found
                return true;
            }
        }

        // No duplicate error messages found
        return false;
    }

    protected function hasError($field)
    {
        if(isset($this->group, $this->errors[$this->group][$field]))
        {
            return true;
        }

        if(isset($this->errors[$field]))
        {
            return true;
        }

        return false;
    }
}