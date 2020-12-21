<?php


namespace MightyCore\Database\Helpers;


class IncludeHelper
{
    public $class;

    public string $localColumn;

    public string $foreignColumn;

    public function __construct($class, $localColumn, $foreignColumn)
    {
        $this->class = $class;
        $this->localColumn = $localColumn;
        $this->foreignColumn = $foreignColumn;
    }
}