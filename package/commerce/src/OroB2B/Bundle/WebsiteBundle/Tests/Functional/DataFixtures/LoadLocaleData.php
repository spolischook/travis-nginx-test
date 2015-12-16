<?php

namespace OroB2B\Bundle\WebsiteBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\WebsiteBundle\Entity\Locale;

class LoadLocaleData extends AbstractFixture
{
    /**
     * @var array
     */
    protected $locales = [
        ['code' => 'en_US', 'parent' => null, 'title' => 'English (United States)'],
        ['code' => 'en_CA', 'parent' => 'en_US', 'title' => 'English (Canada)']
    ];

    /**
     * Load locales
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        // Create locales sample with relationship between locales
        $localesRegistry = [];
        foreach ($this->locales as $item) {
            $locale = new Locale();
            $locale
                ->setCode($item['code'])
                ->setTitle($item['title']);
            if ($item['parent']) {
                $locale->setParentLocale($localesRegistry[$item['parent']]);
            }
            $localesRegistry[$item['code']] = $locale;

            $this->setReference($item['code'], $locale);

            $manager->persist($locale);
        }

        $manager->flush();
        $manager->clear();
    }
}
