<?php

namespace Oro\Bundle\LDAPBundle\Form\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

use Oro\Bundle\IntegrationBundle\Form\Type\ChannelType;
use Oro\Bundle\IntegrationBundle\Manager\TypesRegistry;

class LdapConnectorFormSubscriber implements EventSubscriberInterface
{
    /** @var TypesRegistry */
    protected $typeRegistry;

    public function __construct(TypesRegistry $registry)
    {
        $this->typeRegistry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SET_DATA => 'preSet',
            FormEvents::PRE_SUBMIT => 'preSubmit'
        ];
    }

    /**
     * Populate websites choices if exist in entity
     *
     * @param FormEvent $event
     */
    public function preSet(FormEvent $event)
    {
        $this->modify($event->getData(), $event->getForm()->getParent());
    }

    /**
     * Pre submit event listener
     * Encrypt passwords and populate if empty
     * Populate websites choices from hidden fields
     *
     * @param FormEvent $event
     */
    public function preSubmit(FormEvent $event)
    {
        $this->modify($event->getData(), $event->getForm()->getParent());
    }

    /**
     * @param array $data
     * @param FormInterface $form
     */
    protected function modify($data, FormInterface $form)
    {
        if ($form && $form->getConfig()->getType()->getInnerType() instanceof ChannelType) {
            $connectors = $form->get('connectors');
            if ($connectors) {
                $config = $connectors->getConfig()->getOptions();
                unset($config['choice_list']);
                unset($config['choices']);
            } else {
                $config = [];
            }

            if (array_key_exists('auto_initialize', $config)) {
                $config['auto_initialize'] = false;
            }

            $form->add('connectors', 'choice', array_merge($config, ['choices' => []]));
        }
    }

}
