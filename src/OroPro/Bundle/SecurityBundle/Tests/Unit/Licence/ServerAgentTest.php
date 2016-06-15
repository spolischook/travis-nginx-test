<?php

namespace OroPro\Bundle\SecurityBundle\Tests\Unit\Licence;

use OroPro\Bundle\SecurityBundle\Licence\ServerAgent;

class ServerAgentTest extends \PHPUnit_Framework_TestCase
{
    const LICENCE = 'test_licence';

    /**
     * @var ServerAgent
     */
    protected $agent;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $sender;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    protected function setUp()
    {
        $this->sender = $this->getMockBuilder('OroPro\Bundle\SecurityBundle\Licence\Sender')
            ->disableOriginalConstructor()
            ->getMock();

        $this->registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');

        $this->agent = new ServerAgent(self::LICENCE, $this->sender, $this->registry);
    }

    protected function tearDown()
    {
        unset($this->registry);
        unset($this->agent);
    }

    public function testSendStatusInformation()
    {
        $totalUsers = 10;
        $activeUsers = 5;

        $userRepository = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\Repository\UserRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $userRepository->expects($this->any())->method('getUsersCount')
            ->will(
                $this->returnValueMap(
                    array(
                        array(null, $totalUsers),
                        array(true, $activeUsers),
                    )
                )
            );

        $this->registry->expects($this->once())->method('getRepository')->with('OroUserBundle:User')
            ->will($this->returnValue($userRepository));

        $this->sender->expects($this->once())->method('sendPost')->with($this->isType('string'), $this->isType('array'))
            ->will(
                $this->returnCallback(
                    function ($type, $data) use ($totalUsers, $activeUsers) {
                        $this->assertEquals('status_information', $type);

                        $this->assertArrayHasKey('licence', $data);
                        $this->assertArrayHasKey('total_users', $data);
                        $this->assertArrayHasKey('active_users', $data);
                        $this->assertArrayHasKey('timestamp', $data);
                        $this->assertArrayHasKey('datetime', $data);

                        $this->assertEquals(self::LICENCE, $data['licence']);
                        $this->assertEquals($totalUsers, $data['total_users']);
                        $this->assertEquals($activeUsers, $data['active_users']);
                        $this->assertNotEmpty($data['timestamp']);
                        $this->assertNotEmpty($data['datetime']);
                    }
                )
            );

        $this->agent->sendStatusInformation();
    }
}
