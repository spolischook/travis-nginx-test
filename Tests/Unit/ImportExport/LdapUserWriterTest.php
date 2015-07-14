<?php

namespace Oro\Bundle\LDAPBundle\Tests\Unit\ImportExport;

use Oro\Bundle\DataGridBundle\Common\Object;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Provider\ConnectorContextMediator;
use Oro\Bundle\LDAPBundle\Entity\LdapTransport;
use Oro\Bundle\LDAPBundle\ImportExport\LdapUserWriter;
use Oro\Bundle\LDAPBundle\Provider\Transport\LdapTransportInterface;

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

    public function setUp()
    {
        $this->channel = $this->getMock('Oro\Bundle\IntegrationBundle\Entity\Channel');
        $this->channel->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(1));
        $mappingSettings = Object::create(
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
        $syncSettings = Object::create(
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
                    $this->transport = $this->getMock('Oro\Bundle\LDAPBundle\Provider\Transport\LdapTransportInterface')
                )
            );

        $this->writer = new LdapUserWriter($this->contextRegistry, $this->contextMediator);
        $this->writer->setImportExportContext(
            $this->context = $this->getMock('Oro\Bundle\ImportExportBundle\Context\Contextinterface')
        );
    }

    public function testNoItemsWritten()
    {
        $this->transport->expects($this->never())
            ->method('exists');
        $this->transport->expects($this->never())
            ->method('add');
        $this->transport->expects($this->never())
            ->method('update');

        $this->writer->write([]);
    }

    public function testOneUpdatedItem()
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

    public function testOneAddedItem()
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
