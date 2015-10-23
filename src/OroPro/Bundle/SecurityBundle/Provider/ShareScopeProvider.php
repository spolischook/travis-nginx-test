<?php

namespace OroPro\Bundle\SecurityBundle\Provider;

use Oro\Bundle\SecurityBundle\Provider\ShareScopeProvider as BaseShareScopeProvider;

use OroPro\Bundle\SecurityBundle\Form\Model\Share;

class ShareScopeProvider extends BaseShareScopeProvider
{
    /**
     * {@inheritdoc}
     */
    public function getClassNamesBySharingScopes(array $shareScopes)
    {
        $result = parent::getClassNamesBySharingScopes($shareScopes);

        foreach ($shareScopes as $shareScope) {
            if ($shareScope === Share::SHARE_SCOPE_ORGANIZATION) {
                array_unshift($result, 'Oro\Bundle\OrganizationBundle\Entity\Organization');
            }
        }

        return $result;
    }
}
