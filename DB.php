<?php

namespace Orm;

class DB
{
    /**
     * @var \PDO $dbh
     */
    private $dbh;
    static private $_instance;
    private $dbInfos;

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
        $this->dbInfos = array('dsn' => $dsn, 'user' => $user, 'password' => $password, 'options' => $options);
    }

    public function initDbh()
    {
        if (empty($this->dbh)) {
            $this->dbh = new \PDO($this->dbInfos['dsn'], $this->dbInfos['user'], $this->dbInfos['password'], $this->dbInfos['options']);
        }
    }

    public static function query($sql)
    {
        return static::i()->_query($sql);
    }

    public static function lastId()
    {
        return static::i()->_lastInsertId();
    }

    public function _lastInsertId()
    {
        $this->initDbh();
        return $this->dbh->lastInsertId();
    }

    public function _query($sql)
    {
        $this->initDbh();
        $query = $this->dbh->query($sql);
        if (!$query) {
            throw new \Exception(Tools::implode($this->dbh->errorInfo(), ' :: '));
        }
        return $query;
    }


}