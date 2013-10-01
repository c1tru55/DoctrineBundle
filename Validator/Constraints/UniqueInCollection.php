<?php

namespace ITE\DoctrineBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class UniqueInCollection extends Constraint
{
    public $message = 'This value is already used.';

    public $fields = array();

    public $errorPath = null;

    public function getRequiredOptions()
    {
        return array('fields');
    }

    public function getDefaultOption()
    {
        return 'fields';
    }
}