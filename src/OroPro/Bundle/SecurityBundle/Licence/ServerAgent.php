<?php

namespace OroPro\Bundle\SecurityBundle\Licence;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\UserBundle\Entity\Repository\UserRepository;

class ServerAgent
{
    /**
     * @var string
     */
    protected $licence;

    /**
     * @var Sender
     */
    protected $sender;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @param Sender $sender
     * @param string $licence
     * @param ManagerRegistry $registry
     */
    public function __construct($licence, Sender $sender, ManagerRegistry $registry)
    {
        $this->licence = $licence;
        $this->sender = $sender;
        $this->registry = $registry;
    }

    public function sendStatusInformation()
    {
        $datetime = new \DateTime();

        $data = array(
            'licence'   => $this->licence,
            'timestamp' => $datetime->getTimestamp(),
            'datetime'  => $datetime->format('c'),
        );
        $data = array_merge($data, $this->collectUserStatistics());

        $this->sender->sendPost('status_information', $data);
    }

    /**
     * @return array
     */
    protected function collectUserStatistics()
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->registry->getRepository('OroUserBundle:User');
        $totalUsers = $userRepository->getUsersCount();
        $activeUsers = $userRepository->getUsersCount(true);

        return array(
            'total_users' => $totalUsers,
            'active_users' => $activeUsers
        );
    }
}
