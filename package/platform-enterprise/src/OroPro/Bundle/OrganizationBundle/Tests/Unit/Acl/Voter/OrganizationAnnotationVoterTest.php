<?php

namespace OroPro\Bundle\OrganizationBundle\Tests\Unit\Acl\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Metadata\AclAnnotationProvider;

use OroPro\Bundle\OrganizationBundle\Acl\Voter\OrganizationAnnotationVoter;

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

    /**
     * @param mixed $object
     * @param array $attributes
     * @param array $supportedAttributes
     * @param bool $expected
     *
     * @dataProvider voteDataProvider
     */
    public function testVote($object, array $attributes, array $supportedAttributes, $expected)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|TokenInterface $token */
        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');

        $this->annotationProvider->expects($this->any())
            ->method('findAnnotationById')
            ->with($this->isType('string'))
            ->will(
                $this->returnCallback(
                    function ($attribute) use ($supportedAttributes) {
                        return in_array($attribute, $supportedAttributes, true);
                    }
                )
            );

        $this->assertEquals($expected, $this->voter->vote($token, $object, $attributes));
    }

    /**
     * @return array
     */
    public function voteDataProvider()
    {
        return [
            [new \stdClass(), ['resource_1'], ['resource_1'], 0],
            [new \stdClass(), ['resource_1'], ['resource_2'], 0],
            [new Organization(), ['resource_1'], ['resource_2'], 0],
            [new Organization(), ['resource_1'], ['resource_1'], 1],
        ];
    }
}
