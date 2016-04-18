<?php

namespace OroB2B\Bundle\FalbackBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCase;

use OroB2B\Bundle\FallbackBundle\Entity\LocalizedFallbackValue;
use OroB2B\Bundle\FallbackBundle\Model\FallbackType;
use OroB2B\Bundle\WebsiteBundle\Entity\Locale;

class LocalizedFallbackValueTest extends EntityTestCase
{
    public function testAccessors()
    {
        $properties = [
            ['id', 1],
            ['locale', new Locale()],
            ['locale', null],
            ['fallback', FallbackType::SYSTEM],
            ['string', 'string'],
            ['text', 'text'],
        ];

        $this->assertPropertyAccessors(new LocalizedFallbackValue(), $properties);
    }

    public function testToString()
    {
        $stringValue = new LocalizedFallbackValue();
        $stringValue->setString('string');
        $this->assertEquals('string', (string)$stringValue);

        $textValue = new LocalizedFallbackValue();
        $textValue->setText('text');
        $this->assertEquals('text', (string)$textValue);

        $emptyValue = new LocalizedFallbackValue();
        $this->assertEquals('', (string)$emptyValue);
    }

    public function testClone()
    {
        $id = 123;
        $value = new LocalizedFallbackValue();

        $reflection = new \ReflectionProperty(get_class($value), 'id');
        $reflection->setAccessible(true);
        $reflection->setValue($value, $id);

        $clonedValue = clone $value;
        $this->assertEquals($id, $value->getId());
        $this->assertNull($clonedValue->getId());
    }

    public function testGetFallback()
    {
        $this->assertInternalType('array', LocalizedFallbackValue::getFallbacks());
        $this->assertNotEmpty('array', LocalizedFallbackValue::getFallbacks());
    }
}
