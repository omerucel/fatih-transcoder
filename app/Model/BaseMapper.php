<?php

namespace Model;

abstract class BaseMapper
{
    /**
     * @var \PDO
     */
    protected $pdo = null;

    /**
     * @param \PDO $pdo
     */
    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }
}