<?php

namespace OroPro\Bundle\OrganizationBundle\Tests\Selenium;

use Oro\Bundle\TestFrameworkBundle\Test\Selenium2TestCase;
use Oro\Bundle\UserBundle\Tests\Selenium\Pages\Users;
use OroPro\Bundle\OrganizationBundle\Tests\Selenium\Pages\Organization;
use OroPro\Bundle\OrganizationBundle\Tests\Selenium\Pages\Organizations;

class OrganizationsSwitchingTest extends Selenium2TestCase
{
    /**
     * @return array
     */
    public function testCreateOrganization()
    {
        $fields = array (
            'role' => 'Administrator',
            'username' => 'user_'.mt_rand(99, 999),
            'organization' => 'organization'.mt_rand(99, 999)
        );

        $description = 'Some_description_for_'.$fields['organization'];

        $login = $this->login();
        /** @var Organizations $login */
        $login->openOrganizations('OroPro\Bundle\OrganizationBundle')
            ->assertTitle('All - Organizations - User Management - System')
            ->add()
            ->assertTitle('Create Organization - Organizations - User Management - System')
            ->setStatus('Active')
            ->setName($fields['organization'])
            ->setDescription($description)
            ->save()
            ->assertMessage('Organization saved')
            ->checkEntityFieldData('Name', $fields['organization'])
            ->checkEntityFieldData('Description', $description)
            ->toGrid()
            ->close()
            ->assertTitle('All - Organizations - User Management - System');

        return $fields;
    }

    /**
     * @depends testCreateOrganization
     * @param $fields
     * @return array
     */
    public function testOrganizationFirstLoginCheck($fields)
    {
        $login = $this->login();
        /* @var Users $login */
        $login->openUsers('Oro\Bundle\UserBundle')
            ->add()
            ->assertTitle('Create User - Users - User Management - System')
            ->setUsername($fields['username'])
            ->enable()
            //->setOwner('Main') //not necessary, because Owner will be preselected and 'Main' may NOT be present.
            ->setFirstpassword('123123q')
            ->setSecondpassword('123123q')
            ->setFirstName('First_'.$fields['username'])
            ->setLastName('Last_'.$fields['username'])
            ->setEmail($fields['username'].'@mail.com')
            ->setRoles(array($fields['role']))
            ->setOrganizationOnForm(array ($fields['organization']))
            ->setBusinessUnit(array ('OroCRM'))
            ->uncheckInviteUser()
            ->save()
            ->assertMessage('User saved')
            ->close()
            ->logout()
            ->setUsername($fields['username'])
            ->setPassword('123123q')
            ->submit();
        /** @var Organization $login */
        $login->openOrganization('OroPro\Bundle\OrganizationBundle')
            ->checkFirstLoginTooltip();

        return $fields;
    }

    /**
     * @depends testCreateOrganization
     * @param $fields
     * @return array
     */
    public function testOrganizationSwitch($fields)
    {
        $login = $this->login($fields['username'], '123123q');
        /** @var Organization $login */
        $login->openOrganization('OroPro\Bundle\OrganizationBundle')
            ->switchOrganization($fields['organization'])
            ->checkCurrentOrganization($fields['organization']);

        return $fields;
    }

    /**
     * @depends testOrganizationSwitch
     * @param $fields
     */
    public function testLastOrganizationLogin($fields)
    {
        $login = $this->login($fields['username'], '123123q');
        /** @var Organization $login */
        $login->openOrganization('OroPro\Bundle\OrganizationBundle')
            ->checkCurrentOrganization($fields['organization']);
    }

    /**
     * @depends testCreateOrganization
     * @param $fields
     */
    public function testCheckLoginToDisabledOrg($fields)
    {
        $login = $this->login();
        /** @var Organizations $login */
        $login->openOrganizations('OroPro\Bundle\OrganizationBundle')
            ->filterBy('Name', $fields['organization'])
            ->open(array($fields['organization']))
            ->edit()
            ->setStatus('Inactive')
            ->save()
            ->close()
            ->logout()
            ->setUsername($fields['username'])
            ->setPassword('123123q')
            ->submit();
        /** @var Organization $login */
        $login->openOrganization('OroPro\Bundle\OrganizationBundle')
            ->checkOrganizationNotAvailableTooltip();
    }
}
