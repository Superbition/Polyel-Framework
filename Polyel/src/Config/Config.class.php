<?php

namespace Polyel\Config;

class Config
{
    // Config holds the whole application configuration values
    private $config;
    private $configDirPath = ROOT_DIR . "/config";

    // env config holds the whole env configuration
    private $envConfig;
    private $envPath = ROOT_DIR . "/env/.env";

    public function __construct()
    {

    }

    public function load()
    {
        // Only load the .env file if it has been created
        if(file_exists($this->envPath))
        {
            // Parse the main env config file.
            $this->envConfig = parse_ini_file($this->configDirPath . "/env/.env", true);
        }

        // Scan the config directory and get all the files in there
        $configFiles = scandir($this->configDirPath);

        // Loop through and load each config file dynamically based on the file name
        foreach ($configFiles as $configFile)
        {
            // Each config file must be a .php file and is split based on the dot to get the name of the config
            if(preg_match('/^.+\.php$/i', $configFile))
            {
                // Split on the dot to get the name of the config, the file would be file.php and config would be "file"
                $configName = explode(".", $configFile)[0];

                // Dynamically load the configuration and use the file name as the config name
                $this->config[strtolower($configName)] = require_once $this->configDirPath . "/" . $configFile;
            }
        }
    }

    public function reload()
    {
        $this->load();
    }

    public function get($configRequest)
    {
        $configRequest = explode(".", $configRequest);

        $configKey = $this->configMap[$configRequest[0]];

        switch($configKey)
        {
            case 0:

                return $this->main[$configRequest[1]];

                break;

            case 1:

                return $this->database[$configRequest[1]][$configRequest[2]];

                break;

            case 2:

                return $this->path[$configRequest[1]];

                break;

            case 3:

                return $this->template[$configRequest[1]];

                break;
        }
    }

    public function env($envRequest, $defaultValue)
    {
        // Split the incoming env request in the format of: Category.Parameter
        $envRequest = explode(".", $envRequest);

        // Check to see if the requested parameter exists and return it if true.
        if(isset($this->envConfig[$envRequest[0]][$envRequest[1]]) && !empty($this->envConfig[$envRequest[0]][$envRequest[1]]))
        {
            return $this->envConfig[$envRequest[0]][$envRequest[1]];
        }
        else
        {
            // Else return the default argument passed in.
            return $defaultValue;
        }
    }
}