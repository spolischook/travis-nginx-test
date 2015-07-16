<?php

namespace OroCRMPro\Bundle\LDAPBundle\Tests\Unit\Twig;

use OroCRMPro\Bundle\LDAPBundle\Twig\LdapDynamicFieldsExtension;

class LdapDynamicFieldsExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var \Oro\Bundle\EntityExtendBundle\Twig\DynamicFieldsExtension */
    protected $baseExtension;

    /** @var \Oro\Bundle\SecurityBundle\SecurityFacade */
    protected $securityFacade;

    /** @var LdapDynamicFieldsExtension */
    protected $dynamicFieldsExtension;

    public function setUp()
    {
        $this->baseExtension = $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Twig\DynamicFieldsExtension')
            ->disableOriginalConstructor()
            ->getMock();

        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->dynamicFieldsExtension = new LdapDynamicFieldsExtension($this->baseExtension, $this->securityFacade);
    }

    public function testGetFieldsWhenLdapFieldNotPresent()
    {
        $base = [
            'some_field'  => [
                'some_field_property',
                'other_field_property',
            ],
            'other_field' => [
                'first_field_property',
                'second_field_property',
            ],
        ];

        $expected = [
            'some_field'  => [
                'some_field_property',
                'other_field_property',
            ],
            'other_field' => [
                'first_field_property',
                'second_field_property',
            ],
        ];

        $entity = $this->getMock('Oro\Bundle\UserBundle\Entity\User');

        $this->baseExtension->expects($this->once())
            ->method('getFields')
            ->with($this->equalTo($entity))
            ->will($this->returnValue($base));

        $result = $this->dynamicFieldsExtension->getFields($entity);

        $this->assertEquals($expected, $result);
    }

    public function testGetFieldsWhenAccessGranted()
    {
        $base = [
            'some_field'  => [
                'some_field_property',
                'other_field_property',
            ],
            'other_field' => [
                'first_field_property',
                'second_field_property',
            ],
            'ldap_distinguished_names' => [
                'properties...'
            ],
        ];

        $expected = [
            'some_field'  => [
                'some_field_property',
                'other_field_property',
            ],
            'other_field' => [
                'first_field_property',
                'second_field_property',
            ],
            'ldap_distinguished_names' => [
                'properties...'
            ],
        ];

        $entity = $this->getMock('Oro\Bundle\UserBundle\Entity\User');

        $this->baseExtension->expects($this->any())
            ->method('getFields')
            ->with($this->equalTo($entity))
            ->will($this->returnValue($base));

        $this->securityFacade->expects($this->any())
            ->method('isGranted')
            ->will($this->returnValue(true));

        $result = $this->dynamicFieldsExtension->getFields($entity);

        $this->assertEquals($expected, $result);
    }

    public function testGetFieldsWhenAccessNotGranted()
    {
        $base = [
            'some_field'  => [
                'some_field_property',
                'other_field_property',
            ],
            'other_field' => [
                'first_field_property',
                'second_field_property',
            ],
            'ldap_distinguished_names' => [
                'properties...'
            ],
        ];

        $expected = [
            'some_field'  => [
                'some_field_property',
                'other_field_property',
            ],
            'other_field' => [
                'first_field_property',
                'second_field_property',
            ],
        ];

        $entity = $this->getMock('Oro\Bundle\UserBundle\Entity\User');

        $this->baseExtension->expects($this->any())
            ->method('getFields')
            ->with($this->equalTo($entity))
            ->will($this->returnValue($base));

        $this->securityFacade->expects($this->any())
            ->method('isGranted')
            ->will($this->returnValue(false));

        $result = $this->dynamicFieldsExtension->getFields($entity);

        $this->assertEquals($expected, $result);
    }
}
