<?php

namespace OroPro\Bundle\SecurityBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityExtendBundle\Migration\OroOptions;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroProSecurityBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $defaultShareScopes = ['user'];

        $options = new OroOptions();
        $options->append('security', 'share_scopes', $defaultShareScopes);

        $this->addOptionToTable($schema, 'oro_tracking_website', $options);
        $this->addOptionToTable($schema, 'orocrm_account', $options);
        $this->addOptionToTable($schema, 'orocrm_call', $options);
        $this->addOptionToTable($schema, 'orocrm_campaign', $options);
        $this->addOptionToTable($schema, 'orocrm_campaign_email', $options);
        $this->addOptionToTable($schema, 'orocrm_case', $options);
        $this->addOptionToTable($schema, 'orocrm_contact', $options);
        $this->addOptionToTable($schema, 'orocrm_contactus_request', $options);
        $this->addOptionToTable($schema, 'orocrm_marketing_list', $options);
        $this->addOptionToTable($schema, 'orocrm_sales_lead', $options);
        $this->addOptionToTable($schema, 'orocrm_sales_opportunity', $options);
        $this->addOptionToTable($schema, 'orocrm_sales_funnel', $options);
        $this->addOptionToTable($schema, 'orocrm_task', $options);
    }

    /**
     * @param Schema $schema
     * @param string $name
     * @param OroOptions $options
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    protected function addOptionToTable(Schema $schema, $name, OroOptions $options)
    {
        $table = $schema->getTable($name);
        $table->addOption(OroOptions::KEY, $options);
    }
}
