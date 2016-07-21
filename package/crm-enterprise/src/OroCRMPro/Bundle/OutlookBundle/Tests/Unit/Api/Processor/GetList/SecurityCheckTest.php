<?php

namespace OroCRMPro\Bundle\OutlookBundle\Tests\Unit\Api\Processor\GetList;

use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorTestCase;
use OroCRMPro\Bundle\OutlookBundle\Api\Processor\GetList\SecurityCheck;

class SecurityCheckTest extends GetListProcessorTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    /** @var SecurityCheck */
    protected $processor;

    public function setUp()
    {
        parent::setUp();

        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new SecurityCheck($this->securityFacade);
    }

    public function testProcessWhenAccessIsDenied()
    {
        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->with('orocrmpro_outlook_integration')
            ->willReturn(false);

        $this->processor->process($this->context);

        $this->assertEquals([], $this->context->getSkippedGroups());
    }

    public function testProcessWhenAccessIsGranted()
    {
        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->with('orocrmpro_outlook_integration')
            ->willReturn(true);

        $this->processor->process($this->context);

        $this->assertEquals(['security_check'], $this->context->getSkippedGroups());
    }
}
