<?php

namespace Oro\Bundle\ActivityBundle\Tests\Unit\Form\Type;

use Genemu\Bundle\FormBundle\Form\JQuery\Type\Select2Type;

use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Form\PreloadedExtension;

use Oro\Bundle\ActivityBundle\Form\Type\ContextsSelectType;

class ContextsSelectTypeTest extends TypeTestCase
{
    /** \PHPUnit_Framework_MockObject_MockObject */
    protected $em;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $translator;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $mapper;

    /* @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityTokenStorage;

    protected function setUp()
    {
        parent::setUp();
        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->translator = $this->getMockBuilder('Symfony\Component\Translation\DataCollectorTranslator')
            ->disableOriginalConstructor()
            ->getMock();

        $this->mapper = $this->getMockBuilder('Oro\Bundle\SearchBundle\Engine\ObjectMapper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->securityTokenStorage =
            $this->getMockBuilder('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface')
                ->disableOriginalConstructor()
                ->getMock();
    }

    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                    'genemu_jqueryselect2_hidden' => new Select2Type('hidden')
                ],
                []
            )
        ];
    }

    public function testBuildForm()
    {
        $builder = $this->getMock('Symfony\Component\Form\FormBuilderInterface');
        $builder->expects($this->once())
            ->method('addViewTransformer');
        $type = new ContextsSelectType(
            $this->em,
            $this->configManager,
            $this->translator,
            $this->mapper,
            $this->securityTokenStorage
        );
        $type->buildForm($builder, []);
    }

    public function testSetDefaultOptions()
    {
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                [
                    'tooltip' => false,
                    'configs' => [
                        'placeholder'        => 'oro.activity.contexts.placeholder',
                        'allowClear'         => true,
                        'multiple'           => true,
                        'separator'          => ';',
                        'forceSelectedData'  => true,
                        'minimumInputLength' => 0,
                    ]
                ]
            );

        $type = new ContextsSelectType(
            $this->em,
            $this->configManager,
            $this->translator,
            $this->mapper,
            $this->securityTokenStorage
        );
        $type->setDefaultOptions($resolver);
    }

    public function testGetParent()
    {
        $type = new ContextsSelectType(
            $this->em,
            $this->configManager,
            $this->translator,
            $this->mapper,
            $this->securityTokenStorage
        );
        $this->assertEquals('genemu_jqueryselect2_hidden', $type->getParent());

    }

    public function testGetName()
    {
        $type = new ContextsSelectType(
            $this->em,
            $this->configManager,
            $this->translator,
            $this->mapper,
            $this->securityTokenStorage
        );
        $this->assertEquals('oro_activity_contexts_select', $type->getName());
    }
}
