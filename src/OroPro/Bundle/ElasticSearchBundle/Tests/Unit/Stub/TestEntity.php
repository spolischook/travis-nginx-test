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
    public $firstName;

    /**
     * @var string
     */
    public $lastName;

    /**
     * @param int|null $id
     * @param string|null $firstName
     * @param string|null $lastName
     */
    public function __construct($id = null, $firstName = null, $lastName = null)
    {
        $this->id = $id;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
    }
}
