<?php

namespace OroPro\Bundle\SecurityBundle\Tests\Unit\Form\Type;

use OroPro\Bundle\SecurityBundle\Form\Type\ShareType;

class ShareTypeTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityManager;

    /** @var ShareType */
    protected $type;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->type = new ShareType();
        $this->type->setEntityManager($this->entityManager);
    }

    public function testBuildForm()
    {
        $classMetadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $classMetadata->expects($this->once())
            ->method('getSingleIdentifierFieldName')
            ->willReturn('id');
        $this->entityManager->expects($this->once())
            ->method('getClassMetadata')
            ->willReturn($classMetadata);

        $builder = $this->getMockBuilder('Symfony\Component\Form\FormBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $builder->expects($this->at(0))->method('add')->with(
            'entityClass', 'hidden', ['required' => false]
        )->willReturn($builder);
        $builder->expects($this->at(1))->method('add')->with(
            'entityId', 'hidden', ['required' => false]
        )->willReturn($builder);
        $builder->expects($this->at(2))->method('add')->with(
            'users',
            'oro_user_organization_acl_multiselect',
            [
                'label' => 'oro.user.entity_plural_label',
            ]
        )->willReturn($builder);
        $builder->expects($this->at(3))->method('add')->with(
            'businessunits',
            'oro_business_unit_multiselect',
            [
                'label' => 'oro.organization.businessunit.entity_plural_label',
            ]
        )->willReturn($builder);
        $builder->expects($this->at(4))->method('add')->with(
            'organizations',
            'oro_organization_select',
            [
                'label' => 'oro.organization.entity_plural_label',
                'configs' => [
                    'placeholder' => 'oro.organization.form.choose_organization',
                    'allowClear' => true,
                    'multiple' => true,
                ]
            ]
        )->willReturn($builder);
        $child = $this->getMockBuilder('Symfony\Component\Form\FormBuilderInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $child->expects($this->once())
            ->method('addModelTransformer');
        $builder->expects($this->at(5))
            ->method('get')
            ->with('organizations')
            ->willReturn($child);
        $this->type->buildForm($builder, []);
    }
}
