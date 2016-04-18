<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\UserBundle\Entity\User;

use OroB2B\Bundle\AccountBundle\Datagrid\ActionPermissionProvider;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;

class ActionPermissionProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ActionPermissionProvider
     */
    protected $actionPermissionProvider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ResultRecordInterface
     */
    protected $record;

    /**
     * @var array
     */
    protected $actionsList = [
        'enable',
        'disable',
        'view',
        'update',
        'delete'
    ];

    /**
     * @var array
     */
    protected $accountUserRoleActionList = [
        'view',
        'update',
        'delete'
    ];

    /** @var \PHPUnit_Framework_MockObject_MockObject|SecurityFacade */
    protected $securityFacade;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->record = $this->getMock('Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface');
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();
        $this->actionPermissionProvider = new ActionPermissionProvider($this->securityFacade);
    }

    /**
     * @param boolean $isRecordEnabled
     * @param array $expected
     * @param $user
     * @dataProvider recordConditions
     */
    public function testGetRequestStatusDefinitionPermissions($isRecordEnabled, array $expected, $user)
    {
        $this->record->expects($this->any())
            ->method('getValue')
            ->with($this->isType('string'))
            ->willReturn($isRecordEnabled);

        $this->securityFacade->expects($this->once())->method('getLoggedUser')->willReturn($user);
        $result = $this->actionPermissionProvider->getUserPermissions($this->record);

        $this->assertCount(count($this->actionsList), $result);
        foreach ($this->actionsList as $action) {
            $this->assertArrayHasKey($action, $result);
        }

        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function recordConditions()
    {
        return [
            'enabled record' => [
                'isRecordEnabled' => true,
                'expected' => [
                    'enable' => false,
                    'disable' => true,
                    'view' => true,
                    'update' => true,
                    'delete' => true
                ],
                'user' => new AccountUser()
            ],
            'disabled record' => [
                'isRecordEnabled' => false,
                'expected' => [
                    'enable' => true,
                    'disable' => false,
                    'view' => true,
                    'update' => true,
                    'delete' => true
                ],
                'user' => new User()
            ]
        ];
    }

    /**
     * @param boolean  $isRolePredefined
     * @param boolean  $isGranted
     * @param array    $expected
     *
     * @dataProvider getAccountUserRolePermissionProvider
     */
    public function testGetAccountUserRolePermission($isRolePredefined, $isGranted, array $expected)
    {
        $this->record->expects($this->any())
            ->method('getValue')
            ->with($this->isType('string'))
            ->willReturn($isRolePredefined);

        $this->securityFacade->expects($isRolePredefined ? $this->once() : $this->never())
            ->method('isGranted')
            ->with($this->isType('string'))
            ->willReturn($isGranted);

        $result = $this->actionPermissionProvider->getAccountUserRolePermission($this->record);

        $this->assertCount(count($this->accountUserRoleActionList), $result);

        foreach ($this->accountUserRoleActionList as $action) {
            $this->assertArrayHasKey($action, $result);
        }

        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function getAccountUserRolePermissionProvider()
    {
        return [
            'user have permission to create and role is predefined' => [
                'isRolePredefined' => true,
                'isGranted' => true,
                'expected' => [
                    'view' => true,
                    'update' => true,
                    'delete' => false
                ]
            ],
            'user have no permission to create and role is predefined' => [
                'isRolePredefined' => true,
                'isGranted' => false,
                'expected' => [
                    'view' => true,
                    'update' => false,
                    'delete' => false
                ]
            ],
            'user have no permission to create and role is no predefined' => [
                'isRolePredefined' => false,
                'isGranted' => false,
                'expected' => [
                    'view' => true,
                    'update' => true,
                    'delete' => true
                ]
            ],
        ];
    }
}
