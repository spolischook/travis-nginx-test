<?php

namespace OroCRMPro\Bundle\OutlookBundle\Tests\Unit\Api\Processor\Get;

use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get\GetProcessorTestCase;
use Oro\Bundle\ConfigBundle\Api\Model\ConfigurationOption;
use Oro\Bundle\ConfigBundle\Api\Model\ConfigurationSection;
use Oro\Bundle\ConfigBundle\Api\Processor\GetScope;
use OroCRMPro\Bundle\OutlookBundle\Api\Processor\Get\LoadConfigurationSection;

class LoadConfigurationSectionTest extends GetProcessorTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $outlookConfigRepository;

    /** @var LoadConfigurationSection */
    protected $processor;

    public function setUp()
    {
        parent::setUp();

        $this->outlookConfigRepository = $this
            ->getMockBuilder('OroCRMPro\Bundle\OutlookBundle\Api\Repository\OutlookConfigurationRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new LoadConfigurationSection($this->outlookConfigRepository);
    }

    public function testProcessWhenNoResult()
    {
        $this->outlookConfigRepository->expects($this->never())
            ->method('getOutlookSectionOptions');

        $this->processor->process($this->context);

        $this->assertFalse($this->context->hasResult());
    }

    public function testProcessForNotOutlookSection()
    {
        $this->outlookConfigRepository->expects($this->never())
            ->method('getOutlookSectionOptions');

        $configSection = new ConfigurationSection('test');
        $configSection->setOptions([new ConfigurationOption('scope', 'key1')]);

        $this->context->setResult($configSection);
        $this->processor->process($this->context);

        $expectedResult = new ConfigurationSection('test');
        $expectedResult->setOptions([new ConfigurationOption('scope', 'key1')]);
        $this->assertEquals($expectedResult, $this->context->getResult());
    }

    public function testProcessForOutlookSection()
    {
        $scope = 'scope';

        $this->outlookConfigRepository->expects($this->once())
            ->method('getOutlookSectionOptions')
            ->with($scope)
            ->willReturn([new ConfigurationOption($scope, 'key2')]);

        $configSection = new ConfigurationSection('outlook');
        $configSection->setOptions([new ConfigurationOption($scope, 'key1')]);

        $this->context->set(GetScope::CONTEXT_PARAM, $scope);
        $this->context->setId($configSection->getId());
        $this->context->setResult($configSection);
        $this->processor->process($this->context);

        $expectedResult = new ConfigurationSection('outlook');
        $expectedResult->setOptions(
            [
                new ConfigurationOption($scope, 'key1'),
                new ConfigurationOption($scope, 'key2')
            ]
        );
        $this->assertEquals($expectedResult, $this->context->getResult());
    }
}
