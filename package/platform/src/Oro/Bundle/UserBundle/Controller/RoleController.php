<?php

namespace Oro\Bundle\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

/**
 * @Route("/role")
 */
class RoleController extends Controller
{
    /**
     * @Acl(
     *      id="oro_user_role_create",
     *      type="entity",
     *      class="OroUserBundle:Role",
     *      permission="CREATE"
     * )
     * @Route("/create", name="oro_user_role_create")
     * @Template("OroUserBundle:Role:update.html.twig")
     */
    public function createAction()
    {
        return $this->update(new Role());
    }

    /**
     * @Acl(
     *      id="oro_user_role_update",
     *      type="entity",
     *      class="OroUserBundle:Role",
     *      permission="EDIT"
     * )
     * @Route("/update/{id}", name="oro_user_role_update", requirements={"id"="\d+"}, defaults={"id"=0})
     * @Template
     */
    public function updateAction(Role $entity)
    {
        return $this->update($entity);
    }

    /**
     * @Route(
     *      "/clone/{id}",
     *      name="oro_user_role_clone",
     *      requirements={"id"="\d+"}
     * )
     * @AclAncestor("oro_user_role_create")
     * @Template("OroUserBundle:Role:update.html.twig")
     *
     * @param Role $entity
     * @return array
     */
    public function cloneAction(Role $entity)
    {
        /** @var TranslatorInterface $translator */
        $translator = $this->get('translator');
        $clonedLabel = $translator->trans('oro.user.role.clone.label', array('%name%' => $entity->getLabel()));

        $clonedRole = clone $entity;
        $clonedRole->setLabel($clonedLabel);

        return $this->update($clonedRole);
    }

    /**
     * @Route(
     *      "/{_format}",
     *      name="oro_user_role_index",
     *      requirements={"_format"="html|json"},
     *      defaults={"_format" = "html"}
     * )
     * @Acl(
     *      id="oro_user_role_view",
     *      type="entity",
     *      class="OroUserBundle:Role",
     *      permission="VIEW"
     * )
     * @Template
     */
    public function indexAction(Request $request)
    {
        return array(
            'entity_class' => $this->container->getParameter('oro_user.role.entity.class')
        );
    }

    /**
     * @param Role $entity
     * @return array
     */
    protected function update(Role $entity)
    {
        $aclRoleHandler = $this->get('oro_user.form.handler.acl_role');
        $aclRoleHandler->createForm($entity);

        if ($aclRoleHandler->process($entity)) {
            $this->get('session')->getFlashBag()->add(
                'success',
                $this->get('translator')->trans('oro.user.controller.role.message.saved')
            );

            return $this->get('oro_ui.router')->redirectAfterSave(
                ['route' => 'oro_user_role_update', 'parameters' => ['id' => $entity->getId()]],
                ['route' => 'oro_user_role_index'],
                $entity
            );
        }
        $categories = [
            'account_management' => [
                'label' => 'Account Management',
                'system' => false
            ],
            'marketing' => [
                'label' => 'Marketing',
                'system' => false
            ],
            'sales_data' => [
                'label' => 'Sales Data',
                'system' => false
            ],
            'address' => [
                'label' => 'Address',
                'system' => true
            ],
            'calendar' => [
                'label' => 'Calendar',
                'system' => true
            ]
        ];
        // @todo: redevelop it as grid
        $form = $aclRoleHandler->createView();
        $translator = $this->get('translator');
        $permissionManager = $this->get('oro_security.acl.permission_manager');

        $form->children['entity']->children;
        $gridData = [];
        foreach ($form->children['entity']->children as $child) {
            $identity = $child->children['identity'];
            $item = [
                'entity' => $translator->trans($identity->children['name']->vars['value']),
                'identity' => $identity->children['id']->vars['value'],
                'permissions' => []
            ];
            // all data transformation are taken from form type blocks
            foreach ($child->vars['privileges_config']['permissions'] as $field) {
                foreach ($child->children['permissions']->children as $permission) {
                    if ($permission->vars['value']->getName() === $field) {
                        $accessLevelVars = $permission->children['accessLevel']->vars;
                        $permissionEntity = $permissionManager->getPermissionByName($field);
                        $permissionLabel = $permissionEntity->getLabel() ? $permissionEntity->getLabel() : $field;
                        $permissionDescription = '';
                        if ($permissionEntity->getDescription()) {
                            $permissionDescription = $translator->trans($permissionEntity->getDescription());
                        }
                        $valueText = $accessLevelVars['translation_prefix'] .
                            (empty($accessLevelVars['level_label']) ? 'NONE' : $accessLevelVars['level_label']);
                        $valueText = $translator->trans($valueText, [], $accessLevelVars['translation_domain']);
                        $item['permissions'][] = [
                            'id' => $permissionEntity->getId(),
                            'name' => $permissionEntity->getName(),
                            'label' => $translator->trans($permissionLabel),
                            'description' => $permissionDescription,
                            'full_name' => $accessLevelVars['full_name'],
                            'identity' => $accessLevelVars['identity'],
                            'value' => $accessLevelVars['value'],
                            'value_text' => $valueText
                        ];
                        break;
                    }
                }
            }

            $gridData[] = $item;
        }
        $capabilitiesData = [];
        foreach ($form->children['action']->children as $action_id => $child) {
            $permissions = reset($child->children['permissions']->children)->vars['value'];
            $capabilitiesData[] = [
                'id' => $action_id,
                'identityId' => $child->children['identity']->children['id']->vars['value'],
                'label' => $translator->trans($child->children['identity']->children['name']->vars['value']),
                'permissionName' => $permissions->getName(),
                'accessLevel' => $permissions->getAccessLevel()
            ];
        }

        foreach($gridData as $index => &$gridDataItem) {
            $gridDataItem['group'] = ['account_management', 'marketing', 'sales_data', null][$index % 4];
        }
        foreach($capabilitiesData as $index => &$capabilitiesDataItem) {
            $capabilitiesDataItem['group'] =
                ['account_management', 'marketing', 'sales_data', 'address', 'calendar'][$index % 5];
        }

        return array(
            'entity' => $entity,
            'form' => $form,
            'categories' => $categories,
            'gridData' => $gridData,
            'capabilitiesData' => $capabilitiesData,
            'privilegesConfig' => $this->container->getParameter('oro_user.privileges'),
            // TODO: it is a temporary solution. In a future it is planned to give an user a choose what to do:
            // completely delete a role and un-assign it from all users or reassign users to another role before
            'allow_delete' =>
                $entity->getId() &&
                !$this->get('doctrine.orm.entity_manager')
                    ->getRepository('OroUserBundle:Role')
                    ->hasAssignedUsers($entity)
        );
    }
}
