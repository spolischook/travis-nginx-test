<?php

namespace Oro\Bundle\AccountProBundle\Tests\Unit\Datagrid;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\AccountProBundle\Datagrid\RolePermissionDatasource;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Model\AclPermission;
use Oro\Bundle\UserBundle\Entity\Role;

use OroB2B\Bundle\AccountBundle\Datagrid\RolePermissionDatasource as BaseRolePermissionDatasource;
use OroB2B\Bundle\AccountBundle\Tests\Unit\Datagrid\RolePermissionDatasourceTest as BaseTest;

class RolePermissionDatasourceTest extends BaseTest
{
    public function testGetResults()
    {
        $datasource = $this->getDatasource();
        $identity = 'entity:OroB2B\Bundle\AccountBundle\Entity\Account';

        $results = $this->retrieveResultsFromPermissionsDatasource($datasource, $identity);

        /** @var ResultRecord $record1 */
        $record1 = array_shift($results);

        /** @var ResultRecord $record2 */
        $record2 = array_shift($results);

        $this->assertInstanceOf(ResultRecord::class, $record1);
        $this->assertEquals($identity, $record1->getValue('identity'));
        $this->assertNotEmpty($record1->getValue('permissions'));

        $this->assertInstanceOf(ResultRecord::class, $record2);
        $this->assertEquals($identity, $record2->getValue('identity'));
        $this->assertEmpty($record2->getValue('permissions'));
    }

    /**
     * {@inheritdoc}
     */
    protected function getDatasource()
    {
        $datasource = new RolePermissionDatasource(
            $this->translator,
            $this->permissionManager,
            $this->aclRoleHandler,
            $this->categoryProvider,
            $this->configEntityManager,
            $this->roleTranslationPrefixResolver
        );
        $datasource->addExcludePermission('SHARE');

        return $datasource;
    }

    /**
     * {@inheritdoc}
     */
    protected function retrieveResultsFromPermissionsDatasource(BaseRolePermissionDatasource $datasource, $identity)
    {
        $role = new Role();

        $datasource->process($this->getDatagrid($role), []);

        $this->aclRoleHandler->expects($this->once())
            ->method('getAllPrivileges')
            ->with($role)
            ->willReturn(
                [
                    'action' => new ArrayCollection(
                        [
                            $this->getAclPrivilege('action:test_action', 'test', new AclPermission('test', 1))
                        ]
                    ),
                    'entity' => new ArrayCollection(
                        [
                            $this->getAclPrivilege(
                                $identity,
                                'VIEW',
                                new AclPermission('VIEW', AccessLevel::GLOBAL_LEVEL)
                            ),
                            $this->getAclPrivilege(
                                $identity,
                                'SHARE',
                                new AclPermission('SHARE', AccessLevel::GLOBAL_LEVEL)
                            )
                        ]
                    )
                ]
            );

        return $datasource->getResults();
    }
}
