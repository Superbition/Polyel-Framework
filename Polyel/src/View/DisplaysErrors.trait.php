<?php

namespace Polyel\View;

trait DisplaysErrors
{
    protected function processErrors()
    {
        /*
         * Process all @errors(<templateName>, <groupAndOrField>) tags.
         * If the template names exist, we collect all the errors and render them
         * within the chosen template and inject the final output into the main view.
         */
        if($errorsParameters = $this->getStringsBetween($this->resource, "{{ @errors(", ") }}"))
        {
            $this->processAllErrorTags($errorsParameters);
        }

        /*
         * Process all @error(<field>, <output>) tags.
         * Foreach error field, we replace the @error tag based on the parameters passed in.
         * If no output is set, we replace the error tag with the actual error for the field.
         */
        if($errorParameters = $this->getStringsBetween($this->resource, "{{ @error(", ") }}"))
        {
            $this->processSingleErrorTags($errorParameters);
        }

        /*
         * Process all @errorCount(<groupAndOrField>) tags.
         * Adds the error count for a field, counts all errors inside the session.
         * Does not only count the first error, counts all.
         */
        if($errorCountParameters = $this->getStringsBetween($this->resource, "{{ @errorCount(", ") }}"))
        {
            $this->processErrorCountTags($errorCountParameters);
        }

        // All errors within the session should have been processed, so we remove them so they don't show up again
        $this->HttpKernel->session->remove('errors');
    }

    protected function processAllErrorTags($errorsTagParameters)
    {
        // Process all @errors(<templateName>, <groupAndOrField>) tags.
        foreach($errorsTagParameters as $errorsParameters)
        {
            // Explode the parameters into single variables, fill them as null if they are omitted
            [$errorTemplateName, $groupAndOrField] = array_pad(
                explode(',', $errorsParameters, 2), 2, null);

            // The error HTML template defined from @errors(<templateName>,...)
            $errorTemplate = new ViewBuilder("$errorTemplateName:error");

            if($errorTemplate->isValid())
            {
                // Get the error template from file as a string representation
                $errorTemplate = $errorTemplate->__toString();

                // Grab the error line we want to use for each error we render
                $errorReplacementLine = $this->getStringsBetween(
                    $errorTemplate, "{{ @error(", ") }}"
                )[0];

                // Render all errors based on the error replacement line from the error template
                $errors = $this->renderAllErrors($errorReplacementLine, $groupAndOrField);

                if(exists($groupAndOrField))
                {
                    // Prepare the group name, ready to be used to replace the errors tag...
                    $groupAndOrField = ",$groupAndOrField";
                }

                if(exists($errors))
                {
                    // Inject the errors into the error template
                    $errorTemplate = str_replace("{{ @error($errorReplacementLine) }}", $errors, $errorTemplate);

                    // Then inject the error template with the errors into the main view/resource
                    $this->resource = str_replace(
                        "{{ @errors($errorTemplateName$groupAndOrField) }}",
                        $errorTemplate,
                        $this->resource);

                    continue;
                }

                // For when no errors exist, just remove the @errors(...) tag from the resource
                $this->resource = str_replace(
                    "{{ @errors($errorTemplateName$groupAndOrField) }}", '', $this->resource
                );
            }
        }
    }

    protected function renderAllErrors($errorReplacementLine, $groupAndOrField)
    {
        // Remove any whitespace from the group and or field parameter
        $groupAndOrField = trim($groupAndOrField);

        // Check if contains a defined group with a field, in the format of "groupName:field.location"
        if(strpos($groupAndOrField, ':') !== false)
        {
            // Split based on the delimiter so we have both the group and field separated
            $groupAndOrField = explode(':', $groupAndOrField);

            // Only get the errors from the provided group
            $errorPath = "errors.$groupAndOrField[0]";

            // If a field has been set along with the a group...
            if(count($groupAndOrField) > 1)
            {
                // Set the error field from the parameter passed in
                $errorField = $groupAndOrField[1];
            }
        }
        else if(exists($groupAndOrField))
        {
            /*
             * Else it means we have either a group or field set, because of no ':' delimiter,
             * we can't tell if the parameter is a group or field, this will be checked later.
             * At this stage we just set the error path and field to what was provided from
             * the parameter.
             */
            $errorPath = "errors.$groupAndOrField";
            $errorField = $groupAndOrField;
        }
        else
        {
            // Else no parameter for the group or field was given...
            $errorPath = 'errors';
            $errorField = '';
        }

        $errorsFromSession = $this->HttpKernel->session->get($errorPath);

        if(!exists($errorsFromSession))
        {
            return null;
        }

        $errorList = '';

        // Check if the field is using an array wildcard, for example: person.*.id
        if($errorField && strpos($errorField, '*') !== false)
        {
            $pattern = str_replace('\*', '([^\.]*)', preg_quote($errorField, '/'));

            /*
             * Search for matching fields using the pattern which will match any fields
             * against the given wildcard key. For example if we have person.*.id, this
             * will search for any key and accept any "person" who has an id. So a key
             * like person.luke.id will be matched and its errors returned...
             */
            foreach($errorsFromSession as $field => $error)
            {
                if(preg_match('/^'. $pattern . '$/', $field))
                {
                    // Because there could be multiple errors for a matched field...
                    foreach($error as $message)
                    {
                        $errorList .= $this->replaceErrorMessageTag($message, $errorReplacementLine);
                    }
                }
            }
        }
        else
        {
            // Used to select an error group or a specific field with errors
            if(array_key_exists($errorField, $errorsFromSession))
            {
                $errorsFromSession = $errorsFromSession[$errorField];
            }

            // Collect all the error messages and add them to the error list
            foreach($errorsFromSession as $error)
            {
                // If the error is an array it means the messages are inside...
                if(is_array($error))
                {
                    foreach($error as $message)
                    {
                        /*
                         * If the $message is an array still at this level,
                         * it means we are inside an error group and should not
                         * add any errors to the list from this group as this
                         * group has not been selected by the given parameter.
                         */
                        if(is_array($message))
                        {
                            break;
                        }

                        // Add the string error $message to the error list
                        $errorList .= $this->replaceErrorMessageTag($message, $errorReplacementLine);
                    }

                    // Onto the next error message...
                    continue;
                }

                /*
                 * Else we have selected a specific error field and can add the error directly,
                 * as it will already be a string at this level.
                 */
                $errorList .= $this->replaceErrorMessageTag($error, $errorReplacementLine);
            }
        }

        return $errorList;
    }

    protected function replaceErrorMessageTag($error, $messageTemplate)
    {
        // Using the message template, place the error message where the @message tag is
        return str_replace('@message', $error, $messageTemplate);
    }

    protected function processSingleErrorTags($errorTagParameters)
    {
        // Looping through each @error() directive and their parameters
        foreach($errorTagParameters as $errorParameters)
        {
            // Explode the parameters set within the @error() tag, optional parameters are set to null when omitted
            [$groupAndOrField, $output] = array_pad(
                explode(',', $errorParameters, 2), 2, null
            );

            // Check for a defined group with a field, in the format of "groupName:field.location"
            if(strpos($groupAndOrField, ':') !== false)
            {
                // Split up the group and field based on the : delimiter
                $groupAndOrField = explode(':', $groupAndOrField);

                /*
                 * Set the error path to use the group.
                 * Field to use the field only.
                 * Error tag to use both the group and field format of "groupName:field.location".
                 */
                $errorPath = "errors.$groupAndOrField[0]";
                $field = $groupAndOrField[1];
                $errorTag = "$groupAndOrField[0]:$groupAndOrField[1]";
            }
            else
            {
                /*
                 * Else it means we have either a group or field set, because of no ':' delimiter,
                 * we can't tell if the parameter is a group or field, this will be checked later.
                 * At this stage we just set the error path, errorTag and field to what was provided
                 * from the parameter.
                 */
                $errorPath = 'errors';
                $errorTag = $field = $groupAndOrField;
            }

            $error = $this->HttpKernel->session->get($errorPath);

            // Check if the field is using an array wildcard...
            if(strpos($field, '*') !== false && exists($error))
            {
                $pattern = str_replace('\*', '([^\.]*)', preg_quote($field, '/'));

                /*
                 * Search for matching fields using the pattern which will match any fields
                 * against the given wildcard key. For example if we have person.*.id, this
                 * will search for any key and accept any "person" who has an id. So a key
                 * like person.luke.id will be matched and then we can use the full length
                 * key instead of person.*.id.
                 */
                foreach($error as $key => $message)
                {
                    if(preg_match('/^'. $pattern . '$/', $key))
                    {
                        // Set the field to the first fully matched key, so person.*.id would become person.luke.id...
                        $field = $key;

                        // Break because the @error tag only processes the first error of a field...
                        break;
                    }
                }
            }

            // Make sure the field exists inside the errors before using an error message
            if(exists($error) && array_key_exists($field, $error))
            {
                // If an output is set, we replace the error tag with the output given from the parameter
                if(exists($output))
                {
                    // Add the output to the error tag, so we can replace it...
                    $errorTag .= ',' . $output;

                    // If the output contains the @message tag, inject the actual error
                    $output = str_replace(
                        '@message', $error[$field][0], $output
                    );

                    // Finally trim and replace the error tag with the output...
                    $this->resource = str_replace(
                        "{{ @error($errorTag) }}", trim($output), $this->resource
                    );

                    // Continue because we have injected the error and can move onto the next
                    continue;
                }

                // For when no output exists, we replace the error tag with the actual error message itself
                $this->resource = str_replace("{{ @error($errorTag) }}", $error[$field][0], $this->resource);
            }
            else
            {
                if(exists($output))
                {
                    $errorTag .= ',' . $output;
                }

                // No error exists within the session but an output was set, remove the error tag from the view
                $this->resource = str_replace("{{ @error($errorTag) }}", '', $this->resource);
            }
        }
    }

    protected function processErrorCountTags($errorCountParameters)
    {
        // Process all @errorCount() tags
        foreach($errorCountParameters as $groupAndOrField)
        {
            // Check for a defined group with a field, in the format of "groupName:field.location"
            if(strpos($groupAndOrField, ':') !== false)
            {
                // Split up the group and field
                $groupAndOrField = explode(':', $groupAndOrField);

                /*
                 * Error path is used to get the errors from the session.
                 * Error tag is used to perform the error injection.
                 * Field is used so we have the field location on its own.
                 */
                $errorPath = "errors.$groupAndOrField[0]";
                $errorTag = "$groupAndOrField[0]:$groupAndOrField[1]";
                $field = $groupAndOrField[1];
            }
            else
            {
                // For when either a group or field is set on its own
                $errorPath = 'errors';
                $field = $errorTag = $groupAndOrField;
            }

            $errorMessages = [];

            if($errors = $this->HttpKernel->session->get($errorPath))
            {
                // Check if the field is using an array wildcard...
                if(strpos($field, '*') !== false)
                {
                    $pattern = str_replace('\*', '([^\.]*)', preg_quote($field, '/'));

                    foreach($errors as $key => $messages)
                    {
                        // Collect all the error messages which match the wildcard pattern
                        if(preg_match('/^'. $pattern . '$/', $key))
                        {
                            $errorMessages[] = $messages;
                        }
                    }
                }
                else if($field)
                {
                    if(array_key_exists($field, $errors))
                    {
                        // Else use the specified field and its error messages
                        $errorMessages = $errors[$field];
                    }
                }
                else
                {
                    // Finally, resort to use the error array from the session
                    $errorMessages = $errors;
                }
            }

            // Replace the error count tag with the number of errors found using count()
            $this->resource = str_replace("{{ @errorCount($errorTag) }}", count($errorMessages), $this->resource);
        }
    }
}