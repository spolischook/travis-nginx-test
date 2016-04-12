<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Method\View;

use OroB2B\Bundle\PaymentBundle\Method\PaymentMethodRegistry;
use OroB2B\Bundle\PaymentBundle\Method\View\PaymentMethodViewRegistry;
use OroB2B\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface;

class PaymentMethodViewRegistryTest extends \PHPUnit_Framework_TestCase
{
    /** @var PaymentMethodViewRegistry */
    protected $registry;

    /** @var PaymentMethodRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $paymentMethodRegistry;

    protected function setUp()
    {
        $this->paymentMethodRegistry = $this->getMockBuilder('OroB2B\Bundle\PaymentBundle\Method\PaymentMethodRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->registry = new PaymentMethodViewRegistry($this->paymentMethodRegistry);
    }

    public function testRegistry()
    {
        /** @var PaymentMethodViewInterface $testView */
        $testView = $this->getTypeMock('test_method_view', 10);

        /** @var PaymentMethodViewInterface $testView2 */
        $testView2 = $this->getTypeMock('test_method_view2', 5);

        /** @var PaymentMethodViewInterface $testView2 */
        $testViewMethodDisabled = $this->getTypeMock('test_method_view_disabled');

        $this->assertEmpty($this->registry->getPaymentMethodViews());

        $this->registry->addPaymentMethodView($testView);
        $this->registry->addPaymentMethodView($testView2);
        $this->registry->addPaymentMethodView($testViewMethodDisabled);

        $paymentMethod = $this->getMock('OroB2B\Bundle\PaymentBundle\Method\PaymentMethodInterface');
        $paymentMethod->expects($this->exactly(3))->method('isEnabled')
            ->willReturnOnConsecutiveCalls(true, true, false);
        $this->paymentMethodRegistry->expects($this->exactly(3))->method('getPaymentMethod')
            ->willReturn($paymentMethod);

        $views = $this->registry->getPaymentMethodViews();
        $this->assertCount(2, $views);
        $this->assertEquals($testView2, reset($views));
        $this->assertEquals($testView, end($views));
    }

    /**
     * @param string $name
     * @param int $order
     * @return \PHPUnit_Framework_MockObject_MockObject|PaymentMethodViewInterface
     */
    protected function getTypeMock($name, $order = 0)
    {
        $type = $this->getMock('OroB2B\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface');
        $type->expects($this->any())->method('getPaymentMethodType')->will($this->returnValue($name));
        $type->expects($this->any())->method('getOrder')->will($this->returnValue($order));

        return $type;
    }
}
