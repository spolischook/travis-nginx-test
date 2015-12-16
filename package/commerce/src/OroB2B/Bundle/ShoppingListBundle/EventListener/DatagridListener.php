<?php

namespace OroB2B\Bundle\ShoppingListBundle\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;

class DatagridListener
{
    /**
     * @param BuildBefore $event
     */
    public function onBuildBeforeFrontendProducts(BuildBefore $event)
    {
        $this->addAddToShoppingListAction($event->getConfig());
    }

    /**
     * @param DatagridConfiguration $config
     */
    protected function addAddToShoppingListAction(DatagridConfiguration $config)
    {
        // properties
        $addToShoppingListLink = [
            'type' => 'url',
            'route' => 'orob2b_shopping_list_line_item_frontend_add_widget',
            'params' => ['productId' => 'id'],
        ];
        $this->addConfigElement($config, '[properties]', $addToShoppingListLink, 'add_to_shopping_list_link');

        // actions
        $addToShoppingList = [
            'type' => 'dialog',
            'label' => 'orob2b.shoppinglist.product.add_to_shopping_list.label',
            'link' => 'add_to_shopping_list_link',
            'icon' => 'shopping-cart',
            'acl_resource' => 'orob2b_shopping_list_line_item_frontend_add',
            'widgetOptions' => [
                'options' => [
                    'dialogOptions' => [
                        'title' => 'orob2b.shoppinglist.datagrid.add_to_shopping_list'
                    ],
                    'alias' => 'shopping_list_add_product_grid'
                ]
            ]
        ];
        $this->addConfigElement($config, '[actions]', $addToShoppingList, 'add_to_shopping_list');

        //mass actions
        $addToShoppingListMassAction = [
            'type' => 'addproducts',
            'entity_name' => '%orob2b_product.product.class%',
            'data_identifier' => 'product.id',
            'label' => 'orob2b.shoppinglist.product.add_to_shopping_list.label',
            'acl_resource' => 'orob2b_shopping_list_line_item_frontend_add',
        ];
        $this->addConfigElement($config, '[mass_actions]', $addToShoppingListMassAction, 'addproducts');
    }

    /**
     * @param DatagridConfiguration $config
     * @param string                $path
     * @param mixed                 $element
     * @param mixed                 $key
     */
    protected function addConfigElement(DatagridConfiguration $config, $path, $element, $key)
    {
        $select = $config->offsetGetByPath($path);
        $select[$key] = $element;
        $config->offsetSetByPath($path, $select);
    }
}
