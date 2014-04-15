<?php

namespace OroPro\Bundle\EwsBundle\Sync;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Psr\Log\LoggerInterface;

use Oro\Bundle\EmailBundle\Builder\EmailEntityBuilder;
use Oro\Bundle\EmailBundle\Entity\EmailAddress;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressManager;
use Oro\Bundle\EmailBundle\Sync\AbstractEmailSynchronizationProcessor;

use OroPro\Bundle\EwsBundle\Ews\EwsType as EwsType;
use OroPro\Bundle\EwsBundle\Connector\Search\SearchQuery;
use OroPro\Bundle\EwsBundle\Connector\Search\SearchQueryBuilder;
use OroPro\Bundle\EwsBundle\Entity\EwsEmail;
use OroPro\Bundle\EwsBundle\Entity\EwsEmailFolder;
use OroPro\Bundle\EwsBundle\Entity\EwsEmailOrigin;
use OroPro\Bundle\EwsBundle\Manager\EwsEmailManager;
use OroPro\Bundle\EwsBundle\Manager\DTO\Email;
use OroPro\Bundle\EwsBundle\Provider\EwsEmailIterator;

/**
 * @todo the implemented synchronization algorithm is just a demo and it will be fixed soon
 */
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
        /** @var EwsEmailOrigin $origin */
        $this->manager->selectUser($origin->getUserEmail());

        // make sure that the entity builder is empty
        $this->emailEntityBuilder->clear();

        // get a list of emails belong to any object, for example an user or a contacts
        $emailAddressBatches = $this->getKnownEmailAddressBatches($origin->getSynchronizedAt());

        // iterate through all folders and do a synchronization of emails for each one
        $folders = $this->getFolders($origin);
        foreach ($folders as $ewsFolder) {
            $folder = $ewsFolder->getFolder();
            if ($folder->getType() === EmailFolder::OTHER) {
                $this->manager->selectFolder($ewsFolder->getEwsId());
            } else {
                $this->manager->selectFolder($folder->getType());
            }

            // register the current folder in the entity builder
            $this->emailEntityBuilder->setFolder($folder);

            $this->log->notice(sprintf('Loading emails from "%s" folder ...', $folder->getFullName()));
            foreach ($emailAddressBatches as $emailAddressBatch) {
                // build a search query
                $sqb = $this->manager->getSearchQueryBuilder();
                if ($origin->getSynchronizedAt()
                    && $folder->getSynchronizedAt()
                    && !$emailAddressBatch['needFullSync']
                ) {
                    $sqb->sent($folder->getSynchronizedAt());
                }

                $sqb->openParenthesis();

                $sqb->openParenthesis();
                $this->addEmailAddressesToSearchQueryBuilder($sqb, 'from', $emailAddressBatch['items']);
                $sqb->closeParenthesis();

                $sqb->openParenthesis();
                $this->addEmailAddressesToSearchQueryBuilder($sqb, 'to', $emailAddressBatch['items']);
                $sqb->orOperator();
                $this->addEmailAddressesToSearchQueryBuilder($sqb, 'cc', $emailAddressBatch['items']);
                $sqb->orOperator();
                $this->addEmailAddressesToSearchQueryBuilder($sqb, 'bcc', $emailAddressBatch['items']);
                $sqb->closeParenthesis();

                $sqb->closeParenthesis();

                // load emails using this search query
                $this->loadEmails($folder, $sqb->get());
            }

            $folder->setSynchronizedAt(new \DateTime('now', new \DateTimeZone('UTC')));
            $this->em->flush();
        }
    }

    /**
     * Adds the given email addresses to the search query.
     * Addresses are delimited by OR operator.
     *
     * @param SearchQueryBuilder $sqb
     * @param string $addressType
     * @param EmailAddress[] $addresses
     */
    protected function addEmailAddressesToSearchQueryBuilder(SearchQueryBuilder $sqb, $addressType, array $addresses)
    {
        for ($i = 0; $i < count($addresses); $i++) {
            if ($i > 0) {
                $sqb->orOperator();
            }
            $sqb->{$addressType}($addresses[$i]->getEmail());
        }
    }

    /**
     * Gets a list of email addresses which have an owner and splits them into batches
     *
     * @param \DateTime|null $lastSyncTime
     * @return array
     *             key = index
     *             value = array
     *                 'needFullSync' => true/false
     *                 'items' => EmailAddress[]
     */
    protected function getKnownEmailAddressBatches($lastSyncTime)
    {
        $batches = array();
        $batchIndex = 0;
        $count = 0;
        foreach ($this->getKnownEmailAddresses() as $emailAddress) {
            $needFullSync = !$lastSyncTime || $emailAddress->getUpdatedAt() > $lastSyncTime;
            if ($count >= self::EMAIL_ADDRESS_BATCH_SIZE
                || (isset($batches[$batchIndex]) && $needFullSync !== $batches[$batchIndex]['needFullSync'])
            ) {
                $batchIndex++;
                $count = 0;
            }
            if ($count === 0) {
                $batches[$batchIndex] = array('needFullSync' => $needFullSync, 'items' => array());
            }
            $batches[$batchIndex]['items'][$count] = $emailAddress;
            $count++;
        }

        return $batches;
    }

    /**
     * Gets a list of folders to be synchronized
     *
     * @param EmailOrigin $origin
     * @return EwsEmailFolder[]
     */
    protected function getFolders(EmailOrigin $origin)
    {
        $this->log->notice('Loading folders ...');

        $repo = $this->em->getRepository('OroProEwsBundle:EwsEmailFolder');
        $query = $repo->createQueryBuilder('ews')
            ->select('ews, f')
            ->innerJoin('ews.folder', 'f')
            ->where('f.origin = ?1')
            ->orderBy('f.name')
            ->setParameter(1, $origin)
            ->getQuery();
        $folders = $query->getResult();

        $this->log->notice(sprintf('Loaded %d folder(s).', count($folders)));

        $this->ensureFoldersInitialized($folders, $origin);

        return $folders;
    }

    /**
     * Check the given folders and if needed correct them
     *
     * @param EwsEmailFolder[] $folders
     * @param EmailOrigin $origin
     */
    protected function ensureFoldersInitialized(array &$folders, EmailOrigin $origin)
    {
        $this->log->notice('Retrieving folders from an email server ...');
        $retrievedFolderCount = $this->ensureDistinguishedFolderInitialized(
            $folders,
            $origin,
            EwsType\DistinguishedFolderIdNameType::INBOX,
            EmailFolder::INBOX
        );
        $retrievedFolderCount += $this->ensureDistinguishedFolderInitialized(
            $folders,
            $origin,
            EwsType\DistinguishedFolderIdNameType::OUTBOX,
            EmailFolder::SENT
        );
        $this->log->notice(sprintf('Retrieved %d folder(s).', $retrievedFolderCount));

        $this->em->flush();
    }

    /**
     * @param EwsEmailFolder[] $folders
     * @param EmailOrigin      $origin
     * @param string           $folderName
     * @param string           $folderType
     * @return int Number of loaded folders including sub folders
     */
    protected function ensureDistinguishedFolderInitialized(
        array &$folders,
        EmailOrigin $origin,
        $folderName,
        $folderType
    ) {
        $folderCount = 0;

        $distinguishedFolder = $this->manager->getDistinguishedFolder($folderName);
        if ($distinguishedFolder) {
            $folderCount++;
            $this->ensureFolderPersisted(
                $origin,
                $folders,
                $distinguishedFolder->FolderId,
                $distinguishedFolder->DisplayName,
                $distinguishedFolder->DisplayName,
                $folderType
            );
            $childFolders = $this->manager->getFolders($distinguishedFolder->FolderId, true);
            foreach ($childFolders as $childFolder) {
                $folderCount++;
                $this->ensureFolderPersisted(
                    $origin,
                    $folders,
                    $childFolder->FolderId,
                    $this->buildFolderFullName($childFolder, $distinguishedFolder, $childFolders),
                    $childFolder->DisplayName,
                    EmailFolder::OTHER
                );
            }
        }

        return $folderCount;
    }

    /**
     * @param EwsType\FolderType   $folder
     * @param EwsType\FolderType   $distinguishedFolder
     * @param EwsType\FolderType[] $folders
     * @return string
     */
    protected function buildFolderFullName(
        EwsType\FolderType $folder,
        EwsType\FolderType $distinguishedFolder,
        array &$folders
    ) {
        if ($folder->ParentFolderId === null) {
            return $folder->DisplayName;
        }

        $parentFolder = null;
        if ($folder->ParentFolderId->Id === $distinguishedFolder->FolderId->Id) {
            $parentFolder = $distinguishedFolder;
        } else {
            foreach ($folders as $f) {
                if ($folder->ParentFolderId->Id === $f->FolderId->Id) {
                    $parentFolder = $f;
                    break;
                }
            }
        }

        if ($parentFolder) {
            return sprintf(
                '%s/%s',
                $this->buildFolderFullName($parentFolder, $distinguishedFolder, $folders),
                $folder->DisplayName
            );
        }

        return $folder->DisplayName;
    }

    /**
     * @param EmailOrigin          $origin
     * @param EwsEmailFolder[]     $folders
     * @param EwsType\FolderIdType $id
     * @param string               $fullName
     * @param string               $localName
     * @param string               $type
     */
    protected function ensureFolderPersisted(
        EmailOrigin $origin,
        array &$folders,
        EwsType\FolderIdType $id,
        $fullName,
        $localName,
        $type
    ) {
        $ewsFolder = null;
        foreach ($folders as $f) {
            if ($f->getEwsId() === $id->Id) {
                $ewsFolder = $f;
                break;
            }
        }

        if ($ewsFolder) {
            $folder = $ewsFolder->getFolder();
            if ($folder->getFullName() !== $fullName) {
                $this->log->notice(
                    sprintf('Change folder full name from "%s" to "%s".', $folder->getFullName(), $fullName)
                );
                $folder->setFullName($fullName);
            }
            if ($folder->getName() !== $localName) {
                $this->log->notice(
                    sprintf('Change folder name from "%s" to "%s".', $folder->getName(), $localName)
                );
                $folder->setName($localName);
            }
            if ($folder->getType() !== $type) {
                $this->log->notice(
                    sprintf('Change folder type from "%s" to "%s".', $folder->getType(), $type)
                );
                $folder->setType($type);
            }
            if ($ewsFolder->getEwsChangeKey() !== $id->ChangeKey) {
                $this->log->notice(
                    sprintf(
                        'Change folder EWS ChangeKey from "%s" to "%s".',
                        $ewsFolder->getEwsChangeKey(),
                        $id->ChangeKey
                    )
                );
                $ewsFolder->setEwsChangeKey($id->ChangeKey);
            }
        } else {
            $this->log->notice(sprintf('Persisting "%s" folder ...', $fullName));

            $folder = new EmailFolder();
            $folder
                ->setFullName($fullName)
                ->setName($localName)
                ->setType($type);

            $ewsFolder = new EwsEmailFolder();
            $ewsFolder->setEwsId($id->Id);
            $ewsFolder->setEwsChangeKey($id->ChangeKey);
            $ewsFolder->setFolder($folder);

            $origin->addFolder($folder);

            $this->em->persist($origin);
            $this->em->persist($ewsFolder);
            $this->em->persist($folder);

            $folders[] = $ewsFolder;

            $this->log->notice(sprintf('The "%s" folder was persisted.', $fullName));
        }
    }

    /**
     * Checks if the folder exists in the given list
     *
     * @param EmailFolder[] $folders
     * @param string $folderType
     * @param string $folderFullName
     * @return bool
     */
    protected function isFolderExist(array &$folders, $folderType, $folderFullName)
    {
        $exists = false;
        foreach ($folders as $folder) {
            if ($folder->getType() === $folderType && $folder->getFullName() === $folderFullName) {
                $exists = true;
                break;
            }
        }

        return $exists;
    }

    /**
     * Loads emails from an email server and save them into the database
     *
     * @param EmailFolder $folder
     * @param SearchQuery $searchQuery
     */
    protected function loadEmails(EmailFolder $folder, SearchQuery $searchQuery)
    {
        $this->log->notice(sprintf('Query: "%s".', $searchQuery->convertToQueryString()));

        $iterator = new EwsEmailIterator($this->manager, $searchQuery);

        $needFolderFlush = true;
        $count = 0;
        $batch = array();
        foreach ($iterator as $email) {
            $count++;
            $batch[] = $email;
            if ($count === self::DB_BATCH_SIZE) {
                $this->saveEmails($batch, $folder);
                $needFolderFlush = false;
                $count = 0;
                $batch = array();
            }
        }
        if ($count > 0) {
            $this->saveEmails($batch, $folder);
            $needFolderFlush = false;
        }

        if ($needFolderFlush) {
            $this->em->flush();
        }
    }

    /**
     * Saves emails into the database
     *
     * @param Email[] $emails
     * @param EmailFolder $folder
     */
    protected function saveEmails(array $emails, EmailFolder $folder)
    {
        $this->emailEntityBuilder->removeEmails();

        $ewsIds = array_map(
            function ($el) {
                /** @var Email $el */
                return $el->getId()->getId();
            },
            $emails
        );

        $repo = $this->em->getRepository('OroProEwsBundle:EwsEmail');
        $query = $repo->createQueryBuilder('e')
            ->select('e.ewsId')
            ->innerJoin('e.email', 'se')
            ->innerJoin('se.folder', 'sf')
            ->where('sf.id = :folderId AND e.ewsId IN (:ewsIds)')
            ->setParameter('folderId', $folder->getId())
            ->setParameter('ewsIds', $ewsIds)
            ->getQuery();
        $existingEwsIds = array_map(
            function ($el) {
                return $el['ewsId'];
            },
            $query->getResult()
        );

        foreach ($emails as $src) {
            if (!in_array($src->getId()->getId(), $existingEwsIds)) {
                $this->log->notice(
                    sprintf('Persisting "%s" email (EWS ID: %s) ...', $src->getSubject(), $src->getId()->getId())
                );

                $email = $this->emailEntityBuilder->email(
                    $src->getSubject(),
                    $src->getFrom(),
                    $src->getToRecipients(),
                    $src->getSentAt(),
                    $src->getReceivedAt(),
                    $src->getInternalDate(),
                    $src->getImportance(),
                    $src->getCcRecipients(),
                    $src->getBccRecipients()
                );
                $email->setMessageId($src->getMessageId());
                $email->setXMessageId($src->getXMessageId());
                $email->setXThreadId($src->getXThreadId());
                $email->setFolder($folder);
                $ewsEmail = new EwsEmail();
                $ewsEmail
                    ->setEwsId($src->getId()->getId())
                    ->setEwsChangeKey($src->getId()->getChangeKey())
                    ->setEmail($email);
                $this->em->persist($ewsEmail);

                $this->log->notice(sprintf('The "%s" email was persisted.', $src->getSubject()));
            } else {
                $this->log->notice(
                    sprintf(
                        'Skip "%s" (EWS ID: %s) email, because it is already synchronised.',
                        $src->getSubject(),
                        $src->getId()->getId()
                    )
                );
            }
        }

        $this->emailEntityBuilder->getBatch()->persist($this->em);
        $this->em->flush();
    }
}
