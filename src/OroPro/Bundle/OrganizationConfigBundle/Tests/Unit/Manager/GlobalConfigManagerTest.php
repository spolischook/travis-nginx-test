<?php

namespace OroPro\Bundle\OrganizationConfigBundle\Tests\Unit\Manager;

use Oro\Bundle\UserBundle\Entity\User;
use OroPro\Bundle\OrganizationConfigBundle\Manager\GlobalConfigManager;

class GlobalConfigManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \RuntimeException
     */
    public function testSaveUserConfigSignatureWithException()
    {
        $manager = new GlobalConfigManager();
        $manager->saveUserConfigSignature(new User(), 'testSignature');
    }

    public function testSaveUserConfigSignatureWithNoPreferredOrganization()
    {
        $configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $userScopeManager = $this->getMockBuilder(
            'OroPro\Bundle\OrganizationConfigBundle\Config\UserOrganizationScopeManager'
        )
            ->disableOriginalConstructor()
            ->getMock();

        $userScopeManager->expects($this->exactly(2))
            ->method('getScopedEntityName');
        $configManager->expects($this->once())
            ->method('addManager');
        $configManager->expects($this->once())
            ->method('setScopeName');
        $userScopeManager->expects($this->once())
            ->method('getPreferredOrganizationId')
            ->will($this->returnValue(0));

        $manager = new GlobalConfigManager($configManager, $userScopeManager);
        $manager->saveUserConfigSignature(new User(), 'testSignature');
    }

    public function testSaveUserConfigSignature()
    {
        $configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $userScopeManager = $this->getMockBuilder(
            'OroPro\Bundle\OrganizationConfigBundle\Config\UserOrganizationScopeManager'
        )
            ->disableOriginalConstructor()
            ->getMock();

        $userScopeManager->expects($this->exactly(2))
            ->method('getScopedEntityName');
        $configManager->expects($this->once())
            ->method('addManager');
        $configManager->expects($this->once())
            ->method('setScopeName');
        $userScopeManager->expects($this->once())
            ->method('getPreferredOrganizationId')
            ->will($this->returnValue(1));
        $configManager->expects($this->exactly(2))
            ->method('setScopeId');
        $configManager->expects($this->once())
            ->method('save');
        $manager = new GlobalConfigManager($configManager, $userScopeManager);
        $manager->saveUserConfigSignature(new User(), 'testSignature');
    }
}
