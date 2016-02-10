<?php

namespace OroB2B\Bundle\ProductBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class QuickAddCopyPasteType extends AbstractType
{
    const NAME = 'orob2b_product_quick_add_copy_paste';
    const COPY_PASTE_FIELD_NAME = 'copyPaste';
    const FORMAT_REGEX = '/[^\s]+[\t\,]\s*?[0-9]+\.?[0-9]*/';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            self::COPY_PASTE_FIELD_NAME,
            'textarea',
            [
                'constraints' => [
                    new NotBlank(),
                    new Regex([
                        'pattern' => self::FORMAT_REGEX,
                        'message' => 'orob2b.product.frontend.quick_add.invalid_format'
                    ]),
                ],
                'label' => false,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
