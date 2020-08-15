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

        // All errors within the session should have been processed, so we remove them so they don't show up again
        $this->HttpKernel->session->remove('errors');
    }

    protected function processAllErrorTags($errorsParameters)
    {
        foreach($errorsParameters as $errorTemplateName)
        {
            // The error HTML template defined from @errors(<templateName>)
            $errorTemplate = new ViewBuilder("$errorTemplateName:error");

            if($errorTemplate->isValid())
            {
                // Get the error template from file as a string representation
                $errorTemplate = $errorTemplate->__toString();

                // Grab the error line we want to use for each error we render
                $errorReplacementLine = $this->getStringsBetween($errorTemplate, "{{ @error(", ") }}")[0];

                // Render all errors based on the error replacement line from the error template
                $errors = $this->renderAllErrors($errorReplacementLine);

                if(exists($errors))
                {
                    // Inject the errors into the error template
                    $errorTemplate = str_replace("{{ @error($errorReplacementLine) }}", $errors, $errorTemplate);

                    // Then inject the error template with the errors into the main view/resource
                    $this->resource = str_replace("{{ @errors($errorTemplateName) }}", $errorTemplate, $this->resource);

                    continue;
                }

                // For when no errors exist, just remove the @errors(...) tag from the resource
                $this->resource = str_replace("{{ @errors($errorTemplateName) }}", '', $this->resource);
            }
        }
    }

    protected function renderAllErrors($errorReplacementLine)
    {
        $errorsFromSession = $this->HttpKernel->session->get('errors');

        if(!exists($errorsFromSession))
        {
            return null;
        }

        $errorList = '';

        // Looping through each field and its errors (array)
        foreach($errorsFromSession as $field => $errors)
        {
            // Loop through the current fields errors
            foreach($errors as $error)
            {
                // Using the error replacement line, place the error message where the @message tag is
                $errorList .= str_replace('@message', $error, $errorReplacementLine);
            }
        }

        return $errorList;
    }

    protected function processSingleErrorTags($errorTagParameters)
    {
        // Looping through each @error() directive and their parameters
        foreach($errorTagParameters as $errorParameters)
        {
            // Explode the parameters set within the @error() tag, optional params are replaced as null when omitted
            [$field, $output] = array_pad(explode(',', $errorParameters, 2), 2, null);

            // Only process error tags if the field actually has errors from the session storage
            if($this->HttpKernel->session->exists("errors.$field"))
            {
                // If an output is set, we replace the error tag with that
                if(exists($output))
                {
                    $originalOutput = $output;

                    $output = str_replace('{{ @message }}', $this->HttpKernel->session->get("errors.$field")[0], $output);

                    $this->resource = str_replace("{{ @error($field,$originalOutput) }}", trim($output), $this->resource);

                    continue;
                }

                // For when no output exists, we replace the error tag with the actual error msg itself
                if($error = $this->HttpKernel->session->get("errors.$field"))
                {
                    $this->resource = str_replace("{{ @error($field) }}", $error[0], $this->resource);

                    continue;
                }
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
}