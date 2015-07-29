<?php

namespace OroCRMPro\Bundle\LDAPBundle\Tests\Unit\EventListener;

use Symfony\Component\Form\FormView;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Event\LoadIntegrationThemesEvent;

use OroCRMPro\Bundle\LDAPBundle\EventListener\LoadIntegrationThemesListener;
use OroCRMPro\Bundle\LDAPBundle\Provider\ChannelType;

class LoadIntegrationThemesListenerTest extends \PHPUnit_Framework_TestCase
{
    protected $loadIntegrationThemesListener;

    public function setUp()
    {
        $this->loadIntegrationThemesListener = new LoadIntegrationThemesListener();
    }

    public function testListenerShouldModifyThemesIfChannelTypeIsLdap()
    {
        $channel = new Channel();
        $channel->setType(ChannelType::TYPE);

        $formView = new FormView();
        $formView->vars['value'] = $channel;

        $event = new LoadIntegrationThemesEvent($formView, ['default']);

        $this->loadIntegrationThemesListener->onLoad($event);
        $this->assertEquals(['default', LoadIntegrationThemesListener::LDAP_THEME], $event->getThemes());
    }

    public function testListenerShouldNotModifyThemesIfValueIsNotSet()
    {
        $formView = new FormView();

        $event = new LoadIntegrationThemesEvent($formView, ['default']);

        $this->loadIntegrationThemesListener->onLoad($event);
        $this->assertEquals(['default'], $event->getThemes());
    }

    public function testListenerShouldNotModifyThemesIfValueIsNotInstanceOfChannel()
    {
        $formView = new FormView();
        $formView->vars['value'] = [];

        $event = new LoadIntegrationThemesEvent($formView, ['default']);

        $this->loadIntegrationThemesListener->onLoad($event);
        $this->assertEquals(['default'], $event->getThemes());
    }

    public function testListenerShouldNotModifyThemesIfChannelTypeIsNotLdap()
    {
        $channel = new Channel();
        $channel->setType('notLdap');

        $formView = new FormView();
        $formView->vars['value'] = $channel;

        $event = new LoadIntegrationThemesEvent($formView, ['default']);

        $this->loadIntegrationThemesListener->onLoad($event);
        $this->assertEquals(['default'], $event->getThemes());
    }
}
