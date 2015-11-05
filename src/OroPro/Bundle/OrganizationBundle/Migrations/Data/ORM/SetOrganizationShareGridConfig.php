<?php

namespace OroPro\Bundle\OrganizationBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

class SetOrganizationShareGridConfig extends AbstractFixture implements ContainerAwareInterface
{
    const ENTITY_CLASS = 'Oro\Bundle\OrganizationBundle\Entity\Organization';

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
        /** @var ConfigManager $configManager */
        $configManager = $this->container->get('oro_entity_config.config_manager');

        if (!$configManager->hasConfig(self::ENTITY_CLASS)) {
            return;
        }

        $entityConfig = $configManager->getProvider('security')->getConfig(self::ENTITY_CLASS);
        $entityConfig->set('share_grid', 'share-with-organizations-datagrid');
        $configManager->persist($entityConfig);
        $configManager->flush();
    }
}
