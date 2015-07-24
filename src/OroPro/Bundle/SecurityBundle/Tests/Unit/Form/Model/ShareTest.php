<?php

namespace OroPro\Bundle\SecurityBundle\Tests\Unit\Form\Model;

use OroPro\Bundle\SecurityBundle\Form\Model\Share;

class ShareTest extends \PHPUnit_Framework_TestCase
{
    const ENTITY_CLASS = 'OroCRM\Bundle\AccountBundle\Entity\Account';
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
        $organization = [4];
        $this->model->setOrganizations($organization);
        $this->assertEquals($organization, $this->model->getOrganizations());
    }
}
