<?php

namespace VblSqlGen\MariaDB;

class ForeignKey extends Field
{
    public function __construct(array $fieldModel, string $table)
    {
        parent::__construct($fieldModel, $table);
        $this->references = $fieldModel['references'];
        $this->database = isset($fieldModel['database']) ?
            $fieldModel['database'] : null;
    }

    public function getReferences() : string
    {
        return $this->references;
    }

    public function getDatabase() : ?string
    {
        return $this->database;
    }

    protected $references;
    protected $database;
}
