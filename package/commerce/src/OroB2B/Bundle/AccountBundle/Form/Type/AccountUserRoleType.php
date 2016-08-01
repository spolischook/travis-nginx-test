<?php

namespace OroB2B\Bundle\AccountBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;

class AccountUserRoleType extends AbstractAccountUserRoleType
{
    const NAME = 'orob2b_account_account_user_role';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        $builder->add(
            'account',
            AccountSelectType::NAME,
            [
                'required' => false,
                'label' => 'orob2b.account.accountuserrole.account.label'
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }
}
