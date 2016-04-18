<?php

namespace OroB2B\Bundle\ProductBundle\Storage;

class ProductDataStorage extends AbstractSessionDataStorage
{
    const PRODUCT_DATA_KEY = 'orob2b_product_data';

    const ENTITY_DATA_KEY = 'entity_data';
    const ENTITY_ITEMS_DATA_KEY = 'entity_items_data';

    const ADDITIONAL_DATA_KEY = 'additional_data';

    const PRODUCT_SKU_KEY = 'productSku';
    const PRODUCT_QUANTITY_KEY = 'productQuantity';

    /** {@inheritdoc} */
    protected function getKey()
    {
        return self::PRODUCT_DATA_KEY;
    }
}
