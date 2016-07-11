<?php

namespace OroB2B\Bundle\WebsiteBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\OrganizationBundle\Migrations\Data\ORM\LoadOrganizationAndBusinessUnitData;

use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class LoadWebsiteData extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    const DEFAULT_WEBSITE_NAME = 'Default';

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
    public function getDependencies()
    {
        return [
            'Oro\Bundle\OrganizationBundle\Migrations\Data\ORM\LoadOrganizationAndBusinessUnitData',
            'Oro\Bundle\LocaleBundle\Migrations\Data\ORM\LoadLocalizationData'
        ];
    }

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $organization = $this->getReference('default_organization');

        $businessUnit = $manager
            ->getRepository('OroOrganizationBundle:BusinessUnit')
            ->findOneBy(['name' => LoadOrganizationAndBusinessUnitData::MAIN_BUSINESS_UNIT]);

        $url = $this->container->get('oro_config.manager')->get('oro_ui.application_url');

        //todo fix localization reference retrieval if changed BB-3367
        ///** @var Localization $localization */
        //$localization = $this->getReference('default_localization');
        

        $website = new Website();
        $website
            ->setName(self::DEFAULT_WEBSITE_NAME)
            ->setOrganization($organization)
            ->setOwner($businessUnit)
            ->setUrl($url);
            //->addLocalization($localization); //todo add localization direct to configuration impl in BB-3367

        $manager->persist($website);
        /** @var EntityManager $manager */
        $manager->flush($website);
    }
}
