<?php 

if (!function_exists('byteLength')) {
    function byteLength($value)
    {
        if(is_scalar($value)) {
            $var = (string) $value;
        } else {
            $var = serialize($value);
        }

        return mb_strlen($var, '8bit');
    }
}