<?php

  class Phase_Time
  {
    // Default global class time format.
    private static $timeFormat = "Y-m-d H:i:s";

    public static function now($timeFormatToUse = null)
    {
      // Use the default time format if no time format is passed in.
      $timeFormat = self::$timeFormat;
      if(isset($timeFormatToUse))
      {
        $timeFormat = $timeFormatToUse;
      }

      $now = new DateTime("now");

      return $now->format($timeFormat);
    }

    public static function validateDate($dateToCheck)
    {
      $d = DateTime::createFromFormat(self::$timeFormat, $dateToCheck);
      return $d && $d->format(self::$timeFormat) === $dateToCheck;
    }

    public static function intervalCheck($dateTimeToCheck, $interval, $timeFormatToUse = null)
    {
      // Use the default time format if no time format is passed in.
      $timeFormat = self::$timeFormat;
      if(isset($timeFormatToUse))
      {
        $timeFormat = $timeFormatToUse;
      }

      $dateTimeToCheck = DateTime::createFromFormat($timeFormat, $dateTimeToCheck);
      $dateTimeToCheck->modify($interval);
      $now = DateTime::createFromFormat($timeFormat, self::now($timeFormat));

      // Check to see if the dateTime to check has passed or is equal to the current dateTime.
      if($dateTimeToCheck <= $now)
      {
        // True if the dateTime to check has passed or is equal to the current dateTime.
        return true;
      }
      else
      {
        // False if the dateTime to check has not passed or not equal to the current dateTime.
        return false;
      }
    }

    public static function diff($startDate, $endDate = null)
    {
      if(isset($endDate))
      {
        if(!self::validateDate($endDate))
        {
          return false;
        }

        $endDate = DateTime::createFromFormat(self::$timeFormat, $endDate);
      }
      else
      {
        $endDate = DateTime::createFromFormat(self::$timeFormat, self::now());
      }

      if(!self::validateDate($startDate))
      {
        return false;
      }

      $startDate = DateTime::createFromFormat(self::$timeFormat, $startDate);
      $difference = $startDate->diff($endDate);

      $dateTimeElapsed = $difference->format('%y|%m|%a|%h|%i|%S');

      $outPuts = [
        "years", "months", "days", "hours", "minutes", "seconds"
      ];

      $explodedDateString = explode("|", $dateTimeElapsed);

      $dateTimeElapsed = array_combine($outPuts, $explodedDateString);

      $finalDateDiff = 0;
      foreach($dateTimeElapsed as $string => $value)
      {
        if($value > 0)
        {
          if($value == 1)
          {
            $string = substr($string, 0, -1);
          }
          $finalDateDiff = $value . " " . $string;
          break;
        }
      }

      return $finalDateDiff;
    }

    public static function addTimeToDate($dateToAddTo, $timeToAdd)
    {
      $dateTime = DateTime::createFromFormat(self::$timeFormat, $dateToAddTo);
      $dateTime->modify("+" . $timeToAdd);
      return $dateTime->format(self::$timeFormat);
    }

    /*
      This function is to be used to convert universal time to another time
      format. The first parameter must of a date datatype, and the second is
      an optional string format of a date. If left out, it will default to
      standard british time.
      The function will return a string.
    */
    public static function convertUniversalDate($dateIn, $timeFormat = null)
    {
      // If the date passed in is a string
      if(gettype($dateIn) == "string")
      {
        // Convert the string to a date datatype
        $date = strtotime($dateIn);
        // If the format has been set, use it
        if(isset($timeFormat))
        {
          return (string)date($timeFormat, $date);
        }
        // If not, use the default
        else
        {
          return (string)date('d-m-Y H:i:s', $date);
        }
      }
      // else it is a date and it doesn't need converting first
      else
      {
        // If the format has been set, use it
        if(isset($timeFormat))
        {
          return (string)date($timeFormat, $dateIn);
        }
        // If not, use the default
        else
        {
          return (string)date('d-m-Y H:i:s', $dateIn);
        }
      }
    }
  }