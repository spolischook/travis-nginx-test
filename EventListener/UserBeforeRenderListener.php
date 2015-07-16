<?php
namespace OroCRMPro\Bundle\LDAPBundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Oro\Bundle\EntityExtendBundle\Event\ValueRenderEvent;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\UserBundle\Entity\User;

use OroCRMPro\Bundle\LDAPBundle\Provider\ChannelType;

class UserBeforeRenderListener
{
    /** @var Registry */
    private $registry;

    /**
     * @param Registry $registry
     */
    public function __construct(Registry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * Handles transformation of ldap mappings before rendering.
     *
     * @param ValueRenderEvent $event
     */
    public function beforeValueRender(ValueRenderEvent $event)
    {
        if (($event->getEntity() instanceof User) && $event->getFieldConfigId()
                ->getFieldName() == 'ldap_distinguished_names'
        ) {
            $value = (array)$event->getFieldValue();

            $mappings = [];

            /** @var Channel[] $channels */
            $channels = $this->registry->getRepository('OroIntegrationBundle:Channel')
                ->findBy(['type' => ChannelType::TYPE]);

            foreach ($channels as $channel) {
                if (!isset($value[$channel->getId()])) {
                    continue;
                }

                $mappings[] = [
                    'name' => $channel->getName(),
                    'dn'   => $value[$channel->getId()],
                ];
            }

            $event->setFieldViewValue(
                [
                    'mappings' => $mappings,
                    'template' => 'OroCRMProLDAPBundle:User:ldapDistinguishedNames.html.twig',
                ]
            );
        }
    }
}
