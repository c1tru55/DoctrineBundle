<?php

namespace ITE\DoctrineBundle\Request\ParamConverter;

use Doctrine\Common\Persistence\ObjectManager;
use InvalidArgumentException;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\DoctrineParamConverter as BaseDoctrineParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ConfigurationInterface;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * Class DoctrineParamConverter
 * @package ITE\DoctrineBundle\Request\ParamConverter
 */
class DoctrineParamConverter extends BaseDoctrineParamConverter
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var PropertyAccessor $propertyAccessor
     */
    protected $propertyAccessor;

    /**
     * @param ManagerRegistry $registry
     * @param ContainerInterface $container
     */
    public function __construct(ManagerRegistry $registry = null, ContainerInterface $container)
    {
        $this->registry = $registry;
        $this->container = $container;
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();

        parent::__construct($registry);
    }

    /**
     * Get container
     *
     * @return ContainerInterface
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Get propertyAccessor
     *
     * @return PropertyAccessor
     */
    public function getPropertyAccessor()
    {
        return $this->propertyAccessor;
    }

    /**
     * @param $class
     * @param Request $request
     * @param $options
     * @param $name
     * @return bool|mixed
     */
    protected function find($class, Request $request, $options, $name)
    {
        if ($options['mapping'] || $options['exclude']) {
            return false;
        }

        $id = $this->getIdentifier($request, $options, $name);

        if (false === $id || null === $id) {
            return false;
        }

        if (isset($options['repository_method'])) {
            $method = $options['repository_method'];
        } else {
            $method = 'find';
        }

        $self = $this;
        if (isset($options['repository_args'])) {
            $args = $options['repository_args'];
            $args = array_map(function($arg) use ($id, $self) {
                if ('id' === $arg) {
                    return $id;
                } elseif (false !== strpos($arg, '::')) {
                    list($class, $property) = explode('::', $arg, 2);
                    if ('@' === substr($class, 0, 1)) {
                        // service?
                        $serviceId = substr($class, 1);
                        if ($self->getContainer()->has($serviceId)) {
                            $service = $self->getContainer()->get($serviceId);

                            return $self->getPropertyAccessor()->getValue($service, $property);
                        } else {
                            throw new InvalidArgumentException(sprintf('Service "%s" does not exist.', $class));
                        }
                    } else {
                        // class?
                        if (class_exists($class)) {
                            if (method_exists($class, $property)) {
                                return call_user_func(array($class, $property));
                            } elseif (property_exists($class, $property)) {
                                return $class::$$property;
                            } else {
                                throw new InvalidArgumentException('Invalid arguments in repository_args option.');
                            }
                        } else {
                            throw new InvalidArgumentException(sprintf('Class "%s" does not exist.', $class));
                        }
                    }
                } else {
                    // function?
                    if (function_exists($arg)) {
                        return $arg();
                    } else {
                        throw new InvalidArgumentException(sprintf('Function "%s" does not exist.', $arg));
                    }
                }
            }, $args);
        } else {
            $args = array($id);
        }

        $repository = $this->getManager($options['entity_manager'], $class)->getRepository($class);
        return call_user_func_array(array($repository, $method), $args);
    }

    /**
     * @param $name
     * @param $class
     * @return ObjectManager|null
     */
    protected function getManager($name, $class)
    {
        if (null === $name) {
            return $this->registry->getManagerForClass($class);
        }

        return $this->registry->getManager($name);
    }
}