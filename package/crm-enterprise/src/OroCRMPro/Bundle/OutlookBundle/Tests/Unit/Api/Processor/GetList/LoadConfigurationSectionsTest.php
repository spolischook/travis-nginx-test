<?php

namespace OroCRMPro\Bundle\OutlookBundle\Tests\Unit\Api\Processor\GetList;

use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorTestCase;
use Oro\Bundle\ConfigBundle\Api\Model\ConfigurationOption;
use Oro\Bundle\ConfigBundle\Api\Model\ConfigurationSection;
use Oro\Bundle\ConfigBundle\Api\Processor\GetScope;
use OroCRMPro\Bundle\OutlookBundle\Api\Processor\GetList\LoadConfigurationSections;

class LoadConfigurationSectionsTest extends GetListProcessorTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $outlookConfigRepository;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configRepository;

    /** @var LoadConfigurationSections */
    protected $processor;

    public function setUp()
    {
        parent::setUp();

        $this->outlookConfigRepository = $this
            ->getMockBuilder('OroCRMPro\Bundle\OutlookBundle\Api\Repository\OutlookConfigurationRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->configRepository = $this
            ->getMockBuilder('Oro\Bundle\ConfigBundle\Api\Repository\ConfigurationRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new LoadConfigurationSections(
            $this->outlookConfigRepository,
            $this->configRepository
        );
    }

    public function testProcessWhenNoResult()
    {
        $this->outlookConfigRepository->expects($this->never())
            ->method('getOutlookSectionOptions');

        $this->processor->process($this->context);

        $this->assertFalse($this->context->hasResult());
    }

    public function testProcessWhenOutlookSectionsExist()
    {
        $scope = 'scope';

        $configSection = new ConfigurationSection('test');
        $configSection->setOptions([new ConfigurationOption($scope, 'key1')]);
        $outlookConfigSection = new ConfigurationSection('outlook');
        $outlookConfigSection->setOptions([new ConfigurationOption($scope, 'key2')]);
        $outlookConfigSubsection = new ConfigurationSection('outlook.sub-section');
        $outlookConfigSubsection->setOptions([new ConfigurationOption($scope, 'key3')]);

        $this->configRepository->expects($this->once())
            ->method('getSectionIds')
            ->willReturn(['test', 'outlook', 'outlook.sub-section']);
        $this->configRepository->expects($this->never())
            ->method('getSection');

        $this->outlookConfigRepository->expects($this->once())
            ->method('getOutlookSectionOptions')
            ->with($scope)
            ->willReturn([new ConfigurationOption($scope, 'key10')]);

        $this->context->set(GetScope::CONTEXT_PARAM, $scope);
        $this->context->setResult(
            [
                $configSection,
                $outlookConfigSection,
                $outlookConfigSubsection
            ]
        );
        $this->processor->process($this->context);

        $expectedConfigSection = new ConfigurationSection('test');
        $expectedConfigSection->setOptions([new ConfigurationOption($scope, 'key1')]);
        $expectedOutlookConfigSection = new ConfigurationSection('outlook');
        $expectedOutlookConfigSection->setOptions(
            [
                new ConfigurationOption($scope, 'key2'),
                new ConfigurationOption($scope, 'key10')
            ]
        );
        $expectedOutlookConfigSubsection = new ConfigurationSection('outlook.sub-section');
        $expectedOutlookConfigSubsection->setOptions([new ConfigurationOption($scope, 'key3')]);
        $this->assertEquals(
            [
                $expectedConfigSection,
                $expectedOutlookConfigSection,
                $expectedOutlookConfigSubsection
            ],
            $this->context->getResult()
        );
    }

    public function testProcessWhenOutlookSectionsDoNotExist()
    {
        $scope = 'scope';

        $configSection = new ConfigurationSection('test');
        $configSection->setOptions([new ConfigurationOption($scope, 'key1')]);
        $outlookConfigSection = new ConfigurationSection('outlook');
        $outlookConfigSection->setOptions([new ConfigurationOption($scope, 'key2')]);
        $outlookConfigSubsection = new ConfigurationSection('outlook.sub-section');
        $outlookConfigSubsection->setOptions([new ConfigurationOption($scope, 'key3')]);

        $this->configRepository->expects($this->once())
            ->method('getSectionIds')
            ->willReturn(['test', 'outlook', 'outlook.sub-section']);
        $this->configRepository->expects($this->exactly(2))
            ->method('getSection')
            ->willReturnMap(
                [
                    ['outlook', $scope, $outlookConfigSection],
                    ['outlook.sub-section', $scope, $outlookConfigSubsection],
                ]
            );

        $this->outlookConfigRepository->expects($this->once())
            ->method('getOutlookSectionOptions')
            ->with($scope)
            ->willReturn([new ConfigurationOption($scope, 'key10')]);

        $this->context->set(GetScope::CONTEXT_PARAM, $scope);
        $this->context->setResult([$configSection]);
        $this->processor->process($this->context);

        $expectedConfigSection = new ConfigurationSection('test');
        $expectedConfigSection->setOptions([new ConfigurationOption($scope, 'key1')]);
        $expectedOutlookConfigSection = new ConfigurationSection('outlook');
        $expectedOutlookConfigSection->setOptions(
            [
                new ConfigurationOption($scope, 'key2'),
                new ConfigurationOption($scope, 'key10')
            ]
        );
        $expectedOutlookConfigSubsection = new ConfigurationSection('outlook.sub-section');
        $expectedOutlookConfigSubsection->setOptions([new ConfigurationOption($scope, 'key3')]);
        $this->assertEquals(
            [
                $expectedConfigSection,
                $expectedOutlookConfigSection,
                $expectedOutlookConfigSubsection
            ],
            $this->context->getResult()
        );
    }
}
