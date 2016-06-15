<?php

namespace OroPro\Bundle\OrganizationBundle\Provider;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\EntityBundle\Provider\ExclusionProviderInterface;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

class OrganizationExclusionProvider implements ExclusionProviderInterface
{
    /** @var ServiceLink */
    protected $securityFacadeLink;

    /** @var ConfigProvider */
    protected $organizationConfigProvider;

    /** @var SystemAccessModeOrganizationProvider */
    protected $organizationProvider;

    /**
     * @param ServiceLink                          $securityFacadeLink
     * @param ConfigProvider                       $organizationConfigProvider
     * @param SystemAccessModeOrganizationProvider $organizationProvider
     */
    public function __construct(
        ServiceLink $securityFacadeLink,
        ConfigProvider $organizationConfigProvider,
        SystemAccessModeOrganizationProvider $organizationProvider
    ) {
        $this->securityFacadeLink         = $securityFacadeLink;
        $this->organizationConfigProvider = $organizationConfigProvider;
        $this->organizationProvider       = $organizationProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function isIgnoredEntity($className)
    {
        return $this->isIgnored($className);
    }

    /**
     * {@inheritdoc}
     */
    public function isIgnoredField(ClassMetadata $metadata, $fieldName)
    {
        if (!$metadata->hasField($fieldName)) {
            // skip virtual fields
            return false;
        }

        return $this->isIgnored($metadata->getName(), $fieldName);
    }

    /**
     * {@inheritdoc}
     */
    public function isIgnoredRelation(ClassMetadata $metadata, $associationName)
    {
        if (!$metadata->hasAssociation($associationName)) {
            // skip virtual relations
            return false;
        }

        return $this->isIgnored($metadata->getName(), $associationName);
    }

    /**
     * @param      $className
     * @param null $propertyName
     *
     * @return bool
     */
    protected function isIgnored($className, $propertyName = null)
    {
        if ($this->organizationConfigProvider->hasConfig($className, $propertyName)) {
            $config = $this->organizationConfigProvider->getConfig($className, $propertyName);
            if ($config->has('applicable')) {
                $applicable = $config->get('applicable');

                $organizationId = $this->organizationProvider->getOrganizationId() ? :
                    $this->securityFacadeLink->getService()->getOrganizationId();

                return !(
                    $applicable['all'] == true
                    || in_array($organizationId, $applicable['selective'])
                );
            }
        }

        return false;
    }
}
