<?php

namespace OroPro\Bundle\OrganizationBundle\Tests\Functional\Grid;

use Oro\Bundle\DataGridBundle\Tests\Functional\AbstractDatagridTestCase;

/**
 * @dbIsolation
 */
class OrganizationUsersGridTest extends AbstractDatagridTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->loadFixtures(['OroPro\Bundle\OrganizationBundle\Tests\Functional\Fixture\LoadOrganizationUsersData']);
    }

    /**
     * @dataProvider gridProvider
     *
     * @param array $requestData
     */
    public function testGrid($requestData)
    {
        $gridId = $requestData['gridParameters']['gridName'];
        $requestData['gridParameters'][$gridId]['organization_id'] = $this->getReference('test_organization')->getId();

        parent::testGrid($requestData);
    }

    /**
     * @return array
     */
    public function gridProvider()
    {
        return [
            'User grid'                                  => [
                [
                    'gridParameters'      => [
                        'gridName' => 'organization-users-grid',
                    ],
                    'gridFilters'         => [
                        'organization-users-grid[_filter][has_organization][value]' => 1,
                    ],
                    'assert'              => [
                        'has_organization' => true,
                        'firstName'        => 'test',
                        'lastName'         => 'user',
                        'username'         => 'test.user',
                        'email'            => 'test.user@email.com'
                    ],
                    'expectedResultCount' => 1
                ],
            ],
            'User grid with filters'                     => [
                [
                    'gridParameters'      => [
                        'gridName' => 'organization-users-grid'
                    ],
                    'gridFilters'         => [
                        'organization-users-grid[_filter][has_organization][value]' => 1,
                        'organization-users-grid[_filter][username][value]'         => 'test.user',
                    ],
                    'assert'              => [
                        'has_organization' => true,
                        'firstName'        => 'test',
                        'lastName'         => 'user',
                        'username'         => 'test.user',
                        'email'            => 'test.user@email.com'
                    ],
                    'expectedResultCount' => 1
                ],
            ],
            'User grid without result'                   => [
                [
                    'gridParameters'      => [
                        'gridName' => 'organization-users-grid'
                    ],
                    'gridFilters'         => [
                        'organization-users-grid[_filter][has_organization][value]' => 1,
                        'organization-users-grid[_filter][username][value]'         => 'nonexisting.user',
                    ],
                    'assert'              => [],
                    'expectedResultCount' => 0
                ],
            ],
            'Organization view User grid'                => [
                [
                    'gridParameters'      => [
                        'gridName' => 'organization-view-users-grid',
                    ],
                    'gridFilters'         => [],
                    'assert'              => [
                        'firstName' => 'test',
                        'lastName'  => 'user',
                        'username'  => 'test.user',
                        'email'     => 'test.user@email.com'
                    ],
                    'expectedResultCount' => 1
                ],
            ],
            'Organization view User grid with filters'   => [
                [
                    'gridParameters'      => [
                        'gridName' => 'organization-view-users-grid'
                    ],
                    'gridFilters'         => [
                        'organization-view-users-grid[_filter][email][value]' => 'test.user@email.com',
                    ],
                    'assert'              => [
                        'firstName' => 'test',
                        'lastName'  => 'user',
                        'username'  => 'test.user',
                        'email'     => 'test.user@email.com'
                    ],
                    'expectedResultCount' => 1
                ],
            ],
            'Organization view User grid without result' => [
                [
                    'gridParameters'      => [
                        'gridName' => 'organization-view-users-grid'
                    ],
                    'gridFilters'         => [
                        'organization-view-users-grid[_filter][username][value]' => 'nonexisting.user',
                    ],
                    'assert'              => [],
                    'expectedResultCount' => 0
                ],
            ],
        ];
    }
}
