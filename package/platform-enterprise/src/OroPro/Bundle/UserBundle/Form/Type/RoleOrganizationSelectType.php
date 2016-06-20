<?php

namespace OroPro\Bundle\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Constraints as Assert;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;
use Oro\Bundle\UserBundle\Entity\Role;

use OroPro\Bundle\UserBundle\Helper\UserProHelper;
use OroPro\Bundle\OrganizationBundle\Helper\OrganizationProHelper;

/**
 * Class RoleOrganizationSelectType.
 *
 * Value is required when Global organization exist and user is not assigned to Global organization.
 * Default value is set to current organization if it's not Global and Global organization exist.
 */
class RoleOrganizationSelectType extends AbstractType
{
    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var UserProHelper
     */
    protected $userHelper;

    /**
     * @var OrganizationProHelper
     */
    protected $organizationHelper;

    /**
     * @param TokenStorageInterface $tokenStorage
     * @param UserProHelper $userHelper
     * @param OrganizationProHelper $organizationHelper
     */
    public function __construct(
        TokenStorageInterface $tokenStorage,
        UserProHelper $userHelper,
        OrganizationProHelper $organizationHelper
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->userHelper = $userHelper;
        $this->organizationHelper = $organizationHelper;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'preSetData']);
    }

    /**
     * Set default value for organization if role is new
     *
     * @param FormEvent $event
     */
    public function preSetData(FormEvent $event)
    {
        $form = $event->getForm();

        if (!$form->getParent() || $event->getData()) {
            return;
        }

        $role = $form->getParent()->getData();

        if ($role instanceof Role && !$role->getId()) {
            $event->setData($this->getDefaultValue());
        }
    }

    /**
     * Returns default value if current organization is not global.
     *
     * @return Organization|null
     */
    protected function getDefaultValue()
    {
        $organization = $this->getCurrentOrganization();

        if ($organization &&
            !$organization->getIsGlobal() &&
            $this->organizationHelper->isGlobalOrganizationExists()
        ) {
            return $organization;
        }

        return null;
    }

    /**
     * @return Organization|null
     */
    protected function getCurrentOrganization()
    {
        $token = $this->tokenStorage->getToken();
        if ($token instanceof OrganizationContextTokenInterface) {
            return $token->getOrganizationContext();
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
            $view->vars['required'] = true;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $defaults = [
            'configs' => [
                'placeholder' => 'oropro.user.role.global_organization.label',

            ],
            'autocomplete_alias' => 'oropro_user_role_organizations',
        ];

        if ($this->isRequired()) {
            $defaults['constraints'] = [new Assert\NotBlank()];
            $defaults['attr']['data-validation'] = json_encode(['NotBlank' => []]);
            $defaults['read_only'] = true;
        }

        $resolver->setDefaults($defaults);
    }

    /**
     * Returns true if field value is required.
     *
     * @return bool
     */
    protected function isRequired()
    {
        return
            $this->organizationHelper->isGlobalOrganizationExists() &&
            !$this->userHelper->isUserAssignedToGlobalOrganization();
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'oro_jqueryselect2_hidden';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oropro_user_role_organization_select';
    }
}
