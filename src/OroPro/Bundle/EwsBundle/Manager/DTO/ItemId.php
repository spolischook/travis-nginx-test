<?php

namespace OroPro\Bundle\EwsBundle\Manager\DTO;

use OroPro\Bundle\EwsBundle\Ews\EwsType as EwsType;

class ItemId
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $changeKey;

    /**
     * Constructor
     *
     * @param string $id
     * @param string $changeKey
     */
    public function __construct($id, $changeKey)
    {
        $this->id = $id;
        $this->changeKey = $changeKey;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getChangeKey()
    {
        return $this->changeKey;
    }

    /**
     * @param string $changeKey
     */
    public function setChangeKey($changeKey)
    {
        $this->changeKey = $changeKey;
    }
}
