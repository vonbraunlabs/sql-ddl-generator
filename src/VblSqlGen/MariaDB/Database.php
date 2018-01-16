<?php

namespace VblSqlGen\MariaDB;

define('COMMENT_SEPARATOR', '-- -----------------------------------------------------' . PHP_EOL);

class Database
{
    public function __construct(array $databaseModel, $create_schema = true)
    {
        $this->name = $databaseModel['name'];
        $this->create_schema = $create_schema;
        $this->tableList = [];

        if (array_key_exists('table', $databaseModel)) {
            $this->tableList[0] = new \VblSqlGen\MariaDB\Table($databaseModel['table'], $this->name);
        }
        
        if (array_key_exists('table_list', $databaseModel)) {
            foreach ($databaseModel['table_list'] as $tableModel) {
                $this->tableList[] = new \VblSqlGen\MariaDB\Table($tableModel, $this->name);
            }
        }
    }

    public function __toString()
    {
        $str = '';
        
        if ($this->create_schema) {
            $str .= COMMENT_SEPARATOR;
            $str .= "-- Database {$this->name}" . PHP_EOL;
            $str .= COMMENT_SEPARATOR;
            $str .= PHP_EOL;
            $str .= "CREATE SCHEMA IF NOT EXISTS `{$this->name}` DEFAULT CHARACTER SET utf8 ;" . PHP_EOL;
            $str .= "USE `{$this->name}`;" . PHP_EOL;
            $str .= PHP_EOL;
        }

        foreach ($this->tableList as $table) {
            $str .= $table . PHP_EOL;
        }

        return $str;
    }

    protected $name;
    protected $tableList;
    protected $create_schema;
}
