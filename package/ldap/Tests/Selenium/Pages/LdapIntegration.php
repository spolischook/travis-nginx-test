<?php

namespace OroCRMPro\Bundle\LDAPBundle\Tests\Selenium\Pages;

/**
 * Class LdapIntegration
 * @package OroCRM\Bundle\TestFrameworkBundle\Tests\Selenium\Pages
 * @method LdapIntegration openLdapIntegration(string $bundlePath)
 * {@inheritdoc}
 */
class LdapIntegration extends MappingSettings
{
    /**
     * @param string $value
     * @return $this
     */
    public function setHostName($value)
    {
        $field = $this->test->byXpath("//*[@data-ftid='oro_integration_channel_form_transport_host']");
        $field->clear();
        $field->value($value);

        return $this;
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setPort($value)
    {
        $field = $this->test->byXpath("//*[@data-ftid='oro_integration_channel_form_transport_port']");
        $field->clear();
        $field->value($value);

        return $this;
    }

    /**
     * @param string $type
     * @return $this
     */
    public function setEncryption($type)
    {
        $field = $this->test->select($this->test->byXpath(
            "//*[@data-ftid='oro_integration_channel_form_transport_encryption']"
        ));
        $field->selectOptionByValue($type);

        return $this;
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setBaseDistinguishedName($value)
    {
        $field = $this->test->byXpath("//*[@data-ftid='oro_integration_channel_form_transport_baseDn']");
        $field->clear();
        $field->value($value);

        return $this;
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setUserNameLogin($value)
    {
        $field = $this->test->byXpath("//*[@data-ftid='oro_integration_channel_form_transport_username']");
        $field->clear();
        $field->value($value);

        return $this;
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setPassword($value)
    {
        $field = $this->test->byXpath("//*[@data-ftid='oro_integration_channel_form_transport_password']");
        $field->clear();
        $field->value($value);

        return $this;
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setAccountDomainName($value)
    {
        $field = $this->test->byXpath("//*[@data-ftid='oro_integration_channel_form_transport_accountDomainName']");
        $field->clear();
        $field->value($value);

        return $this;
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setDefaultBU($value)
    {
        $element = $this->test->byXpath(
            "//div[starts-with(@id,'s2id_oro_integration_channel_form_defaultBusinessUnitOwner')]/a"
        );
        $element->click();
        $this->waitForAjax();
        if ($this->isElementPresent("//div[@id='select2-drop']/div/input")) {
            $this->test->byXpath("//div[@id='select2-drop']/div/input")->value($value);
            $this->waitForAjax();
            $this->assertElementPresent(
                "//div[@id='select2-drop']//div[contains(., '{$value}')]",
                "Default business unit autocomplete doesn't return search value"
            );
        }
        $this->test->byXpath("//div[@id='select2-drop']//div[contains(., '{$value}')]")->click();

        return $this;
    }

    /**
     * @return $this
     */
    public function checkConnection()
    {
        $this->test->byXPath(
            "//button[starts-with(@id, 'oro_integration_channel_form_transport_connectionCheck')]"
        )->click();
        $this->waitForAjax();
        $this->assertElementPresent(
            "//div[@class='controls messages'][contains(., 'Successfully connected to LDAP server.')]",
            'Connection to LDAP server not established'
        );

        return $this;
    }

    /**
     * @param $ldapRole
     * @param $oroRole
     * @param int $mappingId
     * @return $this
     */
    public function addRoleMappings($ldapRole, $oroRole, $mappingId = 1)
    {
        $this->test->byXPath(
            "//div[@class='responsive-cell responsive-cell-no-blocks ldap-role-mapping']//a[contains(., 'Add')]"
        )->click();
        $this->waitForAjax();
        $this->assertElementPresent(
            "//input[@data-ftid='oro_integration_channel_form_mappingSettings_roleMapping_{$mappingId}_ldapName']",
            'LDAP role mapping field not available'
        );

        $ldapRoleField = $this->test->byXpath(
            "//*[@data-ftid='oro_integration_channel_form_mappingSettings_roleMapping_{$mappingId}_ldapName']"
        );
        $ldapRoleField->clear();
        $ldapRoleField->value($ldapRole);

        $oroRoleField = $this->test->byXpath(
            "//div[starts-with(@id, 's2id_oro_integration_channel_form_mappingSettings".
            "_roleMapping_{$mappingId}_crmRoles')]//input"
        );
        $oroRoleField->click();
        $oroRoleField->value($oroRole);
        $this->waitForAjax();
        $this->assertElementPresent(
            "//div[@id='select2-drop']//span[text()='{$oroRole}']",
            "Oro Roles autocomplete doesn't find role"
        );
        $this->test->byXpath("//div[@id='select2-drop']//span[text()='{$oroRole}']")->click();

        return $this;
    }

    public function setAllRequiredFields($values)
    {
        $this->setName($values['name']);
        $this->setHostName($values['hostname']);
        $this->setPort($values['port']);
        $this->setEncryption($values['encryption']);
        $this->setBaseDistinguishedName($values['basedistinguishedname']);
        $this->setUserNameLogin($values['usernamelogin']);
        $this->setPassword($values['password']);
        $this->setDefaultBU($values['defaultBU']);
        $this->setUserFilter($values['userfilter']);
        $this->setUserName($values['username']);
        $this->setPrimaryEmail($values['primaryemail']);
        $this->setFirstName($values['firstname']);
        $this->setLastName($values['lastname']);
        $this->setRoleFilter($values['rolefilter']);
        $this->setRoleIdAttribute($values['roleidattribute']);
        $this->setRoleUserIdAttribute($values['roleuseridattribute']);
        $this->setExportUserObjectClass($values['exportuserobjectclass']);
        $this->setExportUserBaseDistinguishedName($values['exportuserbasedistinguishedname']);

        return $this;
    }
}
