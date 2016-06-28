<?php

namespace OroB2BPro\Bundle\WebsiteBundle\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;

use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use OroB2B\Bundle\WebsiteBundle\Provider\WebsiteProviderInterface;

class WebsiteProvider implements WebsiteProviderInterface
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @return Website[]
     */
    public function getWebsites()
    {
        return $this->registry->getManagerForClass('OroB2BWebsiteBundle:Website')
            ->getRepository('OroB2BWebsiteBundle:Website')
            ->getAllWebsites();
    }
}
