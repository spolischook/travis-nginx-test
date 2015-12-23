<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Functional\Form\Type;

use OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;

/**
 * @dbIsolation
 */
abstract class AbstractFrontendScopedProductSelectTypeTest extends AbstractScopedProductSelectTypeTest
{
    public function setUp()
    {
        $this->setDatagridIndexPath('orob2b_frontend_datagrid_index');
        $this->setSearchAutocompletePath('orob2b_frontend_autocomplete_search');

        parent::setUp();
    }

    /**
     * @return array
     */
    public function restrictionDataProvider()
    {
        return [
            [
                ['availableInventoryStatuses' => ['in_stock', 'out_of_stock']],
                'expectedProducts' => [
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_2,
                    LoadProductData::PRODUCT_3,
                    LoadProductData::PRODUCT_6,
                    LoadProductData::PRODUCT_7,
                    LoadProductData::PRODUCT_8,
                ],
            ],
            [
                ['availableInventoryStatuses' => ['in_stock']],
                'expectedProducts' => [
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_2,
                    LoadProductData::PRODUCT_6,
                    LoadProductData::PRODUCT_7,
                    LoadProductData::PRODUCT_8,
                ],
            ],
            [
                ['availableInventoryStatuses' => ['out_of_stock']],
                'expectedProducts' => [
                    LoadProductData::PRODUCT_3,
                ],
            ],
            [
                ['availableInventoryStatuses' => ['discontinued']],
                'expectedProducts' => [],
            ],
            [
                ['availableInventoryStatuses' => ['in_stock', 'discontinued']],
                'expectedProducts' => [
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_2,
                    LoadProductData::PRODUCT_6,
                    LoadProductData::PRODUCT_7,
                    LoadProductData::PRODUCT_8,
                ],
            ],
        ];
    }
}
