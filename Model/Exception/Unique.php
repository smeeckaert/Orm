<?php

namespace FW\Orm\Model\Exception;

use FW\Orm\Model;

/**
 * Class Unique
 * Thrown when a unique property matched on a model before saving
 *
 * @package Orm\Model\Exception
 */
class Unique extends \Exception
{
    private $model;

    public function __construct($model)
    {
        $this->model   = $model;
        $this->message = "Unique property matched";
    }

    /**
     * @return Model
     */
    public function getModel()
    {
        return $this->model;
    }
}