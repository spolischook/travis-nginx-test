<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\EventListener;

use Symfony\Component\Form\FormInterface;

use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductVariantLink;
use OroB2B\Bundle\ProductBundle\EventListener\ProductHandlerListener;

class ProductHandlerListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProductHandlerListener
     */
    protected $listener;

    protected function setUp()
    {
        $this->listener = new ProductHandlerListener();
    }

    protected function tearDown()
    {
        unset($this->listener);
    }

    public function testVariantLinksWithoutHasVariant()
    {
        $entity = new Product();
        $entity->setHasVariants(false);
        $entity->addVariantLink($this->createProductVariantLink());
        $event = $this->createEvent($entity);
        $this->listener->onBeforeFlush($event);
        $this->assertCount(0, $entity->getVariantLinks());
    }

    public function testVariantLinksWithHasVariant()
    {
        $entity = new Product();
        $entity->setHasVariants(true);
        $entity->addVariantLink($this->createProductVariantLink());
        $event = $this->createEvent($entity);
        $this->listener->onBeforeFlush($event);
        $this->assertCount(1, $entity->getVariantLinks());
    }

    protected function createEvent($entity)
    {
        /** @var FormInterface $form */
        $form = $this->getMock('\Symfony\Component\Form\FormInterface');
        return new AfterFormProcessEvent($form, $entity);
    }

    protected function createProductVariantLink($parentProduct = null, $product = null)
    {
        return new ProductVariantLink($parentProduct, $product);
    }
}
