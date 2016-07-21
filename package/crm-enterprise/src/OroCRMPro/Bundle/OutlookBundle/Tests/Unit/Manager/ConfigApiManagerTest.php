<?php

namespace OroCRMPro\Bundle\OutlookBundle\Tests\Unit\Manager;

use Oro\Bundle\ConfigBundle\Api\Model\ConfigurationOption;
use OroCRMPro\Bundle\OutlookBundle\Manager\ConfigApiManager;

class ConfigApiManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $baseConfigApiManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $outlookConfigRepository;

    /** @var ConfigApiManager */
    protected $outlookConfigApiManager;

    protected function setUp()
    {
        $this->baseConfigApiManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigApiManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->outlookConfigRepository = $this
            ->getMockBuilder('OroCRMPro\Bundle\OutlookBundle\Api\Repository\OutlookConfigurationRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->outlookConfigApiManager = new ConfigApiManager(
            $this->baseConfigApiManager,
            $this->outlookConfigRepository
        );
    }

    public function testOutlookSubsection()
    {
        $path = 'outlook/layouts';
        $data = [
            [
                'key'   => 'key1',
                'type'  => 'string',
                'value' => 'val1'
            ]
        ];

        $this->baseConfigApiManager->expects($this->once())
            ->method('getData')
            ->with($path)
            ->willReturn($data);

        $this->outlookConfigRepository->expects($this->never())
            ->method('getOutlookSectionOptions');

        $this->assertEquals(
            $data,
            $this->outlookConfigApiManager->getData($path)
        );
    }

    public function testOutlookSection()
    {
        $path = 'outlook';
        $scope = 'global';
        $data = [
            [
                'key'   => 'key1',
                'type'  => 'string',
                'value' => 'val1'
            ]
        ];

        $this->baseConfigApiManager->expects($this->once())
            ->method('getData')
            ->with($path)
            ->willReturn($data);

        $configOption = new ConfigurationOption($scope, 'key2');
        $configOption->setDataType('string');
        $configOption->setValue('val2');
        $this->outlookConfigRepository->expects($this->once())
            ->method('getOutlookSectionOptions')
            ->with($scope)
            ->willReturn([$configOption]);

        $this->assertEquals(
            [
                [
                    'key'   => 'key1',
                    'type'  => 'string',
                    'value' => 'val1'
                ],
                [
                    'key'   => 'key2',
                    'type'  => 'string',
                    'value' => 'val2'
                ],
            ],
            $this->outlookConfigApiManager->getData($path, $scope)
        );
    }
}
