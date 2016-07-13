<?php

namespace OroPro\Bundle\SecurityBundle\Owner\Metadata;

use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
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

    /**
     * {@inheritdoc}
     */
    public function getAccessLevelNames()
    {
        if (!$this->hasOwner()) {
            return [
                AccessLevel::NONE_LEVEL   => AccessLevel::NONE_LEVEL_NAME,
                AccessLevel::SYSTEM_LEVEL => AccessLevel::getAccessLevelName(AccessLevel::SYSTEM_LEVEL)
            ];
        }

        $minLevel = AccessLevel::BASIC_LEVEL;

        if ($this->isBasicLevelOwned()) {
            $minLevel = AccessLevel::BASIC_LEVEL;
        } elseif ($this->isLocalLevelOwned()) {
            $minLevel = AccessLevel::LOCAL_LEVEL;
        } elseif ($this->isGlobalLevelOwned()) {
            $minLevel = AccessLevel::GLOBAL_LEVEL;
        }

        return AccessLevel::getAccessLevelNames($minLevel);
    }
}
