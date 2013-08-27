<?php

namespace ITE\DoctrineBundle\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;
use ITE\DoctrineBundle\Core\DateTime;
use Doctrine\DBAL\Types\DateType as BaseDateType;

/**
 * Type that maps an SQL DATE to a PHP Date object.
 *
 * @since 2.0
 */
class DateType extends BaseDateType
{
    const ITE_DATE = 'ite_date';

    public function getName()
    {
        return self::ITE_DATE;
    }

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value === null || $value instanceof DateTime) {
            return $value;
        }

        $val = DateTime::createFromFormat('!'.$platform->getDateFormatString(), $value);
        if ( ! $val) {
            throw ConversionException::conversionFailedFormat($value, $this->getName(), $platform->getDateFormatString());
        }
        return $val;
    }
}
