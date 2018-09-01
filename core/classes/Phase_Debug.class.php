<?php

class Phase_Debug
{
    public static $lastDump = NULL;

    public static function dump($input = NULL)
    {
        if(!empty(self::$lastDump))
        {
            self::$lastDump .= "<br>";
        }

        self::$lastDump .= var_export($input, True);
    }

    public static function doDumpsExist()
    {
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
}