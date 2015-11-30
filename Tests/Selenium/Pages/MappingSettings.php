<?php

namespace OroCRMPro\Bundle\LDAPBundle\Tests\Selenium\Pages;

use Oro\Bundle\IntegrationBundle\Tests\Selenium\Pages\Integration;

/**
 * Class MappingSettings
 * @package OroCRM\Bundle\TestFrameworkBundle\Tests\Selenium\Pages
 * {@inheritdoc}
 */
class MappingSettings extends Integration
{
    /**
     * @param string $value
     * @return $this
     */
    public function setUserFilter($value)
    {
        $field = $this->test->byXpath("//*[@data-ftid='oro_integration_channel_form_mappingSettings_userFilter']");
        $field->clear();
        $field->value($value);

        return $this;
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setUserName($value)
    {
        $field = $this->test->byXpath(
            "//*[@data-ftid='oro_integration_channel_form_mappingSettings_userMapping_username']"
        );
        $field->clear();
        $field->value($value);

        return $this;
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setPrimaryEmail($value)
    {
        $field = $this->test->byXpath(
            "//*[@data-ftid='oro_integration_channel_form_mappingSettings_userMapping_email']"
        );
        $field->clear();
        $field->value($value);

        return $this;
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setFirstName($value)
    {
        $field = $this->test->byXpath(
            "//*[@data-ftid='oro_integration_channel_form_mappingSettings_userMapping_firstName']"
        );
        $field->clear();
        $field->value($value);

        return $this;
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setLastName($value)
    {
        $field = $this->test->byXpath(
            "//*[@data-ftid='oro_integration_channel_form_mappingSettings_userMapping_lastName']"
        );
        $field->clear();
        $field->value($value);

        return $this;
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setRoleFilter($value)
    {
        $field = $this->test->byXpath(
            "//*[@data-ftid='oro_integration_channel_form_mappingSettings_roleFilter']"
        );
        $field->clear();
        $field->value($value);

        return $this;
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setRoleIdAttribute($value)
    {
        $field = $this->test->byXpath(
            "//*[@data-ftid='oro_integration_channel_form_mappingSettings_roleIdAttribute']"
        );
        $field->clear();
        $field->value($value);

        return $this;
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setRoleUserIdAttribute($value)
    {
        $field = $this->test->byXpath(
            "//*[@data-ftid='oro_integration_channel_form_mappingSettings_roleUserIdAttribute']"
        );
        $field->clear();
        $field->value($value);

        return $this;
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setExportUserObjectClass($value)
    {
        $field = $this->test->byXpath(
            "//*[@data-ftid='oro_integration_channel_form_mappingSettings_exportUserObjectClass']"
        );
        $field->clear();
        $field->value($value);

        return $this;
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setExportUserBaseDistinguishedName($value)
    {
        $field = $this->test->byXpath(
            "//*[@data-ftid='oro_integration_channel_form_mappingSettings_exportUserBaseDn']"
        );
        $field->clear();
        $field->value($value);

        return $this;
    }
}
