<?php


namespace Orm;

class Tools
{
    public static function implode($data, $glue = ',', $protect = '')
    {

        if (is_array($data)) {
            if (!empty($protect)) {
                array_walk($data, function (&$i, $key, $protect) {
                    $i = static::protect($i, $protect);
                }, $protect);
            }
            return implode($glue, $data);
        }
        return static::protect($data, $protect);
    }

    public static function protect($str, $protect)
    {
        return $protect . str_replace($protect, "\\$protect", $str) . $protect;
    }
}