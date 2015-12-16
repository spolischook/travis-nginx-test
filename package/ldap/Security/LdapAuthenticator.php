<?php

namespace OroCRMPro\Bundle\LDAPBundle\Security;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Symfony\Component\Security\Core\User\UserInterface;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\UserBundle\Entity\User as OroUser;

use OroCRMPro\Bundle\LDAPBundle\Provider\ChannelType;
use OroCRMPro\Bundle\LDAPBundle\Provider\Transport\LdapTransportInterface;

class LdapAuthenticator
{
    /** @var Channel[] */
    protected $channels;
    /** @var LdapTransportInterface */
    protected $transport;
    /** @var Registry */
    private $registry;

    /**
     * @param Registry               $registry
     * @param LdapTransportInterface $transport
     */
    public function __construct(Registry $registry, LdapTransportInterface $transport)
    {
        $this->transport = $transport;
        $this->registry = $registry;
    }

    /**
     * @return Channel[]
     */
    protected function getChannels()
    {
        if ($this->channels === null) {
            $this->channels = $this->registry->getRepository('OroIntegrationBundle:Channel')
                ->findBy(['type' => ChannelType::TYPE]);
        }

        return $this->channels;
    }

    /**
     * @param UserInterface $user
     * @param string        $password
     *
     * @return bool
     */
    public function check(UserInterface $user, $password)
    {
        if (!($user instanceof OroUser)) {
            return false;
        }

        $userDns = (array)$user->getLdapDistinguishedNames();

        foreach ($this->getChannels() as $channel) {
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
