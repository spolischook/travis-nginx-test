<?php

namespace OroCRMPro\Bundle\LDAPBundle\Tests\Unit\Provider\Transport;

use Zend\Ldap\Exception\LdapException;

use OroCRMPro\Bundle\LDAPBundle\Provider\Transport\LdapTransport;

class LdapTransportTest extends \PHPUnit_Framework_TestCase
{
    private $ldap;
    /** @var LdapTransport */
    private $transport;
    /** @var \ReflectionClass */
    private $reflection;

    public function setUp()
    {
        $this->ldap = $this->getMock('Zend\Ldap\Ldap');
        $this->transport = new LdapTransport();
        $this->reflection = new \ReflectionClass($this->transport);
        $prop = $this->reflection->getProperty('ldap');
        $prop->setAccessible(true);
        $prop->setValue($this->transport, $this->ldap);
    }

    public function searchDataProvider()
    {
        return [
            ['filter', ['dn', 'objectClass']],
            ['filter', ['dn']],
            ['filter', []],
        ];
    }

    /**
     * @dataProvider searchDataProvider
     */
    public function testSearch($filter, $attributes)
    {
        $this->ldap->expects($this->once())
            ->method('search')
            ->with(
                $this->equalTo($filter),
                $this->anything(),
                $this->anything(),
                $this->equalTo($attributes)
            );

        $this->transport->search($filter, $attributes);
    }

    public function testBindSuccess()
    {
        $username = 'username';

        $this->ldap->expects($this->once())
            ->method('bind');

        $this->ldap->expects($this->once())
            ->method('getBoundUser')
            ->will($this->returnValue($username));

        $this->assertTrue($this->transport->bind($username));
    }

    public function testBindNotSameBoundUser()
    {
        $username = 'username';

        $this->ldap->expects($this->once())
            ->method('bind');

        $this->ldap->expects($this->once())
            ->method('getBoundUser')
            ->will($this->returnValue('differentUsername'));

        $this->assertFalse($this->transport->bind($username));
    }

    public function testBindException()
    {
        $username = 'username';

        $this->ldap->expects($this->once())
            ->method('bind')
            ->will($this->throwException(new LdapException));

        $this->assertFalse($this->transport->bind($username));
    }
}
