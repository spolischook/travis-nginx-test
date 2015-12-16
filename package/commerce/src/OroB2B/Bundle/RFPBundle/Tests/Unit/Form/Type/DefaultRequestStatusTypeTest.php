<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use OroB2B\Bundle\RFPBundle\Form\Type\DefaulRequestStatusType;

class DefaultRequestStatusTypeTest extends \PHPUnit_Framework_TestCase
{
    const REQUEST_STATUS_CLASS = 'OroB2B\Bundle\RFPBundle\Entity\RequestStatus';

    /**
     * @var DefaulRequestStatusType
     */
    protected $formType;

    /**
     * @var \OroB2B\Bundle\RFPBundle\Entity\RequestStatus[]
     */
    protected $choices;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->choices = [
            $this->getMock(self::REQUEST_STATUS_CLASS),
            $this->getMock(self::REQUEST_STATUS_CLASS),
        ];

        $repository = $this->getMockBuilder('OroB2B\Bundle\RFPBundle\Entity\Repository\RequestStatusRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $repository->expects($this->any())
            ->method('getNotDeletedStatuses')
            ->willReturn($this->choices);

        /** @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry $registry */
        $registry = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $registry->expects($this->any())
            ->method('getRepository')
            ->with(self::REQUEST_STATUS_CLASS)
            ->willReturn($repository);

        $this->formType = new DefaulRequestStatusType($registry);
        $this->formType->setRequestStatusClass(self::REQUEST_STATUS_CLASS);
    }

    /**
     * Test setDefaultOptions
     */
    public function testSetDefaultOptions()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|OptionsResolverInterface $resolver */
        $resolver = $this->getMockBuilder('Symfony\Component\OptionsResolver\OptionsResolverInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $resolver->expects($this->once())
            ->method('setDefaults')
            ->withAnyParameters();

        $this->formType->setDefaultOptions($resolver);
    }

    /**
     * Test getName
     */
    public function testGetName()
    {
        $this->assertEquals(DefaulRequestStatusType::NAME, $this->formType->getName());
    }

    /**
     * Test getParent
     */
    public function testGetParent()
    {
        $this->assertEquals('choice', $this->formType->getParent());
    }
}
