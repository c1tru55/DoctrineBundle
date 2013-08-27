<?php

namespace ITE\DoctrineBundle\Service;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\EntityManager;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class CascadePersister
{
    /**
     * @var EntityManager $em
     */
    protected $em;

    /**
     * @var PropertyAccessor $propertyAccessor
     */
    protected $propertyAccessor;

    protected $i = 0;

    /**
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
    }

    /**
     * @param $entity
     */
    public function persistAndFlush($entity)
    {
        $classMetadataCache = array();
        do {
            $invalidPropertyPaths = $this->recursiveCheckEntity($entity, get_class($entity), $classMetadataCache);

            $relationships = array();

            foreach ($invalidPropertyPaths as $invalidPropertyPath) {
                $relationships[$invalidPropertyPath] = clone $this->propertyAccessor->getValue($entity, $invalidPropertyPath);
                $this->propertyAccessor->setValue($entity, $invalidPropertyPath, new ArrayCollection());
            }

            $this->em->persist($entity);
            $this->em->flush();

            foreach ($relationships as $invalidPropertyPath => $value) {
                $this->propertyAccessor->setValue($entity, $invalidPropertyPath, $value);
            }

        } while (!empty($invalidPropertyPaths));
    }

    /**
     * @param $entity
     * @param $className
     * @param $classMetadataCache
     * @param null $parentClassName
     * @param string $propertyPath
     * @return array
     */
    protected function recursiveCheckEntity($entity, $className, &$classMetadataCache, $parentClassName = null, $propertyPath = '')
    {
        if (!array_key_exists($className, $classMetadataCache)) {
            $classMetadataCache[$className] = $this->em->getClassMetadata($className);
        }
        /* @var $class ClassMetadata */
        $class = $classMetadataCache[$className];

        $idFieldNames = $class->getIdentifierFieldNames();
        $associations = $class->getAssociationNames();

        $isCompositeIdentifier = $class->isIdentifierComposite;
        $containsForeignIdentifier = $class->containsForeignIdentifier;
        $associationsInIdentifier = array_intersect($idFieldNames, $associations);

        $invalidPropertyPaths = array();
        foreach ($associations as $association) {
            $associationClassName = $class->getAssociationTargetClass($association);

            $associationValue = $this->propertyAccessor->getValue($entity, $this->appendPropertyPath($propertyPath, $association));

            if ($isCompositeIdentifier
                && $containsForeignIdentifier
                && in_array($association, $associationsInIdentifier)
                && $class->associationMappings[$association]['isOwningSide']
                && $parentClassName === $associationClassName) {
                    /* @var $parentClass ClassMetadata */
                    $parentClass = $classMetadataCache[$associationClassName];
                    if (isset($associationValue) && !$this->isIdentifierSet($parentClass, $associationValue)) {
                        return array(
                            $this->getParentPropertyPath($propertyPath)
                        );
                    }
            }

            if ($class->associationMappings[$association]['isCascadePersist']
                && !$class->associationMappings[$association]['isOwningSide']) {
                if ($class->isCollectionValuedAssociation($association)) {
                    // collection value association
                    foreach ($associationValue->getKeys() as $index) {
                        $invalidPropertyPath = $this->recursiveCheckEntity(
                            $entity,
                            $associationClassName,
                            $classMetadataCache,
                            $className,
                            $this->appendPropertyPath($propertyPath, $association . '[' . $index . ']')
                        );
                        if (!empty($invalidPropertyPath)) {
                            $invalidPropertyPaths = array_merge($invalidPropertyPaths, $invalidPropertyPath);
                        }
                    }
                } else {
                    // single value association
                    $invalidPropertyPath = $this->recursiveCheckEntity(
                        $entity,
                        $associationClassName,
                        $classMetadataCache,
                        $className,
                        $this->appendPropertyPath($propertyPath, $association)
                    );
                    if (!empty($invalidPropertyPath)) {
                        $invalidPropertyPaths = array_merge($invalidPropertyPaths, $invalidPropertyPath);
                    }
                }
            }
        }
        return array_unique($invalidPropertyPaths);
    }

    /**
     * @param ClassMetadata $class
     * @param $entity
     * @return bool
     */
    protected function isIdentifierSet(ClassMetadata $class, $entity)
    {
        $idFieldNames = $class->getIdentifierFieldNames();
        $idValues = $class->getIdentifierValues($entity);

        return count($idFieldNames) === count($idValues);
    }

    /**
     * @param $propertyPath
     * @param $association
     * @return string
     */
    protected function appendPropertyPath($propertyPath, $association)
    {
        return $propertyPath . (!empty($propertyPath) ? '.' . $association : $association);
    }

    /**
     * @param $propertyPath
     * @return string
     */
    protected function getParentPropertyPath($propertyPath)
    {
        if (']' === substr($propertyPath, -1)) {
            return substr($propertyPath, 0, strrpos($propertyPath, '['));
        }
        return $propertyPath;
    }
}