<?php

namespace Oro\Bundle\LocaleBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Intl\Intl;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;

class LoadLocalizationData extends AbstractFixture implements ContainerAwareInterface
{
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
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        /* @var $localeSettings LocaleSettings */
        $localeSettings = $this->container->get('oro_locale.settings');
        $localeCode = $localeSettings->getLocale();
        $title = Intl::getLanguageBundle()->getLanguageName($localeSettings->getLanguage(), $localeCode);

        $localization = new Localization();
        $localization->setName($title)
            ->setDefaultTitle($title)
            ->setLanguageCode($localeCode)
            ->setFormattingCode($localeCode);

        $manager->persist($localization);
        $manager->flush();

        $this->addReference('default_localization', $localization);
    }
}
