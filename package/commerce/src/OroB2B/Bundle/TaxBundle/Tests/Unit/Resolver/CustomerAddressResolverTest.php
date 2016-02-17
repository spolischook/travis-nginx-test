<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Resolver;

use OroB2B\Bundle\TaxBundle\Model\Taxable;
use OroB2B\Bundle\TaxBundle\Resolver\CustomerAddressItemResolver;
use OroB2B\Bundle\TaxBundle\Resolver\CustomerAddressResolver;

class CustomerAddressResolverTest extends \PHPUnit_Framework_TestCase
{
    /** @var CustomerAddressResolver */
    protected $resolver;

    /** @var CustomerAddressItemResolver|\PHPUnit_Framework_MockObject_MockObject */
    protected $itemResolver;

    /** {@inheritdoc} */
    protected function setUp()
    {
        $this->itemResolver = $this->getMockBuilder('OroB2B\Bundle\TaxBundle\Resolver\CustomerAddressItemResolver')
            ->disableOriginalConstructor()
            ->getMock();

        $this->resolver = new CustomerAddressResolver($this->itemResolver);
    }

    public function testEmptyCollection()
    {
        $this->itemResolver->expects($this->never())->method($this->anything());

        $this->resolver->resolve(new Taxable());
    }

    public function testResolveCollection()
    {
        $taxable = new Taxable();
        $taxableItem = new Taxable();
        $taxable->addItem($taxableItem);

        $this->itemResolver->expects($this->once())->method('resolve')->with(
            $this->callback(
                function ($dispatchedTaxable) use ($taxableItem) {
                    $this->assertSame($taxableItem, $dispatchedTaxable);

                    return true;
                }
            )
        );

        $this->resolver->resolve($taxable);
    }
}
