<?php

namespace OroPro\Bundle\OrganizationBundle\Twig;

use Oro\Bundle\OrganizationBundle\Twig\OrganizationExtension as BaseOrganizationExtension;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

class OrganizationProExtension extends BaseOrganizationExtension
{
    /**
     * {@inheritdoc}
     */
    public function getLoginOrganizations(\Twig_Environment $environment, $fieldName, $label, $showLabels)
    {
        $organizations = $this->entityManager->getRepository('OroOrganizationBundle:Organization')->getEnabled(
            null,
            ['is_global' => 'DESC']
        );

        $result = [];

        $hasGlobalOrg = $organizations[0]->getIsGlobal();
        foreach ($organizations as $org) {
            $orgName = $org->getName();
            if ($hasGlobalOrg && !$org->getIsGlobal()) {
                $orgName = '&nbsp;&nbsp;&nbsp;' . $orgName;
            }
            $result[] = [
                'id' => $org->getId(),
                'name' => $orgName
            ];
        }
        return $environment->loadTemplate(self::ORGANIZATION_INPUT_TEMPLATE)->render(
            [
                'organizations' => $result,
                'fieldName' => $fieldName,
                'label' => $label,
                'showLabels' => $showLabels
            ]
        );
    }
}
