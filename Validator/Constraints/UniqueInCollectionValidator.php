<?php

namespace ITE\DoctrineBundle\Validator\Constraints;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class UniqueInCollectionValidator extends ConstraintValidator
{
    private $collectionValues = array();

    public function validate($value, Constraint $constraint)
    {
        $hash = spl_object_hash($constraint);
        if (!array_key_exists($hash, $this->collectionValues)) {
            $this->collectionValues[$hash] = array();
        }

        /** @var $constraint UniqueInCollection */
        if (null !== $constraint->errorPath && !is_string($constraint->errorPath)) {
            throw new UnexpectedTypeException($constraint->errorPath, 'string or null');
        }

        // Apply the property path if specified
        if ($constraint->propertyPath) {
            $accessor = PropertyAccess::createPropertyAccessor();

            $value = $accessor->getValue($value, $constraint->propertyPath);
        }

        // Check that the value is not in the array
        if (in_array($value, $this->collectionValues[$hash])) {
            if ($constraint->errorPath) {
                $this->context->addViolationAt($constraint->errorPath, $constraint->message, array());
            } else {
                $this->context->addViolation($constraint->message, array());
            }
        }

        // Add the value in the array for next items validation
        $this->collectionValues[$hash][] = $value;
    }
}