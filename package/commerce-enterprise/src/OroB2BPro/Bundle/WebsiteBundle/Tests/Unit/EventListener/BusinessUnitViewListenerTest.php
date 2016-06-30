<?php

namespace OroB2BPro\Bundle\WebsiteBundle\Tests\Unit\EventListener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;

use OroB2BPro\Bundle\WebsiteBundle\EventListener\BusinessUnitViewListener;

class BusinessUnitViewListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var BusinessUnitViewListener
     */
    protected $listener;

    /**
     * @var RequestStack|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $requestStack;

    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    public function setUp()
    {
        /** @var TranslatorInterface $translator */
        $translator = $this->getMock(TranslatorInterface::class);
        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)->disableOriginalConstructor()->getMock();
        $this->requestStack = $this->getMockBuilder(RequestStack::class)->disableOriginalConstructor()->getMock();

        $this->listener = new BusinessUnitViewListener($translator, $this->doctrineHelper, $this->requestStack);
    }

    public function testOnOrganizationViewWithoutRequest()
    {
        $request = $this->getMock(Request::class);
        $request->method('get')->willReturn(1);
        $this->requestStack->method('getCurrentRequest')->willReturn($request);
        $this->doctrineHelper->method('getEntityReference')->willReturn($this->getMock(BusinessUnit::class));
        /** @var BeforeListRenderEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder(BeforeListRenderEvent::class)
            ->disableOriginalConstructor()
            ->getMock();

        $event->expects($this->once())
            ->method('getEnvironment')
        ->willReturn($this->getMock(\Twig_Environment::class));

        $scrollData = $this->getMock(ScrollData::class);

        $scrollData->expects($this->once())->method('addBlock');
        $scrollData->expects($this->once())->method('addSubBlock');

        $event->expects($this->any())->method('getScrollData')
            ->willReturn($scrollData);

        $this->listener->onBusinessUnitView($event);
    }
}
