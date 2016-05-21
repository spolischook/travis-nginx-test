<?php

namespace OroCRMPro\Bundle\LDAPBundle\Tests\Selenium;

use Oro\Bundle\CronBundle\Tests\Selenium\Pages\Jobs;
use Oro\Bundle\IntegrationBundle\Tests\Selenium\Pages\Integrations;
use Oro\Bundle\TestFrameworkBundle\Test\Selenium2TestCase;
use Oro\Bundle\UserBundle\Tests\Selenium\Pages\Users;
use OroCRMPro\Bundle\LDAPBundle\Tests\Selenium\Pages\LdapIntegration;

class LdapIntegrationTest extends Selenium2TestCase
{
    /**
     * Test create new LDAP integration with mapping of 2 LDAP roles
     * Test start daemon
     * Test check that integration job finished successfully
     * @return array
     */
    public function testCreateLdapIntegration()
    {
        $ldapIntegration = array(
            'name' => 'testLDAP'.mt_rand(10, 99),
            'hostname' => '88.198.43.162',
            'port' => '399',
            'encryption' => 'none',
            'basedistinguishedname' => 'dc=orocrm,dc=com',
            'usernamelogin' => 'cn=admin,dc=orocrm,dc=com',
            'password' => '123456',
            'defaultBU' => 'Main',
            'userfilter' => '(&(objectClass=inetOrgPerson)(!(ou:dn:=testintegration)))',
            'username' => 'sn',
            'primaryemail' => 'cn',
            'firstname' => 'givenName',
            'lastname' => 'displayName',
            'rolefilter' => 'objectClass=simpleSecurityObject',
            'roleidattribute' => 'cn',
            'roleuseridattribute' => 'roleOccupant',
            'exportuserobjectclass' => 'inetOrgPerson',
            'exportuserbasedistinguishedname' => 'ou=testintegration,dc=orocrm,dc=com'
        );

        $login = $this->login();
        /** @var Jobs $login */
        $login->openJobs('Oro\Bundle\CronBundle')
            ->runDaemon();
        /** @var Integrations $login */
        $login->openIntegrations('Oro\Bundle\IntegrationBundle')
            ->add()
            ->setType('LDAP');
        /** @var LdapIntegration $login */
        $login->openLdapIntegration('OroCRMPro\Bundle\LDAPBundle')
            ->setAllRequiredFields($ldapIntegration)
            ->checkConnection()
            ->addRoleMappings('admin', 'Administrator')
            ->addRoleMappings('manager', 'Marketing Manager', 2)
            ->save()
            ->assertMessage('Integration saved');
        /** @var Integrations $login */
        $integrationId = $login->openIntegrations('Oro\Bundle\IntegrationBundle')
            ->filterBy('Name', $ldapIntegration['name'])
            ->edit([$ldapIntegration['name']])
            ->getId('update');
        /** @var Jobs $login */
        $command = "oro:cron:integration:sync --integration-id={$integrationId}";
        $login->openJobs('Oro\Bundle\CronBundle')
            ->filterBy('Command', $command)
            ->open($command)
            ->checkJobState('Finished')
            ->assertOutputMessages(array('read [5]', 'added [5]', 'Completed'));

        return $ldapIntegration;
    }

    /**
     * Test checks login with LDAP password
     * Test set new Oro password
     * Test checks login with Oro password
     * @depends testCreateLdapIntegration
     */
    public function testLdapUserLoginWithBothPasswords()
    {
        $userName = 'ldap1';
        $ldapPassword = '123456';
        $oroPassword = '123123q';
        $login = $this->login($userName, $ldapPassword)
            ->assertTitle('Dashboard');
        /** @var Users $login */
        $login = $login->openUsers('Oro\Bundle\UserBundle')
            ->filterBy('Username', $userName)
            ->open(array($userName))
            ->checkEntityFieldData('Roles', 'Administrator')
            ->changePassword($oroPassword)
            ->logout();
        /** @var $login */
        $login->setUsername($userName)
            ->setPassword($oroPassword)
            ->submit()
            ->assertTrue($login->loggedIn(), 'User was not logged in as was expected');
    }


    /**
     * Test checks mapping of second LDAP role
     * @depends testCreateLdapIntegration
     */
    public function testSecondMappingRole()
    {
        $userName = 'ldap5';
        $login = $this->login();
        /** @var Users $login */
        $login->openUsers('Oro\Bundle\UserBundle')
            ->filterBy('Username', $userName)
            ->open(array($userName))
            ->checkEntityFieldData('Roles', 'Marketing Manager');
    }

    /**
     * Test checks login with LDAP password when integration deactivated
     * Test checks that login with oro passwords still works
     * @param $ldapIntegration
     * @depends testCreateLdapIntegration
     * @depends testLdapUserLoginWithBothPasswords
     */
    public function testLoginWithDeactivatedIntegration($ldapIntegration)
    {
        $userName = 'ldap1';
        $ldapPassword = '123456';
        $oroPassword = '123123q';
        $login = $this->login();
        /** @var Integrations $login */
        $login->openIntegrations('Oro\Bundle\IntegrationBundle')
            ->filterBy('Name', $ldapIntegration['name'])
            ->edit([$ldapIntegration['name']])
            ->deactivate()
            ->logout();
        /** @var $login */
        $this->login($userName, $ldapPassword);
        $this->assertFalse($login->loggedIn(), 'User was logged in but it was not expected');
        $message = $login->getErrorLoginMessage();
        static::assertEquals('Invalid user name or password.', $message);
        /** @var $login */
        $this->login($userName, $oroPassword)
            ->assertTrue($login->loggedIn(), 'User was not logged in as was expected');

        return $ldapIntegration;
    }

    /**
     * Test checks that login with LDAP password possible after integration reactivation
     * @param $ldapIntegration
     * @depends testLoginWithDeactivatedIntegration
     */
    public function testLoginAfterLdapReActivation($ldapIntegration)
    {
        $userName = 'ldap1';
        $ldapPassword = '123456';
        $login = $this->login();
        /** @var Integrations $login */
        $login->openIntegrations('Oro\Bundle\IntegrationBundle')
            ->filterBy('Name', $ldapIntegration['name'])
            ->edit([$ldapIntegration['name']])
            ->activate()
            ->logout();
        /** @var $login */
        $this->login($userName, $ldapPassword)
            ->assertTrue($login->loggedIn(), 'User was not logged in as was expected');
    }

    /**
     * Test checks deletion of integration
     * @depends testCreateLdapIntegration
     * @param $ldapIntegration
     */
    public function testDeleteLdapIntegration($ldapIntegration)
    {
        $login = $this->login();
        /** @var Integrations $login */
        $login->openIntegrations('Oro\Bundle\IntegrationBundle')
            ->filterBy('Name', $ldapIntegration['name'])
            ->action($ldapIntegration['name'], 'Delete', 1)
            ->assertMessage('Item deleted');
        $login->openIntegrations('Oro\Bundle\IntegrationBundle');
        if ($login->getRowsCount() > 0) {
            $login->filterBy('Name', $ldapIntegration['name'])
                ->assertNoDataMessage('No channel was found to match your search');
        }
    }

    public function testCloseWidgetWindow()
    {
        $login = $this->login();
        $login->closeWidgetWindow();
    }
}
