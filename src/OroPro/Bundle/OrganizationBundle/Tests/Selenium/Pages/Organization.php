<?php

namespace OroPro\Bundle\OrganizationBundle\Tests\Selenium\Pages;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPageEntity;

/**
 * Class Organization
 *
 * @package OroPro\Bundle\OrganizationBundle\Tests\Selenium\Pages
 * @method Organization openOrganizations() openOrganizations(string)
 * @method Organization openOrganization() openOrganization(string)
 * @method Organization assertTitle() assertTitle($title, $message = '')
 */
class Organization extends AbstractPageEntity
{
    /**
     * @param string $status
     * @return $this
     */
    public function setStatus($status)
    {
        $enabled = $this->test->select($this->test->byId('oro_organization_form_enabled'));
        $enabled->selectOptionByLabel($status);
        return $this;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $username = $this->test->byId('oro_organization_form_name');
        $username->clear();
        $username->value($name);
        return $this;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setDescription($name)
    {
        $description = $this->test->byId('oro_organization_form_description');
        $description->clear();
        $description->value($name);
        return $this;
    }

    /**
     * @return $this
     */
    public function edit()
    {
        $this->test->byXpath(
            "//div[@class='pull-left btn-group icons-holder']/a[@title = 'Edit Organization']"
        )->click();
        $this->waitPageToLoad();
        $this->waitForAjax();
        return $this;
    }
}
