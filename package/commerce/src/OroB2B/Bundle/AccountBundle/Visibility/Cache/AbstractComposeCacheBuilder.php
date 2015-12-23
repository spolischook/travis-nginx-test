<?php

namespace OroB2B\Bundle\AccountBundle\Visibility\Cache;

use OroB2B\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

abstract class AbstractComposeCacheBuilder implements CacheBuilderInterface
{
    /**
     * @var CacheBuilderInterface[]
     */
    protected $builders = [];

    /**
     * {@inheritdoc}
     */
    public function resolveVisibilitySettings(VisibilityInterface $visibilitySettings)
    {
        foreach ($this->builders as $builder) {
            if ($builder->isVisibilitySettingsSupported($visibilitySettings)) {
                $builder->resolveVisibilitySettings($visibilitySettings);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isVisibilitySettingsSupported(VisibilityInterface $visibilitySettings)
    {
        foreach ($this->builders as $builder) {
            if ($builder->isVisibilitySettingsSupported($visibilitySettings)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function buildCache(Website $website = null)
    {
        foreach ($this->builders as $builder) {
            $builder->buildCache($website);
        }
    }
}
