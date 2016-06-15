<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\ImportExport\Normalizer;

use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;

use OroB2B\Bundle\FallbackBundle\Entity\LocalizedFallbackValue;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\ImportExport\Normalizer\InventoryStatusNormalizer;
use OroB2B\Bundle\ProductBundle\Tests\Unit\ImportExport\Stub\ProductStub;

class InventoryStatusNormalizerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var InventoryStatusNormalizer
     */
    protected $inventoryStatusNormalizer;

    protected function setUp()
    {
        $this->inventoryStatusNormalizer = new InventoryStatusNormalizer();
    }

    public function testSupportsNormalization()
    {
        $data = '';
        $format = '';
        $this->assertFalse($this->inventoryStatusNormalizer->supportsNormalization($data, $format, []));

        $data = new Product();
        $this->assertTrue($this->inventoryStatusNormalizer->supportsNormalization($data, $format, []));
    }

    public function testNormalize()
    {
        $object = $this->getMock(ProductStub::class);
        $localizedFallbackValue = new LocalizedFallbackValue();
        $localizedFallbackValue->setString('testName');
        $object->expects($this->once())
            ->method('getSku')
            ->willReturn('xxx');
        $object->expects($this->exactly(2))
            ->method('getDefaultName')
            ->willReturn($localizedFallbackValue);

        $inventoryStatus = $this->getMockBuilder(AbstractEnumValue::class)
            ->disableOriginalConstructor()
            ->getMock();
        $inventoryStatus->expects($this->once())
            ->method('getName')
            ->willReturn('testStatus');
        $object->expects($this->exactly(2))
            ->method('getInventoryStatus')
            ->willReturn($inventoryStatus);

        $result = $this->inventoryStatusNormalizer->normalize($object, '', []);
        $this->assertEquals(
            ['product' => [
                'sku' => 'xxx',
                'defaultName' => 'testName',
                'inventoryStatus' => 'testStatus'
            ]],
            $result
        );
    }
}
