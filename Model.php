<?php

namespace Orm;

use Orm\Model\Exception\Unique;
use Fuel\Core\Arr;

abstract class Model
{
    static protected $_id;
    static protected $_table;
    static protected $_prefix;
    static protected $_unique;

    private $_prefixLen;

    /**
     * Find a matching set of elements
     *
     * @param $properties
     *
     * @return Model
     */
    public static function find($properties)
    {
        if (!is_array($properties)) {
            $properties = array('where' => static::$_id . ' = ' . $properties);
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
        if ($result->rowCount() == 1) {
            return current($results);
        }
        return $results;
    }

    protected function dbToProp($field)
    {
        if (empty($this->_prefixLen)) {
            $this->_prefixLen = strlen(static::$_prefix) + 1;
        }
        return substr($field, $this->_prefixLen);
    }

    protected function propToDb($field)
    {
        return static::$_prefix . '_' . $field;
    }

    public function __construct($data = null)
    {
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                $prop        = static::dbToProp($key);
                $this->$prop = $value;
            }
        }
    }

    protected function before_save()
    {
        if (!empty(static::$_unique)) {
            $where = array();
            foreach (static::$_unique as $unique) {
                $where[] = Tools::implode($this->propToDb($unique), '', '`') . ' = ' . Tools::implode($this->$unique, '', '"');
            }
            $matches = static::find(array('where' => Tools::implode($where, ' OR ')));

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
        $idField = static::$_id;
        if (empty($this->$idField)) {
            $this->idField = $this->insert();
        } else {
            $this->update();
        }
        $this->after_save();
    }

    public function delete()
    {
        return DB::query(static::buildQuery('delete', array('limit' => '1', 'where' => Tools::implodeWithKeys($this->getFields()))));
    }

    private function insert()
    {
        $query = static::buildQuery('insert', array('values' => $this->getFields()));
        echo $query;
        DB::query($query);
    }

    protected function getFields()
    {
        $properties = get_object_vars($this);
        $dbFields   = array();
        foreach ($properties as $key => $value) {
            if ($key[0] == '_' || empty($value)) {
                continue;
            }
            $dbFields[$this->propToDb($key)] = $value;
        }
        return $dbFields;
    }

    private static function buildQuery($type, $properties)
    {
        $properties = Arr::merge(static::defaultProperties(), $properties);
        $type       = strtoupper($type);
        $from       = 'FROM';
        if ($type == 'INSERT') {
            $from = 'INTO';
        }
        $query = "$type " . Tools::implode($properties['fields']) . " $from `" . static::$_table . '`';
        if (!empty($properties['values'])) {
            $query .= " (" . Tools::implode(array_keys($properties['values']), ',', '`') . ") VALUES (" . Tools::implode($properties['values'], ',', '"') . ")";
        }
        if (!empty($properties['where'])) {
            $query .= " WHERE " . $properties['where'];
        }
        if (!empty($properties['limit'])) {
            $query .= " LIMIT " . $properties['limit'];
        }
        return $query;
    }

    private static function defaultProperties()
    {
        return array(
            'fields' => '',
            'where'  => '',
            'order'  => array(),
            'limit'  => '',
            'value'  => ''
        );
    }
}