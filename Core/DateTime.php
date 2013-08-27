<?php

namespace ITE\DoctrineBundle\Core;

class DateTime extends \DateTime
{
    public function __toString()
    {
        return $this->format(static::W3C);
    }
}