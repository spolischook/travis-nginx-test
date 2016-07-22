<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\EventListener\Datagrid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\SecurityBundle\SecurityFacade;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\EventListener\Datagrid\AccountUserRoleDatagridListener;

class DatagridListenerFrontendTest extends \PHPUnit_Framework_TestCase
{
    const USER_ID = 1;
    const ACCOUNT_ID = 1;

    /**
     * @var AccountUserRoleDatagridListener
     */
    protected $listener;

    /**
     * @var SecurityFacade|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $securityFacade;

    /**
     * @var array
     */
    protected $expectedDataForWorse = [
        'source' => [
            'query' => [
                'where' => [
                    'and' => [
                        '1=0'
                    ]
                ]
            ]
        ]
    ];

    protected function setUp()
    {
        $this->securityFacade = $this->getMockBuilder('\Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new AccountUserRoleDatagridListener($this->securityFacade);
    }

    protected function tearDown()
    {
        unset($this->listener, $this->securityFacade);
    }

    /**
     * @param bool     $isGranted
     * @param null|int $user
     * @param bool     $hasAccount
     * @param array    $expected
     * @dataProvider   onBuildBeforeProvider
     */
    public function testOnBuildBefore($isGranted = false, $user = null, $hasAccount = true, array $expected = [])
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|DatagridInterface $datagrid */
        $datagrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $config = DatagridConfiguration::create([]);

        if ($user) {
            $this->securityFacade->expects($this->once())
                ->method('isGranted')
                ->with('orob2b_account_frontend_account_user_role_view')
                ->willReturn($isGranted);
        }

        if ($hasAccount) {
            $this->mockUser($user);
        }

        $event = new BuildBefore($datagrid, $config);
        $this->listener->onBuildBefore($event);

        $this->assertEquals($expected, $config->toArray());
    }

    /**
     * @return array
     */
    public function onBuildBeforeProvider()
    {
        return [
            'when user have permissions' => [
                'isGranted' => true,
                'user' => static::USER_ID,
                'hasAccount' => true,
                'expected' => [
                    'source' => [
                        'query' => [
                            'where' => [
                                'and' => [
                                    'role.account IN (' . static::USER_ID . ') or role.account IS NULL'
                                ],
                            ]
                        ]
                    ]
                ]
            ],
            'when user have not permissions' => [
                'isGranted' => false,
                'user' => static::USER_ID,
                'hasAccount' => true,
                'expected' => $this->expectedDataForWorse
            ],
            'when user not logged' => [
                'isGranted' => false,
                'user' => false,
                'hasAccount' => true,
                'expected' => $this->expectedDataForWorse
            ],
            'when user not logged and have no token' => [
                'isGranted' => true,
                'user' => false,
                'hasAccount' => false,
                'expected' => $this->expectedDataForWorse
            ]
        ];
    }

    /**
     * @param string $className
     * @param int $id
     * @return object
     */
    protected function getEntity($className, $id)
    {
        $entity = new $className;
        $reflectionClass = new \ReflectionClass($className);
        $method = $reflectionClass->getProperty('id');
        $method->setAccessible(true);
        $method->setValue($entity, $id);
        return $entity;
    }

    /**
     * @param int $userId
     */
    protected function mockUser($userId)
    {
        /** @var AccountUser $user */
        $user = $this->getEntity('\OroB2B\Bundle\AccountBundle\Entity\AccountUser', $userId);

        /** @var Account $account */
        $account = $this->getEntity('\OroB2B\Bundle\AccountBundle\Entity\Account', static::ACCOUNT_ID);
        $user->setAccount($account);

        $this->securityFacade->expects($this->any())
            ->method('getLoggedUser')
            ->willReturn($userId ? $user : null);
    }
}
