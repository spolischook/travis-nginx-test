<?php

namespace OroB2B\Bundle\CatalogBundle\Migrations\Data\Demo\ORM;

use OroB2B\Bundle\CatalogBundle\Migrations\Data\ORM\AbstractCategoryFixture;

class LoadCategoryDemoData extends AbstractCategoryFixture
{
    /**
     * @var array
     */
    protected $categories = [
        'Lighting Products' => [],
        'Medical Apparel' => [],
        'Office Furniture' => [],
        'Retail Supplies' => [],
        //'Uniforms' => [
        //    'Healthcare' => [
        //        'Medical Scrubs' => [],
        //        'Lab Coats' => [],
        //        'Patient Gowns' => [],
        //        'Counter Coats' => [],
        //    ],
        //],
        //'Identification' => [
        //    'Medical Identification Tags' => [],
        //],
        //'Patient Transport Equipment' => [
        //    'Hospital Wheelchairs' => [],
        //],
    ];
}
