<?php

namespace OroPro\Bundle\OrganizationBundle\Tests\Unit;

/**
 * Class ReflectionUtil
 * @package OroPro\Bundle\OrganizationBundle\Tests\Unit
 */
class ReflectionUtil
{
    /**
     * @param mixed  $obj
     * @param string $propName
     * @param mixed  $val
     */
    public static function setPrivateProperty($obj, $propName, $val)
    {
        $class = new \ReflectionClass($obj);
        $prop  = $class->getProperty($propName);
        $prop->setAccessible(true);

        $prop->setValue($obj, $val);
    }

    /**
     * @param mixed $obj
     * @param mixed $val
     */
    public static function setId($obj, $val)
    {
        $class = new \ReflectionClass($obj);
        $prop  = $class->getProperty('id');
        $prop->setAccessible(true);

        $prop->setValue($obj, $val);
    }
}
