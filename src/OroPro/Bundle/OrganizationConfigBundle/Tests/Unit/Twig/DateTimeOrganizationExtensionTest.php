<?php

namespace OroPro\Bundle\OrganizationConfigBundle\Tests\Unit\Twig;

use OroPro\Bundle\OrganizationConfigBundle\Twig\DateTimeOrganizationExtension;
use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatter;

class DateTimeOrganizationExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DateTimeOrganizationExtension
     */
    protected $extension;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $container;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $localeSettings;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $configManager;

    /**
     * @var DateTimeFormatter
     */
    protected $formatter;

    protected function setUp()
    {
        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager->expects($this->any())->method('getScopeId');
        $this->configManager->expects($this->any())->method('setScopeId');

        $this->container = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->localeSettings = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Model\LocaleSettings')
            ->disableOriginalConstructor()
            ->getMock();
        $translator = $this->getMockBuilder('Symfony\Bundle\FrameworkBundle\Translation\Translator')
            ->disableOriginalConstructor()
            ->getMock();
        $this->formatter = new DateTimeFormatter($this->localeSettings, $translator);

        $this->extension = new DateTimeOrganizationExtension($this->formatter);
        $this->extension->setContainer($this->container);
    }

    public function testGetFilters()
    {
        $filters = $this->extension->getFilters();

        $this->assertCount(5, $filters);

        $this->assertInstanceOf('Twig_SimpleFilter', $filters[4]);
        $this->assertEquals('oro_format_datetime_organization', $filters[4]->getName());
    }

    /**
     * @param string $value
     * @param string $expected
     * @param array $options
     * @param string|null $locale
     * @param string|null $timeZone
     *
     * @dataProvider formatDateTimeOrganizationDataProvider
     */
    public function testFormatDateTimeOrganization($value, $expected, array $options, $locale = null, $timeZone = null)
    {
        $this->container->expects($this->any())
            ->method('get')
            ->with('oro_config.organization')
            ->willReturn($this->configManager);

        $this->configManager->expects($this->any())
            ->method('get')
            ->will(
                $this->returnValueMap(
                    [
                        ['oro_locale.locale', false, false, $locale],
                        ['oro_locale.timezone', false, false, $timeZone],
                    ]
                )
            );

        $this->assertEquals($expected, $this->extension->formatDateTimeOrganization($value, $options));
    }

    /**
     * @return array
     */
    public function formatDateTimeOrganizationDataProvider()
    {
        return [
            'options without organization negative shift' => [
                'value' => new \DateTime('2016-05-31 00:00:00', new \DateTimeZone('UTC')),
                'expected' => 'May 30, 2016, 5:00 PM',
                'options' => [
                    'locale' => 'en_US',
                    'timeZone' => 'America/Los_Angeles',
                ],
            ],
            'options without organization positive shift' => [
                'value' => new \DateTime('2016-05-31 00:00:00', new \DateTimeZone('UTC')),
                'expected' => 'May 31, 2016, 3:00 AM',
                'options' => [
                    'locale' => 'en_US',
                    'timeZone' => 'Europe/Athens',
                ],
                'locale' => 'en_US',
                'timeZone' => 'Europe/Athens',
            ],
            'organization timeZone positive shift' => [
                'value' => new \DateTime('2016-05-31 00:00:00', new \DateTimeZone('UTC')),
                'expected' => 'May 31, 2016, 3:00 AM',
                'options' => [
                    'organization' => 1
                ],
                'locale' => 'en_US',
                'timeZone' => 'Europe/Athens',
            ],
            'organization timeZone negative shift' => [
                'value' => new \DateTime('2016-05-31 00:00:00', new \DateTimeZone('UTC')),
                'expected' => 'May 30, 2016, 6:00 PM',
                'options' => [
                    'organization' => 2
                ],
                'locale' => 'en_US',
                'timeZone' => 'Pacific/Easter',
            ],
        ];
    }

    public function testGetName()
    {
        $this->assertEquals('oropro_organization_config_datetime_organization', $this->extension->getName());
    }
}
