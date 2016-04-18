<?php

namespace OroPro\Bundle\EwsBundle\Sync;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\EmailBundle\Builder\EmailEntityBuilder;
use Oro\Bundle\EmailBundle\Sync\KnownEmailAddressCheckerInterface;

use OroPro\Bundle\EwsBundle\Manager\EwsEmailManager;

class EwsEmailSynchronizationProcessorFactory
{
    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var EmailEntityBuilder */
    protected $emailEntityBuilder;

    /**
     * @param ManagerRegistry    $doctrine
     * @param EmailEntityBuilder $emailEntityBuilder
     */
    public function __construct(
        ManagerRegistry $doctrine,
        EmailEntityBuilder $emailEntityBuilder
    ) {
        $this->doctrine           = $doctrine;
        $this->emailEntityBuilder = $emailEntityBuilder;
    }

    /**
     * Creates new instance of EWS email synchronization processor
     *
     * @param EwsEmailManager                   $emailManager
     * @param KnownEmailAddressCheckerInterface $knownEmailAddressChecker
     *
     * @return EwsEmailSynchronizationProcessor
     */
    public function create(
        EwsEmailManager $emailManager,
        KnownEmailAddressCheckerInterface $knownEmailAddressChecker
    ) {
        return new EwsEmailSynchronizationProcessor(
            $this->getEntityManager(),
            $this->emailEntityBuilder,
            $knownEmailAddressChecker,
            $emailManager
        );
    }

    /**
     * Returns default entity manager
     *
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        /** @var EntityManager $em */
        $em = $this->doctrine->getManager();
        if (!$em->isOpen()) {
            $this->doctrine->resetManager();
            $em = $this->doctrine->getManager();
        }

        return $em;
    }
}
