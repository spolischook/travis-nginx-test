<?php

namespace OroPro\Bundle\OrganizationConfigBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

class ConfigurationController extends Controller
{
    /**
     * @Route(
     *      "/organization/{id}/{activeGroup}/{activeSubGroup}",
     *      name="oropro_organization_config",
     *      requirements={"id"="\d+"},
     *      defaults={"activeGroup" = null, "activeSubGroup" = null}
     * )
     * @Template()
     * @AclAncestor("oro_organization_update")
     */
    public function organizationConfigAction(Organization $entity, $activeGroup = null, $activeSubGroup = null)
    {
        $provider = $this->get('oropro_organization_config.provider.form_provider');

        list($activeGroup, $activeSubGroup) = $provider->chooseActiveGroups($activeGroup, $activeSubGroup);

        $tree = $provider->getTree();
        $form = false;

        if ($activeSubGroup !== null) {
            $form = $provider->getForm($activeSubGroup);

            $manager = $this->get('oro_config.organization');

            $prevScopeId = $manager->getScopeId();
            $manager->setScopeId($entity->getId());

            if ($this->get('oro_config.form.handler.config')
                ->setConfigManager($manager)
                ->process($form, $this->getRequest())
            ) {
                $this->get('session')->getFlashBag()->add(
                    'success',
                    $this->get('translator')->trans('oro.config.controller.config.saved.message')
                );

                // outdate content tags, it's only special case for generation that are not covered by NavigationBundle
                $taggableData = ['name' => 'organization_configuration', 'params' => [$activeGroup, $activeSubGroup]];
                $sender       = $this->get('oro_navigation.content.topic_sender');

                $sender->send($sender->getGenerator()->generate($taggableData));

                // recreate form to drop values for fields with use_parent_scope_value
                $form = $provider->getForm($activeSubGroup);
                $form->setData($manager->getSettingsByForm($form));
            }

            $manager->setScopeId($prevScopeId);
        }

        return array(
            'entity'         => $entity,
            'data'           => $tree,
            'form'           => $form ? $form->createView() : null,
            'activeGroup'    => $activeGroup,
            'activeSubGroup' => $activeSubGroup,
        );
    }
}
