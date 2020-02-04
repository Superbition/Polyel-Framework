<?php

class Debug
{
    public static $lastDump = NULL;

    public static function dump($input = NULL)
    {
        // Add a break line when there is already a dump.
        if(!empty(self::$lastDump))
        {
            // Used to help break up when multiple dumps exist
            self::$lastDump .= "<br>";
        }

        // Export the dump into a variable. This is basically var_dump().
        self::$lastDump .= var_export($input, True);
    }

    public static function doDumpsExist()
    {
        // Check if dumps exist and return true or false
        if(!empty(self::$lastDump))
        {
            return True;
        }
        else
        {
            return False;
        }
    }

    public static function getDumps()
    {
        return self::$lastDump;
    }

    public static function cleanup()
    {
        // Resets the last amount of dumps so duplicates are not shown upon next request
        self::$lastDump = NULL;
    }
}