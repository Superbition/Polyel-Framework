<?php

namespace Polyel\Validation;

trait RequestValidation
{
    public function validate(array $rules, string $group = '')
    {
        $validator = new Validator($this->getRequestDataForValidation(), $rules, $group);

        return $validator->validate();
    }

    protected function getRequestDataForValidation()
    {
        // Get all the request data
        $data = $this->data();

        // Process any uploaded files separately because they are part of another array
        if($this->hasFiles())
        {
            // If data is an array, merge files together with the request data
            if(is_array($data))
            {
                $data = array_merge($data, $this->files());
            }
            else
            {
                /*
                 * Else there is no data, so we only need to process files
                 * If data does not exist, it grabs the raw content of the request, which
                 * in the end would be the files sent.
                 */
                $data = $this->files();
            }
        }

        // Convert RAW JSON data into a decoded PHP array if a JSON request is sent
        if(is_string($data) && $this->hasHeader("content-type", "application/json"))
        {
            $data = json_decode($data, true);
        }
        else if($this->isMethod("GET"))
        {
            // Use URL query data if a GET request is sent and no other data is found
            $data = $this->query();
        }

        return $data;
    }
}