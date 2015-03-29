<?php


namespace FW\Orm;

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

    public static function merge($a1, $a2)
    {
        foreach ($a1 as $key => $value) {
            if (isset($a2[$key])) {
                $a1[$key] = $a2[$key];
                unset($a2[$key]);
            }
        }
        foreach ($a2 as $key => $value) {
            $a1[$key] = $value;
        }
        return $a1;
    }

    public static function deepMerge($a1, $a2)
    {
        foreach ($a1 as $key => $value) {
            if (isset($a2[$key])) {
                if (is_array($value)) {
                    $value = static::deepMerge($value, $a2[$key]);
                }
                $a2[$key] = $value;
            }
        }
        foreach ($a2 as $key => $value) {
            $a1[$key] = $value;
        }
        return $a1;
    }

    public static function implodeWithKeys($array, $keyValueSeparator = ' = ', $elementsSeparator = ' AND ', $protects = array('`', '"'))
    {
        if (!is_array($array)) {
            return $array;
        }
        $elements = array();
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $args       = static::merge(func_get_args(), $value);
                $elements[] = call_user_func_array(__CLASS__ . '::' . __FUNCTION__, $args);
                continue;
            }
            $elements[] = static::implode(array(static::protect($key, $protects[0]), static::protect($value, $protects[1])), $keyValueSeparator);
        }
        return static::implode($elements, $elementsSeparator);
    }
}