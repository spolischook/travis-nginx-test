<?php

namespace OroB2B\Bundle\AccountBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\UserBundle\Form\Type\UserMultiSelectType;

class AccountUserMultiSelectType extends AbstractType
{
    const NAME = 'orob2b_account_account_user_multiselect';

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return UserMultiSelectType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'autocomplete_alias' => 'orob2b_account_account_user',
                'configs' => [
                    'multiple' => true,
                    'component' => 'autocomplete-accountuser',
                    'placeholder' => 'orob2b.account.accountuser.form.choose',
                ],
                'attr' => [
                    'class' => 'account-accountuser-multiselect',
                ],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::NAME;
    }
}
