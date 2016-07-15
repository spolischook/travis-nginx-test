<?php

namespace Oro\Bundle\WebsiteProBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;

use OroB2B\Bundle\WebsiteBundle\Entity\Website;


class LoadWebsiteDemoData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    use UserUtilityTrait;

    const US = 'US';

    /**
     * @var array
     */
    protected $webSites = [
        [
            'name' => self::US,
            'url' => 'http://www.us.com',
            'localizations' => ['en_US', 'es_MX'],
            'sharing' => ['Mexico', 'Canada'],
        ],
        [
            'name' => 'Australia',
            'url' => 'http://www.australia.com',
            'localizations' => ['en_AU'],
            'sharing' => null,
        ],
        [
            'name' => 'Mexico',
            'url' => 'http://www.mexico.com',
            'localizations' => ['es_MX'],
            'sharing' => [self::US, 'Canada'],
        ],
        [
            'name' => 'Canada',
            'url' => 'http://www.canada.com',
            'localizations' => ['fr_CA', 'en_CA'],
            'sharing' => [self::US, 'Mexico'],
        ],
        [
            'name' => 'Europe',
            'url' => 'http://www.europe.com',
            'localizations' => ['en_GB', 'fr', 'de'],
            'sharing' => null,
        ],
    ];

    /**
     * @var ContainerInterface
     */
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
    public function getDependencies()
    {
        return [
            'Oro\Bundle\LocaleBundle\Migrations\Data\Demo\ORM\LoadLocalizationDemoData'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var EntityManager $manager */
        $user = $this->getFirstUser($manager);
        $businessUnit = $user->getOwner();
        $organization = $user->getOrganization();

        $manager->flush();

        // Create websites
        foreach ($this->webSites as $webSite) {
            $manager->persist((new Website())
                ->setName($webSite['name'])
                ->setUrl($webSite['url'])
                ->setOwner($businessUnit)
                ->setOrganization($organization));
        }

        $manager->flush();

        // Create website localizations relationships
        /** @var ConfigManager $configManager */
        $configManager = $this->container->get('oro_config.website');
        foreach ($this->webSites as $webSite) {
            $localizationIds = array_map(function ($code) {
                return $this->getLocalization('localization_' . $code)->getId();
            }, $webSite['localizations']);

            $site = $this->getWebsiteByName($manager, $webSite['name']);

            $configManager->setScopeId($site->getId());

            $configManager->set(
                Configuration::getConfigKeyByName(Configuration::ENABLED_LOCALIZATIONS),
                $localizationIds
            );

            $configManager->set(
                Configuration::getConfigKeyByName(Configuration::DEFAULT_LOCALIZATION),
                reset($localizationIds)
            );

            $configManager->flush();
        }

        // Create website sharing relationship
        foreach ($this->webSites as $webSite) {
            $site = $this->getWebsiteByName($manager, $webSite['name']);
            if ($webSite['sharing']) {
                foreach ($webSite['sharing'] as $siteName) {
                    $relatedWebsite = $this->getWebsiteByName($manager, $siteName);
                    $site->addRelatedWebsite($relatedWebsite);
                }
            }
        }

        $manager->flush();
        $manager->clear();
    }

    /**
     * @param string $code
     * @return Localization
     */
    protected function getLocalization($code)
    {
        return $this->getReference($code);
    }

    /**
     * @param EntityManager $manager
     * @param string $name
     * @return Website
     */
    protected function getWebsiteByName(EntityManager $manager, $name)
    {
        $website = $manager->getRepository('OroB2BWebsiteBundle:Website')->findOneBy(['name' => $name]);

        if (!$website) {
            throw new \LogicException(sprintf('There is no website with name "%s" .', $name));
        }

        return $website;
    }
}
