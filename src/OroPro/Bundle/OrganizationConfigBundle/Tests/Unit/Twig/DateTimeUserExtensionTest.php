<?php

namespace OroPro\Bundle\OrganizationConfigBundle\Tests\Unit\Twig;

use OroPro\Bundle\OrganizationConfigBundle\Twig\DateTimeUserExtension;

class DateTimeUserExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DateTimeUserExtension
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

    protected function setUp()
    {
        $this->formatter = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatter')
            ->disableOriginalConstructor()
            ->getMock();

        $this->helper = $this->getMockBuilder('OroPro\Bundle\OrganizationConfigBundle\Helper\OrganizationConfigHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new DateTimeUserExtension($this->formatter);
        $this->extension->setHelper($this->helper);
    }

    public function testGetFilters()
    {
        $filters = $this->extension->getFilters();

        $this->assertCount(5, $filters);

        $this->assertInstanceOf('Twig_SimpleFilter', $filters[4]);
        $this->assertEquals('oro_format_datetime_user', $filters[4]->getName());
    }

    public function testFormatDateTimeUserShouldUseConfigurationTimezoneIfUserAndOrganizationProvided()
    {
        $date = new \DateTime('2016-05-31 00:00:00');
        $expected = 'May 30, 2016, 4:00 PM';

        $user = $this->getMock('Oro\Bundle\UserBundle\Entity\User');
        $organization = $this->getMock('Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface');
        $organizationId = 42;
        $organization->expects($this->any())
            ->method('getId')
            ->willReturn($organizationId);
        $user->expects($this->any())
            ->method('getOrganization')
            ->willReturn($organization);

        $userLocale = 'en_US';
        $userTimezone = 'America/Los_Angeles';
        $this->helper->expects($this->any())
            ->method('getOrganizationScopeConfig')
            ->willReturnMap(
                [
                    [$organizationId, 'oro_locale.locale', $userLocale],
                    [$organizationId, 'oro_locale.timezone', $userTimezone],
                ]
            );
        $this->formatter->expects($this->once())
            ->method('format')
            ->with($date, null, null, $userLocale, $userTimezone)
            ->willReturn($expected);

        $options = [
            'locale'   => 'fr_FR',
            'timeZone' => 'Europe/Athens',
            'user'     => $user
        ];
        $actual = $this->extension->formatDateTimeUser($date, $options);

        $this->assertEquals($expected, $actual);
    }

    public function testFormatDateTimeUserShouldUseTimezonePassedInOptionsIfUserHasNoOrganization()
    {
        $date = new \DateTime('2016-05-31 00:00:00');
        $expected = 'May 30, 2016, 4:00 PM';

        $this->helper->expects($this->never())
            ->method('getOrganizationScopeConfig');

        $locale = 'en_US';
        $timezone = 'America/Los_Angeles';
        $this->formatter->expects($this->once())
            ->method('format')
            ->with($date, null, null, $locale, $timezone)
            ->willReturn($expected);

        $user = $this->getMock('Oro\Bundle\UserBundle\Entity\UserInterface');
        $options = [
            'locale'   => $locale,
            'timeZone' => $timezone,
            $user
        ];
        $actual = $this->extension->formatDateTimeUser($date, $options);

        $this->assertEquals($expected, $actual);
    }

    public function testFormatDateTimeUserShouldUseTimezonePassedInOptionsIfUserNotProvided()
    {
        $date = new \DateTime('2016-05-31 00:00:00');
        $expected = 'May 30, 2016, 4:00 PM';

        $this->helper->expects($this->never())
            ->method('getOrganizationScopeConfig');

        $locale = 'en_US';
        $timezone = 'America/Los_Angeles';
        $this->formatter->expects($this->once())
            ->method('format')
            ->with($date, null, null, $locale, $timezone)
            ->willReturn($expected);

        $options = [
            'locale'   => $locale,
            'timeZone' => $timezone
        ];
        $actual = $this->extension->formatDateTimeUser($date, $options);

        $this->assertEquals($expected, $actual);
    }

    public function testGetName()
    {
        $this->assertEquals('oropro_organization_config_datetime_user', $this->extension->getName());
    }
}
