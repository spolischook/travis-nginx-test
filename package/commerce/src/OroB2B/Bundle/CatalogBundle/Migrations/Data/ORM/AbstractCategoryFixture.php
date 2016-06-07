<?php

namespace OroB2B\Bundle\CatalogBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use OroB2B\Bundle\FallbackBundle\Entity\LocalizedFallbackValue;

abstract class AbstractCategoryFixture extends AbstractFixture
{
    /**
     * Key is a category title, value is an array of categories
     *
     * @var array
     */
    protected $categories = [];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var CategoryRepository $categoryRepository */
        $categoryRepository = $manager->getRepository('OroB2BCatalogBundle:Category');
        $root = $categoryRepository->getMasterCatalogRoot();

        $this->addCategories($root, $this->categories, $manager);

        $manager->flush();
    }

    /**
     * @param Category $root
     * @param array $categories
     */
    protected function addCategories(Category $root, array $categories, ObjectManager $manager)
    {
        if (!$categories) {
            return;
        }

        foreach ($categories as $title => $nestedCategories) {
            $categoryTitle = new LocalizedFallbackValue();
            $categoryTitle->setString($title);

            $category = new Category();
            $category->addTitle($categoryTitle);

            $category->addMetaTitles($this->getSeoMetaFieldData($manager, 'defaultMetaTitle'));
            $category->addMetaDescriptions($this->getSeoMetaFieldData($manager, 'defaultMetaDescription'));
            $category->addMetaKeywords($this->getSeoMetaFieldData($manager, 'defaultMetaKeywords'));
            $manager->persist($category);

            $this->addReference($title, $category);

            $root->addChildCategory($category);

            $this->addCategories($category, $nestedCategories, $manager);
        }
    }

    private function getSeoMetaFieldData(ObjectManager $manager, $seoFieldValue)
    {
        $seoField = new LocalizedFallbackValue();
        $seoField->setString($seoFieldValue);
        $manager->persist($seoField);

        return $seoField;
    }
}
