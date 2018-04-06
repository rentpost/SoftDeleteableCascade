<?php

namespace FrankHouweling\SoftDeleteableCascade\Exception;

class OnSoftDeleteUknownTypeException extends \Exception
{
    public function __construct($type)
    {
        parent::__construct('Type '.$type.' for onSoftDelete annotation does not exists.');
    }
}
