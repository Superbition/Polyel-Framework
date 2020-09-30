<?php

namespace Polyel\Validation;

use finfo;
use DateTime;
use Spoofchecker;
use JsonException;
use Polyel\Database\Facade\DB;
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
            $this->lastSizeMetric = $value;
            return $value;
        }
        else if(is_array($value))
        {
            $this->lastSizeType = 'Array';
            $arrayCount = count($value);
            $this->lastSizeMetric = $arrayCount;

            return $arrayCount;
        }
        else if($value instanceof UploadedFile)
        {
            $this->lastSizeType = 'File';
            $fileSize = $value->getSize() / 1024;
            $this->lastSizeMetric = $fileSize;

            return $fileSize;
        }
        else if(is_string($value))
        {
            $this->lastSizeType = 'String';
            $charSize = mb_strlen($value);
            $this->lastSizeMetric = $charSize;

            return $charSize;
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

    protected function validateStartsWith($field, $value, $parameters)
    {
        $needles = $parameters;
        $haystack = $value;

        foreach($needles as $needle)
        {
            if($needle !== '' && strncmp($haystack, $needle, strlen($needle)) === 0)
            {
                return true;
            }
        }

        return false;
    }

    protected function validateEndsWith($field, $value, $parameters)
    {
        $needles = $parameters;
        $haystack = $value;

        foreach($needles as $needle)
        {
            if(substr($haystack, -strlen($needle)) === $needle)
            {
                return true;
            }
        }

        return false;
    }

    protected function validateRemoveIf($field, $value, $parameters)
    {
        [$otherFieldValue, $values] = $this->prepareRemovalData($parameters);

        // Remove field if other field is equal to any values
        return !in_array($otherFieldValue, $values);
    }

    protected function validateRemoveUnless($field, $value, $parameters)
    {
        [$otherFieldValue, $values] = $this->prepareRemovalData($parameters);

        // Remove field unless the other field is equal to any values
        return in_array($otherFieldValue, $values);
    }

    protected function prepareRemovalData($parameters)
    {
        $otherFieldValue = $this->getValue($parameters[0]);

        // Remove the other field from the parameters, so we are left with the values to check against
        $values = array_slice($parameters, 1);

        return [$otherFieldValue, $values];
    }

    protected function validateExists($field, $value, $parameters)
    {
        [$connection, $table] = $this->parseTable($parameters[0]);

        $column = $this->getDatabaseColumn($parameters, $field);

        $query = $connection ? DB::connection($connection, $table) : DB::table($table);

        // Support processing multiple values from an array
        if(is_array($value))
        {
            // The number of expected values to exist
            $expected = count(array_unique($value));

            foreach($value as $data)
            {
                $query->orWhere($column, '=', $data);
            }
        }
        else
        {
            $expected = 1;

            $query->where($column, '=', $value);
        }

        $existsCount = $query->count($column);

        return end($existsCount) >= $expected;
    }

    protected function validateUnique($field, $value, $parameters)
    {
        // The Unique rule does not work with value arrays
        if(is_array($value))
        {
            return false;
        }

        [$connection, $table] = $this->parseTable($parameters[0]);

        $column = $this->getDatabaseColumn($parameters, $field);

        $query = $connection ? DB::connection($connection, $table) : DB::table($table);

        $query->where($column, '=', $value);

        // Check if an ID to ignore has been passed as a parameter
        if(isset($parameters[2]) && !empty($parameters[2]))
        {
            // Default ID column to ignore
            $idColumn = 'id';

            // Use the 3rd parameter value as a ignore column name if it has been set
            if(isset($parameters[3]) && !empty($parameters[3]))
            {
                $idColumn = $parameters[3];
            }

            /*
             * Set an ID column to ignore so that a false positive is
             * avoided. For example, if a user updates their profile
             * and only changes their username, we don't want to fail
             * on their unchanged email as already existing, as they
             * already own it.
             */
            $query->where($idColumn, '!=', $parameters[2]);
        }

        $uniqueCount = $query->count($column);

        return (end($uniqueCount) === 0);
    }

    protected function parseTable($table)
    {
        if(strpos($table, '.') !== false)
        {
            // A connection is in the format of "connectionName.tableName"
            return [$connection, $table] = explode('.', $table, 2);
        }

        // No connection given, only the table
        return [null, $table];
    }

    protected function getDatabaseColumn($parameters, $field)
    {
        // Return either the column from the parameter or get the column from the field name
        return (isset($parameters[1]) && $parameters[1] !== null)
                    ? $parameters[1] : $this->getDatabaseColumnFromField($field);
    }

    protected function getDatabaseColumnFromField($field)
    {
        // Support field names using dot syntax
        if(strpos($field, '.') !== false)
        {
            $column = explode('.', $field);

            // Send back the last name using dot syntax
            return end($column);
        }

        return $field;
    }

    protected function validateFile($field, $value)
    {
        return $value instanceof UploadedFile && $value->isValid() && $value->path() !== '';
    }

    protected function validatePopulated($field, $value)
    {
        if(array_key_exists($field, $this->data))
        {
            return $this->validateRequired($field, $value);
        }

        return true;
    }

    protected function validateNumeric($field, $value)
    {
        return is_numeric($value);
    }

    protected function validateGreaterThan($field, $value, $parameters)
    {
        if(is_null($value) || is_null($parameters[0]))
        {
            return false;
        }

        $comparisionValue = $parameters[0];

        if($this->hasRule($field, $this->numericRules) && is_numeric($value) && is_numeric($comparisionValue))
        {
            $this->lastSizeType = 'Numeric';
            $this->lastSizeMetric = $comparisionValue;

            return $value > $comparisionValue;
        }

        if(gettype($value) !== gettype($comparisionValue))
        {
            return false;
        }

        return $this->getFieldSize($field, $value) > $this->getFieldSize($field, $comparisionValue);
    }

    protected function validateGreaterThanOrEqual($field, $value, $parameters)
    {
        if(is_null($value) || is_null($parameters[0]))
        {
            return false;
        }

        $comparisionValue = $parameters[0];

        if($this->hasRule($field, $this->numericRules) && is_numeric($value) && is_numeric($comparisionValue))
        {
            $this->lastSizeType = 'Numeric';
            $this->lastSizeMetric = $comparisionValue;

            return $value >= $comparisionValue;
        }

        if(gettype($value) !== gettype($comparisionValue))
        {
            return false;
        }

        return $this->getFieldSize($field, $value) >= $this->getFieldSize($field, $comparisionValue);
    }

    protected function validateLessThan($field, $value, $parameters)
    {
        if(is_null($value) || is_null($parameters[0]))
        {
            return false;
        }

        $comparisionValue = $parameters[0];

        if($this->hasRule($field, $this->numericRules) && is_numeric($value) && is_numeric($comparisionValue))
        {
            $this->lastSizeType = 'Numeric';
            $this->lastSizeMetric = $comparisionValue;

            return $value < $comparisionValue;
        }

        if(gettype($value) !== gettype($comparisionValue))
        {
            return false;
        }

        return $this->getFieldSize($field, $value) < $this->getFieldSize($field, $comparisionValue);
    }

    protected function validateLessThanOrEqual($field, $value, $parameters)
    {
        if(is_null($value) || is_null($parameters[0]))
        {
            return false;
        }

        $comparisionValue = $parameters[0];

        if($this->hasRule($field, $this->numericRules) && is_numeric($value) && is_numeric($comparisionValue))
        {
            $this->lastSizeType = 'Numeric';
            $this->lastSizeMetric = $comparisionValue;

            return $value <= $comparisionValue;
        }

        if(gettype($value) !== gettype($comparisionValue))
        {
            return false;
        }

        return $this->getFieldSize($field, $value) <= $this->getFieldSize($field, $comparisionValue);
    }

    protected function validateImage($field, $value)
    {
        if(!$value instanceof UploadedFile || $value->isValid() === false)
        {
            return false;
        }

        $fileInfo = new finfo(FILEINFO_MIME_TYPE);

        if($fileInfo === false)
        {
            return false;
        }

        $fileType = explode('/', $fileInfo->file($value->fullPath()))[1];

        // No SVG as it is deemed an XML file
        $imageExifConversion = [
            'jpeg' => 2,
            'png' => 3,
            'gif' => 1,
            'bmp' => 6,
            'webp' => 18,
        ];

        // If a matching exif_image type is found, check that the file type and constant values match
        if(isset($imageExifConversion[$fileType]))
        {
            $imageExifType = exif_imagetype($value->fullPath());

            if($imageExifConversion[$fileType] !== $imageExifType)
            {
                return false;
            }
        }

        $imageTypes = ['jpeg', 'png', 'gif', 'bmp', 'svg', 'webp'];

        return in_array($fileType, $imageTypes, true);
    }

    protected function validateWithin($field, $value, $parameters)
    {
        if(is_array($value) && $this->hasRule($field, ['Array']))
        {
            foreach($value as $element)
            {
                if(is_array($element))
                {
                    return false;
                }
            }

            return count(array_diff($value, $parameters)) === 0;
        }

        return !is_array($value) && in_array($value, $parameters);
    }

    protected function validateWithinArray($field, $value, $parameters)
    {
        if(!exists($parameters[0]))
        {
            return false;
        }

        $otherFieldName = $parameters[0];

        // Add on the wildcard * if only the array name is given
        if(strpos($otherFieldName, '.*') === false)
        {
            $otherFieldName .= '.*';
        }

        $otherFieldValues = [];

        // Get the other field values we want to check...
        if($fieldData = $this->getFieldDataFromPath($otherFieldName))
        {
            $fieldNamePattern = str_replace('\*', '[^.]+', preg_quote($otherFieldName, '/'));

            // Using a loop and regex check if any keys match our field name pattern and collect the values
            foreach($fieldData as $key => $data)
            {
                if(preg_match('/^' . $fieldNamePattern . '\z/u', $key))
                {
                    $otherFieldValues[] = $data;
                }
            }
        }

        // Check if the value is within any of the other fields values
        return in_array($value, $otherFieldValues);
    }

    protected function getFieldDataFromPath($originalDataPath)
    {
        /*
         * The leading data path is the path before the wildcard, so job.name.*.id would give job.name
         * This means we don't have to bother searching through extra data to get to our desired array
         * level.
         */
        $leadingFieldDataPath = rtrim(explode('*', $originalDataPath)[0], '.') ?: null;

        /*
         * Based on the leading data path, get the fields data in a flattered format which,
         * allows us to quickly process a fields data quickly instead of using a multi dimensional
         * array.
         */
        $fieldDataBasedOnPath = $this->flatternData(
            $this->getValue($leadingFieldDataPath),
            $leadingFieldDataPath . '.'
        );

        // Making sure we have at least one element in our data array, return the data or false when none at all
        return count($fieldDataBasedOnPath) >= 1 ? $fieldDataBasedOnPath : false;
    }

    protected function validateInteger($field, $value)
    {
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    protected function validateIP($field, $value)
    {
        return filter_var($value, FILTER_VALIDATE_IP) !== false;
    }

    protected function validateIPv4($field, $value)
    {
        return filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
    }

    protected function validateIPv6($field, $value)
    {
        return filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
    }

    protected function validateIPNotPriv($field, $value)
    {
        return filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE) !== false;
    }

    protected function validateIPNotRes($field, $value)
    {
        return filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE) !== false;
    }

    protected function validateJSON($field, $value)
    {
        if(!is_string($value))
        {
            return false;
        }

        try
        {
            json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        }
        catch(JsonException $jsonException)
        {
            return false;
        }

        return true;
    }

    protected function validateMax($field, $value, $parameters)
    {
        if($value instanceof UploadedFile && $value->isValid() === false)
        {
            return false;
        }

        return $this->getFieldSize($field, $value) <= $parameters[0];
    }

    protected function validateMimesAllowed($field, $value, $parameters)
    {
        // Make sure we have an actual uploaded file that is valid
        if(!$value instanceof UploadedFile || $value->isValid() === false)
        {
            return false;
        }

        // Check that we have an actual file path
        if($value->path() === '')
        {
            return false;
        }

        // Get the mime type using this PHP function, to be checked against other sources
        $mimeContentType = mime_content_type($value->fullPath());

        // Using the PHP-ext finfo we can also get the mime type, working as another source
        $fileInfoInstance = new finfo(FILEINFO_MIME_TYPE);

        if($fileInfoInstance === false)
        {
            return false;
        }

        // Using the PHP-ext finfo, get the mime-type to use as another source/check
        $fileInfoMimeType = $fileInfoInstance->file($value->fullPath());

        // Using the Linux 'file' command, get the mime-type from the command line
        $fileMimeCommand = 'file -b --mime-type %s 2>/dev/null';
        exec(sprintf($fileMimeCommand, escapeshellarg($value->fullPath())), $mimeTypeFromFileCommand);

        // If the 'file' command return value was valid it will be an array and have one element
        if(!is_array($mimeTypeFromFileCommand) && !isset($mimeTypeFromFileCommand[0]))
        {
            // Return false because if the file command fails, it means we cannot validated the file type fully
            return false;
        }

        // The mime type from the file command returns an array, we only need the first element
        $mimeTypeFromFileCommand = $mimeTypeFromFileCommand[0];

        // Store the detected mime types into an array for easy processing
        $detectedMimeTypes = explode(' ', "$mimeContentType $fileInfoMimeType $mimeTypeFromFileCommand");

        // Check that all the detected mime types are the same, otherwise it means the file type is ambiguous
        if(count(array_unique($detectedMimeTypes)) > 1)
        {
            return false;
        }

        // The final detected mime type should be the only mime type from the detected array of mime types
        $detectedMimeType = $detectedMimeTypes[0];

        // Finally, check to see if the detected mime type matches any of the given parameters
        return in_array($detectedMimeType, $parameters, true);
    }

    protected function validateMin($field, $value, $parameters)
    {
        if($value instanceof UploadedFile && $value->isValid() === false)
        {
            return false;
        }

        return $this->getFieldSize($field, $value) >= $parameters[0];
    }

    protected function validateNotWithin($field, $value, $parameters)
    {
        return !$this->validateWithin($field, $value, $parameters);
    }

    protected function validateRegex($field, $value, $parameters)
    {
        if(!is_string($value) && !is_numeric($value))
        {
            return false;
        }

        return preg_match($parameters[0], $value) > 0;
    }

    protected function validateRegexNot($field, $value, $parameters)
    {
        if(!is_string($value) && !is_numeric($value))
        {
            return false;
        }

        return preg_match($parameters[0], $value) < 1;
    }

    /*
     * Always returns true because it allows us to use
     * 'Optional' in the rules for a field.
     */
    protected function validateOptional($field, $value)
    {
        return true;
    }

    protected function validatePasswordAuth($field, $value, $parameters)
    {
        // We must have the protector that we want to use
        if(count($parameters) === 0)
        {
            return false;
        }

        // The user must already be logged in
        if($this->auth->user() === false)
        {
            return false;
        }

        // Validate a users password
        if($parameters[0] === 'web')
        {
            $password = ['password' => $value];
            $user = $this->auth->user();

            return $this->auth->protector('session')->hasValidCredentials($user, $password);
        }

        // Validate a given client ID and API key
        if($parameters[0] === 'api' && is_array($value))
        {
            if(!isset($value[0]) && !isset($value[1]))
            {
                return false;
            }

            $apiCredentials['ClientID'] = $value[0];
            $apiCredentials['Authorization'] = $value[1];

            return $this->auth->protector('token')->attemptTokenAuthentication($apiCredentials);
        }

        return false;
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

    protected function validateRequiredIf($field, $value, $parameters)
    {
        [$parameterValues, $otherFieldValue] = $this->prepareParameterValuesAndOtherFieldValue($parameters);

        if(in_array($otherFieldValue, $parameterValues, true))
        {
            return $this->validateRequired($field, $value);
        }

        return true;
    }

    protected function validateRequiredUnless($field, $value, $parameters)
    {
        [$parameterValues, $otherFieldValue] = $this->prepareParameterValuesAndOtherFieldValue($parameters);

        if(!in_array($otherFieldValue, $parameterValues, true))
        {
            return $this->validateRequired($field, $value);
        }

        return true;
    }

    protected function prepareParameterValuesAndOtherFieldValue($parameters)
    {
        $otherFieldValue = $parameters[0];

        // We don't need the first parameter as it is the name of the other field and not a value
        $parameterValues = array_slice($parameters, 1);

        // Convert bool values if the other field is using a valid bool type
        if(is_bool($otherFieldValue))
        {
            $parameterValues = $this->convertParameterValuesToProperBooleans($parameterValues);
        }

        return [$parameterValues, $otherFieldValue];
    }

    protected function convertParameterValuesToProperBooleans($parameterValues)
    {
        return array_map(function($parameterValues)
        {
            if($parameterValues === 'true')
            {
                return true;
            }

            if($parameterValues === 'false')
            {
                return false;
            }

            return $parameterValues;

        }, $parameterValues);
    }

    protected function validateRequiredWithAny($field, $value, $parameters)
    {
        if($this->allParametersFailBeingRequired($parameters) === false)
        {
            return $this->validateRequired($field, $value);
        }

        return true;
    }

    protected function validateRequiredWithAll($field, $value, $parameters)
    {
        if($this->anyParametersFailBeingRequired($parameters) === false)
        {
            return $this->validateRequired($field, $value);
        }

        return true;
    }

    protected function validateRequiredWithoutAny($field, $value, $parameters)
    {
        if($this->anyParametersFailBeingRequired($parameters))
        {
            return $this->validateRequired($field, $value);
        }

        return true;
    }

    protected function validateRequiredWithoutAll($field, $value, $parameters)
    {
        if($this->allParametersFailBeingRequired($parameters))
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

    protected function anyParametersFailBeingRequired(array $parameters)
    {
        foreach($parameters as $parameter)
        {
            if(!$this->validateRequired(null, $parameter))
            {
                return true;
            }
        }

        return false;
    }

    protected function validateSize($field, $value, $parameters)
    {
        // Do not check types here because one may be a string integer and we want them to match freely
        return $this->getFieldSize($field, $value) == $parameters[0];
    }

    protected function validateString($field, $value)
    {
        return is_string($value);
    }

    protected function validateValidTimezone($field, $value)
    {
        return in_array($value, timezone_identifiers_list(), true);
    }

    protected function validateValidURL($field, $value)
    {
        if(!is_string($value))
        {
            return false;
        }

        /*
         * This pattern is originally derived from Symfony\Component\Validator\Constraints\UrlValidator (5.0.7).
         *
         * (C) Fabien Potencier <fabien@symfony.com> http://symfony.com
         */
        $validURLPattern = '~^
            ((aaa|aaas|about|acap|acct|acd|acr|adiumxtra|adt|afp|afs|aim|amss|android|appdata|apt|ark|attachment|aw|barion|beshare|bitcoin|bitcoincash|blob|bolo|browserext|calculator|callto|cap|cast|casts|chrome|chrome-extension|cid|coap|coap\+tcp|coap\+ws|coaps|coaps\+tcp|coaps\+ws|com-eventbrite-attendee|content|conti|crid|cvs|dab|data|dav|diaspora|dict|did|dis|dlna-playcontainer|dlna-playsingle|dns|dntp|dpp|drm|drop|dtn|dvb|ed2k|elsi|example|facetime|fax|feed|feedready|file|filesystem|finger|first-run-pen-experience|fish|fm|ftp|fuchsia-pkg|geo|gg|git|gizmoproject|go|gopher|graph|gtalk|h323|ham|hcap|hcp|http|https|hxxp|hxxps|hydrazone|iax|icap|icon|im|imap|info|iotdisco|ipn|ipp|ipps|irc|irc6|ircs|iris|iris\.beep|iris\.lwz|iris\.xpc|iris\.xpcs|isostore|itms|jabber|jar|jms|keyparc|lastfm|ldap|ldaps|leaptofrogans|lorawan|lvlt|magnet|mailserver|mailto|maps|market|message|mid|mms|modem|mongodb|moz|ms-access|ms-browser-extension|ms-calculator|ms-drive-to|ms-enrollment|ms-excel|ms-eyecontrolspeech|ms-gamebarservices|ms-gamingoverlay|ms-getoffice|ms-help|ms-infopath|ms-inputapp|ms-lockscreencomponent-config|ms-media-stream-id|ms-mixedrealitycapture|ms-mobileplans|ms-officeapp|ms-people|ms-project|ms-powerpoint|ms-publisher|ms-restoretabcompanion|ms-screenclip|ms-screensketch|ms-search|ms-search-repair|ms-secondary-screen-controller|ms-secondary-screen-setup|ms-settings|ms-settings-airplanemode|ms-settings-bluetooth|ms-settings-camera|ms-settings-cellular|ms-settings-cloudstorage|ms-settings-connectabledevices|ms-settings-displays-topology|ms-settings-emailandaccounts|ms-settings-language|ms-settings-location|ms-settings-lock|ms-settings-nfctransactions|ms-settings-notifications|ms-settings-power|ms-settings-privacy|ms-settings-proximity|ms-settings-screenrotation|ms-settings-wifi|ms-settings-workplace|ms-spd|ms-sttoverlay|ms-transit-to|ms-useractivityset|ms-virtualtouchpad|ms-visio|ms-walk-to|ms-whiteboard|ms-whiteboard-cmd|ms-word|msnim|msrp|msrps|mss|mtqp|mumble|mupdate|mvn|news|nfs|ni|nih|nntp|notes|ocf|oid|onenote|onenote-cmd|opaquelocktoken|openpgp4fpr|pack|palm|paparazzi|payto|pkcs11|platform|pop|pres|prospero|proxy|pwid|psyc|pttp|qb|query|redis|rediss|reload|res|resource|rmi|rsync|rtmfp|rtmp|rtsp|rtsps|rtspu|s3|secondlife|service|session|sftp|sgn|shttp|sieve|simpleledger|sip|sips|skype|smb|sms|smtp|snews|snmp|soap\.beep|soap\.beeps|soldat|spiffe|spotify|ssh|steam|stun|stuns|submit|svn|tag|teamspeak|tel|teliaeid|telnet|tftp|things|thismessage|tip|tn3270|tool|turn|turns|tv|udp|unreal|urn|ut2004|v-event|vemmi|ventrilo|videotex|vnc|view-source|wais|webcal|wpid|ws|wss|wtai|wyciwyg|xcon|xcon-userid|xfire|xmlrpc\.beep|xmlrpc\.beeps|xmpp|xri|ymsgr|z39\.50|z39\.50r|z39\.50s))://    # protocol
            (([\_\.\pL\pN-]+:)?([\_\.\pL\pN-]+)@)?                              # basic auth
            (
                ([\pL\pN\pS\-\_\.])+(\.?([\pL\pN]|xn\-\-[\pL\pN-]+)+\.?)        # a domain name
                    |                                                           # or
                \d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}                              # an IP address
                    |                                                           # or
                \[
                    (?:(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){6})(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:::(?:(?:(?:[0-9a-f]{1,4})):){5})(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:[0-9a-f]{1,4})))?::(?:(?:(?:[0-9a-f]{1,4})):){4})(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){0,1}(?:(?:[0-9a-f]{1,4})))?::(?:(?:(?:[0-9a-f]{1,4})):){3})(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){0,2}(?:(?:[0-9a-f]{1,4})))?::(?:(?:(?:[0-9a-f]{1,4})):){2})(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){0,3}(?:(?:[0-9a-f]{1,4})))?::(?:(?:[0-9a-f]{1,4})):)(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){0,4}(?:(?:[0-9a-f]{1,4})))?::)(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){0,5}(?:(?:[0-9a-f]{1,4})))?::)(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){0,6}(?:(?:[0-9a-f]{1,4})))?::))))
                \]                                                              # an IPv6 address
            )
            (:[0-9]+)?                                                          # a port (optional)
            (?:/ (?:[\pL\pN\-._\~!$&\'()*+,;=:@]|%%[0-9A-Fa-f]{2})* )*          # a path
            (?:\? (?:[\pL\pN\-._\~!$&\'\[\]()*+,;=:@/?]|%%[0-9A-Fa-f]{2})* )?   # a query (optional)
            (?:\# (?:[\pL\pN\-._\~!$&\'()*+,;=:@/?]|%%[0-9A-Fa-f]{2})* )?       # a fragment (optional)
        $~ixu';

        return preg_match($validURLPattern, trim($value)) > 0;
    }
}