<?php

namespace OroPro\Bundle\OrganizationConfigBundle\Tests\Unit\Twig;

use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatter;
use Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\User;

use OroPro\Bundle\OrganizationConfigBundle\Twig\DateRangeFormatUserExtension;
use OroPro\Bundle\OrganizationConfigBundle\Helper\OrganizationConfigHelper;
use OroPro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\Organization;

class DateRangeFormatUserExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DateRangeFormatUserExtension
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

    /**
     * @var OrganizationConfigHelper
     */
    protected $helper;

    protected function setUp()
    {
        $this->markTestSkipped('CRM-5745');

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

        $this->helper = new OrganizationConfigHelper($this->container);

        $this->extension = new DateRangeFormatUserExtension($this->formatter);
        $this->extension->setHelper($this->helper);
    }

    public function testGetFilters()
    {
        $functions = $this->extension->getFunctions();

        $this->assertCount(2, $functions);

        $this->assertInstanceOf('Twig_Function_Method', $functions['calendar_date_range_user']);
        $this->assertAttributeEquals('formatCalendarDateRangeUser', 'method', $functions['calendar_date_range_user']);
    }

    /**
     * @param string $start
     * @param string $end
     * @param array $config
     * @param string|null $locale
     * @param string|null $timeZone
     * @param User $user
     * @param string $expected
     *
     * @dataProvider formatCalendarDateRangeUserProvider
     */
    public function testFormatCalendarDateRangeUser($start, $end, array $config, $locale, $timeZone, $user, $expected)
    {
        $startDate = new \DateTime($start, new \DateTimeZone('UTC'));
        $endDate = $end === null ? null : new \DateTime($end, new \DateTimeZone('UTC'));

        $this->container->expects($this->any())
            ->method('get')
            ->with('oro_config.organization')
            ->willReturn($this->configManager);

        $this->configManager->expects($this->any())
            ->method('get')
            ->will(
                $this->returnValueMap(
                    [
                        ['oro_locale.locale', false, false, $config['locale']],
                        ['oro_locale.timezone', false, false, $config['timeZone']],
                    ]
                )
            );

        $result = $this->extension->formatCalendarDateRangeUser(
            $startDate,
            $endDate,
            false,
            null,
            null,
            $locale,
            $timeZone,
            $user
        );

        $this->assertEquals($expected, $result);
    }

    public function formatCalendarDateRangeUserProvider()
    {
        $organization = new Organization(1);
        $user = new User(1, null, $organization);

        return [
            'Localization settings from organization scope' => [
                '2016-05-01 10:30:15',
                '2016-05-01 11:30:15',
                ['locale' => 'en_US', 'timeZone' => 'America/Los_Angeles'], // config organization scope
                null,
                null,
                $user,
                'May 1, 2016 3:30 AM - 4:30 AM'
            ],
            'Localization settings from organization scope start=end ' => [
                '2016-05-01 10:30:15',
                '2016-05-01 10:30:15',
                ['locale' => 'en_US', 'timeZone' => 'America/Los_Angeles'], // config organization scope
                null,
                null,
                $user,
                'May 1, 2016, 3:30 AM'
            ],
            'Localization settings from global scope' => [
                '2016-05-01 10:30:15',
                '2016-05-01 11:30:15',
                ['locale' => 'en_US', 'timeZone' => 'UTC'], // config global scope
                null,
                null,
                $user,
                'May 1, 2016 10:30 AM - 11:30 AM'
            ],
            'Localization settings from global scope start=end' => [
                '2016-05-01 10:30:15',
                '2016-05-01 10:30:15',
                ['locale' => 'en_US', 'timeZone' => 'UTC'], // config global scope
                null,
                null,
                $user,
                'May 1, 2016, 10:30 AM'
            ],
            'Localization settings from params values' => [
                '2016-05-01 10:30:15',
                '2016-05-01 11:30:15',
                ['locale' => 'en_US', 'timeZone' => 'UTC'], // config global scope
                'en_US',
                'Europe/Athens',
                null,
                'May 1, 2016 1:30 PM - 2:30 PM'
            ],
            'Localization settings from params values start=end' => [
                '2016-05-01 10:30:15',
                '2016-05-01 10:30:15',
                ['locale' => 'en_US', 'timeZone' => 'UTC'], // config global scope
                'en_US',
                'Europe/Athens',
                null,
                'May 1, 2016, 1:30 PM'
            ]
        ];
    }

    public function testGetName()
    {
        $this->assertEquals('oropro_organization_config_daterange_format_user', $this->extension->getName());
    }
}
