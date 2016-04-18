<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Entity\Manager;

use Doctrine\ORM\QueryBuilder;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

use OroB2B\Bundle\ProductBundle\Entity\Manager\ProductManager;
use OroB2B\Bundle\ProductBundle\Event\ProductSelectDBQueryEvent;

class ProductManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var  EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $eventDispatcher;

    /** @var  ProductManager */
    protected $productManager;

    public function setUp()
    {
        $this->eventDispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $this->productManager = new ProductManager($this->eventDispatcher);
    }

    public function testRestrictQueryBuilder()
    {
        /** @var QueryBuilder|\PHPUnit_Framework_MockObject_MockObject $qb */
        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')->disableOriginalConstructor()->getMock();
        $params = ['some' => 'params'];

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                ProductSelectDBQueryEvent::NAME,
                new ProductSelectDBQueryEvent($qb, new ParameterBag($params))
            );

        $this->productManager->restrictQueryBuilder($qb, $params);
    }
}
