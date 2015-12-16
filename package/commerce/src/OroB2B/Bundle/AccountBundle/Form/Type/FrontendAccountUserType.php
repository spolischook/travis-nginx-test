<?php

namespace OroB2B\Bundle\AccountBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\SecurityBundle\SecurityFacade;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;

class FrontendAccountUserType extends AbstractType
{
    const NAME = 'orob2b_account_frontend_account_user';

    /**
     * @var SecurityFacade
     */
    protected $securityFacade;

    /**
     * @var string
     */
    protected $accountUserClass;

    /**
     * @param SecurityFacade $securityFacade
     */
    public function __construct(SecurityFacade $securityFacade)
    {
        $this->securityFacade = $securityFacade;
    }

    /**
     * @param string $class
     */
    public function setAccountUserClass($class)
    {
        $this->accountUserClass = $class;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'onPreSetData']);
        $builder->add(
            'roles',
            FrontendAccountUserRoleSelectType::NAME,
            [
                'label' => 'orob2b.account.accountuser.roles.label'
            ]
        );
    }

    /**
     * @param FormEvent $event
     * @return bool
     */
    public function onPreSetData(FormEvent $event)
    {
        /** @var $user AccountUser */
        $user = $this->securityFacade->getLoggedUser();
        if (!$user instanceof AccountUser) {
            return;
        }

        /** @var AccountUser $data */
        $data = $event->getData();
        $data->setAccount($user->getAccount());
        $data->setOrganization($user->getOrganization());
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return AccountUserType::NAME;
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
            'data_class' => $this->accountUserClass,
        ]);
    }
}
