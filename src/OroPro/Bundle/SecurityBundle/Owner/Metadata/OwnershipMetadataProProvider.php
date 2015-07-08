<?php

namespace OroPro\Bundle\SecurityBundle\Owner\Metadata;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\OrganizationBundle\Form\Type\OwnershipType;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider;
use Symfony\Component\Security\Core\SecurityContextInterface;

class OwnershipMetadataProProvider extends OwnershipMetadataProvider
{
    /**
     * @var SecurityContextInterface
     */
    private $securityContext;

    /**
     * @return SecurityContextInterface
     */
    public function getSecurityContext()
    {
        if (!$this->securityContext) {
            $this->securityContext = $this->getContainer()->get('security.context');
        }

        return $this->securityContext;
    }

    /**
     * {@inheritdoc}
     */
    protected function getNoOwnershipMetadata()
    {
        return new OwnershipProMetadata();
    }

    /**
     * {@inheritdoc}
     */
    public function getMaxAccessLevel($accessLevel, $className = null)
    {
        // for global mode we should not hide system access level as organization
        if ($this->isGlobalMode()) {
            return $accessLevel;
        }

        return parent::getMaxAccessLevel($accessLevel, $className);
    }

    /**
     * Check if current mode is global (isGlobal for current organization is set to true)
     *
     * @return bool
     */
    protected function isGlobalMode()
    {
        $token = $this->getSecurityContext()->getToken();
        if ($token instanceof OrganizationContextTokenInterface) {
            return $token->getOrganizationContext()->getIsGlobal();
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function getOwnershipMetadata(ConfigInterface $config)
    {
        $ownerType = $config->get('owner_type');
        $ownerFieldName = $config->get('owner_field_name');
        $ownerColumnName = $config->get('owner_column_name');
        $organizationFieldName = $config->get('organization_field_name');
        $organizationColumnName = $config->get('organization_column_name');
        $globalView = $config->get('global_view');

        if (!$organizationFieldName && $ownerType == OwnershipType::OWNER_TYPE_ORGANIZATION) {
            $organizationFieldName = $ownerFieldName;
            $organizationColumnName = $ownerColumnName;
        }

        return new OwnershipProMetadata(
            $ownerType,
            $ownerFieldName,
            $ownerColumnName,
            $organizationFieldName,
            $organizationColumnName,
            $globalView
        );
    }
}
