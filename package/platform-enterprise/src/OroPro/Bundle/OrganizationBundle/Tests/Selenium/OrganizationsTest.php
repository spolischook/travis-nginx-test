<?php

namespace OroPro\Bundle\OrganizationBundle\Tests\Selenium;

use Oro\Bundle\TestFrameworkBundle\Test\Selenium2TestCase;
use OroPro\Bundle\OrganizationBundle\Tests\Selenium\Pages\Organizations;

class OrganizationsTest extends Selenium2TestCase
{
     /**
     * @return string
     */
    public function testCreateOrganization()
    {
        $organizationName = 'Organization_'.mt_rand();
        $descriptionName = 'Some_description_for_'.$organizationName;

        $login = $this->login();
        /** @var Organizations $login */
        $login->openOrganizations('OroPro\Bundle\OrganizationBundle')
            ->assertTitle('All - Organizations - User Management - System')
            ->add()
            ->assertTitle('Create Organization - Organizations - User Management - System')
            ->setStatus('Active')
            ->setName($organizationName)
            ->setDescription($descriptionName)
            ->save()
            ->assertMessage('Organization saved')
            ->checkEntityFieldData('Name', $organizationName)
            ->checkEntityFieldData('Description', $descriptionName)
            ->toGrid()
            ->close()
            ->assertTitle('All - Organizations - User Management - System');

        return $organizationName;
    }

    /**
     * @depends testCreateOrganization
     * @param $organizationName
     */
    public function testUpdateOrganization($organizationName)
    {
        $newOrganizationName = 'Update_' . $organizationName;
        $descriptionName = 'Some_NEW_description_for_'.$organizationName;

        $login = $this->login();
        /** @var Organizations $login */
        $login->openOrganizations('OroPro\Bundle\OrganizationBundle')
            ->filterBy('Name', $organizationName)
            ->open(array($organizationName))
            ->edit()
            ->assertTitle("{$organizationName} - Edit - Organizations - User Management - System")
            ->setName($newOrganizationName)
            ->setDescription($descriptionName)
            ->save()
            ->assertMessage('Organization saved')
            ->checkEntityFieldData('Name', $newOrganizationName)
            ->checkEntityFieldData('Description', $descriptionName)
            ->toGrid()
            ->assertTitle('All - Organizations - User Management - System')
            ->close();
    }
}
