<?php

namespace OroPro\Bundle\UserBundle\Form\Extension;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\Role;

class UserTypeExtension extends AbstractTypeExtension
{
    /**
     * @var ManagerRegistry
     */
    protected $managerRegistry;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(ManagerRegistry $managerRegistry, TranslatorInterface $translator)
    {
        $this->managerRegistry = $managerRegistry;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return 'oro_user_user';
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['permissions_map'] = json_encode($this->getRolesToOrganizationsMap());
    }

    /**
     * @return array
     */
    protected function getRolesToOrganizationsMap()
    {
        /** @var Role[] $roles */
        $roles = $this->managerRegistry->getRepository('OroUserBundle:Role')
            ->createQueryBuilder('r')
            ->andWhere('r.role <> :anon')
            ->setParameter('anon', User::ROLE_ANONYMOUS)
            ->getQuery()->getResult();

        $permissionsMap = [];
        foreach ($roles as $role) {
            /** @var Organization $organization */
            $organization = $role->getOrganization();
            $organizationName = $this->translator->trans('oropro.user.role.global_organization.label');
            $organizationId = null;
            if ($organization) {
                $organizationId = $organization->getId();
                $organizationName = $organization->getName();
            };

            $label = $role->getLabel() . ' [' . $organizationName . ']';
            $permissionsMap[$role->getId()] = [
                'org_id' => $organizationId,
                'label' => $label,
            ];
        }

        return $permissionsMap;
    }
}
