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

    /**
     * @param ServiceLink $securityFacadeLink
     * @param ConfigProvider $organizationConfigProvider
     */
    public function __construct(ServiceLink $securityFacadeLink, ConfigProvider $organizationConfigProvider)
    {
        $this->securityFacadeLink         = $securityFacadeLink;
        $this->organizationConfigProvider = $organizationConfigProvider;
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
        return $this->isIgnored($metadata->getName(), $fieldName);
    }

    /**
     * {@inheritdoc}
     */
    public function isIgnoredRelation(ClassMetadata $metadata, $associationName)
    {
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

                return !(
                    $applicable['all'] == true
                    || in_array($this->securityFacadeLink->getService()->getOrganizationId(), $applicable['selective'])
                );
            }
        }

        return false;
    }
}
