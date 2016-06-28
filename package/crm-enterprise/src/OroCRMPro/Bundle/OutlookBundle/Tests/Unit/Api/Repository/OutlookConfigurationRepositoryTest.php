<?php

namespace OroCRMPro\Bundle\OutlookBundle\Tests\Unit\Api\Repository;

use Oro\Bundle\ConfigBundle\Api\Model\ConfigurationOption;
use OroCRM\Bundle\CRMBundle\OroCRMBundle;
use OroCRMPro\Bundle\OutlookBundle\Api\Repository\OutlookConfigurationRepository;

class OutlookConfigurationRepositoryTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $versionHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $addInManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $urlHelper;

    /** @var OutlookConfigurationRepository */
    protected $outlookConfigurationRepository;

    protected function setUp()
    {
        $this->versionHelper = $this->getMockBuilder('Oro\Bundle\PlatformBundle\Composer\VersionHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->addInManager = $this->getMockBuilder('OroCRMPro\Bundle\OutlookBundle\Manager\AddInManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->urlHelper = $this->getMockBuilder('Symfony\Bridge\Twig\Extension\HttpFoundationExtension')
            ->disableOriginalConstructor()
            ->getMock();

        $this->outlookConfigurationRepository = new OutlookConfigurationRepository(
            $this->versionHelper,
            $this->addInManager,
            $this->urlHelper
        );
    }

    /**
     * @param array                 $expected
     * @param ConfigurationOption[] $actual
     */
    protected function assertConfigOptionsEqual($expected, $actual)
    {
        $actualData = [];
        foreach ($actual as $option) {
            $actualData[] = [
                'key'   => $option->getKey(),
                'type'  => $option->getDataType(),
                'value' => $option->getValue()
            ];
        }

        $this->assertEquals($expected, $actualData);
    }

    public function testOutlookSectionWhenNoAddIn()
    {
        $this->versionHelper->expects($this->once())
            ->method('getVersion')
            ->with(OroCRMBundle::PACKAGE_NAME)
            ->willReturn('1.2');

        $this->addInManager->expects($this->once())
            ->method('getLatestVersion')
            ->willReturn(null);
        $this->addInManager->expects($this->never())
            ->method('getFile');

        $this->assertConfigOptionsEqual(
            [
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
            $this->outlookConfigurationRepository->getOutlookSectionOptions('global')
        );
    }

    public function testOutlookSectionWhenNoDocFileAndMinSupportedVersion()
    {
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

        $this->assertConfigOptionsEqual(
            [
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
            $this->outlookConfigurationRepository->getOutlookSectionOptions('global')
        );
    }

    public function testOutlookSectionWithDocFileAndMinSupportedVersion()
    {
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

        $this->assertConfigOptionsEqual(
            [
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
            $this->outlookConfigurationRepository->getOutlookSectionOptions('global')
        );
    }
}
