<?php

namespace OroPro\Bundle\OrganizationBundle\Twig;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;

class EntityDataExtension extends \Twig_Extension
{
    const NAME = 'oro_entity_data';

    /** @var  Registry */
    protected $doctrine;

    /**
     * @param Registry $doctrine
     */
    public function __construct(Registry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('oro_entity_data', array($this, 'getEntityData')),
        );
    }

    /**
     * @param string $entityClass
     * @param array  $ids
     *
     * @return array
     */
    public function getEntityData($entityClass, $ids = [])
    {
        /**
         * TODO: retrive organization names for given organization ids
         */

        /** @var EntityManager $em */
        $em = $this->doctrine->getManager();
        $data = [];

        return $data;

    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
