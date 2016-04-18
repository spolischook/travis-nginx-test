<?php

namespace Oro\Bundle\LayoutBundle\Layout\Block\Type;

use Oro\Component\Layout\Block\Type\AbstractContainerType;

class BodyType extends AbstractContainerType
{
    const NAME = 'body';

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
