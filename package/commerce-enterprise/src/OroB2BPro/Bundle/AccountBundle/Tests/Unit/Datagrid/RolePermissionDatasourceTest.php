<?php

namespace OroB2BPro\Bundle\AccountBundle\Tests\Unit\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;

use OroB2B\Bundle\AccountBundle\Tests\Unit\Datagrid\RolePermissionDatasourceTestCase;

use OroB2BPro\Bundle\AccountBundle\Datagrid\RolePermissionDatasource;

class RolePermissionDatasourceTest extends RolePermissionDatasourceTestCase
{
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

    /**
     * {@inheritdoc}
     */
    protected function assertResults(array $results, $identity)
    {
        $this->assertCount(2, $results);
        
        foreach ($results as $record) {
            $this->assertInstanceOf(ResultRecord::class, $record);
            $this->assertStringStartsWith($identity, $record->getValue('identity'));
            $this->assertNotEmpty($record->getValue('permissions'));
        }
    }
}
