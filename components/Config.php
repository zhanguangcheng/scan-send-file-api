<?php

class Config
{
    protected static $config = [];

    public static function set($key, $value = null)
    {
        if (is_null($value)) {
            self::$config = $key;
            return;
        }
        self::$config[$key] = $value;
    }

    public static function get($key = null, $defaultValue = null)
    {
        if (is_null($key)) {
            return self::$config;
        }
        return array_key_exists($key, self::$config) ? self::$config[$key] : $defaultValue;
    }
}
