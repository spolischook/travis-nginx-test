<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Stub;

use Oro\Bundle\UserBundle\Form\Type\ChangePasswordType;

use Symfony\Component\Form\FormBuilderInterface;

class ChangePasswordTypeStub extends ChangePasswordType
{
    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
    }
}
