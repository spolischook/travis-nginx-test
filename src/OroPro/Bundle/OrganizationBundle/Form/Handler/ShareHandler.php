<?php

namespace OroPro\Bundle\OrganizationBundle\Form\Handler;

use Symfony\Component\Security\Acl\Domain\Entry;
use Symfony\Component\Security\Acl\Model\AclInterface;
use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface;

use Oro\Bundle\OrganizationBundle\Entity\Repository\OrganizationRepository;
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
    protected function prepareForm($entity)
    {
        parent::prepareForm($entity);

        if (!in_array('organization', $this->shareScopes, true)) {
            $this->form->remove('organizations');
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function applyEntities(BaseShareModel $model, AclInterface $acl = null)
    {
        if (!$acl) {
            return;
        }

        parent::applyEntities($model, $acl);

        $orgIds = [];
        foreach ($acl->getObjectAces() as $ace) {
            /** @var $ace Entry */
            $securityIdentity = $ace->getSecurityIdentity();
            if ($securityIdentity instanceof OrganizationSecurityIdentity) {
                $orgIds[] = $securityIdentity->getId();
            }
        }

        if ($orgIds) {
            /** @var $repo OrganizationRepository */
            $repo = $this->manager->getRepository('OroOrganizationBundle:Organization');
            $organizations = $repo->getEnabledOrganizations($orgIds);
            /** @var Share $model */
            $model->setOrganizations($organizations);
            $this->form->setData($model);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function isSidApplicable(SecurityIdentityInterface $sid)
    {
        if (parent::isSidApplicable($sid)) {
            return true;
        } else {
            return $this->form->has('organizations') &&
                    $sid instanceof OrganizationSecurityIdentity &&
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
        $organizations = $model->getOrganizations();
        foreach ($organizations as $organization) {
            $newSids[] = OrganizationSecurityIdentity::fromOrganization($organization);
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
