<?php

namespace OroPro\Bundle\OrganizationBundle\Twig;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Collections\Criteria;
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
         * TODO: add ACL checking
         */

        /** @var EntityManager $em */
        $em = $this->doctrine->getManager();

        //$pk = $em->getClassMetadata($entityClass)->getIdentifier();
        //return $pk;

        $data = $em->getRepository($entityClass)->matching(
            new Criteria()
        );
        //findBy(['id in(' . implode(',', $ids) . ')']);


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
