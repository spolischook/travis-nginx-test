<?php

namespace OroB2B\Bundle\WebsiteBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Intl\Intl;

use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;

use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use OroB2B\Bundle\WebsiteBundle\Entity\Locale;

class LoadWebsiteDemoData extends AbstractFixture implements ContainerAwareInterface
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
            'locales' => ['en_US', 'es_MX'],
            'sharing' => ['Mexico', 'Canada'],
        ],
        [
            'name' => 'Australia',
            'url' => 'http://www.australia.com',
            'locales' => ['en_AU'],
            'sharing' => null,
        ],
        [
            'name' => 'Mexico',
            'url' => 'http://www.mexico.com',
            'locales' => ['es_MX'],
            'sharing' => [self::US, 'Canada'],
        ],
        [
            'name' => 'Canada',
            'url' => 'http://www.canada.com',
            'locales' => ['fr_CA', 'en_CA'],
            'sharing' => [self::US, 'Mexico'],
        ],
        [
            'name' => 'Europe',
            'url' => 'http://www.europe.com',
            'locales' => ['en_GB', 'fr_FR', 'de_DE'],
            'sharing' => null,
        ],
    ];

    /**
     * @var array
     */
    protected $locales = [
        ['code' => 'en_US', 'parent' => null],
        ['code' => 'en_CA', 'parent' => 'en_US'],
        ['code' => 'en_GB', 'parent' => 'en_US'],
        ['code' => 'en_AU', 'parent' => 'en_US'],
        ['code' => 'es_MX', 'parent' => 'en_US'],
        ['code' => 'fr_CA', 'parent' => 'en_CA'],
        ['code' => 'fr_FR', 'parent' => 'fr_CA'],
        ['code' => 'de_DE', 'parent' => 'en_US'],
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
    public function load(ObjectManager $manager)
    {
        /** @var EntityManager $manager */
        $user = $this->getFirstUser($manager);
        $businessUnit = $user->getOwner();
        $organization = $user->getOrganization();

        // Create locales sample with relationship between locales
        $localesRegistry = [];
        foreach ($this->locales as $item) {
            $code = $item['code'];
            $title = $this->getLocaleNameByCode($item['code']);

            $locale = new Locale();
            $locale
                ->setCode($code)
                ->setTitle($title);

            if ($item['parent']) {
                $parentCode = $item['parent'];

                $locale->setParentLocale($localesRegistry[$parentCode]);
            }
            $localesRegistry[$code] = $locale;

            $manager->persist($locale);
        }

        $manager->flush();

        // Create websites
        foreach ($this->webSites as $webSite) {
            $site = new Website();

            $siteLocales = [];
            foreach ($webSite['locales'] as $localeCode) {
                $siteLocales[] = $this->getLocaleByCode($manager, $localeCode);
            }

            $site->setOwner($businessUnit)
                ->setOrganization($organization)
                ->setName($webSite['name'])
                ->setUrl($webSite['url'])
                ->resetLocales($siteLocales);

            $manager->persist($site);
        }

        $manager->flush();

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
     * @return string
     */
    protected function getLocaleNameByCode($code)
    {
        return Intl::getLocaleBundle()->getLocaleName($code, $this->container->get('oro_locale.settings')->getLocale());
    }

    /**
     * @param EntityManager $manager
     * @param string $code
     * @return Locale
     */
    protected function getLocaleByCode(EntityManager $manager, $code)
    {
        $locale = $manager->getRepository('OroB2BWebsiteBundle:Locale')
            ->findOneBy(['code' => $code]);

        if (!$locale) {
            throw new \LogicException(sprintf('There is no locale with code "%s" .', $code));
        }

        return $locale;
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
