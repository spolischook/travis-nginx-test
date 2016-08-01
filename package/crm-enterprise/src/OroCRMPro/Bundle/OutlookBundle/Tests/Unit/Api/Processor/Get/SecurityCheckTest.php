<?php

namespace OroCRMPro\Bundle\OutlookBundle\Tests\Unit\Api\Processor\Get;

use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get\GetProcessorTestCase;
use OroCRMPro\Bundle\OutlookBundle\Api\Processor\Get\SecurityCheck;

class SecurityCheckTest extends GetProcessorTestCase
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

    public function testProcessForNotOutlookSection()
    {
        $this->securityFacade->expects($this->never())
            ->method('isGranted');

        $this->context->setId('test');
        $this->processor->process($this->context);

        $this->assertEquals([], $this->context->getSkippedGroups());
    }

    /**
     * @dataProvider outlookSectionsProvider
     */
    public function testProcessForOutlookSectionWhenAccessIsDenied($sectionId)
    {
        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->with('orocrmpro_outlook_integration')
            ->willReturn(false);

        $this->context->setId($sectionId);
        $this->processor->process($this->context);

        $this->assertEquals([], $this->context->getSkippedGroups());
    }

    /**
     * @dataProvider outlookSectionsProvider
     */
    public function testProcessForOutlookSectionWhenAccessIsGranted($sectionId)
    {
        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->with('orocrmpro_outlook_integration')
            ->willReturn(true);

        $this->context->setId($sectionId);
        $this->processor->process($this->context);

        $this->assertEquals(['security_check'], $this->context->getSkippedGroups());
    }

    public function outlookSectionsProvider()
    {
        return [
            ['outlook'],
            ['outlook.sub-section'],
        ];
    }
}
