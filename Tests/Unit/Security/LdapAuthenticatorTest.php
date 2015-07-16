<?php

namespace OroCRMPro\Bundle\LDAPBundle\Tests\Unit\Security;

use OroCRMPro\Bundle\LDAPBundle\Security\LdapAuthenticator;
use OroCRMPro\Bundle\LDAPBundle\Tests\Unit\Stub\TestingUser;

class LdapAuthenticatorTest extends \PHPUnit_Framework_TestCase
{
    private $authenticator;
    private $repository;
    private $registry;
    private $transport;
    private $channels;

    public function mockChannel($id)
    {
        $channel = $this->getMock('Oro\Bundle\IntegrationBundle\Entity\Channel');

        $channel->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($id));

        $channel->expects($this->any())
            ->method('getTransport')
            ->will($this->returnValue($this->getMock('Oro\Bundle\IntegrationBundle\Entity\Transport')));

        return $channel;
    }

    public function mockChannels()
    {
        return $this->channels = [
            $this->mockChannel(1),
            $this->mockChannel(5),
            $this->mockChannel(70),
        ];
    }

    public function mockChannelRepository()
    {
        $repo = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repo->expects($this->any())
            ->method('findBy')
            ->will($this->returnValue($this->mockChannels()));

        return $repo;
    }

    public function setUp()
    {
        $this->registry = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->repository = $this->mockChannelRepository();
        $this->registry->expects($this->any())
            ->method('getRepository')
            ->with($this->equalTo('OroIntegrationBundle:Channel'))
            ->will($this->returnValue($this->repository));
        $this->transport = $this->getMock('OroCRMPro\Bundle\LDAPBundle\Provider\Transport\LdapTransportInterface');
        $this->authenticator = new LdapAuthenticator($this->registry, $this->transport);
    }

    public function testCheckSuccess()
    {
        $this->channels[0]->expects($this->any())
            ->method('isEnabled')
            ->will($this->returnValue(false));
        $this->channels[1]->expects($this->any())
            ->method('isEnabled')
            ->will($this->returnValue(true));
        $this->channels[2]->expects($this->any())
            ->method('isEnabled')
            ->will($this->returnValue(true));

        $this->transport->expects($this->exactly(2))
            ->method('bind')
            ->will($this->onConsecutiveCalls(false, true));
        $user = new TestingUser();
        $user->setLdapDistinguishedNames(
            [
                5  => 'dn1',
                70 => 'dn5',
            ]
        );

        $this->assertTrue($this->authenticator->check($user, 'password'));
    }

    public function testCheckFailBecauseNoChannelIsActive()
    {
        $this->channels[0]->expects($this->any())
            ->method('isEnabled')
            ->will($this->returnValue(false));
        $this->channels[1]->expects($this->any())
            ->method('isEnabled')
            ->will($this->returnValue(false));
        $this->channels[2]->expects($this->any())
            ->method('isEnabled')
            ->will($this->returnValue(false));

        $this->transport->expects($this->never())
            ->method('bind');
        $user = new TestingUser();
        $user->setLdapDistinguishedNames(
            [
                5  => 'dn1',
                70 => 'dn5',
            ]
        );

        $this->assertFalse($this->authenticator->check($user, 'password'));
    }

    public function testCheckFailBecauseUserHasNoMappings()
    {
        $this->channels[0]->expects($this->any())
            ->method('isEnabled')
            ->will($this->returnValue(false));
        $this->channels[1]->expects($this->any())
            ->method('isEnabled')
            ->will($this->returnValue(true));
        $this->channels[2]->expects($this->any())
            ->method('isEnabled')
            ->will($this->returnValue(true));

        $this->transport->expects($this->never())
            ->method('bind');
        $user = new TestingUser();
        $user->setLdapDistinguishedNames([]);

        $this->assertFalse($this->authenticator->check($user, 'password'));
    }

    public function testCheckFailBecauseNoChannelBound()
    {
        $this->channels[0]->expects($this->any())
            ->method('isEnabled')
            ->will($this->returnValue(false));
        $this->channels[1]->expects($this->any())
            ->method('isEnabled')
            ->will($this->returnValue(true));
        $this->channels[2]->expects($this->any())
            ->method('isEnabled')
            ->will($this->returnValue(true));

        $this->transport->expects($this->exactly(2))
            ->method('bind')
            ->will($this->onConsecutiveCalls(false, false));
        $user = new TestingUser();
        $user->setLdapDistinguishedNames(
            [
                5  => 'dn1',
                70 => 'dn5',
            ]
        );

        $this->assertFalse($this->authenticator->check($user, 'password'));
    }
}
