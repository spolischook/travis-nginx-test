<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Form\Model;

use OroPro\Bundle\SecurityBundle\Form\Model\Share;

class ShareTest extends \PHPUnit_Framework_TestCase
{
    const ENTITY_CLASS = 'OroPro\Bundle\OrganizationBundle\Entity\UserOrganization';
    const ENTITY_ID = 2;

    /** @var Share */
    protected $model;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->model = new Share();
    }

    public function testGettersSetters()
    {
        $this->model->setEntityClass(self::ENTITY_CLASS);
        $this->assertEquals(self::ENTITY_CLASS, $this->model->getEntityClass());

        $this->model->setEntityId(self::ENTITY_ID);
        $this->assertEquals(self::ENTITY_ID, $this->model->getEntityId());

        $businessUnits = [2];
        $this->model->setEntities($businessUnits);
        $this->assertEquals($businessUnits, $this->model->getEntities());
    }
}
