<?php

namespace OroPro\Bundle\OrganizationConfigBundle\Tests\Unit\Twig;

use OroPro\Bundle\OrganizationConfigBundle\Twig\DateRangeFormatOrganizationExtension;

class DateRangeFormatOrganizationExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DateRangeFormatOrganizationExtension
     */
    protected $extension;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $formatter;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $helper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $configManager;

    protected function setUp()
    {
        $this->formatter = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatter')
            ->disableOriginalConstructor()
            ->getMock();

        $this->helper = $this->getMockBuilder('OroPro\Bundle\OrganizationConfigBundle\Helper\OrganizationConfigHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new DateRangeFormatOrganizationExtension($this->formatter, $this->configManager);
        $this->extension->setHelper($this->helper);
    }

    public function testGetFilters()
    {
        $functions = $this->extension->getFunctions();

        $this->assertCount(2, $functions);

        $this->assertArrayHasKey('calendar_date_range_organization', $functions);
        $this->assertInstanceOf('Twig_Function_Method', $functions['calendar_date_range_organization']);
        $this->assertAttributeEquals('formatCalendarDateRangeOrganization', 'method', $functions['calendar_date_range_organization']);
    }

    public function testFormatCalendarDateRangeOrganizationShouldGetLocaleFromConfigurationIfOrganizationProvided()
    {
        $startDate = new \DateTime('2016-05-31 00:00:00');
        $endDate = new \DateTime('2016-06-01 00:00:00');

        $locale = 'en_US';
        $timeZone = 'America/Los_Angeles';

        $organization = $this->getMock('Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface');
        $organizationId = 42;
        $organization->expects($this->any())
            ->method('getId')
            ->willReturn($organizationId);

        $this->helper->expects($this->exactly(2))
            ->method('getOrganizationScopeConfig')
            ->willReturnMap(
                [
                    [$organizationId, 'oro_locale.locale', $locale],
                    [$organizationId, 'oro_locale.timezone', $timeZone]
                ]
            );

        $formattedStartDate = 'May 30, 2016, 4:00 PM';
        $formattedEndDate = 'May 31, 2016, 4:00 PM';

        $this->formatter
            ->expects($this->exactly(2))
            ->method('format')
            ->willReturnMap([
                [$startDate, null, null, $locale, $timeZone, null, $formattedStartDate],
                [$endDate, null, null, $locale, $timeZone, null, $formattedEndDate],
            ]);

        $actual = $this->extension->formatCalendarDateRangeOrganization(
            $startDate,
            $endDate,
            false,
            null,
            null,
            'fr_FR',
            'Europe/Athens',
            $organization
        );

        $expected = "$formattedStartDate - $formattedEndDate";
        $this->assertEquals($expected, $actual);
    }

    public function testFormatCalendarDateRangeOrganizationShouldUseTimezonePassedInOptionsIfOrganizationNotProvided()
    {
        $startDate = new \DateTime('2016-05-31 00:00:00');
        $endDate = new \DateTime('2016-06-01 00:00:00');

        $locale = 'en_US';
        $timeZone = 'America/Los_Angeles';

        $this->helper->expects($this->never())
            ->method('getOrganizationScopeConfig');

        $formattedStartDate = 'May 30, 2016, 4:00 PM';
        $formattedEndDate = 'May 31, 2016, 4:00 PM';

        $this->formatter
            ->expects($this->exactly(2))
            ->method('format')
            ->willReturnMap([
                [$startDate, null, null, $locale, $timeZone, null, $formattedStartDate],
                [$endDate, null, null, $locale, $timeZone, null, $formattedEndDate],
            ]);

        $actual = $this->extension->formatCalendarDateRangeOrganization(
            $startDate,
            $endDate,
            false,
            null,
            null,
            $locale,
            $timeZone,
            null
        );

        $expected = "$formattedStartDate - $formattedEndDate";
        $this->assertEquals($expected, $actual);
    }

    public function testGetName()
    {
        $this->assertEquals('oropro_organization_config_daterange_format_organization', $this->extension->getName());
    }
}
