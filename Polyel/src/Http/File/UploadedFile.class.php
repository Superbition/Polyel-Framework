<?php

namespace Polyel\Http\File;

use SplFileInfo;
use Polyel\Storage\Facade\Storage;

class UploadedFile extends SplFileInfo
{
    // The real file name of the uploaded file
    public $realName;

    // The file type based on the Swoole info of the upload
    public $type;

    // The extension of the tmp uploaded file
    public $extension;

    // The type detected by Swoole
    private $swooleType;

    // the type detected by Polyel
    private $polyelType;

    // The temp file path and name where the file was uploaded
    public $tmpName;

    // Upload error code
    public $errorCode;

    // The file size in bytes
    public $size;

    // Bool used to set if the file uploaded is valid and without any errors
    private $isValid;

    private $errors;

    // Used to know when and when not to generate unique file names
    private $storeAsFlag;

    public function __construct($uploadedFiles, $fileName)
    {
        // Make sure both the uploadedFiles array is set and the fileName is not empty
        if(!exists($uploadedFiles) || !exists($fileName))
        {
            // The file upload failed...
            $this->isValid = false;
            $this->addError(0, "No uploaded files exist or file name is missing");
        }
        else
        {
            // Get the file status after processing the new uploaded file...
            $fileStatus = $this->process($uploadedFiles, $fileName);

            // If the file status is true, it has passed the first validation stage done by process()
            if($fileStatus)
            {
                // Give SplFileInfo it's required file path and name...
                parent::__construct($this->tmpName);

                // Using SplFileInfo set the ext of the file
                $this->extension = $this->getExtension();

                // Used to detect when to generate a unique file name when saving uploaded files, this is the default
                $this->storeAsFlag = false;
            }
        }
    }

    /*
     * Processes a newly uploaded file, runs a few checks making sure the file
     * can be used further with this class/service. Gets data from the upload and processes
     * it.
     */
    private function process($uploadedFiles, $fileName)
    {
        // Making sure the file we want actually exists in the uploads
        if(array_key_exists($fileName, $uploadedFiles) && exists($uploadedFiles[$fileName]["tmp_name"]))
        {
            // Process uploaded file information...
            $this->realName = $uploadedFiles[$fileName]["name"];
            $this->swooleType = $uploadedFiles[$fileName]["type"];
            $this->type = $this->swooleType;
            $this->tmpName = $uploadedFiles[$fileName]["tmp_name"];
            $this->polyelType = mime_content_type($this->tmpName);
            $this->errorCode = $uploadedFiles[$fileName]["error"];
            $this->size = $uploadedFiles[$fileName]["size"];

            // First validation phase, the file passes at this level and is deemed usable
            $this->isValid = true;

            return $this->isValid;
        }

        // The uploaded file does not exists or was not uploaded
        $this->isValid = false;
        $this->addError(1, "Failed processing uploaded temp file, no file info");
        return $this->isValid;
    }

    /*
     * Used to make sure the file is still valid and usable.
     */
    public function isValid()
    {
        // Check the upload error codes, 0 means everything is fine. Based on PHP upload errors.
        if($this->errorCode !== 0)
        {
            $this->isValid = false;
            $this->addError(2, "File upload failed, PHP error code " . $this->errorCode . " given");
        }

        // Check that both the Swoole and Polyel Mime types are the same
        if($this->swooleType !== $this->polyelType)
        {
            // Sometimes there is a mismatch between types when a file is uploaded but empty...
            if($this->polyelType !== "application/x-empty")
            {
                $this->isValid = false;
                $this->addError(3, "Type mismatch found, file not valid for processing");
            }
        }

        // Return the file validity status
        return $this->isValid;
    }

    public function errors()
    {
        return $this->errors;
    }

    private function addError($errorCode, $errorMsg)
    {
        $this->errors[$errorCode] = $errorMsg;
    }

    public function path()
    {
        return $this->getPath();
    }

    public function fullPath()
    {
        return $this->getRealPath();
    }

    public function extension()
    {
        return $this->extension;
    }

    public function getMimeType()
    {
        return $this->polyelType;
    }

    public function save($newFilePath, $diskToSaveTo)
    {
        // Check that the file is valid before trying to move it
        if($this->isValid())
        {
            // Trim off any supplied forward slashes which will interfere with file paths
            $newFilePath = ltrim($newFilePath, "/");
            $newFilePath = rtrim($newFilePath, "/");

            // When storeAsFlag is true we use the provided file name instead of a unique name
            if($this->storeAsFlag === false)
            {
                $ranPrefix = random_int(1, 100) . "-";
                $uniqueFileID = uniqid($ranPrefix, true);
                $ranSuffix = "-" . random_int(1, 100);
                $uniqueFileID .= $ranSuffix;
                $uniqueFileID = str_replace(".", "-", $uniqueFileID);

                $newFilePath .= "/" . $uniqueFileID;
            }

            // Reset the storeAsFlag
            $this->storeAsFlag = false;

            // Using the storage service, move the file into its new saved location
            $newSavedPath = Storage::drive($diskToSaveTo)->move($this->tmpName, "/" . $newFilePath, true);

            return $newSavedPath;
        }

        return false;
    }

    public function saveAs($newFilePath, $newFileName, $diskToSaveTo)
    {
        // Set the storeAsFlag so that the file will be saved with the provided file name
        $this->storeAsFlag = true;

        // Create the full file path with its provided file name
        $newFilePathAndName = $newFilePath . "/" . $newFileName;

        return $this->save($newFilePathAndName, $diskToSaveTo);
    }
}