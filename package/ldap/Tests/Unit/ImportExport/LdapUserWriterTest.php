<?php

namespace OroCRMPro\Bundle\LDAPBundle\Tests\Unit\ImportExport;

use Oro\Bundle\DataGridBundle\Common\DataObject;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Provider\ConnectorContextMediator;

use OroCRMPro\Bundle\LDAPBundle\Entity\LdapTransport;
use OroCRMPro\Bundle\LDAPBundle\ImportExport\LdapHelper;
use OroCRMPro\Bundle\LDAPBundle\ImportExport\LdapUserWriter;
use OroCRMPro\Bundle\LDAPBundle\Provider\Transport\LdapTransportInterface;

class LdapUserWriterTest extends \PHPUnit_Framework_TestCase
{
    /** @var LdapUserWriter */
    private $writer;
    /** @var ConnectorContextMediator */
    private $contextMediator;
    /** @var ContextRegistry */
    private $contextRegistry;
    /** @var Channel */
    private $channel;
    /** @var LdapTransportInterface */
    private $transport;
    /** @var ContextInterface */
    private $context;
    /** @var LdapHelper */
    private $helper;

    public function setUp()
    {
        $this->channel = $this->getMock('Oro\Bundle\IntegrationBundle\Entity\Channel');
        $this->channel->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(1));
        $mappingSettings = DataObject::create(
            [
                'exportUserBaseDn'      => 'ou=group,dc=localhost',
                'exportUserObjectClass' => 'inetOrgPerson',
                'userMapping'           => [
                    'username'   => 'cn',
                    'first_name' => 'sn',
                    'last_name'  => 'displayname',
                    'email'      => 'givenname',
                    'status'     => null,
                ],
            ]
        );
        $this->channel->expects($this->any())
            ->method('getMappingSettings')
            ->will($this->returnValue($mappingSettings));
        $syncSettings = DataObject::create(
            [
                'syncPriority'        => 'local',
                'isTwoWaySyncEnabled' => true,
            ]
        );
        $this->channel->expects($this->any())
            ->method('getSynchronizationSettings')
            ->will($this->returnValue($syncSettings));
        $transport = new LdapTransport();
        $this->channel->expects($this->any())
            ->method('getTransport')
            ->will($this->returnValue($transport));

        $this->contextMediator = $this->getMockBuilder('Oro\Bundle\IntegrationBundle\Provider\ConnectorContextMediator')
            ->disableOriginalConstructor()
            ->getMock();

        $this->contextRegistry = $this->getMockBuilder('Oro\Bundle\ImportExportBundle\Context\ContextRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->contextMediator->expects($this->any())
            ->method('getChannel')
            ->will($this->returnValue($this->channel));

        $this->contextMediator->expects($this->any())
            ->method('getTransport')
            ->will(
                $this->returnValue(
                    $this->transport = $this->getMock(
                        'OroCRMPro\Bundle\LDAPBundle\Provider\Transport\LdapTransportInterface'
                    )
                )
            );

        $this->helper = $this->getMockBuilder('OroCRMPro\Bundle\LDAPBundle\ImportExport\LdapHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->writer = new LdapUserWriter($this->contextRegistry, $this->contextMediator, $this->helper);
        $this->writer->setImportExportContext(
            $this->context = $this->getMock('Oro\Bundle\ImportExportBundle\Context\Contextinterface')
        );
    }

    public function testWriteNoItemsWritten()
    {
        $this->transport->expects($this->never())
            ->method('exists');
        $this->transport->expects($this->never())
            ->method('add');
        $this->transport->expects($this->never())
            ->method('update');
        $this->helper->expects($this->once())
            ->method('updateUserDistinguishedNames')
            ->with(
                $this->equalTo(1),
                $this->equalTo([])
            );

        $this->writer->write([]);
    }

    public function testWriteOneUpdatedItem()
    {
        $this->transport->expects($this->once())
            ->method('exists')
            ->will($this->returnValue(true));
        $this->transport->expects($this->never())
            ->method('add');
        $this->transport->expects($this->once())
            ->method('update');
        $this->context->expects($this->once())
            ->method('incrementUpdateCount');
        $this->helper->expects($this->once())
            ->method('updateUserDistinguishedNames')
            ->with(
                $this->equalTo(1),
                $this->equalTo(['username' => 'example_dn'])
            );

        $this->writer->write(
            [
                [
                    'dn' => [
                        1 => 'example_dn',
                    ],
                    'cn' => 'username',
                ],
            ]
        );
    }

    public function testWriteOneAddedItem()
    {
        $this->transport->expects($this->once())
            ->method('exists')
            ->will($this->returnValue(false));
        $this->transport->expects($this->once())
            ->method('add');
        $this->transport->expects($this->never())
            ->method('update');
        $this->context->expects($this->once())
            ->method('incrementAddCount');
        $this->helper->expects($this->once())
            ->method('updateUserDistinguishedNames')
            ->with(
                $this->equalTo(1),
                $this->equalTo(['username' => 'cn=username,ou=group,dc=localhost'])
            );

        $this->writer->write(
            [
                [
                    'dn' => null,
                    'cn' => 'username',
                ],
            ]
        );
    }

    public function testWriteAddFailedItem()
    {
        $this->transport->expects($this->once())
            ->method('exists')
            ->will($this->returnValue(false));
        $this->transport->expects($this->once())
            ->method('add')
            ->will($this->throwException(new \Exception('Some error.')));
        $this->transport->expects($this->never())
            ->method('update');
        $this->context->expects($this->once())
            ->method('addError')
            ->with($this->equalTo('Some error.'));
        $this->context->expects($this->once())
            ->method('incrementErrorEntriesCount');
        $this->helper->expects($this->once())
            ->method('updateUserDistinguishedNames')
            ->with(
                $this->equalTo(1),
                $this->equalTo([])
            );

        $this->writer->write(
            [
                [
                    'dn' => null,
                    'cn' => 'username',
                ],
            ]
        );
    }

    public function testWriteUpdateFailedItem()
    {
        $this->transport->expects($this->once())
            ->method('exists')
            ->will($this->returnValue(true));
        $this->transport->expects($this->never())
            ->method('add');
        $this->transport->expects($this->once())
            ->method('update')
            ->will($this->throwException(new \Exception('Some error.')));
        $this->context->expects($this->once())
            ->method('addError')
            ->with($this->equalTo('Some error.'));
        $this->context->expects($this->once())
            ->method('incrementErrorEntriesCount');
        $this->helper->expects($this->once())
            ->method('updateUserDistinguishedNames')
            ->with(
                $this->equalTo(1),
                $this->equalTo([])
            );

        $this->writer->write(
            [
                [
                    'dn' => null,
                    'cn' => 'username',
                ],
            ]
        );
    }
}
