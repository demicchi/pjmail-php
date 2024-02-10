<?php

namespace StudioDemmys\pjmail;

$__pjmail_global_config = null;

class Config
{
    const CONFIG_FILE = "./config/config.yml";
    
    protected function __construct()
    {
    }

    public static function loadConfig()
    {
        global $__pjmail_global_config;
        $config_path = Common::getAbsolutePath(self::CONFIG_FILE);
        $__pjmail_global_config = yaml_parse_file($config_path);
        if ($__pjmail_global_config === false) {
            $__pjmail_global_config = null;
            throw new \Exception("[FATAL] failed to load " . $config_path . ".");
        }
    }
    
    public static function getConfig(string $key)
    {
        global $__pjmail_global_config;
        $key_array = explode("/", $key);
        if (empty($key_array))
            throw new \Exception("[FATAL](getConfig) config key is empty.");
        $target_config =& $__pjmail_global_config;
        foreach ($key_array as $key_part) {
            if (isset($target_config[$key_part])) {
                $target_config =& $target_config[$key_part];
            } else {
                return null;
            }
        }
        return $target_config;
    }
    
    public static function setConfig(string $key, ?string $value = null)
    {
        global $__pjmail_global_config;
        $key_array = explode("/", $key);
        if (empty($key_array))
            throw new \Exception("[FATAL](setConfig) config key is empty.");
        $target_config =& $__pjmail_global_config;
        foreach ($key_array as $key_part) {
            if (!isset($target_config[$key_part])) {
                $target_config[$key_part] = null;
            }
            $target_config =& $target_config[$key_part];
        }
        $target_config = $value;
    }
    
    public static function getConfigOrSetIfUndefined(string $key, ?string $default_value = null)
    {
        $value = self::getConfig($key);
        if (is_null($value)) {
            self::setConfig($key, $default_value);
            return $default_value;
        }
        return $value;
    }
}