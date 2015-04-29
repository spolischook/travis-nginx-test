<?php

namespace OroPro\Bundle\OrganizationBundle\Tests\Unit\Acl\Voter;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Metadata\AclAnnotationProvider;

use OroPro\Bundle\OrganizationBundle\Acl\Voter\OrganizationAnnotationVoter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class OrganizationAnnotationVoterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OrganizationAnnotationVoter
     */
    protected $voter;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|AclAnnotationProvider
     */
    protected $annotationProvider;

    protected function setUp()
    {
        $this->annotationProvider = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Metadata\AclAnnotationProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->voter = new OrganizationAnnotationVoter(
            $this->annotationProvider,
            'Oro\Bundle\OrganizationBundle\Entity\Organization'
        );
    }

    protected function tearDown()
    {
        unset($this->annotationProvider, $this->voter);
    }

    /**
     * @param array $attributes
     * @param string $attribute
     * @param bool $expected
     *
     * @dataProvider attributeDataProvider
     */
    public function testSupportsAttribute(array $attributes, $attribute, $expected)
    {
        $this->annotationProvider->expects($this->any())
            ->method('findAnnotationById')
            ->with($this->equalTo($attribute))
            ->will(
                $this->returnCallback(
                    function ($attribute) use ($attributes) {
                        return in_array($attribute, $attributes, true);
                    }
                )
            );

        $this->assertEquals($expected, $this->voter->supportsAttribute($attribute));
    }

    /**
     * @return array
     */
    public function attributeDataProvider()
    {
        return [
            [['resource_1', 'resource_2'], 'resource_0', false],
            [['resource_1', 'resource_2'], 'resource_1', true],
            [['resource_1', 'resource_2'], 'resource_2', true],
        ];
    }

    /**
     * @param string $className
     * @param bool $expected
     *
     * @dataProvider classDataProvider
     */
    public function testSupportsClass($className, $expected)
    {
        $this->assertEquals($expected, $this->voter->supportsClass($className));
    }

    /**
     * @return array
     */
    public function classDataProvider()
    {
        return [
            ['\stdClass', false],
            [null, false],
            ['Oro\Bundle\OrganizationBundle\Entity\Organization', true],
        ];
    }

    public function testVote()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|TokenInterface $token */
        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $this->assertEquals(1, $this->voter->vote($token, new Organization(), []));
    }
}
