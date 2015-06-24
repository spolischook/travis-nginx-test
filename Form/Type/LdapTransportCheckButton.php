<?php

namespace Oro\Bundle\LDAPBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class LdapTransportCheckButton extends AbstractType
{

    const NAME = "oro_ldap_transport_check_button_type";

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::NAME;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        parent::setDefaultOptions($resolver);
        $resolver->setDefaults([
            'mapped' => false,
        ]);
    }


}
