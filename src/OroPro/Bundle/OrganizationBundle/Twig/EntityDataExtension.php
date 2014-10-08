<?php

namespace OroPro\Bundle\OrganizationBundle\Twig;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TranslationBundle\Translation\Translator;

class EntityDataExtension extends \Twig_Extension
{
    const NAME = 'oropro_organization';

    /** @var  Registry */
    protected $doctrine;

    /** @var Translator */
    protected $translator;

    /**
     * @param Registry   $doctrine
     * @param Translator $translator
     */
    public function __construct(
        Registry $doctrine,
        Translator $translator
    ) {
        $this->doctrine   = $doctrine;
        $this->translator = $translator;
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
     * @param string $value
     *
     * @return string
     */
    public function getApplicable($value)
    {
        $result = $this->translator->trans('oropro.organization.datagrid.applicable_none');

        /** @var \stdClass $data */
        $data = json_decode($value);
        if (is_object($data)) {
            if ($data->all === true) {
                $result = $this->translator->trans('oropro.organization.datagrid.applicable_all');
            } elseif (!empty($data->selective)) {
                /** @var EntityManager $em */
                $em = $this->doctrine->getManager();

                $expr     = Criteria::expr();
                $criteria = Criteria::create();
                $criteria->where($expr->in('id', $data->selective));

                /** @var Organization[] $organizations */
                $organizations   = $em->getRepository('OroOrganizationBundle:Organization')->matching($criteria);
                foreach ($organizations as $organization) {
                    $result[] = $organization->getName();
                }

                $result = implode(', ', $result);
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
