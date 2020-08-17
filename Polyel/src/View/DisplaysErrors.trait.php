<?php

namespace Polyel\View;

trait DisplaysErrors
{
    protected function processErrors()
    {
        /*
         * Process all @errors(<templateName>) tags
         * If template names exist, we collect all the errors, render them
         * within the chosen template and inject the final output into the main view.
         */
        if($errorsParameters = $this->getStringsBetween($this->resource, "{{ @errors(", ") }}"))
        {
            $this->processAllErrorTags($errorsParameters);
        }

        /*
         * Process all @error(<field>, <output>) tags
         * Foreach error field, we replace the @error tag based on the parameters passed in.
         * If no output is set, we replace the error tag with the actual error for the field.
         */
        if($errorParameters = $this->getStringsBetween($this->resource, "{{ @error(", ") }}"))
        {
            $this->processSingleErrorTags($errorParameters);
        }

        /*
         * Process all @errorCount(<field>) tags
         * Will add the error count for a field, counts all errors inside the session.
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
        foreach($errorsTagParameters as $errorsParameters)
        {
            // Explode the parameters into single variables, fill them as null if they don't exist
            [$errorTemplateName, $errorGroupName] = array_pad(
                explode(',', $errorsParameters, 2), 2, null);

            // The error HTML template defined from @errors(<templateName>)
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
                $errors = $this->renderAllErrors($errorReplacementLine, $errorGroupName);

                if(exists($errorGroupName))
                {
                    // Prepare the group name, ready to be used to replace the tag...
                    $errorGroupName = ",$errorGroupName";
                }

                if(exists($errors))
                {
                    // Inject the errors into the error template
                    $errorTemplate = str_replace("{{ @error($errorReplacementLine) }}", $errors, $errorTemplate);

                    // Then inject the error template with the errors into the main view/resource
                    $this->resource = str_replace(
                        "{{ @errors($errorTemplateName$errorGroupName) }}",
                        $errorTemplate,
                        $this->resource);

                    continue;
                }

                // For when no errors exist, just remove the @errors(...) tag from the resource
                $this->resource = str_replace(
                    "{{ @errors($errorTemplateName$errorGroupName) }}", '', $this->resource
                );
            }
        }
    }

    protected function renderAllErrors($errorReplacementLine, $errorGroupName)
    {
        if(exists($errorGroupName))
        {
            $errorGroupName = trim($errorGroupName);

            // Only get the errors from the provided group name
            $errorPath = "errors.$errorGroupName";
        }
        else
        {
            // Use all the errors at root level
            $errorPath = "errors";
        }

        $errorsFromSession = $this->HttpKernel->session->get($errorPath);

        if(!exists($errorsFromSession))
        {
            return null;
        }

        $errorList = '';

        // Looping through each field and its errors (array)
        foreach($errorsFromSession as $field => $error)
        {
            // For when an error group field is directly selected like 'login.email' for example
            if(is_string($error))
            {
                $errorList .= $this->replaceErrorMessageTag($error, $errorReplacementLine);

                continue;
            }

            // Loop through the current fields errors
            foreach($error as $message)
            {
                /*
                 * If the error is an array it means we have hit a error group and should not
                 * process this any further because the current errors tag is not using the correct
                 * error path. Meaning the errors are not for this tag.
                 */
                if(is_array($message))
                {
                    return null;
                }

                $errorList .= $this->replaceErrorMessageTag($message, $errorReplacementLine);
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
            // Explode the parameters set within the @error() tag, optional params are replaced as null when omitted
            [$field, $output] = array_pad(explode(',', $errorParameters, 2), 2, null);

            // Only process error tags if the field actually has errors from the session storage
            if($error = $this->HttpKernel->session->get("errors.$field"))
            {
                // If an output is set, we replace the error tag with that
                if(exists($output))
                {
                    $originalOutput = $output;

                    $output = str_replace(
                        '{{ @message }}', $error[0], $output
                    );

                    $this->resource = str_replace(
                        "{{ @error($field,$originalOutput) }}", trim($output), $this->resource
                    );

                    continue;
                }

                // For when no output exists, we replace the error tag with the actual error msg itself
                $this->resource = str_replace("{{ @error($field) }}", $error[0], $this->resource);
            }
            else if(exists($output))
            {
                // No error exists within the session but an output was set, remove the error tag from the view
                $this->resource = str_replace("{{ @error($field,$output) }}", '', $this->resource);
            }
            else
            {
                // No error exists within the session but no output was set, remove the error tag from the view
                $this->resource = str_replace("{{ @error($field) }}", '', $this->resource);
            }
        }
    }

    protected function processErrorCountTags($errorCountParameters)
    {
        foreach($errorCountParameters as $field)
        {
            if($errors = $this->HttpKernel->session->get("errors.$field"))
            {
                $this->resource = str_replace("{{ @errorCount($field) }}", count($errors), $this->resource);

                continue;
            }

            // Default to zero errors when none are found within the session data
            $this->resource = str_replace("{{ @errorCount($field) }}", '0', $this->resource);
        }
    }
}