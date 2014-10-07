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
        $config = $this->configProvider->getConfig($className);
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

    /**
     * {@inheritdoc}
     */
    public function isIgnoredField(ClassMetadata $metadata, $fieldName)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isIgnoredRelation(ClassMetadata $metadata, $associationName)
    {
        return false;
    }
}
