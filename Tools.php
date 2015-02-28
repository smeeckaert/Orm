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

    public static function implodeWithKeys($array, $keyValueSeparator = ' = ', $elementsSeparator = ' AND ', $protects = array('`', '"'))
    {
        if (!is_array($array)) {
            return $array;
        }
        $elements = array();
        foreach ($array as $key => $value) {
            $elements[] = static::implode(array(static::protect($key, $protects[0]), static::protect($value, $protects[1])), $keyValueSeparator);
        }
        return static::implode($elements, $elementsSeparator);
    }
}