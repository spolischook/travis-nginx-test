<?php

namespace OroPro\Bundle\OrganizationBundle\Provider;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\EntityBundle\Provider\ExclusionProviderInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class OrganizationExclusionProvider implements ExclusionProviderInterface
{
    /**
     * @var SecurityFacade
     */
    protected $securityFacade;

    /**
     * @var ConfigProvider
     */
    protected $configProvider;

    /**
     * @param SecurityFacade $securityFacade
     * @param ConfigProvider $configProvider
     */
    public function __construct(SecurityFacade $securityFacade, ConfigProvider $configProvider)
    {
        $this->configProvider = $configProvider;
        $this->securityFacade = $securityFacade;
    }

    /**
     * {@inheritdoc}
     */
    public function isIgnoredEntity($className)
    {
        return $this->checkAvailability($className);
    }

    /**
     * {@inheritdoc}
     */
    public function isIgnoredField(ClassMetadata $metadata, $fieldName)
    {
        return $this->checkAvailability($metadata->getName(), $fieldName);
    }

    /**
     * {@inheritdoc}
     */
    public function isIgnoredRelation(ClassMetadata $metadata, $associationName)
    {
        return $this->checkAvailability($metadata->getName(), $associationName);
    }

    /**
     * @param      $className
     * @param null $propertyName
     *
     * @return bool
     */
    protected function checkAvailability($className, $propertyName = null)
    {
        if (!$this->configProvider->hasConfig($className, $propertyName)) {
            return true;
        }

        $config = $this->configProvider->getConfig($className, $propertyName);
        if ($config->has('applicable')) {
            $applicable = $config->get('applicable');
            if (!$applicable['all']) {
                if (!in_array($this->securityFacade->getOrganizationId(), $applicable['selective'])) {
                    return true;
                }
            }
        }
        return false;
    }
}
