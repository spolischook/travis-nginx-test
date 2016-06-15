<?php

namespace OroPro\Bundle\OrganizationBundle\Tests\Selenium\Pages;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPageEntity;

/**
 * Class Organization
 *
 * @package OroPro\Bundle\OrganizationBundle\Tests\Selenium\Pages
 * @method Organization openOrganizations(string $bundlePath)
 * @method Organization openOrganization(string $bundlePath)
 * @method Organization assertTitle($title, $message = '')
 */
class Organization extends AbstractPageEntity
{
    /**
     * @param string $status
     * @return $this
     */
    public function setStatus($status)
    {
        $enabled = $this->test->select($this->test->byXpath("//*[@data-ftid='oro_organization_form_enabled']"));
        $enabled->selectOptionByLabel($status);
        return $this;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $username = $this->test->byXpath("//*[@data-ftid='oro_organization_form_name']");
        $username->clear();
        $username->value($name);
        return $this;
    }

    /**
     * @param string $description
     * @return $this
     */
    public function setDescription($description)
    {
        return $this->setContentToTinymceElement('oro_organization_form_description', $description);
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

    /**
     * @return $this
     */
    public function checkFirstLoginTooltip()
    {
        $this->assertElementPresent(
            "//div[@class='oropro-organization-notice-holder']//div[@class='popover-content']".
            "[contains(., 'You logged in to')]",
            'First log-in tooltip are not displayed for user'
        );

        return $this;
    }

    /**
     * @param $organization
     * @return $this
     */
    public function switchOrganization($organization)
    {
        $this->test->byXpath(
            "//div[@id='organization-switcher']/div/div[@class='dropdown header-utility-dropdown']/i"
        )->click();
        $this->waitForAjax();
        $this->test->byXPath(
            "//div[@id='organization-switcher']/div/div/ul[@class='dropdown-menu dropdown-organization-switcher']".
            "/li[contains(., '{$organization}')]/a"
        )->click();
        $this->waitPageToLoad();
        $this->waitForAjax();

        return $this;
    }

    /**
     * @param $organization
     * @return $this
     */
    public function checkCurrentOrganization($organization)
    {
        $this->assertElementPresent("//div[@class='nav top-search fix_logo']//a[@title='{$organization}']");

        return $this;
    }

    /**
     * @return $this
     */
    public function checkOrganizationNotAvailableTooltip()
    {
        $this->assertElementPresent(
            "//div[@class='oropro-organization-notice-holder']//div[@class='popover-content']".
            "[contains(., 'The latest organization used is no longer available')]",
            'Organization not available tooltip are not displayed for user'
        );

        return $this;
    }
}
