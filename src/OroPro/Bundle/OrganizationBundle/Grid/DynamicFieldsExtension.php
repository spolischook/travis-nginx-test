<?php

namespace OroPro\Bundle\OrganizationBundle\Grid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridGuesser;

use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\EntityBundle\Grid\DynamicFieldsExtension as DynamicFields;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

use Oro\Bundle\SecurityBundle\SecurityFacade;

class DynamicFieldsExtension extends DynamicFields
{
    /** @var SecurityFacade */
    protected $securityFacade;

    /**
     * @param ConfigManager       $configManager
     * @param EntityClassResolver $entityClassResolver
     * @param DatagridGuesser     $datagridGuesser
     * @param SecurityFacade      $securityFacade
     */
    public function __construct(
        ConfigManager $configManager,
        EntityClassResolver $entityClassResolver,
        DatagridGuesser $datagridGuesser,
        SecurityFacade $securityFacade
    ) {
        parent::__construct($configManager, $entityClassResolver, $datagridGuesser);

        $this->securityFacade = $securityFacade;
    }

    /**
     * {@inheritdoc}
     */
    protected function getFields(DatagridConfiguration $config)
    {
        $fields = parent::getFields($config);

        if (!empty($fields)) {
            $currentOrganizationId      = $this->securityFacade->getOrganizationId();
            $organizationConfigProvider = $this->configManager->getProvider('organization');

            $fields = array_filter(
                $fields,
                function ($fieldConfigId) use ($currentOrganizationId, $organizationConfigProvider) {
                    $fieldConfig = $organizationConfigProvider->getConfigById($fieldConfigId);
                    if ($fieldConfig->has('applicable')) {
                        $config = $fieldConfig->get('applicable');
                        if ($config['all'] === true || in_array($currentOrganizationId, $config['selective'])) {
                            return true;
                        }
                    }

                    return false;
                }
            );
        }

        return $fields;
    }
}
