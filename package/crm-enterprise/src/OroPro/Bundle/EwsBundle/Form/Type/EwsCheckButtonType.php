<?php

namespace OroPro\Bundle\EwsBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class EwsCheckButtonType
 * @package OroPro\Bundle\EwsBundle\Form\Type
 */
class EwsCheckButtonType extends ButtonType
{
    const NAME = 'oro_pro_ews_check_button';

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        parent::setDefaultOptions($resolver);

        $resolver->setDefaults(['attr' => ['class' => 'btn btn-primary']]);
    }
}
