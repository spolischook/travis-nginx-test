<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Formatter;

use Symfony\Component\Translation\TranslatorInterface;

use OroB2B\Bundle\ProductBundle\Entity\MeasurementUnitInterface;
use OroB2B\Bundle\ProductBundle\Formatter\AbstractUnitValueFormatter;

abstract class UnitValueFormatterTestCase extends \PHPUnit_Framework_TestCase
{
    const TRANSLATION_PREFIX = '';

    /** @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $translator;

    /** @var AbstractUnitValueFormatter */
    protected $formatter;

    protected function setUp()
    {
        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');
    }

    protected function tearDown()
    {
        unset($this->formatter, $this->translator);
    }

    /**
     * Test Format
     */
    public function testFormat()
    {
        $this->translator->expects($this->once())
            ->method('transChoice')
            ->with(static::TRANSLATION_PREFIX . '.kg.value.full', 42);

        $this->formatter->format(42, $this->createObject('kg'));
    }

    /**
     * Test FormatShort
     */
    public function testFormatShort()
    {
        $this->translator->expects($this->once())
            ->method('transChoice')
            ->with(static::TRANSLATION_PREFIX . '.item.value.short', 42);

        $this->formatter->formatShort(42, $this->createObject('item'));
    }

    public function testFormatCodeShort()
    {
        $this->translator->expects($this->once())
            ->method('transChoice')
            ->with(static::TRANSLATION_PREFIX . '.item.value.short', 42);

        $this->formatter->formatCode(42, 'item', true);
    }

    public function testFormatCodeFull()
    {
        $this->translator->expects($this->once())
            ->method('transChoice')
            ->with(static::TRANSLATION_PREFIX . '.item.value.full', 42);

        $this->formatter->formatCode(42, 'item');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The parameter "value" must be a numeric, but it is of type string.
     */
    public function testFormatWithInvalidValue()
    {
        $this->formatter->formatShort('test', $this->createObject('item'));
    }

    /**
     * @param string $code
     * @return MeasurementUnitInterface
     */
    abstract protected function createObject($code);
}
