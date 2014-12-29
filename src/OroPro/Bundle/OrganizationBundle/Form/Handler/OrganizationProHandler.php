<?php

namespace OroPro\Bundle\OrganizationBundle\Form\Handler;

use Doctrine\ORM\EntityManager;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

class OrganizationProHandler
{
    /** @var FormInterface */
    protected $form;

    /** @var Request */
    protected $request;

    /** @var EntityManager */
    protected $manager;

    /**
     * @var SecurityContextInterface
     */
    protected $securityContext;

    /**
     * @param FormInterface            $form
     * @param Request                  $request
     * @param EntityManager            $manager
     * @param SecurityContextInterface $securityContext
     */
    public function __construct(
        FormInterface $form,
        Request $request,
        EntityManager $manager,
        SecurityContextInterface $securityContext
    ) {
        $this->form            = $form;
        $this->request         = $request;
        $this->manager         = $manager;
        $this->securityContext = $securityContext;
    }

    /**
     * @param Organization $entity
     *
     * @return bool
     */
    public function process(Organization $entity)
    {
        $this->form->setData($entity);

        if (in_array($this->request->getMethod(), ['POST', 'PUT'])) {
            $this->form->submit($this->request);
            if ($this->form->isValid()) {
                $appendUsers = $this->form->get('appendUsers')->getData();
                $removeUsers = $this->form->get('removeUsers')->getData();

                $this->onSuccess($entity, $appendUsers, $removeUsers);

                return true;
            }
        }

        return false;
    }

    /**
     * @param Organization $entity
     * @param User[]       $appendUsers
     * @param User[]       $removeUsers
     */
    protected function onSuccess(Organization $entity, array $appendUsers, array $removeUsers)
    {
        $this->appendUsers($entity, $appendUsers);
        $this->removeUsers($entity, $removeUsers);

        $this->manager->persist($entity);
        $this->manager->flush();
    }

    /**
     * Append users to organization
     *
     * @param Organization $organization
     * @param User[]       $users
     */
    protected function appendUsers(Organization $organization, array $users)
    {
        foreach ($users as $user) {
            $organization->addUser($user);
        }
    }

    /**
     * Remove users from organization
     *
     * @param Organization $organization
     * @param User[]       $users
     */
    protected function removeUsers(Organization $organization, array $users)
    {
        foreach ($users as $user) {
            $organization->removeUser($user);
        }
    }
}
