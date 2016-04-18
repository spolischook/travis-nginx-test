<?php

namespace OroPro\Bundle\OrganizationConfigBundle\Provider;

use Oro\Bundle\ConfigBundle\Provider\SystemConfigurationFormProvider;

class OrganizationConfigurationFormProvider extends SystemConfigurationFormProvider
{
    const ORGANIZATION_TREE_NAME  = 'organization_configuration';

    /**
     * {@inheritdoc}
     */
    public function getTree()
    {
        return $this->getTreeData(self::ORGANIZATION_TREE_NAME, self::CORRECT_FIELDS_NESTING_LEVEL);
    }

    /**
     * {@inheritdoc}
     */
    protected function getParentCheckboxLabel()
    {
        return 'oropro.organization_config.use_default';
    }
}
