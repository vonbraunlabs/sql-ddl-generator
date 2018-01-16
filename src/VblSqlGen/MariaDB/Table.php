<?php

namespace VblSqlGen\MariaDB;

class Table
{
    public function __construct(array $tableModel, string $database)
    {
        $this->database = $database;
        $this->name = $tableModel['name'];
        $this->audit = isset($tableModel['audit']) ? $tableModel['audit'] : false;
        $this->fieldList = $this->getFieldList($tableModel['field_list']);
        $this->comment = isset($tableModel['comment']) ? $tableModel['comment'] : null;

        $this->fkList = [];
        if ($this->audit) {
            $this->fkList []= new ForeignKey(
                [
                    'name' => 'who_id',
                    'references' => 'user',
                    'not_null' => 'true'
                ],
                $this->name
            );
        }
        if (isset($tableModel['fk_list'])) {
            foreach ($tableModel['fk_list'] as $fk) {
                $this->fkList[] = new ForeignKey($fk, $this->name);
            }
        }
        $this->uniqueList = [];
        if (isset($tableModel['unique_list'])) {
            $this->uniqueList = $tableModel['unique_list'];
        }
    }

    public function __toString() : string
    {
        $str = COMMENT_SEPARATOR;
        $str .= "-- Table `{$this->database}`.`{$this->name}`" . PHP_EOL;
        $str .= COMMENT_SEPARATOR . PHP_EOL;
        $str .= "CREATE TABLE IF NOT EXISTS `" . $this->database . "`.`" .
            $this->name . "` (" . PHP_EOL;

        $first = true;
        foreach ($this->fieldList as $field) {
            if ($first) {
                $first = false;
            } else {
                $str .= ',' . PHP_EOL;
            }
            $str .= '    ' . $field;
        }

        if (count($this->fkList)) {
            $str .= ',' . PHP_EOL;
            $str .= $this->fkListColumnsToString();
        }

        $str .= ',' . PHP_EOL;
        $str .= $this->pkToString();

        if (count($this->fkList)) {
            $str .= ',' . PHP_EOL;
            $str .= $this->fkListConstraintsToString();
        }
        $str .= PHP_EOL;

        $str .= ") Engine=InnoDB";

        if (isset($this->comment)) {
            $comment = str_replace(
                "'",
                "\\'",
                is_array($this->comment) ?
                    implode("\n", $this->comment) :
                    $this->comment
            );

            $str .= " COMMENT='" . $comment . "'";
        }
        $str .= ";" . PHP_EOL . PHP_EOL;

        foreach ($this->uniqueList as $unique) {
            $uniqueName = $this->getUniqueName($unique);
            $uniqueColumns = [];
            foreach ($unique as $column) {
                $uniqueColumns[] = "`$column`";
            }
            $uniqueColumns = implode(', ', $uniqueColumns);
            $str .= "CREATE UNIQUE INDEX `$uniqueName` ON " .
                "`{$this->database}`.`{$this->name}` ($uniqueColumns);" . PHP_EOL;
        }
        $str .= PHP_EOL;

        $str .= "CREATE TRIGGER `{$this->name}_before_insert` BEFORE INSERT " .
            "ON `{$this->database}`.`{$this->name}`" . PHP_EOL;
        $str .= "FOR EACH ROW" . PHP_EOL;
        $str .= "    SET NEW.`create_by` = CURRENT_USER()," . PHP_EOL;
        $str .= "        NEW.`update_by` = CURRENT_USER();" . PHP_EOL;
        $str .= PHP_EOL;
        $str .= "CREATE TRIGGER `{$this->name}_before_update` BEFORE UPDATE " .
            "ON `{$this->database}`.`{$this->name}`" . PHP_EOL;
        $str .= "FOR EACH ROW" . PHP_EOL;
        $str .= "    SET NEW.`update_by` = CURRENT_USER()," . PHP_EOL;
        $str .= "        NEW.`update_time` = CURRENT_TIMESTAMP(3);" . PHP_EOL;
        $str .= PHP_EOL;

        if ($this->audit) {
            $str .= $this->auditToString();
        }

        return $str;
    }

    protected function fkListColumnsToString() : string
    {
        $str = '';
        $first = true;
        foreach ($this->fkList as $fk) {
            if ($first) {
                $first = false;
            } else {
                $str .= "," . PHP_EOL;
            }
            $str .= '    ' . $fk;
        }

        return $str;
    }

    protected function pkToString() : string
    {
        $pkList = [];
        foreach ($this->fieldList as $field) {
            if ($field->isPK()) {
                $pkList []= "`{$field->getName()}`";
            }
        }
        return "    PRIMARY KEY (" . implode(',', $pkList) . ")";
    }

    protected function fkConstraintToString(ForeignKey $fk, bool $audit = false) : string
    {
        $name = Table::wrapName("fk_" . ($audit ? "audit_" : "") . "{$this->name}_{$fk->getName()}");
        return "    CONSTRAINT `$name`" . PHP_EOL .
            "        FOREIGN KEY(`{$fk->getName()}`)" . PHP_EOL .
            "        REFERENCES `{$this->database}`.`{$fk->getReferences()}` (`id`)" . PHP_EOL .
            "        ON DELETE NO ACTION" . PHP_EOL .
            "        ON UPDATE NO ACTION";
    }

    protected function fkListConstraintsToString() : string
    {
        $str = '';
        $first = true;
        foreach ($this->fkList as $fk) {
            if ($first) {
                $first = false;
            } else {
                $str .= "," . PHP_EOL;
            }

            $str .= $this->fkConstraintToString($fk);
        }
        return $str;
    }

    protected static function wrapName(string $name) : string
    {
        if (strlen($name) > 64) {
            $name = substr($name, 0, 32) . md5($name);
        }

        return $name;
    }

    protected function getUniqueName($unique) : string
    {
        $ret = 'unique_' . $this->name . '_' . implode('_', $unique);

        return Table::wrapName($ret);
    }

    protected function getFieldList($model) : array
    {
        $fieldList = [];
        $fieldList []= new Field(
            [
                "name" => "id",
                "type" => Field::$keyType,
                "not_null" => true,
                "default" => null,
                "auto_increment" => true,
                "pk" => true,
                "hide_audit" => true,
            ],
            $this->name
        );
        foreach ($model as $field) {
            $fieldList[] = new Field($field, $this->name);
        }

        $fieldList []= new Field(
            [
                "name" => "active",
                "type" => "BOOLEAN",
                "not_null" => true,
                "default" => 1,
            ],
            $this->name
        );

        $fieldList[] = new Field(
            [
                'name' => 'create_by',
                'type' => 'VARCHAR(32)',
                'not_null' => true,
            ],
            $this->name
        );

        $fieldList[] = new Field(
            [
                'name' => 'create_time',
                'type' => 'TIMESTAMP(3)',
                'not_null' => true,
                'default' => 'CURRENT_TIMESTAMP(3)',
                "hide_audit" => true,
            ],
            $this->name
        );

        $fieldList[] = new Field(
            [
                'name' => 'update_by',
                'type' => 'VARCHAR(32)',
                'not_null' => true,
            ],
            $this->name
        );

        $fieldList[] = new Field(
            [
                'name' => 'update_time',
                'type' => 'TIMESTAMP(3)',
                'not_null' => true,
                'default' => 'CURRENT_TIMESTAMP(3)',
                "hide_audit" => true,
            ],
            $this->name
        );

        return $fieldList;
    }

    protected function getAuditTriggerInsert() : string
    {
        $fullList = array_merge($this->fieldList, $this->fkList);
        $auditName = "audit_{$this->name}";
        $str = "    INSERT INTO {$this->getFullName($auditName)} (" . PHP_EOL;
        $str .= "        `{$this->name}_id`";
        $first = true;
        foreach ($fullList as $field) {
            if (!$field->isHideAudit()) {
                $str .= ',' . PHP_EOL;
                $str .= "        `{$field->getName()}`";
            }
        }
        $str .= PHP_EOL . "    ) VALUES (" . PHP_EOL;
        $str .= "        NEW.`id`";
        $first = true;
        foreach ($fullList as $field) {
            if (!$field->isHideAudit()) {
                $str .= ',' . PHP_EOL;
                $str .= "        NEW.`{$field->getName()}`";
            }
        }
        $str .= PHP_EOL . "    );" . PHP_EOL;

        return $str;
    }

    protected function auditTrigger(string $op) : string
    {
        $upperOp = strtoupper($op);
        $auditName = "audit_{$this->name}";
        $str = "delimiter //" . PHP_EOL;
        $str .= "CREATE TRIGGER `{$auditName}_{$op}_trigger`" . PHP_EOL;
        $str .= "AFTER {$upperOp} ON {$this->getFullName()}" . PHP_EOL;
        $str .= "FOR EACH ROW" . PHP_EOL;
        $str .= "BEGIN" . PHP_EOL;
        $str .= $this->getAuditTriggerInsert();
        $str .= "END;//" . PHP_EOL;
        $str .= "DELIMITER ;" . PHP_EOL . PHP_EOL;

        return $str;
    }

    protected function auditToString() : string
    {
        $auditName = "audit_{$this->name}";
        $auditFullName = $this->getFullName($auditName);
        $fkColumn = new ForeignKey([
            "name" => "{$this->name}_id",
            "references" => $this->name,
            "not_null" => true,
            "after" => 'id'
        ], $this->name);
        $str = COMMENT_SEPARATOR;
        $str .= "-- Audit Table for {$this->name}" . PHP_EOL;
        $str .= COMMENT_SEPARATOR . PHP_EOL;
        $str .= "CREATE TABLE IF NOT EXISTS {$auditFullName}" .
            " LIKE {$this->getFullName()};" . PHP_EOL;
        $str .= "ALTER TABLE {$auditFullName} ADD COLUMN {$fkColumn};" . PHP_EOL;
        $str .= "ALTER TABLE {$auditFullName} ADD {$this->fkConstraintToString($fkColumn, true)};" . PHP_EOL . PHP_EOL;
        foreach ($this->uniqueList as $unique) {
            $str .= "DROP INDEX `{$this->getUniqueName($unique)}` ON {$auditFullName};" . PHP_EOL;
        }
        $str .= PHP_EOL;

        foreach ($this->fkList as $fk) {
            $str .= "ALTER TABLE {$auditFullName} ADD {$this->fkConstraintToString($fk, true)};" . PHP_EOL;
        }



        $str .= $this->auditTrigger('insert');
        $str .= $this->auditTrigger('update');

        return $str;
    }

    protected function getFullName(string $name = null) : string
    {
        if (null === $name) {
            $name = $this->name;
        }
        return "`{$this->database}`.`{$name}`";
    }

    protected $database;
    protected $name;
    protected $fieldList;
    protected $fkList;
    protected $uniqueList;
    protected $audit;
}
