<?php

class Phase_Config
{
    private static $configDir = __DIR__ . "/../../config/";

    private static $main;
    private static $database;
    private static $path;
    private static $template;

    private static $configMap = [
      "main" => 0,
      "database" => 1,
      "path" => 2,
      "template" => 3,
    ];

    public static function load()
    {
        self::$main = require self::$configDir . "main.php";
        self::$database = require self::$configDir . "database.php";
        self::$path = require self::$configDir . "path.php";
        self::$template = require self::$configDir . "template.php";
    }

    public static function reload()
    {
        self::load();
    }

    public static function get($configRequest)
    {
        $configRequest = explode(".", $configRequest);

        $configKey = self::$configMap[$configRequest[0]];

        switch($configKey)
        {
            case 0:

                return self::$main[$configRequest[1]];

                break;

            case 1:

                return self::$database[$configRequest[1]];

                break;

            case 2:

                return self::$path[$configRequest[1]];

                break;

            case 3:

                return self::$template[$configRequest[1]];

                break;
        }
    }
}