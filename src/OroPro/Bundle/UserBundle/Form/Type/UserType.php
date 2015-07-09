<?php

namespace OroPro\Bundle\UserBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;

use Oro\Bundle\UserBundle\Form\Type\UserType as BaseUserType;
use OroPro\Bundle\UserBundle\EventListener\FormUserTypeSubscriber;

class UserType extends BaseUserType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        $builder->addEventSubscriber(new FormUserTypeSubscriber($this->security, $this->isMyProfilePage));
    }
}
