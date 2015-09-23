<?php

namespace OroPro\Bundle\UserBundle\Form\Type;

use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\SecurityContextInterface;

use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;
use Oro\Bundle\UserBundle\Entity\User;
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
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $user = $this->getUser();
        if (!$this->userHelper->isUserAssignedToSystemOrganization($user)) {
            $view->vars['required'] = true;
        }
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
            'autocomplete_alias' => 'oropro_user_role_organizations',
        ];

        $user = $this->getUser();
        if (!$this->userHelper->isUserAssignedToSystemOrganization($user)) {
            $defaults['constraints'] = [new Assert\NotBlank()];
            $defaults['attr']['data-validation'] = json_encode(['NotBlank' => []]);

            $token = $this->securityContext->getToken();
            if ($token instanceof OrganizationContextTokenInterface) {
                $defaults['data'] = $token->getOrganizationContext();
            }
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

    /**
     * @return User
     */
    protected function getUser()
    {
        return $this->securityContext->getToken()->getUser();
    }
}
