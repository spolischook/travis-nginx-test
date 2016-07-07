<?php

namespace Oro\Bundle\WebsiteConfigProBundle\Provider;

use Oro\Bundle\ConfigBundle\Provider\SystemConfigurationFormProvider;

class WebsiteConfigurationFormProvider extends SystemConfigurationFormProvider
{
    const TREE_NAME  = 'website_configuration';

    /**
     * {@inheritdoc}
     */
    public function getTree()
    {
        return $this->getTreeData(self::TREE_NAME, self::CORRECT_FIELDS_NESTING_LEVEL);
    }

    /**
     * {@inheritdoc}
     */
    protected function getParentCheckboxLabel()
    {
        return 'orob2b.website_config.use_default';
    }
}
