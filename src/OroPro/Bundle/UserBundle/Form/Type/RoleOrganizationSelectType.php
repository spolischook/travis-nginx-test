<?php

namespace OroPro\Bundle\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\SecurityContextInterface;

use OroPro\Bundle\UserBundle\Helper\UserProHelper;

class RoleOrganizationSelectType extends AbstractType
{
    /**
     * @var SecurityContextInterface
     */
    protected $securityContext;

    /**
     * @var UserProHelper
     */
    protected $userHelper;

    /**
     * @param SecurityContextInterface $securityContext
     * @param UserProHelper $userHelper
     */
    public function __construct(SecurityContextInterface $securityContext, UserProHelper $userHelper)
    {
        $this->securityContext = $securityContext;
        $this->userHelper = $userHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $defaults = [
            'configs' => [
                'placeholder' => 'oro.organization.form.choose_organization',
            ],
            'autocomplete_alias' => 'oropro_user_organizations',
        ];

        $user = $this->securityContext->getToken()->getUser();
        if (!$this->userHelper->isUserAssignedToSystemOrganization($user)) {
            $defaults['constraints'] = [new Assert\NotBlank()];
        }

        $resolver->setDefaults(
            $defaults
        );
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
