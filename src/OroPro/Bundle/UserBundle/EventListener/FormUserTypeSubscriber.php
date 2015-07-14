<?php

namespace OroPro\Bundle\UserBundle\EventListener;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\Collection;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Security\Core\SecurityContextInterface;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;

class FormUserTypeSubscriber implements EventSubscriberInterface
{
    /**
     * @var SecurityContextInterface
     */
    protected $security;

    /**
     * @var bool
     */
    protected $isMyProfilePage;

    /**
     * @param SecurityContextInterface $security
     * @param bool $isMyProfilePage
     */
    public function __construct(SecurityContextInterface $security, $isMyProfilePage)
    {
        $this->security = $security;
        $this->isMyProfilePage = $isMyProfilePage;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SET_DATA => 'onPreSetData',
        ];
    }

    /**
     * @param FormEvent $event
     */
    public function onPreSetData(FormEvent $event)
    {
        $form = $event->getForm();

        if ($form->has('roles') && !$this->isCurrentUserAssignedToGlobalOrganization()) {
            $form->remove('roles');

            $organizationIds = [];
            $organizations = $this->getCurrentUserOrganizations();
            if ($organizations) {
                $organizationIds = $organizations
                    ->map(
                        function (Organization $organization) {
                            return $organization->getId();
                        }
                    )->toArray();
            }

            $form->add(
                'roles',
                'entity',
                [
                    'property_path' => 'rolesCollection',
                    'label'         => 'oro.user.roles.label',
                    'class'         => 'OroUserBundle:Role',
                    'property'      => 'label',
                    'query_builder' => function (EntityRepository $er) use ($organizationIds) {
                        $qb = $er->createQueryBuilder('r');
                        $qb->where($qb->expr()->neq('r.role', ':anon'))
                            ->andWhere($qb->expr()->in('r.organization', ':orgIds'))
                            ->setParameter('anon', User::ROLE_ANONYMOUS)
                            ->setParameter('orgIds', $organizationIds)
                            ->orderBy('r.label');
                        return $qb;
                    },
                    'multiple'      => true,
                    'expanded'      => true,
                    'required'      => !$this->isMyProfilePage,
                    'read_only'     => $this->isMyProfilePage,
                    'disabled'      => $this->isMyProfilePage,
                    'translatable_options' => false
                ]
            );
        }
    }

    /**
     * @return bool
     */
    protected function isCurrentUserAssignedToGlobalOrganization()
    {
        $assignedToGlobalOrganization = false;
        $token = $this->security->getToken();
        if ($token && is_object($user = $token->getUser())) {
            /** @var User $user */
            $assignedToGlobalOrganization = $user->getOrganizations()
                ->exists(
                    function ($key, Organization $organization) {
                        return $organization->getIsGlobal();
                    }
                );
        }

        return $assignedToGlobalOrganization;
    }

    /**
     * @return Collection|Organization[]|null
     */
    protected function getCurrentUserOrganizations()
    {
        $organizations = null;
        $token = $this->security->getToken();
        if ($token && is_object($user = $token->getUser())) {
            /** @var User $user */
            $organizations = $user->getOrganizations();
        }

        return $organizations;
    }
}
