<?php

namespace OroPro\Bundle\OrganizationBundle\Acl\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

use Oro\Bundle\SecurityBundle\Metadata\AclAnnotationProvider;

class OrganizationAnnotationVoter implements VoterInterface
{
    /**
     * @var AclAnnotationProvider
     */
    protected $aclAnnotationProvider;

    /**
     * @var string
     */
    protected $organizationClassName;

    /**
     * @param AclAnnotationProvider $aclAnnotationProvider
     * @param string $organizationClassName
     */
    public function __construct(AclAnnotationProvider $aclAnnotationProvider, $organizationClassName)
    {
        $this->aclAnnotationProvider = $aclAnnotationProvider;
        $this->organizationClassName = $organizationClassName;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsAttribute($attribute)
    {
        return (bool)$this->aclAnnotationProvider->findAnnotationById($attribute);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsClass($class)
    {
        return is_a($class, $this->organizationClassName, true);
    }

    /**
     * {@inheritdoc}
     */
    public function vote(TokenInterface $token, $object, array $attributes)
    {
        return self::ACCESS_GRANTED;
    }
}
