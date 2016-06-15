<?php

namespace OroPro\Bundle\OrganizationBundle\Form\Handler;

use Doctrine\ORM\EntityManager;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

use OroPro\Bundle\OrganizationBundle\Event\OrganizationUpdateEvent;

class OrganizationProHandler
{
    /** @var FormInterface */
    protected $form;

    /** @var Request */
    protected $request;

    /** @var EntityManager */
    protected $manager;

    /** @var SecurityContextInterface */
    protected $securityContext;

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /**
     * @param FormInterface            $form
     * @param Request                  $request
     * @param EntityManager            $manager
     * @param SecurityContextInterface $securityContext
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        FormInterface $form,
        Request $request,
        EntityManager $manager,
        SecurityContextInterface $securityContext,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->form            = $form;
        $this->request         = $request;
        $this->manager         = $manager;
        $this->securityContext = $securityContext;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param Organization $entity
     *
     * @return bool
     */
    public function process(Organization $entity)
    {
        $this->form->setData($entity);

        $currentUser = $this->getUser();
        if (!$entity->getId() && $currentUser) {
            $this->form->get('appendUsers')->setData([$currentUser]);
        }

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

        //clear channels entities state cache
        $event = new OrganizationUpdateEvent($entity);
        $this->eventDispatcher->dispatch(OrganizationUpdateEvent::NAME, $event);
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

    /**
     * Get the current authenticated user
     *
     * @return User|null
     */
    protected function getUser()
    {
        $token = $this->securityContext->getToken();
        if ($token instanceof TokenInterface) {
            $user = $token->getUser();
            if ($user instanceof User) {
                return $user;
            }
        }

        return null;
    }
}
