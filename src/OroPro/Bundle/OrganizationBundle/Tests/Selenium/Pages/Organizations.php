<?php

namespace OroPro\Bundle\OrganizationBundle\Tests\Selenium\Pages;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPageFilteredGrid;

/**
 * Class Organizations
 *
 * @package OroPro\Bundle\OrganizationBundle\Tests\Selenium\Pages
 * @method Organizations openOrganizations() openOrganizations(string)
 * {@inheritdoc}
 */
class Organizations extends AbstractPageFilteredGrid
{
    const URL = 'organization';

    public function __construct($testCase, $redirect = true)
    {
        $this->redirectUrl = self::URL;
        parent::__construct($testCase, $redirect);
    }

    /**
     * @return Organization
     */
    public function add()
    {
        $this->test->byXPath("//a[@title='Create Organization']")->click();
        $this->waitPageToLoad();
        $this->waitForAjax();

        return new Organization($this->test);
    }

    /**
     * @param array $entityData
     * @return Organization
     */
    public function open($entityData = array())
    {
        $user = $this->getEntity($entityData);
        $user->click();
        sleep(1);
        $this->waitPageToLoad();
        $this->waitForAjax();

        return new Organization($this->test);
    }
}
