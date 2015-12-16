<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroEntitySelectOrCreateInlineType;

use OroB2B\Bundle\AccountBundle\Form\Type\AccountSelectType;

class AccountSelectTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AccountSelectType
     */
    protected $type;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->type = new AccountSelectType();
    }

    public function testGetName()
    {
        $this->assertEquals(AccountSelectType::NAME, $this->type->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals(OroEntitySelectOrCreateInlineType::NAME, $this->type->getParent());
    }

    public function testConfigureOptions()
    {
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->isType('array'))
            ->willReturnCallback(
                function (array $options) {
                    $this->assertArrayHasKey('autocomplete_alias', $options);
                    $this->assertArrayHasKey('create_form_route', $options);
                    $this->assertArrayHasKey('configs', $options);
                    $this->assertEquals('orob2b_account', $options['autocomplete_alias']);
                    $this->assertEquals('orob2b_account_create', $options['create_form_route']);
                    $this->assertEquals(['placeholder' => 'orob2b.account.form.choose'], $options['configs']);
                }
            );

        $this->type->configureOptions($resolver);
    }
}
