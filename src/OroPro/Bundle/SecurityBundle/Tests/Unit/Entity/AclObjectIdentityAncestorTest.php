<?php

namespace OroPro\Bundle\SecurityBundle\Tests\Unit\Entity;

use OroPro\Bundle\SecurityBundle\Entity\AclObjectIdentity;
use OroPro\Bundle\SecurityBundle\Entity\AclObjectIdentityAncestor;

class AclObjectIdentityAncestorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AclObjectIdentityAncestor
     */
    protected $entity;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->entity = new AclObjectIdentityAncestor();
    }

    public function testGettersSetters()
    {
        $objectIdentity = new AclObjectIdentity();
        $this->entity->setObjectIdentity($objectIdentity);
        $this->assertEquals($objectIdentity, $this->entity->getObjectIdentity());

        $ancestor = new AclObjectIdentity();
        $this->entity->setAncestor($ancestor);
        $this->assertEquals($ancestor, $this->entity->getAncestor());
    }
}
