<?php

namespace Oro\Bundle\AccountProBundle\Tests\Unit\Datagrid;

use Oro\Bundle\AccountProBundle\Datagrid\RolePermissionDatasource;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;

use OroB2B\Bundle\AccountBundle\Tests\Unit\Datagrid\RolePermissionDatasourceTestCase;

class RolePermissionDatasourceTest extends RolePermissionDatasourceTestCase
{
    public function testGetResults()
    {
        $datasource = $this->getDatasource();
        $identity = 'entity:OroB2B\Bundle\AccountBundle\Entity\Account';

        $results = $this->retrieveResultsFromPermissionsDatasource($datasource, $identity);

        $this->assertCount(2, $results);

        foreach ($results as $record) {
            $this->assertInstanceOf(ResultRecord::class, $record);
            $this->assertStringStartsWith($identity, $record->getValue('identity'));
            $this->assertNotEmpty($record->getValue('permissions'));
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getDatasource()
    {
        return new RolePermissionDatasource(
            $this->translator,
            $this->permissionManager,
            $this->aclRoleHandler,
            $this->categoryProvider,
            $this->configEntityManager,
            $this->roleTranslationPrefixResolver
        );
    }
}
