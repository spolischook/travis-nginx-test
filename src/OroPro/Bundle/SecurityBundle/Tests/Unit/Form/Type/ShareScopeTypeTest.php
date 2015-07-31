<?php

namespace OroPro\Bundle\SecurityBundle\Tests\Unit\Form\Type;

use OroPro\Bundle\SecurityBundle\Form\Model\Share;
use OroPro\Bundle\SecurityBundle\Form\Type\ShareScopeType;

class ShareScopeTypeTest extends \PHPUnit_Framework_TestCase
{
    /** @var ShareScopeType */
    protected $type;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->type = new ShareScopeType();
    }

    public function testSetDefaultOptions()
    {
        $resolver = $this->getMockBuilder('Symfony\Component\OptionsResolver\OptionsResolverInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                [
                    'multiple' => true,
                    'expanded' => true,
                    'choices' => [
                        Share::SHARE_SCOPE_USER => 'oro.security.share_scopes.user.label',
                        Share::SHARE_SCOPE_BUSINESS_UNIT => 'oro.security.share_scopes.business_unit.label',
                        Share::SHARE_SCOPE_ORGANIZATION => 'oro.security.share_scopes.organization.label'
                    ]
                ]
            );
        $this->type->setDefaultOptions($resolver);
    }
}
