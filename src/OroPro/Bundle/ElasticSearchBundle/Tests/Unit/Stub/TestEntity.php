<?php

namespace OroPro\Bundle\ElasticSearchBundle\Tests\Unit\Stub;

class TestEntity
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $name;

    /**
     * @var \DateTime
     */
    public $birthday;

    /**
     * @var TestEntity
     */
    public $entity;

    /**
     * @param int|null $id
     * @param string|null $name
     * @param string|null $birthday
     * @param TestEntity|null $entity
     */
    public function __construct($id = null, $name = null, $birthday = null, $entity = null)
    {
        $this->id = $id;
        $this->name = $name;
        $this->birthday = $birthday;
        $this->entity = $entity;
    }

    /**
     * @return null|string
     */
    public function __toString()
    {
        return $this->name;
    }
}
