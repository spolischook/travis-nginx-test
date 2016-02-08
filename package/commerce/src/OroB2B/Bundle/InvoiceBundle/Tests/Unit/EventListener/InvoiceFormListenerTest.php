<?php

namespace OroB2B\Bundle\InvoiceBundle\Tests\Unit\EventListener;

use Symfony\Component\Form\Form;

use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;

use OroB2B\Bundle\InvoiceBundle\Entity\Invoice;
use OroB2B\Bundle\InvoiceBundle\EventListener\InvoiceFormListener;
use OroB2B\Bundle\OrderBundle\Model\Subtotal;
use OroB2B\Bundle\PricingBundle\Provider\LineItemsSubtotalProvider;
use OroB2B\Bundle\InvoiceBundle\Entity\InvoiceLineItem;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;

class InvoiceFormListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    const SKU = 'sku';
    const UNIT_CODE = 'pack';

    public function testBeforeFlush()
    {
        /** @var LineItemsSubtotalProvider|\PHPUnit_Framework_MockObject_MockObject $provider*/
        $provider = $this->getMockBuilder('OroB2B\Bundle\PricingBundle\Provider\LineItemsSubtotalProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $subtotal = new Subtotal();
        $subtotal->setAmount(100);
        $provider->expects($this->once())
            ->method('getSubtotal')
            ->willReturn($subtotal);

        $invoice = $this->createInvoice();
        $listener = new InvoiceFormListener($provider);
        /** @var Form $form */
        $form = $this->getMockBuilder('Symfony\Component\Form\Form')->disableOriginalConstructor()->getMock();
        $event = new AfterFormProcessEvent($form, $invoice);
        $listener->beforeFlush($event);

        $this->assertSame(100, $invoice->getSubtotal());
        $this->assertSame(self::UNIT_CODE, $invoice->getLineItems()[0]->getProductUnitCode());
        $this->assertSame(self::SKU, $invoice->getLineItems()[0]->getProductSku());

    }

    /**
     * @return Invoice
     */
    protected function createInvoice()
    {
        $invoice = new Invoice();
        $invoice->setSubtotal(50);
        $lineItem = new InvoiceLineItem();
        $lineItem->setProduct($this->createProduct(self::SKU))
            ->setProductUnit($this->createProductUnit(self::UNIT_CODE));
        $invoice->addLineItem($lineItem);

        return $invoice;
    }

    /**
     * @param string $sku
     * @return Product
     */
    protected function createProduct($sku)
    {
        $product = new Product();
        $product->setSku($sku);

        return $product;
    }

    /**
     * @param $code
     * @return ProductUnit
     */
    protected function createProductUnit($code)
    {
        $productUnit = new ProductUnit();
        $productUnit->setCode($code);

        return $productUnit;
    }
}
