<?php

namespace OroPro\Bundle\UserBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\Role;

class UserTypeExtension extends AbstractTypeExtension
{
    /**
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
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
        $roles = $this->em->createQueryBuilder()
            ->select('r')
            ->from('Oro\Bundle\UserBundle\Entity\Role', 'r')
            ->andWhere('r.role <> :anon')
            ->setParameter('anon', User::ROLE_ANONYMOUS)
            ->getQuery()->getResult();


        $permissionsMap = [];
        foreach ($roles as $role) {
            /** @var Organization $organization */
            $organization = $role->getOrganization();
            $organizationName = 'System';
            $orgId = null;
            if ($organization) {
                $orgId = $organization->getId();
                $organizationName = $organization->getName();
            };

            $label = $role->getLabel() . ' [' . $organizationName . ']';
            $permissionsMap[$role->getId()] = [
                'org_id' => $orgId,
                'label' => $label,
            ];
        }

        return $permissionsMap;
    }
}
