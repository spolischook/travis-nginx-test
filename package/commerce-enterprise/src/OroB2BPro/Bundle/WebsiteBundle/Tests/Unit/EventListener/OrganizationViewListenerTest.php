<?php

namespace OroB2BPro\Bundle\WebsiteBundle\Tests\Unit\EventListener;

use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use OroB2BPro\Bundle\WebsiteBundle\EventListener\OrganizationViewListener;
use Symfony\Component\Translation\TranslatorInterface;

class OrganizationViewListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OrganizationViewListener
     */
    protected $listener;

    public function setUp()
    {
        /** @var TranslatorInterface $translator */
        $translator = $this->getMock(TranslatorInterface::class);
        $this->listener = new OrganizationViewListener($translator);
    }

    public function testOnOrganizationViewWithoutRequest()
    {
        /** @var BeforeListRenderEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder(BeforeListRenderEvent::class)
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->once())
            ->method('getEnvironment')
        ->willReturn($this->getMock(\Twig_Environment::class));

        $event->expects($this->any())->method('getScrollData')
            ->willReturn($this->getMock(ScrollData::class));

        $this->listener->onOrganizationView($event);
    }
}
