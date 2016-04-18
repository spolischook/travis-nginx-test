<?php

namespace OroCRMPro\Bundle\LDAPBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class EncryptionType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'choices' => [
                    'none' => 'orocrmpro.ldap.ldap_encryption.none',
                    'ssl'  => 'orocrmpro.ldap.ldap_encryption.ssl',
                    'tls'  => 'orocrmpro.ldap.ldap_encryption.tls',
                ],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'choice';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'orocrmpro_ldap_encryption';
    }
}
