<?php

namespace OroPro\Bundle\SecurityBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityExtendBundle\Migration\OroOptions;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\PrioritizedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroProSecurityBundle implements Migration, PrioritizedMigrationInterface
{
    protected static $entitiesShareScopesConfig = [
        'oro_tracking_website',
        'orocrm_account',
        'orocrm_call',
        'orocrm_campaign',
        'orocrm_campaign_email',
        'orocrm_case',
        'orocrm_contact',
        'orocrm_contactus_request',
        'orocrm_marketing_list',
        'orocrm_sales_lead',
        'orocrm_sales_opportunity',
        'orocrm_sales_funnel',
        'orocrm_task',
    ];

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        return 100;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->addShareEntityConfig($schema);
    }

    /**
     * Add share_scopes attribute to security entity config
     *
     * @param Schema $schema
     */
    public function addShareEntityConfig(Schema $schema)
    {
        $defaultShareScopes = ['user'];
        $options = new OroOptions();
        $options->append('security', 'share_scopes', $defaultShareScopes);

        foreach (self::$entitiesShareScopesConfig as $entityName) {
            $this->addOptionToTable($schema, $entityName, $options);
        }

        $options = new OroOptions();
        $options->set('security', 'share_grid', 'share-with-users-datagrid');
        $this->addOptionToTable($schema, 'oro_user', $options);

        $options = new OroOptions();
        $options->set('security', 'share_grid', 'share-with-business-units-datagrid');
        $this->addOptionToTable($schema, 'oro_business_unit', $options);

        $options = new OroOptions();
        $options->set('security', 'share_grid', 'share-with-organizations-datagrid');
        $this->addOptionToTable($schema, 'oro_organization', $options);
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
