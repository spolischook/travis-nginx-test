<?php

namespace OroPro\Bundle\SecurityBundle\Owner\Metadata;

use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadata;

class OwnershipProMetadata extends OwnershipMetadata
{
    /** @var boolean */
    protected $globalView;

    /**
     * @param string $ownerType
     * @param string $ownerFieldName
     * @param string $ownerColumnName
     * @param string $organizationFieldName
     * @param string $organizationColumnName
     * @param bool   $globalView
     */
    public function __construct(
        $ownerType = '',
        $ownerFieldName = '',
        $ownerColumnName = '',
        $organizationFieldName = '',
        $organizationColumnName = '',
        $globalView = false
    ) {
        parent::__construct(
            $ownerType,
            $ownerFieldName,
            $ownerColumnName,
            $organizationFieldName,
            $organizationColumnName
        );

        $this->globalView = $globalView;
    }

    /**
     * @return boolean
     */
    public function isGlobalView()
    {
        return filter_var($this->globalView, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize(
            [
                $this->ownerType,
                $this->ownerFieldName,
                $this->ownerColumnName,
                $this->organizationFieldName,
                $this->organizationColumnName,
                $this->globalView
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        list(
            $this->ownerType,
            $this->ownerFieldName,
            $this->ownerColumnName,
            $this->organizationFieldName,
            $this->organizationColumnName,
            $this->globalView
            ) = unserialize($serialized);
    }
}
