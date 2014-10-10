<?php

namespace OroPro\Bundle\OrganizationBundle\Twig;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TranslationBundle\Translation\Translator;

class OrganizationExtension extends \Twig_Extension
{
    const NAME = 'oropro_organization';

    /** @var  Registry */
    protected $doctrine;

    /** @var Translator */
    protected $translator;

    /** @var ConfigProvider */
    private $organizationProvider;

    /**
     * @param ConfigProvider $organizationProvider
     * @param Registry       $doctrine
     * @param Translator     $translator
     */
    public function __construct(
        ConfigProvider $organizationProvider,
        Registry $doctrine,
        Translator $translator
    ) {
        $this->doctrine             = $doctrine;
        $this->translator           = $translator;
        $this->organizationProvider = $organizationProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('oropro_applicable_organizations', array($this, 'getApplicable')),
        );
    }

    /**
     * Used to render configuration value "applicable" from "organization" configuration scope.
     * Due to restriction that configuration property which you want to show on grid should be indexed,
     * and complicated entity config form type "oro_type_choice_organization_type" that stores multiple values.
     *
     * @param string      $value
     * @param string|null $className
     *
     * @return string
     */
    public function getApplicable($value, $className = null)
    {
        $result = $this->translator->trans('oropro.organization.datagrid.applicable_none');

        /** @var \stdClass $data */
        $data = json_decode($value);
        if (!is_object($data)) {
            return $result;
        } elseif ($data->all === true) {
            return $this->translator->trans('oropro.organization.datagrid.applicable_all');
        } elseif (!empty($data->selective)) {
            $selected = $this->filterByParentEntity($data->selective, $className);

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
     * @param array $selected
     * @param null  $className
     *
     * @return array
     */
    protected function filterByParentEntity($selected = [], $className = null)
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
