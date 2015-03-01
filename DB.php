<?php

namespace Orm;

class DB
{
    /**
     * @var \PDO $dbh
     */
    private $dbh;
    static private $_instance;

    /**
     * @return DB
     */
    public static function i()
    {
        return static::$_instance;
    }

    /**
     * Alias for i()
     *
     * @return DB
     */
    public static function instance()
    {
        return static::i();
    }

    /**
     * Call this method to instianciated the database connection
     *
     * @param      $dsn
     * @param null $user
     * @param null $password
     * @param null $options
     */
    public static function init($dsn, $user = null, $password = null, $options = null)
    {
        if (empty(static::$_instance)) {
            static::$_instance = new static($dsn, $user, $password, $options);
        }
    }

    public function __construct($dsn, $user, $password, $options)
    {
        $this->dbh = new \PDO($dsn, $user, $password, $options);
    }

    public static function query($sql)
    {
        echo $sql . "<br>";
        return static::i()->_query($sql);
    }

    public function _query($sql)
    {
        $query = $this->dbh->query($sql);
        if (!$query) {
            throw new \Exception(Tools::implode($this->dbh->errorInfo(), ' :: '));
        }
        return $query;
    }


}