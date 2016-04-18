<?php

namespace OroPro\Bundle\OrganizationBundle\Twig;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManager;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

use OroPro\Bundle\OrganizationBundle\Helper\OrganizationProHelper;

class OrganizationExtension extends \Twig_Extension
{
    const NAME = 'oropro_organization';

    /** @var  Registry */
    protected $doctrine;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var ConfigProvider */
    protected $organizationProvider;

    /** @var OrganizationProHelper */
    protected $organizationHelper;

    /**
     * @param ConfigProvider        $organizationProvider
     * @param Registry              $doctrine
     * @param TranslatorInterface   $translator
     * @param OrganizationProHelper $organizationHelper
     */
    public function __construct(
        ConfigProvider $organizationProvider,
        Registry $doctrine,
        TranslatorInterface $translator,
        OrganizationProHelper $organizationHelper
    ) {
        $this->doctrine             = $doctrine;
        $this->translator           = $translator;
        $this->organizationProvider = $organizationProvider;
        $this->organizationHelper   = $organizationHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('oropro_applicable_organizations', [$this, 'getApplicable']),
            new \Twig_SimpleFunction('oropro_global_org_id', [$this, 'getGlobalOrgId']),
        ];
    }

    /**
     * Return current system global organization id. If the system has no global organization - returns null
     *
     * @return int|null
     */
    public function getGlobalOrgId()
    {
        return $this->organizationHelper->getGlobalOrganizationId();
    }

    /**
     * Used to render configuration value "applicable" from "organization" configuration scope.
     * Due to restriction that configuration property which you want to show on grid should be indexed,
     * and complicated config form type "oro_type_choice_organization_type" that stores multiple values.
     *
     * @param string|array $value
     * @param string|null  $className
     *
     * @return string
     */
    public function getApplicable($value, $className = null)
    {
        $result = $this->translator->trans('oropro.organization.datagrid.applicable_none');

        $data = is_array($value) ? $value : json_decode($value, true);
        if (!is_array($data)) {
            return $result;
        } elseif ($data['all'] === true) {
            return $this->translator->trans('oropro.organization.datagrid.applicable_all');
        } elseif (!empty($data['selective'])) {
            $selected = $this->filterByParentEntity($data['selective'], $className);
            if (empty($selected)) {
                return $result;
            }

            $result = [];

            /** @var EntityManager $em */
            $em = $this->doctrine->getManager();

            $expr     = Criteria::expr();
            $criteria = Criteria::create();
            $criteria->where($expr->in('id', $selected));

            /** @var Organization[] $organizations */
            $organizations = $em->getRepository('OroOrganizationBundle:Organization')->matching($criteria);
            foreach ($organizations as $organization) {
                $result[] = $organization->getName();
            }
            $result = implode(', ', $result);
        }

        return $result;
    }

    /**
     * @param array       $selected
     * @param string|null $className
     *
     * @return array
     */
    protected function filterByParentEntity(array $selected = [], $className = null)
    {
        if (!empty($selected) && $className) {
            $applicable = $this->organizationProvider->getConfig($className)->get('applicable');
            if ($applicable && !$applicable['all']) {
                $selectedInOrganization = $applicable['selective'];
                $selected               = array_filter(
                    $selected,
                    function ($id) use ($selectedInOrganization) {
                        return in_array($id, $selectedInOrganization);
                    }
                );
            }
        }

        return $selected;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
