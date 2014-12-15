<?php

namespace OroPro\Bundle\TestFrameworkBundle\Test;

use Oro\Bundle\TestFrameworkBundle\Test\Selenium2TestCase as BaseSelenium2TestCase;
use Oro\Bundle\UserBundle\Tests\Selenium\Pages\Login;

abstract class Selenium2TestCase extends BaseSelenium2TestCase
{
    /**
     * @param null $userName
     * @param null $password
     * @param string $organizationName
     * @return Login
     */
    public function login($userName = null, $password = null, $organizationName = 'OroCRM')
    {
        /** @var Login $login */
        $login = new Login($this, array());
        $login = $login->setUsername(($userName) ? $userName : PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_LOGIN)
            ->setPassword(($password) ? $password : PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_PASS);
        $organization = $this->byId('prependedInput3');
        if ($organization->displayed()) {
            $value = $this->byXPath("//select[@id='prependedInput3']/option[contains(., '{$organizationName}')]");
            $value = $value->attribute("value");
            $organization = $this->select($this->byId('prependedInput3'));
            $organization->selectOptionByValue($value);
        }
        $login->submit();
        return $login;
    }
}
