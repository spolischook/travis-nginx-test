<?php

namespace OroB2B\Bundle\CMSBundle\Migrations\Data;

use Oro\Bundle\PlatformBundle\Yaml\Yaml;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\OrganizationBundle\Entity\Organization;

use OroB2B\Bundle\CMSBundle\Entity\Page;

abstract class AbstractLoadPageData extends AbstractFixture implements ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var array
     */
    protected $pages = [];

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
    public function load(ObjectManager $manager)
    {
        $organization = $this->getOrganization($manager);

        foreach ((array)$this->getFilePaths() as $filePath) {
            $pages = $this->loadFromFile($filePath, $organization);
            foreach ($pages as $page) {
                $manager->persist($page);
            }
        }
        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @return Organization
     */
    protected function getOrganization(ObjectManager $manager)
    {
        return $manager->getRepository('OroOrganizationBundle:Organization')->getFirst();
    }

    /**
     * @param $filePath
     * @param Organization $organization
     * @return Page[]
     */
    protected function loadFromFile($filePath, Organization $organization)
    {
        $rows = Yaml::parse(file_get_contents($filePath));
        $pages = [];
        foreach ($rows as $reference => $row) {
            $page = new Page();
            $page->setTitle($row['title']);
            $page->setContent($row['content']);
            $page->setOrganization($organization);
            $page->setCurrentSlugUrl($row['slug']);

            if (array_key_exists('parent', $row) && array_key_exists($row['parent'], $this->pages)) {
                /** @var Page $parent */
                $parent = $this->pages[$row['parent']];
                $parent->addChildPage($page);
            }

            $pages[$reference] = $page;
        }

        return $pages;
    }

    /**
     * @return string
     */
    abstract protected function getFilePaths();

    /**
     * @param string $path
     * @return array|string
     */
    protected function getFilePathsFromLocator($path)
    {
        $locator = $this->container->get('file_locator');
        return $locator->locate($path);
    }
}
