<?php

namespace ITE\DoctrineBundle\Validator\Constraints;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;

class UniqueInCollectionValidator extends ConstraintValidator
{
    /**
     * @var PropertyAccessor
     */
    protected $propertyAccessor;

    protected $collectionValues = array();

    public function __construct()
    {
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
    }

    public function validate($value, Constraint $constraint)
    {
        /** @var $constraint UniqueInCollection */
        if (!is_array($constraint->fields) && !is_string($constraint->fields)) {
            throw new UnexpectedTypeException($constraint->fields, 'array');
        }

        if (null !== $constraint->errorPath && !is_string($constraint->errorPath)) {
            throw new UnexpectedTypeException($constraint->errorPath, 'string or null');
        }

        $fields = (array) $constraint->fields;

        if (0 === count($fields)) {
            throw new ConstraintDefinitionException('At least one field has to be specified.');
        }

        $groupHash = spl_object_hash($constraint);
        if (!array_key_exists($groupHash, $this->collectionValues)) {
            $this->collectionValues[$groupHash] = array();
        }

        $dummyObject = new \stdClass();
        foreach ($fields as $field) {
            $fieldValue = $this->propertyAccessor->getValue($value, $field);
            $dummyObject->$field = is_object($fieldValue) ? spl_object_hash($fieldValue) : $fieldValue;
        }

        $objectHash = md5(serialize($dummyObject));

        // Check that the value is not in the array
        if (in_array($objectHash, $this->collectionValues[$groupHash])) {
            if ($constraint->errorPath) {
                $this->context->addViolationAt($constraint->errorPath, $constraint->message, array());
            } else {
                $this->context->addViolation($constraint->message, array());
            }
        }

        // Add the value in the array for next items validation
        $this->collectionValues[$groupHash][] = $objectHash;
    }
}