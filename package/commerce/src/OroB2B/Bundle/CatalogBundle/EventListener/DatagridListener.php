<?php

namespace OroB2B\Bundle\CatalogBundle\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\DataGridBundle\EventListener\DatasourceBindParametersListener;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;

use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use OroB2B\Bundle\CatalogBundle\Handler\RequestProductHandler;

class DatagridListener
{
    const CATEGORY_COLUMN = 'category_name';

    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var RequestProductHandler */
    protected $requestProductHandler;

    /** @var string */
    protected $dataClass;

    /**
     * @param ManagerRegistry $doctrine
     * @param RequestProductHandler $requestProductHandler
     */
    public function __construct(ManagerRegistry $doctrine, RequestProductHandler $requestProductHandler)
    {
        $this->doctrine = $doctrine;
        $this->requestProductHandler = $requestProductHandler;
    }

    /**
     * @param BuildBefore $event
     */
    public function onBuildBeforeProductsSelect(BuildBefore $event)
    {
        $this->addCategoryJoin($event->getConfig());
        $this->addCategoryRelation($event->getConfig());
    }

    /**
     * @param PreBuild $event
     */
    public function onPreBuildProducts(PreBuild $event)
    {
        $this->addFilterByCategory($event);
    }

    /**
     * @param DatagridConfiguration $config
     */
    protected function addCategoryRelation(DatagridConfiguration $config)
    {
        // columns
        $categoryColumn = ['label' => 'orob2b.catalog.category.entity_label'];
        $this->addConfigElement($config, '[columns]', $categoryColumn, self::CATEGORY_COLUMN);

        // properties
        $categoryProperty = ['type' => 'fallback', 'data_name' => 'productCategory.titles'];
        $this->addConfigElement($config, '[properties]', $categoryProperty, self::CATEGORY_COLUMN);

        // sorter
        $categorySorter = ['data_name' => self::CATEGORY_COLUMN];
        $this->addConfigElement($config, '[sorters][columns]', $categorySorter, self::CATEGORY_COLUMN);

        // filter
        $categoryFilter = [
            'type' => 'string',
            'data_name' => self::CATEGORY_COLUMN,
        ];
        $this->addConfigElement($config, '[filters][columns]', $categoryFilter, self::CATEGORY_COLUMN);
    }

    /**
     * @param DatagridConfiguration $config
     */
    protected function addCategoryJoin(DatagridConfiguration $config)
    {
        $path = '[source][query][join][left]';
        // join
        $joinCategory = [
            'join' => 'OroB2BCatalogBundle:Category',
            'alias' => 'productCategory',
            'conditionType' => 'WITH',
            'condition' => 'product MEMBER OF productCategory.products',
        ];
        $joins = $config->offsetGetByPath($path, []);
        if (in_array($joinCategory, $joins, true)) {
            return;
        }
        $this->addConfigElement($config, $path, $joinCategory);
    }

    /**
     * @param PreBuild $event
     */
    protected function addFilterByCategory(PreBuild $event)
    {
        $categoryId = $event->getParameters()->get('categoryId');
        $isIncludeSubcategories = $event->getParameters()->get('includeSubcategories');
        if (!$categoryId) {
            $categoryId = $this->requestProductHandler->getCategoryId();
            $isIncludeSubcategories = $this->requestProductHandler->getIncludeSubcategoriesChoice();
        }
        if (!$categoryId) {
            return;
        }

        $config = $event->getConfig();
        $config->offsetSetByPath('[options][urlParams][categoryId]', $categoryId);
        $config->offsetSetByPath('[options][urlParams][includeSubcategories]', $isIncludeSubcategories);

        /** @var CategoryRepository $repo */
        $repo = $this->doctrine->getRepository($this->dataClass);
        /** @var Category $category */
        $category = $repo->find($categoryId);
        if (!$category) {
            return;
        }

        $productCategoryIds = [$categoryId];
        if ($isIncludeSubcategories) {
            $productCategoryIds = array_merge($repo->getChildrenIds($category), $productCategoryIds);
        }

        $config->offsetSetByPath('[source][query][where][and]', ['productCategory.id IN (:productCategoryIds)']);

        $config->offsetSetByPath(
            DatasourceBindParametersListener::DATASOURCE_BIND_PARAMETERS_PATH,
            ['productCategoryIds']
        );
        $event->getParameters()->set('productCategoryIds', $productCategoryIds);
        $this->addCategoryJoin($event->getConfig());
    }

    /**
     * @param DatagridConfiguration $config
     * @param string $path
     * @param mixed $element
     * @param mixed $key
     */
    protected function addConfigElement(DatagridConfiguration $config, $path, $element, $key = null)
    {
        $select = $config->offsetGetByPath($path);
        if ($key) {
            $select[$key] = $element;
        } else {
            $select[] = $element;
        }
        $config->offsetSetByPath($path, $select);
    }

    /**
     * @param string $dataClass
     */
    public function setDataClass($dataClass)
    {
        $this->dataClass = $dataClass;
    }
}
