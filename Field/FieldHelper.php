<?php

namespace OroCRMPro\Bundle\DemoDataBundle\Field;

use Doctrine\Common\Util\ClassUtils;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class FieldHelper {

    /** @var PropertyAccessor */
    protected $propertyAccessor;

    /**
     * @param object $object
     * @param string $fieldName
     * @param mixed  $value
     * @throws \Exception
     */
    public function setObjectValue($object, $fieldName, $value)
    {
        try {
            $this->getPropertyAccessor()->setValue($object, $fieldName, $value);
        } catch (\Exception $e) {
            $class = ClassUtils::getClass($object);
            if (property_exists($class, $fieldName)) {
                $reflection = new \ReflectionProperty($class, $fieldName);
                $reflection->setAccessible(true);
                $reflection->setValue($object, $value);
            } else {
                throw $e;
            }
        }
    }

    /**
     * @return PropertyAccessor
     */
    protected function getPropertyAccessor()
    {
        if (!$this->propertyAccessor) {
            $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        }

        return $this->propertyAccessor;
    }
}