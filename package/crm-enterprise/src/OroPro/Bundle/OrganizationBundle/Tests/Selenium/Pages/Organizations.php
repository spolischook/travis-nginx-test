<?php

namespace OroPro\Bundle\OrganizationBundle\Tests\Selenium\Pages;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPageFilteredGrid;

/**
 * Class Organizations
 *
 * @package OroPro\Bundle\OrganizationBundle\Tests\Selenium\Pages
 * @method Organizations openOrganizations(string $bundlePath)
 * @method Organization open(array $filter)
 * @method Organization add()
 * {@inheritdoc}
 */
class Organizations extends AbstractPageFilteredGrid
{
    const NEW_ENTITY_BUTTON = "//a[@title='Create Organization']";
    const URL = 'organization';

    public function entityNew()
    {
        return new Organization($this->test);
    }

    public function entityView()
    {
        return new Organization($this->test);
    }
}
