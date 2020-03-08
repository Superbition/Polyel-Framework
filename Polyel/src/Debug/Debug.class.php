<?php

namespace Polyel\Debug;

class Debug
{
    public $lastDump = NULL;

    public function __construct()
    {

    }

    public function dump($input = NULL)
    {
        // Add a break line when there is already a dump.
        if(!empty($this->lastDump))
        {
            // Used to help break up when multiple dumps exist
            $this->lastDump .= "<br>";
        }

        // Export the dump into a variable. This is basically var_dump().
        $this->lastDump .= var_export($input, True);
    }

    public function doDumpsExist()
    {
        // Check if dumps exist and return true or false
        if(!empty($this->lastDump))
        {
            return True;
        }
        else
        {
            return False;
        }
    }

    public function getDumps()
    {
        return $this->lastDump;
    }

    public function cleanup()
    {
        // Resets the last amount of dumps so duplicates are not shown upon next request
        $this->lastDump = NULL;
    }
}