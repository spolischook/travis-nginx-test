<?php

namespace OroPro\Bundle\OrganizationBundle\Form\Handler;

use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Acl\Extension\EntityMaskBuilder;
use Oro\Bundle\SecurityBundle\Form\Handler\ShareHandler as BaseHandler;
use Oro\Bundle\SecurityBundle\Form\Model\Share as BaseShareModel;

use OroPro\Bundle\SecurityBundle\Form\Model\Share;
use OroPro\Bundle\SecurityBundle\Acl\Domain\OrganizationSecurityIdentity;

class ShareHandler extends BaseHandler
{
    /**
     * {@inheritdoc}
     */
    protected function isSidApplicable(SecurityIdentityInterface $sid)
    {
        if (parent::isSidApplicable($sid)) {
            return true;
        } else {
            return $sid instanceof OrganizationSecurityIdentity &&
                    in_array(Share::SHARE_SCOPE_ORGANIZATION, $this->shareScopes, true);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function generateSids(BaseShareModel $model)
    {
        $newSids = parent::generateSids($model);
        /** @var Share $model */
        foreach ($model->getEntities() as $entity) {
            if ($entity instanceof Organization) {
                $newSids[] = OrganizationSecurityIdentity::fromOrganization($entity);
            }
        }

        return $newSids;
    }

    /**
     * {@inheritdoc}
     */
    protected function getMaskBySid(SecurityIdentityInterface $sid)
    {
        $mask = parent::getMaskBySid($sid);

        if ($mask === 0) {
            if ($sid instanceof OrganizationSecurityIdentity) {
                return EntityMaskBuilder::MASK_VIEW_GLOBAL;
            }
        }

        return $mask;
    }
}
