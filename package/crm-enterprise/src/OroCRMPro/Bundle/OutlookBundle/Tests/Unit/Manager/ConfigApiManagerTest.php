<?php

namespace OroCRMPro\Bundle\OutlookBundle\Tests\Unit\Manager;

use OroCRM\Bundle\CRMBundle\OroCRMBundle;
use OroCRMPro\Bundle\OutlookBundle\Manager\ConfigApiManager;

class ConfigApiManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $baseConfigApiManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $versionHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $addInManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $urlHelper;

    /** @var ConfigApiManager */
    protected $outlookConfigApiManager;

    protected function setUp()
    {
        $this->baseConfigApiManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigApiManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->versionHelper = $this->getMockBuilder('Oro\Bundle\PlatformBundle\Composer\VersionHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->addInManager = $this->getMockBuilder('OroCRMPro\Bundle\OutlookBundle\Manager\AddInManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->urlHelper = $this->getMockBuilder('Symfony\Bridge\Twig\Extension\HttpFoundationExtension')
            ->disableOriginalConstructor()
            ->getMock();

        $this->outlookConfigApiManager = new ConfigApiManager(
            $this->baseConfigApiManager,
            $this->versionHelper,
            $this->addInManager,
            $this->urlHelper
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

        $this->versionHelper->expects($this->never())
            ->method('getVersion');

        $this->assertEquals(
            $data,
            $this->outlookConfigApiManager->getData($path)
        );
    }

    public function testOutlookSectionWhenNoAddIn()
    {
        $path = 'outlook';
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

        $this->versionHelper->expects($this->once())
            ->method('getVersion')
            ->with(OroCRMBundle::PACKAGE_NAME)
            ->willReturn('1.2');

        $this->addInManager->expects($this->once())
            ->method('getLatestVersion')
            ->willReturn(null);
        $this->addInManager->expects($this->never())
            ->method('getFile');

        $this->assertEquals(
            [
                [
                    'key'   => 'key1',
                    'type'  => 'string',
                    'value' => 'val1'
                ],
                [
                    'key'   => 'oro_crm_pro_outlook.orocrm_version',
                    'type'  => 'string',
                    'value' => '1.2'
                ],
                [
                    'key'   => 'oro_crm_pro_outlook.addin_latest_version',
                    'type'  => 'string',
                    'value' => null
                ],
            ],
            $this->outlookConfigApiManager->getData($path)
        );
    }

    public function testOutlookSectionWhenNoDocFileAndMinSupportedVersion()
    {
        $path = 'outlook';
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

        $this->versionHelper->expects($this->once())
            ->method('getVersion')
            ->with(OroCRMBundle::PACKAGE_NAME)
            ->willReturn('1.2');

        $this->urlHelper->expects($this->any())
            ->method('generateAbsoluteUrl')
            ->willReturnCallback(
                function ($url) {
                    return 'http://host/' . $url;
                }
            );

        $this->addInManager->expects($this->once())
            ->method('getLatestVersion')
            ->willReturn('2.3.4');
        $this->addInManager->expects($this->once())
            ->method('getFile')
            ->with('2.3.4')
            ->willReturn(['url' => 'AddIn_2.3.4.exe']);
        $this->addInManager->expects($this->once())
            ->method('getMinSupportedVersion')
            ->willReturn(null);

        $this->assertEquals(
            [
                [
                    'key'   => 'key1',
                    'type'  => 'string',
                    'value' => 'val1'
                ],
                [
                    'key'   => 'oro_crm_pro_outlook.orocrm_version',
                    'type'  => 'string',
                    'value' => '1.2'
                ],
                [
                    'key'   => 'oro_crm_pro_outlook.addin_latest_version',
                    'type'  => 'string',
                    'value' => '2.3.4'
                ],
                [
                    'key'   => 'oro_crm_pro_outlook.addin_latest_version_url',
                    'type'  => 'string',
                    'value' => 'http://host/AddIn_2.3.4.exe'
                ],
            ],
            $this->outlookConfigApiManager->getData($path)
        );
    }

    public function testOutlookSectionWithDocFileAndMinSupportedVersion()
    {
        $path = 'outlook';
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

        $this->versionHelper->expects($this->once())
            ->method('getVersion')
            ->with(OroCRMBundle::PACKAGE_NAME)
            ->willReturn('1.2');

        $this->urlHelper->expects($this->any())
            ->method('generateAbsoluteUrl')
            ->willReturnCallback(
                function ($url) {
                    return 'http://host/' . $url;
                }
            );

        $this->addInManager->expects($this->once())
            ->method('getLatestVersion')
            ->willReturn('2.3.4');
        $this->addInManager->expects($this->once())
            ->method('getFile')
            ->with('2.3.4')
            ->willReturn(['url' => 'AddIn_2.3.4.exe', 'doc_url' => 'AddIn_2.3.4.md']);
        $this->addInManager->expects($this->once())
            ->method('getMinSupportedVersion')
            ->willReturn('2.0');

        $this->assertEquals(
            [
                [
                    'key'   => 'key1',
                    'type'  => 'string',
                    'value' => 'val1'
                ],
                [
                    'key'   => 'oro_crm_pro_outlook.orocrm_version',
                    'type'  => 'string',
                    'value' => '1.2'
                ],
                [
                    'key'   => 'oro_crm_pro_outlook.addin_latest_version',
                    'type'  => 'string',
                    'value' => '2.3.4'
                ],
                [
                    'key'   => 'oro_crm_pro_outlook.addin_latest_version_url',
                    'type'  => 'string',
                    'value' => 'http://host/AddIn_2.3.4.exe'
                ],
                [
                    'key'   => 'oro_crm_pro_outlook.addin_latest_version_doc_url',
                    'type'  => 'string',
                    'value' => 'http://host/AddIn_2.3.4.md'
                ],
                [
                    'key'   => 'oro_crm_pro_outlook.addin_min_supported_version',
                    'type'  => 'string',
                    'value' => '2.0'
                ],
            ],
            $this->outlookConfigApiManager->getData($path)
        );
    }
}
