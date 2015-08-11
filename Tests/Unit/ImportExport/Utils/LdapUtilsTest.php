<?php

namespace OroCRMPro\Bundle\LDAPBundle\Tests\Unit\ImportExport\Utils;

use OroCRMPro\Bundle\LDAPBundle\ImportExport\Utils\LdapUtils;

class LdapUtilsTest extends \PHPUnit_Framework_TestCase
{
    public function createDnDataProvider()
    {
        return [
            ['cn=username,ou=unit,dc=localhost', 'cn', 'username', 'ou=unit,dc=localhost'],
            ['cn=username', 'cn', 'username', null],
            ['sn=username', 'sn', 'username', null],
        ];
    }

    /**
     * @dataProvider createDnDataProvider
     */
    public function testCreateDn($expected, $attr, $value, $baseDn)
    {
        $this->assertEquals($expected, LdapUtils::createDn($attr, $value, $baseDn));
    }

    public function getSearchFilterDataProvider()
    {
        return [
            ['(&(objectClass=inetOrgPerson)(cn=username))', 'cn', 'username', 'objectClass=inetOrgPerson'],
            [
                '(&(&(objectClass=inetOrgPerson)(ou:dn:=something))(cn=username))',
                'cn',
                'username',
                '(&(objectClass=inetOrgPerson)(ou:dn:=something))'
            ],
        ];
    }

    /**
     * @dataProvider getSearchFilterDataProvider
     */
    public function testGetSearchFilter($expected, $attr, $value, $filter)
    {
        $this->assertEquals($expected, LdapUtils::getSearchFilter($attr, $value, $filter));
    }
}
