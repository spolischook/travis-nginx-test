<?php

namespace OroCRMPro\Bundle\LDAPBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;

use Oro\Bundle\FormBundle\Utils\FormUtils;
use Oro\Bundle\IntegrationBundle\Entity\Channel;

use OroCRMPro\Bundle\LDAPBundle\Provider\ChannelType;

class ChannelConnectorsExtension extends AbstractTypeExtension
{
    const CLASS_PATH = '[attr][class]';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(
            FormEvents::POST_SET_DATA,
            [$this, 'onPostSetData']
        );

        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            [$this, 'onPostSubmit']
        );

        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            [$this, 'onPreSubmit']
        );
    }

    /**
     * @param Channel|array $data
     * @return bool
     */
    public function isApplicable($data = null)
    {
        if ($data === null) {
            return false;
        }

        if (is_array($data)) {
            return $data['type'] === ChannelType::TYPE;
        }
        return $data->getType() === ChannelType::TYPE;
    }

    /**
     * Hide connectors for LDAP channel
     *
     * @param FormEvent $event
     */
    public function onPostSetData(FormEvent $event)
    {
        $data = $event->getData();
        if (!$this->isApplicable($data)) {
            return;
        }

        $this->hideConnectors($event->getForm());
    }

    /**
     * Set all Connectors to be enabled.
     *
     * @param FormEvent $event
     */
    public function onPostSubmit(FormEvent $event)
    {
        $data = $event->getData();
        if (!$this->isApplicable($data)) {
            return;
        }
        $options = $event->getForm()['connectors']->getConfig()->getOptions();
        $connectors = array_keys($options['choices']);
        $data->setConnectors($connectors);
    }

    /**
     * Hide connectors choices list before submitting.
     *
     * @param FormEvent $event
     */
    public function onPreSubmit(FormEvent $event)
    {
        $data = $event->getData();
        if (!$this->isApplicable($data)) {
            return;
        }

        $this->hideConnectors($event->getForm());
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return 'oro_integration_channel_form';
    }

    /**
     * Hides connector choices fields in form.
     *
     * @param FormInterface $form
     */
    protected function hideConnectors(FormInterface $form)
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $options          = $form['connectors']->getConfig()->getOptions();
        $class            = $propertyAccessor->getValue($options, self::CLASS_PATH);

        FormUtils::replaceField(
            $form,
            'connectors',
            [
                'attr' => [
                    'class' => implode(' ', [$class, 'hide'])
                ]
            ]
        );
    }
}
