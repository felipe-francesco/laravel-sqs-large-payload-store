<?php
if(!function_exists('config')) {
    function config($key, $defaultIfNotFound = null) {
        $keys = explode(".", $key);
        if(count($keys) < 2) {
            return "";
        }
        $config = include __DIR__. "/../config/{$keys[0]}.php";
        array_shift($keys);
        $key = implode(".", $keys);
        return isset($config[$key]) ? $config[$key] : $defaultIfNotFound;
    }
}

if(!function_exists('storage_path')) {
    function storage_path($key) {
        return __DIR__. '/../' . $key;
    }
}