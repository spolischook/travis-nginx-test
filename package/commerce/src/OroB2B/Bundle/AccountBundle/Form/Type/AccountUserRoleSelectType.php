<?php

namespace OroB2B\Bundle\AccountBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

use OroB2B\Bundle\AccountBundle\Entity\AccountUserRole;

class AccountUserRoleSelectType extends AbstractType
{
    const NAME = 'orob2b_account_account_user_role_select';

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @var string
     */
    protected $roleClass;

    /**
     * @param string $roleClass
     */
    public function setRoleClass($roleClass)
    {
        $this->roleClass = $roleClass;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'class' => $this->roleClass,
            'multiple' => true,
            'expanded' => true,
            'required' => true,
            'choice_label' => function ($role) {
                if (!($role instanceof AccountUserRole)) {
                    return (string)$role;
                }

                $roleType = 'orob2b.account.accountuserrole.type.';
                $roleType .= $role->isPredefined() ? 'predefined.label' : 'customizable.label';
                return sprintf('%s (%s)', $role->getLabel(), $this->translator->trans($roleType));
            }
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'entity';
    }
}
