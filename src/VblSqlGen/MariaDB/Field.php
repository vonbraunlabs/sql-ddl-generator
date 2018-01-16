<?php

namespace VblSqlGen\MariaDB;

class Field
{
    public static $keyType = 'INT UNSIGNED';
    public function __construct(array $fieldModel, string $table)
    {
        $this->name = $fieldModel['name'];
        $this->type = isset($fieldModel['type']) ? (string) $fieldModel['type'] : Field::$keyType;
        $this->notNull = isset($fieldModel['not_null']) ? (bool) $fieldModel['not_null'] : false;
        $this->default = isset($fieldModel['default']) ? $fieldModel['default'] : null;
        $this->autoIncrement = isset($fieldModel['auto_increment']) ? (bool) $fieldModel['auto_increment'] : false;
        $this->pk = isset($fieldModel['pk']) ? (bool) $fieldModel['pk'] : false;
        $this->after = isset($fieldModel['after']) ? $fieldModel['after'] : null;
        $this->hideAudit = isset($fieldModel['hide_audit']) ? (bool) $fieldModel['hide_audit'] : false;
        $this->comment = isset($fieldModel['comment']) ? (string) $fieldModel['comment'] : null;
    }

    public function __toString()
    {
        $str = "`" . $this->name . "` " . $this->type;
        if ($this->notNull) {
            $str .= " NOT NULL";
        }

        if ($this->default) {
            $str .=" DEFAULT " . $this->default;
        }

        if ($this->autoIncrement) {
            $str .= " AUTO_INCREMENT";
        }

        if ($this->after) {
            $str .= " AFTER `$this->after`";
        }

        if (null !== $this->comment) {
            $str .= " COMMENT '{$this->comment}'";
        }

        return $str;
    }

    public function getName()
    {
        return $this->name;
    }

    public function isPK() : bool
    {
        return $this->pk;
    }

    public function isHideAudit() : bool
    {
        return $this->hideAudit;
    }


    protected $name;
    protected $type;
    protected $notNull;
    protected $default;
    protected $autoIncrement;
    protected $pk;
    protected $after;
    protected $hideAudit;
}
