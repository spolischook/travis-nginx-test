<?php

namespace OroPro\Bundle\SecurityBundle\Form\Type;

use Oro\Bundle\SecurityBundle\Form\Type\ShareScopeType as BaseType;

use OroPro\Bundle\SecurityBundle\Form\Model\Share;

class ShareScopeType extends BaseType
{
    /**
     * {@inheritdoc}
     */
    protected function getChoices()
    {
        $choices = parent::getChoices();

        $choices[Share::SHARE_SCOPE_ORGANIZATION] = 'oro.security.share_scopes.organization.label';

        return $choices;
    }
}
