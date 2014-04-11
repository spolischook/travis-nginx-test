<?php

namespace OroPro\Bundle\EwsBundle\Sync;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query;
use Psr\Log\LoggerInterface;

use Oro\Bundle\EmailBundle\Builder\EmailEntityBuilder;
use Oro\Bundle\EmailBundle\Entity\EmailAddress;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressManager;
use Oro\Bundle\EmailBundle\Sync\AbstractEmailSynchronizationProcessor;

use OroPro\Bundle\EwsBundle\Connector\Search\SearchQuery;
use OroPro\Bundle\EwsBundle\Connector\Search\SearchQueryBuilder;
use OroPro\Bundle\EwsBundle\Entity\EwsEmail;
use OroPro\Bundle\EwsBundle\Entity\EwsEmailFolder;
use OroPro\Bundle\EwsBundle\Manager\EwsEmailManager;
use OroPro\Bundle\EwsBundle\Manager\DTO\Email;

class EwsEmailSynchronizationProcessor extends AbstractEmailSynchronizationProcessor
{
    const EMAIL_ADDRESS_BATCH_SIZE = 10;

    /**
     * @var EwsEmailManager
     */
    protected $manager;

    /**
     * Constructor
     *
     * @param LoggerInterface $log
     * @param EntityManager $em
     * @param EmailEntityBuilder $emailEntityBuilder
     * @param EmailAddressManager $emailAddressManager
     * @param EwsEmailManager $manager
     */
    public function __construct(
        LoggerInterface $log,
        EntityManager $em,
        EmailEntityBuilder $emailEntityBuilder,
        EmailAddressManager $emailAddressManager,
        EwsEmailManager $manager
    ) {
        parent::__construct($log, $em, $emailEntityBuilder, $emailAddressManager);
        $this->manager = $manager;
    }

    /**
     * Performs a synchronization of emails for the given email origin.
     *
     * @param EmailOrigin $origin
     */
    public function process(EmailOrigin $origin)
    {
    }
}
