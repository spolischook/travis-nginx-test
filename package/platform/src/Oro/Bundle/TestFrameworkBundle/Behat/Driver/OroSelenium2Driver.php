<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Driver;

use Behat\Mink\Driver\Selenium2Driver;
use Behat\Mink\Exception\ExpectationException;
use Behat\Mink\Selector\Xpath\Manipulator;
use WebDriver\Element;

class OroSelenium2Driver extends Selenium2Driver
{
    /**
     * @var Manipulator
     */
    private $xpathManipulator;

    public function __construct($browserName, $desiredCapabilities, $wdHost)
    {
        $this->xpathManipulator = new Manipulator();

        parent::__construct($browserName, $desiredCapabilities, $wdHost);
    }

    /**
     * {@inheritdoc}
     */
    public function setValue($xpath, $value)
    {
        $element = $this->getWebDriverSession()->element('xpath', $xpath);
        $elementName = strtolower($element->name());

        if ('input' === $elementName) {

            $classes = explode(' ', $element->attribute('class'));

            if (in_array('select2-offscreen', $classes, true)) {
                $this->setSelect2Result($xpath, $value);

                return;
            } elseif ('text' === $element->attribute('type')) {
                $this->setTextInput($element, $value);

                return;
            }
        } elseif ('textarea' === $elementName) {
            if ('true' === $element->attribute('aria-hidden')) {
                $this->fillAsTinyMce($element, $value);

                return;
            }
        }

        parent::setValue($xpath, $value);
    }

    /**
     * @param Element $element
     * @param string $value
     * @throws ExpectationException
     */
    protected function fillAsTinyMce(Element $element, $value)
    {
        $fieldId = $element->attribute('id');

        $isTinyMce = $this->evaluateScript(
            sprintf('null != tinyMCE.get("%s");', $fieldId)
        );

        if (!$isTinyMce) {
            throw new ExpectationException(
                sprintf('Field was guessed as tinymce, but can\'t find tiny with id "%s" on page', $fieldId),
                $this
            );
        }

        $this->executeScript(
            sprintf('tinyMCE.get("%s").setContent("%s");', $fieldId, $value)
        );
    }

    /**
     * @param Element $element
     * @param string $value
     */
    protected function setTextInput(Element $element, $value)
    {
        $script = <<<JS
var node = {{ELEMENT}};
node.value = '$value';
JS;
        $this->executeJsOnElement($element, $script);
    }

    /**
     * @param string $xpath
     * @param string $value
     * @throws ExpectationException
     * @throws \Exception
     */
    protected function setSelect2Result($xpath, $value)
    {
        $this
            ->findElement($this->xpathManipulator->prepend('/../a/span[contains(@class, "select2-arrow")]', $xpath))
            ->click();
        $this->findElement('//div[contains(@class, "select2-search")]/input')->postValue(['value' => [$value]]);

        $this->wait(3000, "0 == $('ul.select2-results li.select2-searching').length");
        $results = $this->findElementXpaths('//ul[contains(@class, "select2-results")]/li');

        if (1 < count($results)) {
            throw new ExpectationException(sprintf('Too many results for "%s"', $value), $this);
        }

        $firstResult = $this->getWebDriverSession()->element('xpath', array_shift($results));

        if ('select2-no-results' === $firstResult->attribute('class')) {
            throw new ExpectationException(sprintf('Not found result for "%s"', $value), $this);
        }

        $firstResult->click();
    }

    /**
     * Wait PAGE load
     * @param int $time Time should be in milliseconds
     */
    public function waitPageToLoad($time = 15000)
    {
        $this->wait(
            $time,
            '"complete" == document["readyState"] '.
            '&& (typeof($) != "undefined" '.
            '&& document.title !=="Loading..." '.
            '&& $ !== null '.
            '&& false === $( "div.loader-mask" ).hasClass("shown"))'
        );
    }

    /**
     * Wait AJAX request
     * @param int $time Time should be in milliseconds
     */
    public function waitForAjax($time = 15000)
    {
        $this->waitPageToLoad($time);

        $jsAppActiveCheck = <<<JS
        (function () {
            var isAppActive = false;
            try {
                if (!window.mediatorCachedForSelenium) {
                    window.mediatorCachedForSelenium = require('oroui/js/mediator');
                }
                isAppActive = window.mediatorCachedForSelenium.execute('isInAction');
            } catch (e) {
                return false;
            }

            return !(jQuery && (jQuery.active || jQuery(document.body).hasClass('loading'))) && !isAppActive;
        })();
JS;
        $this->wait($time, $jsAppActiveCheck);
    }

    /**
     * @param string $xpath
     *
     * @return Element
     */
    private function findElement($xpath)
    {
        return $this->getWebDriverSession()->element('xpath', $xpath);
    }

    /**
     * Executes JS on a given element - pass in a js script string and {{ELEMENT}} will
     * be replaced with a reference to the element
     *
     * @example $this->executeJsOnXpath($xpath, 'return {{ELEMENT}}.childNodes.length');
     *
     * @param Element $element the webdriver element
     * @param string  $script  the script to execute
     * @param Boolean $sync    whether to run the script synchronously (default is TRUE)
     *
     * @return mixed
     */
    private function executeJsOnElement(Element $element, $script, $sync = true)
    {
        $script  = str_replace('{{ELEMENT}}', 'arguments[0]', $script);

        $options = array(
            'script' => $script,
            'args'   => array(array('ELEMENT' => $element->getID())),
        );

        if ($sync) {
            return $this->getWebDriverSession()->execute($options);
        }

        return $this->getWebDriverSession()->execute_async($options);
    }
}
