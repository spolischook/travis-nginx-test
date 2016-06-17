<?php

namespace OroPro\Bundle\OrganizationBundle\Validator\Constraints;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Util\ClassUtils;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization as OrganizationEntity;
use Oro\Bundle\SecurityBundle\Owner\EntityOwnerAccessor;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataInterface;
use Oro\Bundle\SecurityBundle\SecurityFacade;

use OroPro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProProvider;

class OrganizationValidator extends ConstraintValidator
{
    /** @var OwnershipMetadataProProvider */
    protected $ownershipMetadataProProvider;

    /** @var EntityOwnerAccessor */
    protected $entityOwnerAccessor;

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var ManagerRegistry */
    protected $registry;

    /**
     * @param ManagerRegistry              $registry
     * @param OwnershipMetadataProProvider $ownershipMetadataProProvider
     * @param EntityOwnerAccessor          $entityOwnerAccessor
     * @param SecurityFacade               $securityFacade
     */
    public function __construct(
        ManagerRegistry $registry,
        OwnershipMetadataProProvider $ownershipMetadataProProvider,
        EntityOwnerAccessor $entityOwnerAccessor,
        SecurityFacade $securityFacade
    ) {
        $this->registry = $registry;
        $this->ownershipMetadataProProvider = $ownershipMetadataProProvider;
        $this->entityOwnerAccessor = $entityOwnerAccessor;
        $this->securityFacade = $securityFacade;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (null === $value) {
            return;
        }

        $entityClass = ClassUtils::getClass($value);
        $manager = $this->registry->getManagerForClass($entityClass);
        if (!$manager) {
            return;
        }

        $organization = $this->entityOwnerAccessor->getOrganization($value);
        if (!$organization) {
            return;
        }

        $ownershipMetadata = $this->ownershipMetadataProProvider->getMetadata($entityClass);
        if (!$ownershipMetadata || !$ownershipMetadata->hasOwner()) {
            return;
        }
        $owner = $this->entityOwnerAccessor->getOwner($value);

        if (!$this->isValidOrganization($ownershipMetadata, $owner, $organization)) {
            $organizationFieldName = $ownershipMetadata->getGlobalOwnerFieldName();
            /** @var ExecutionContextInterface $context */
            $context = $this->context;
            $context->buildViolation($constraint->message)
                ->atPath($organizationFieldName)
                ->setParameter('{{ organization }}', $organizationFieldName)
                ->addViolation();
        }
    }

    /**
     * Returns true if given organization can be used
     *
     * @param OwnershipMetadataInterface $metadata
     * @param object                     $owner
     * @param object                     $organization
     *
     * @return bool
     */
    protected function isValidOrganization(OwnershipMetadataInterface $metadata, $owner, $organization)
    {
        /** @var User $loggedInUser */
        $loggedInUser = $this->securityFacade->getLoggedUser();

        /** @var OrganizationEntity $currentOrganization */
        $currentOrganization = $this->securityFacade->getOrganization();

        if ($currentOrganization->getIsGlobal()) {
            /** @var ArrayCollection $loggedInUserOrganizations */
            $loggedInUserOrganizations = $loggedInUser->getOrganizations(true);
            if (!$loggedInUserOrganizations->contains($organization)) {
                //is current user has access to given organization
                return false;
            }
        } else {
            /** @var OrganizationEntity $loggedInUserOrganization */
            $loggedInUserOrganization = $loggedInUser->getOrganization();
            if ($loggedInUserOrganization->getId() !== $organization->getId()) {
                //is current organization, the user being logged in, the same as given organization
                return false;
            }
        }

        switch (true) {
            case ($metadata->isBasicLevelOwned()):
                //assigned owner(user) belongs to given organization
                /** @var User $owner */
                $isOwnerValid = $owner->getOrganizations(true)->contains($organization);
                break;
            case ($metadata->isLocalLevelOwned()):
                //is assigned owner(business unit) belongs to given organization
                /** @var BusinessUnit $owner */
                $isOwnerValid = $owner->getOrganization()->getId() === $organization->getId();
                break;
            default:
                $isOwnerValid = true;
        }

        return $isOwnerValid;
    }
}
