<?php

namespace Welves\LaravelPix\Tools;

class Arr
{
    public static function has($data, $key)
    {
        return array_key_exists($key, $data);
    }


    public static function get($data, $key)
    {
        return $data[$key];
    }
}
