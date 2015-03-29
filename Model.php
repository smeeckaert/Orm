<?php

namespace FW\Orm;

use FW\Orm\Model\Exception\Unique;


abstract class Model
{
    static protected $_id = 'id';
    static protected $_table;
    static protected $_prefix;
    static protected $_unique;
    protected static $_relations = array();
    private $_prefixLen;
    private $_relationships = array();

    private static function _id()
    {
        return static::$_id;
    }

    /**
     * Find a matching set of elements
     *
     * @param $properties
     *
     * @return Model
     */
    public static function find($properties, $forceArray = false)
    {
        if (!is_array($properties)) {
            $properties = array('and_where' => array(static::_id() => $properties));
        }
        if (empty($properties['fields'])) {
            $properties['fields'] = '*';
        }

        $query   = static::buildQuery('select', $properties);
        $result  = DB::query($query);
        $results = array();
        while (($row = $result->fetch(\PDO::FETCH_ASSOC))) {
            $results[] = new static($row);
        }
        if (!$forceArray && $result->rowCount() == 1) {
            return current($results);
        }
        return $results;
    }

    public function __get($name)
    {
        if (array_key_exists($name, static::$_relations)) {
            if (!isset($this->_relationships[$name])) {
                $rel      = static::$_relations[$name];
                $fromProp = $rel['from'];
                /** @var Model $model */
                $model  = $rel['model'];
                $params = array('and_where' => array($rel['to'] => $this->$fromProp));
                if (!empty($rel['conditions'])) {
                    $params = Tools::deepMerge($params, $rel['conditions']);
                }
                $this->_relationships[$name] = $model::find($params);
            }
            return $this->_relationships[$name];
        }
        $trace = debug_backtrace();
        trigger_error(
            'Undefined property via __get(): ' . $name .
            ' in ' . $trace[0]['file'] .
            ' on line ' . $trace[0]['line'],
            E_USER_NOTICE);
        return null;
    }

    /**
     * @param $relation
     *
     * @return Model
     */
    public function rel($relation)
    {
        if (array_key_exists($relation, static::$_relations)) {
            $rel      = static::$_relations[$relation];
            $fromProp = $rel['from'];
            $toProp   = $rel['to'];

            /** @var Model $model */
            $model         = $rel['model'];
            $item          = new $model();
            $item->$toProp = $this->$fromProp;
            return $item;
        }
        return null;
    }

    private function dbToProp($field)
    {
        if (empty($this->_prefixLen)) {
            $this->_prefixLen = strlen(static::$_prefix) + 1;
        }
        return substr($field, $this->_prefixLen);
    }

    public static function propToDb($field)
    {
        return static::$_prefix . '_' . $field;
    }

    public function __construct($data = null)
    {
        if (!empty($data)) {
            $this->import($data);
        }
    }

    /**
     * Import an array of databases rows into model properties
     *
     * @param $data
     */
    public function import($data)
    {
        foreach ($data as $key => $value) {
            $prop        = $this->dbToProp($key);
            $this->$prop = $value;
        }
    }

    protected function before_save()
    {
        if (!empty(static::$_unique)) {
            $where = array();
            foreach (static::$_unique as $unique) {
                $where[$unique] = $this->$unique;
            }
            $matches = static::find(array('or_where' => $where));

            if (!empty($matches)) {
                throw new Unique(current($matches));
            }
        }
    }

    protected function after_save()
    {

    }

    /**
     * Save into the schema
     *
     * @throws Unique
     */
    public function save()
    {
        $this->before_save();
        $idField = static::_id();
        if (empty($this->$idField)) {
            $this->$idField = $this->insert();
        } else {
            $this->update();
        }
        $this->after_save();
    }

    protected function id()
    {
        $idField = static::_id();
        return $this->$idField;
    }

    public function update()
    {
        return DB::query(static::buildQuery('update', array('set' => $this->getFields(), 'limit' => '1', 'and_where' => array(static::_id() => $this->id()))));
    }

    public function delete()
    {
        return DB::query(static::buildQuery('delete', array('limit' => '1', 'and_where' => $this->getFields())));
    }

    private function insert()
    {
        $query = static::buildQuery('insert', array('values' => $this->getFields()));
        DB::query($query);
        return DB::lastId();
    }

    private function getFields()
    {
        $properties = get_object_vars($this);
        $dbFields   = array();
        foreach ($properties as $key => $value) {
            if ($key[0] == '_') {
                continue;
            }
            $dbFields[$key] = $value;
        }
        return $dbFields;
    }

    /**
     * @param       $array
     * @param Model $item
     *
     * @return array
     */
    private static function keysToDb($array)
    {
        if (!is_array($array)) {
            return $array;
        }
        $db = array();
        foreach ($array as $key => $value) {
            if (is_string($key)) {
                $db[static::propToDb($key)] = static::keysToDb($value);
            } else {
                $db[$key] = static::keysToDb($value);
            }
        }
        return $db;
    }


    private static function buildQuery($type, $properties, $item = '')
    {
        $properties = array_merge(static::defaultProperties(), $properties);
        $type       = strtoupper($type);
        $from       = 'FROM';
        if ($type == 'INSERT') {
            $from = 'INTO';
        } else if ($type == 'UPDATE') {
            $from = '';
        }
        $query = "$type " . Tools::implode($properties['fields']) . " $from `" . static::$_table . '`';
        if (!empty($properties['values'])) {
            $query .= " (" . Tools::implode(array_keys(static::keysToDb($properties['values'])), ',', '`') . ") VALUES (" . Tools::implode($properties['values'], ',', '"') . ")";
        }
        if (!empty($properties['set'])) {
            $query .= " SET " . Tools::implodeWithKeys(static::keysToDb($properties['set']), $keyValueSeparator = ' = ', $elementsSeparator = ',');
        }
        if (!empty($properties['and_where'])) {
            $query .= " WHERE " . Tools::implodeWithKeys(static::keysToDb($properties['and_where']), $keyValueSeparator = ' = ', $elementsSeparator = ' AND ');
        } else if (!empty($properties['or_where'])) {
            $query .= " WHERE " . Tools::implodeWithKeys(static::keysToDb($properties['or_where']), $keyValueSeparator = ' = ', $elementsSeparator = ' OR ');
        } else if (!empty($properties['where'])) {
            $query .= " WHERE " . $properties['where'];
        }
        if (!empty($properties['order'])) {
            $query .= " ORDER BY " . Tools::implodeWithKeys(static::keysToDb($properties['order']), $keyValueSeparator = ' ', $elementsSeparator = ', ', array('`', ''));
        }
        if (!empty($properties['limit'])) {
            $query .= " LIMIT " . $properties['limit'];
        }
        return $query;
    }

    private static function defaultProperties()
    {
        return array(
            'fields'    => '',
            'where'     => '',
            'and_where' => '',
            'or_where'  => '',
            'order'     => array(),
            'limit'     => '',
            'value'     => ''
        );
    }
}