<?php

namespace OroCRMPro\Bundle\LDAPBundle\Tests\Unit\ImportExport;

use Oro\Bundle\DataGridBundle\Common\DataObject;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Provider\ConnectorContextMediator;

use OroCRMPro\Bundle\LDAPBundle\ImportExport\UserDataConverter;

class UserDataConverterTest extends \PHPUnit_Framework_TestCase
{
    /** @var Channel */
    private $channel;
    /** @var ConnectorContextMediator */
    private $contextMediator;
    /** @var ContextRegistry */
    private $contextRegistry;
    /** @var UserDataConverter */
    private $dataConverter;

    public function setUp()
    {
        $this->channel = new Channel();
        $this->channel->setMappingSettings(
            DataObject::create(
                [
                    'userMapping' => [
                        'username'   => 'cn',
                        'first_name' => 'sn',
                        'last_name'  => 'displayName',
                        'phone'      => null,
                        'status'     => null,
                    ]
                ]
            )
        );

        $this->contextMediator = $this->getMockBuilder('Oro\Bundle\IntegrationBundle\Provider\ConnectorContextMediator')
            ->disableOriginalConstructor()
            ->getMock();

        $this->contextRegistry = $this->getMockBuilder('Oro\Bundle\ImportExportBundle\Context\ContextRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->contextMediator->expects($this->any())
            ->method('getChannel')
            ->will($this->returnValue($this->channel));

        $this->dataConverter = new UserDataConverter($this->contextMediator, $this->contextRegistry);
        $this->dataConverter->setImportExportContext(
            $this->getMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface')
        );
    }

    public function testConvertToImportFormatDataProvider()
    {
        return [
            [[
                'cn'          => 'username_value',
                'sn'          => 'fisrt_name_value',
                'displayname' => 'last_name_value',
            ], [
                'username'   => 'username_value',
                'first_name' => 'fisrt_name_value',
                'last_name'  => 'last_name_value',
            ]],
            [[
                'cn'          => 'username_value',
                'sn'          => 'fisrt_name_value',
                'displayname' => 'last_name_value',
                'objectClass' => 'inetOrgPerson',
            ], [
                'username'    => 'username_value',
                'first_name'  => 'fisrt_name_value',
                'last_name'   => 'last_name_value',
                'objectClass' => 'inetOrgPerson',
            ]],
            [[
                'cn'          => 'username_value',
                'sn'          => 'fisrt_name_value',
                'displayname' => 'last_name_value',
                'mobile'      => 'phone',
            ], [
                'username'   => 'username_value',
                'first_name' => 'fisrt_name_value',
                'last_name'  => 'last_name_value',
                'mobile'     => 'phone'
            ]],
        ];
    }

    /**
     * @dataProvider testConvertToImportFormatDataProvider
     */
    public function testConvertToImportFormat($given, $expected)
    {
        $this->assertEquals($expected, $this->dataConverter->convertToImportFormat($given));
    }

    public function testConvertToExportFormatDataProvider()
    {
        return [
            [[
                'username'   => 'username_value',
                'first_name' => 'fisrt_name_value',
                'last_name'  => 'last_name_value',
            ], [
                'cn'          => 'username_value',
                'sn'          => 'fisrt_name_value',
                'displayname' => 'last_name_value',
                'dn'          => '',
            ]],
            [[
                'username'    => 'username_value',
                'first_name'  => 'fisrt_name_value',
                'last_name'   => 'last_name_value',
                'objectClass' => 'inetOrgPerson',
            ], [
                'cn'          => 'username_value',
                'sn'          => 'fisrt_name_value',
                'displayname' => 'last_name_value',
                'dn'          => '',
            ]],
            [[
                'username'   => 'username_value',
                'first_name' => 'fisrt_name_value',
                'last_name'  => 'last_name_value',
                'mobile'     => 'phone'
            ], [
                'cn'          => 'username_value',
                'sn'          => 'fisrt_name_value',
                'displayname' => 'last_name_value',
                'dn'          => '',
            ]],
        ];
    }

    /**
     * @dataProvider testConvertToExportFormatDataProvider
     */
    public function testConvertToExportFormat($given, $expected)
    {
        $this->assertEquals($expected, $this->dataConverter->convertToExportFormat($given));
    }
}
