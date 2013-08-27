<?php

namespace ITE\DoctrineBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class UniqueInCollection extends Constraint
{
    public $message = 'This value is already used.';

    public $errorPath = null;

    public $propertyPath = null;
}