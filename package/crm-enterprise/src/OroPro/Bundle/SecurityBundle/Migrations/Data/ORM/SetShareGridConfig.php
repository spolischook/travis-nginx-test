<?php

namespace OroPro\Bundle\SecurityBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

class SetShareGridConfig extends AbstractFixture implements ContainerAwareInterface
{
    protected static $entitiesSecurityConfig = [
        ['Oro\Bundle\UserBundle\Entity\User', 'share_grid', 'share-with-users-datagrid'],
        ['Oro\Bundle\OrganizationBundle\Entity\BusinessUnit', 'share_grid', 'share-with-business-units-datagrid'],
        ['Oro\Bundle\OrganizationBundle\Entity\Organization', 'share_grid', 'share-with-organizations-datagrid'],
        ['Oro\Bundle\TrackingBundle\Entity\TrackingWebsite', 'share_scopes', ['user']],
    ];

    /** @var ContainerInterface */
    protected $container;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $configManager = $this->container->get('oro_entity_config.config_manager');

        foreach (self::$entitiesSecurityConfig as $securityConfig) {
            $this->setEntityConfig($configManager, $securityConfig[0], $securityConfig[1], $securityConfig[2]);
        }

        $configManager->flush();
    }

    /**
     * @param ConfigManager $configManager
     * @param string $entityClass
     * @param string $code
     * @param mixed $value
     */
    protected function setEntityConfig(ConfigManager $configManager, $entityClass, $code, $value)
    {
        if (!$configManager->hasConfig($entityClass)) {
            return;
        }

        $entityConfig = $configManager->getProvider('security')->getConfig($entityClass);
        $entityConfig->set($code, $value);
        $configManager->persist($entityConfig);
    }
}
