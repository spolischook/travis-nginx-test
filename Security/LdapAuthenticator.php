<?php

namespace Oro\Bundle\LDAPBundle\Security;

use Symfony\Component\Security\Core\User\UserInterface;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\LDAPBundle\Provider\ChannelType;
use Oro\Bundle\LDAPBundle\Provider\Transport\LdapTransportInterface;
use Oro\Bundle\UserBundle\Entity\User as OroUser;

class LdapAuthenticator
{

    /** @var Channel[] */
    protected $channels;

    /** @var LdapTransportInterface */
    protected $transport;

    public function __construct(Registry $registry, LdapTransportInterface $transport)
    {
        $this->channels = $registry->getRepository('OroIntegrationBundle:Channel')->findBy(['type' => ChannelType::TYPE]);
        $this->transport = $transport;
    }

    public function check(UserInterface $user, $password)
    {
        if (!($user instanceof OroUser)) {
            return false;
        }

        $userDns = (array)$user->getLdapDistinguishedNames();

        foreach ($this->channels as $channel) {
            if (!$channel->isEnabled()) {
                continue;
            }

            if (!isset($userDns[$channel->getId()])) {
                continue;
            }

            $this->transport->init($channel->getTransport());
            $username = $userDns[$channel->getId()];

            if ($this->transport->bind($username, $password)) {
                return true;
            }
        }

        return false;
    }
} 
