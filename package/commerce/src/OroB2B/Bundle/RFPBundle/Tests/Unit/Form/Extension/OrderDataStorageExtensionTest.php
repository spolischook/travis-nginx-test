<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Unit\Form\Extension;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\PricingBundle\Model\PriceListTreeHandler;
use OroB2B\Bundle\PricingBundle\Provider\ProductPriceProvider;
use OroB2B\Bundle\RFPBundle\Form\Extension\OrderDataStorageExtension;
use OroB2B\Bundle\ProductBundle\Storage\DataStorageInterface;

class OrderDataStorageExtensionTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var OrderDataStorageExtension
     */
    protected $extension;

    /**
     * @var RequestStack|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $requestStack;

    /**
     * @var ProductPriceProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $productPriceProvider;

    /**
     * @var PriceListTreeHandler|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $priceListTreeHandler;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->requestStack = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');
        $this->productPriceProvider = $this->getMockBuilder('OroB2B\Bundle\PricingBundle\Provider\ProductPriceProvider')
            ->disableOriginalConstructor()->getMock();
        $this->priceListTreeHandler = $this->getMockBuilder('OroB2B\Bundle\PricingBundle\Model\PriceListTreeHandler')
            ->disableOriginalConstructor()->getMock();

        $this->extension = new OrderDataStorageExtension(
            $this->requestStack,
            $this->productPriceProvider,
            $this->priceListTreeHandler
        );
    }

    public function testExtendedTypeAccessors()
    {
        $extensionType = 'TestExtensionType';
        $this->assertNull($this->extension->getExtendedType());
        $this->extension->setExtendedType($extensionType);
        $this->assertEquals($extensionType, $this->extension->getExtendedType());
    }

    /**
     * @dataProvider buildFormDataProvider
     *
     * @param array $lineItems
     * @param array $lineItemToMatchedPrices
     * @param array $matchedPrices
     */
    public function testBuildForm(array $lineItems, array $lineItemToMatchedPrices, array $matchedPrices)
    {
        $order = $this->getOrder($lineItems);
        $matchedPrices = $this->getMatchedPrices($matchedPrices);

        /** @var Request|\PHPUnit_Framework_MockObject_MockObject $request */
        $request = $this->getMock('Symfony\Component\HttpFoundation\Request');
        $request->expects($this->once())
            ->method('get')
            ->with(DataStorageInterface::STORAGE_KEY)
            ->willReturn(true);
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $priceList = $this->getMock('OroB2B\Bundle\PricingBundle\Entity\BasePriceList');

        $this->priceListTreeHandler->expects($this->once())
            ->method('getPriceList')
            ->with($order->getAccount(), $order->getWebsite())
            ->willReturn($priceList);

        $this->productPriceProvider->expects($this->once())
            ->method('getMatchedPrices')
            ->with($this->isType('array'), $priceList)
            ->willReturn($matchedPrices);

        $builder = $this->getBuilderMock();
        $builder->expects($this->any())
            ->method('addEventListener')
            ->with(
                FormEvents::PRE_SET_DATA,
                $this->logicalAnd(
                    $this->isInstanceOf('\Closure'),
                    $this->callback(function (\Closure $closure) use ($order) {
                        $event = $this->getMockBuilder('Symfony\Component\Form\FormEvent')
                            ->disableOriginalConstructor()
                            ->getMock();
                        $event->expects($this->once())->method('getData')->willReturn($order);
                        $this->assertNull($closure($event));
                        return true;
                    })
                )
            );
        $this->extension->buildForm($builder, []);

        foreach ($order->getLineItems() as $lineItem) {
            if (array_key_exists($lineItem->getId(), $lineItemToMatchedPrices)) {
                $identifier = $lineItemToMatchedPrices[$lineItem->getId()];
                $this->assertEquals($matchedPrices[$identifier], $lineItem->getPrice());
            } else {
                $this->assertNull($lineItem->getPrice());
            }
        }
    }

    /**
     * @return array
     */
    public function buildFormDataProvider()
    {
        return [
            [
                'data' => [
                    'account' => ['id' => 1],
                    'website' => ['id' => 1],
                    'currency' => 'USD',
                    'lineItems' => [
                        [
                            'id' => 1,
                            'product' => ['id' => 1],
                            'productUnit' => ['code' => 'piece'],
                            'quantity' => 2,
                        ],
                        [
                            'id' => 2,
                            'product' => ['id' => 3],
                            'productUnit' => ['code' => 'kg'],
                            'quantity' => 20,
                        ],
                        [
                            'id' => 3,
                            'product' => ['id' => 5],
                            'productUnit' => ['code' => 'box'],
                            'quantity' => 200,
                        ],
                    ],
                ],
                'lineItemToMatchedPrices' => [
                    1 => '1-piece-2-USD',
                    2 => '3-kg-20-USD',
                ],
                'matchedPrices' => [
                    '1-piece-2-USD' => [
                        'value' => 10,
                        'currency' => 'USD',
                    ],
                    '3-kg-20-USD' => [
                        'value' => 100,
                        'currency' => 'USD',
                    ],
                ],
            ]
        ];
    }

    /**
     * @param array $data
     * @return Order
     */
    protected function getOrder(array $data)
    {
        $lineItems = new ArrayCollection();
        foreach ($data['lineItems'] as $lineItem) {
            $lineItem['product'] = $this
                ->getEntity('OroB2B\Bundle\ProductBundle\Entity\Product', $lineItem['product']);
            $lineItem['productUnit'] = $this
                ->getEntity('OroB2B\Bundle\ProductBundle\Entity\ProductUnit', $lineItem['productUnit']);
            $lineItems->add($this->getEntity('OroB2B\Bundle\OrderBundle\Entity\OrderLineItem', $lineItem));
        }
        $data['account'] = $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\Account', $data['account']);
        $data['website'] = $this->getEntity('OroB2B\Bundle\WebsiteBundle\Entity\Website', $data['website']);
        $data['lineItems'] = $lineItems;
        return $this->getEntity('OroB2B\Bundle\OrderBundle\Entity\Order', $data);
    }

    /**
     * @param array $matchedPrices
     * @return array
     */
    protected function getMatchedPrices(array $matchedPrices)
    {
        foreach ($matchedPrices as &$matchedPrice) {
            $matchedPrice = Price::create($matchedPrice['value'], $matchedPrice['currency']);
        }
        return $matchedPrices;
    }

    public function testBuildFormNotApplicableEmptyGetParameter()
    {
        $builder = $this->getBuilderMock();
        $builder->expects($this->never())
            ->method('addEventListener');
        /** @var Request|\PHPUnit_Framework_MockObject_MockObject $request */
        $request = $this->getMock('Symfony\Component\HttpFoundation\Request');
        $request->expects($this->once())
            ->method('get')
            ->with(DataStorageInterface::STORAGE_KEY)
            ->willReturn(null);
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);
        $this->extension->buildForm($builder, []);
    }

    public function testBuildFormNotApplicableEmptyRequest()
    {
        $builder = $this->getBuilderMock();
        $builder->expects($this->never())
            ->method('addEventListener');
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn(null);
        $this->extension->buildForm($builder, []);
    }

    /**
     * @return FormBuilderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getBuilderMock()
    {
        return $this->getMock('Symfony\Component\Form\FormBuilderInterface');
    }
}
