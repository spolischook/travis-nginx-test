<?php

namespace OroPro\Bundle\UserBundle\Form\Type;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\UserBundle\Form\Type\UserType as BaseUserType;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\ConfigBundle\Manager\UserConfigManager;
use Doctrine\ORM\EntityManager;

class UserType extends BaseUserType
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @param SecurityContextInterface $security
     * @param SecurityFacade $securityFacade
     * @param Request $request
     * @param UserConfigManager $userConfigManager
     * @param EntityManager $em
     */
    public function __construct(
        SecurityContextInterface $security,
        SecurityFacade           $securityFacade,
        Request                  $request,
        UserConfigManager        $userConfigManager,
        EntityManager            $em
    ) {
        parent::__construct($security, $securityFacade, $request, $userConfigManager);
        $this->em = $em;
    }

    /**
     * @param FormView $view
     * @param FormInterface $form
     * @param array $options
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
        $roles = $this->em->createQueryBuilder()
            ->select('r')
            ->from('Oro\Bundle\UserBundle\Entity\Role', 'r')
            ->andWhere('r.role <> :anon')
            ->setParameter('anon', User::ROLE_ANONYMOUS)
            ->getQuery()->getResult();


        $permissionsMap = [];
        foreach ($roles as $role) {
            $organization = $role->getOrganization();
            $orgId = null;
            if ($organization) {
                $orgId = $organization->getId();
            };
            $permissionsMap[$role->getId()] = $orgId;
        }

        return $permissionsMap;
    }
}
