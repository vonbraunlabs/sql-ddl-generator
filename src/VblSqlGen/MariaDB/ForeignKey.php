<?php

namespace VblSqlGen\MariaDB;

class ForeignKey extends Field
{
    public function __construct(array $fieldModel, string $table)
    {
        parent::__construct($fieldModel, $table);
        $this->references = $fieldModel['references'];
    }

    public function getReferences() : string
    {
        return $this->references;
    }

    protected $references;
}
