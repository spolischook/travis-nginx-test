<?php

namespace OroPro\Bundle\EwsBundle\Sync;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;

use OroPro\Bundle\EwsBundle\Manager\DTO\ItemId;
use Psr\Log\LoggerInterface;

use Oro\Bundle\EmailBundle\Builder\EmailEntityBuilder;
use Oro\Bundle\EmailBundle\Entity\Email as EmailEntity;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressManager;
use Oro\Bundle\EmailBundle\Sync\AbstractEmailSynchronizationProcessor;
use Oro\Bundle\EmailBundle\Sync\KnownEmailAddressChecker;

use OroPro\Bundle\EwsBundle\Ews\EwsType as EwsType;
use OroPro\Bundle\EwsBundle\Connector\Search\SearchQuery;
use OroPro\Bundle\EwsBundle\Entity\EwsEmail;
use OroPro\Bundle\EwsBundle\Entity\EwsEmailFolder;
use OroPro\Bundle\EwsBundle\Entity\EwsEmailOrigin;
use OroPro\Bundle\EwsBundle\Manager\EwsEmailManager;
use OroPro\Bundle\EwsBundle\Manager\DTO\Email;
use OroPro\Bundle\EwsBundle\Provider\EwsEmailIterator;

class EwsEmailSynchronizationProcessor extends AbstractEmailSynchronizationProcessor
{
    /** @var EwsEmailManager */
    protected $manager;

    /**
     * Constructor
     *
     * @param LoggerInterface          $log
     * @param EntityManager            $em
     * @param EmailEntityBuilder       $emailEntityBuilder
     * @param EmailAddressManager      $emailAddressManager
     * @param KnownEmailAddressChecker $knownEmailAddressChecker
     * @param EwsEmailManager          $manager
     */
    public function __construct(
        LoggerInterface $log,
        EntityManager $em,
        EmailEntityBuilder $emailEntityBuilder,
        EmailAddressManager $emailAddressManager,
        KnownEmailAddressChecker $knownEmailAddressChecker,
        EwsEmailManager $manager
    ) {
        parent::__construct($log, $em, $emailEntityBuilder, $emailAddressManager, $knownEmailAddressChecker);
        $this->manager = $manager;
    }

    /**
     * {@inheritdoc}
     */
    public function process(EmailOrigin $origin, $syncStartTime)
    {
        /** @var EwsEmailOrigin $origin */
        $this->manager->selectUser($origin->getUserEmail());

        // make sure that the entity builder is empty
        $this->emailEntityBuilder->clear();

        // iterate through all folders and do a synchronization of emails for each one
        $folders = $this->getFolders($origin);
        foreach ($folders as $folderInfo) {
            $folder = $folderInfo->ewsFolder->getFolder();

            // check if the current folder need to be synchronized
            if (!$folderInfo->needSynchronization) {
                $this->log->notice(
                    sprintf('Skip "%s" folder, because it is up-to-date.', $folder->getFullName())
                );
                $lastSynchronizedAt = $syncStartTime;
            } else {
                // set current folder
                $this->manager->selectFolder(
                    $folder->getType() === EmailFolder::OTHER ? $folderInfo->ewsFolder->getEwsId() : $folder->getType()
                );

                // register the current folder in the entity builder
                $this->emailEntityBuilder->setFolder($folder);

                // build a search query
                $sqb = $this->manager->getSearchQueryBuilder();
                if ($origin->getSynchronizedAt() && $folder->getSynchronizedAt()) {
                    $sqb->sent($folder->getSynchronizedAt());
                }

                // load emails using this search query
                $lastSynchronizedAt = $this->loadEmails($folderInfo, $sqb->get());
            }

            // update synchronization date for the current folder
            $folder->setSynchronizedAt($lastSynchronizedAt > $syncStartTime ? $lastSynchronizedAt : $syncStartTime);
            $this->em->flush($folder);
        }
    }

    /**
     * Gets a list of folders to be synchronized
     *
     * @param EmailOrigin $origin
     *
     * @return FolderInfo[]
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
        /** @var EwsEmailFolder[] $ewsFolders */
        $ewsFolders = $query->getResult();

        $folders = [];
        foreach ($ewsFolders as $ewsFolder) {
            $needSynchronization = $origin->getSynchronizedAt() && $ewsFolder->getFolder()->getSynchronizedAt()
                ? $ewsFolder->getFolder()->getSynchronizedAt() < $origin->getSynchronizedAt()
                : true;

            $folders[$ewsFolder->getEwsId()] = new FolderInfo($ewsFolder, $needSynchronization);
        }

        $this->log->notice(sprintf('Loaded %d folder(s).', count($folders)));

        $this->ensureFoldersInitialized($folders, $origin);

        return $folders;
    }

    /**
     * Check the given folders and if needed correct them
     *
     * @param FolderInfo[] $folders
     * @param EmailOrigin  $origin
     */
    protected function ensureFoldersInitialized(array &$folders, EmailOrigin $origin)
    {
        $this->log->notice('Retrieving folders from an email server ...');
        $retrievedFolderCount = $this->ensureDistinguishedFolderInitialized(
            $folders,
            $origin,
            EmailFolder::SENT
        );
        $retrievedFolderCount += $this->ensureDistinguishedFolderInitialized(
            $folders,
            $origin,
            EmailFolder::INBOX
        );
        $this->log->notice(sprintf('Retrieved %d folder(s).', $retrievedFolderCount));

        $this->em->flush();
    }

    /**
     * @param FolderInfo[] $folders
     * @param EmailOrigin  $origin
     * @param string       $folderType
     *
     * @return int Number of loaded folders including sub folders
     */
    protected function ensureDistinguishedFolderInitialized(
        array &$folders,
        EmailOrigin $origin,
        $folderType
    ) {
        $folderCount = 0;

        $distinguishedFolder = $this->manager->getDistinguishedFolder(
            $this->manager->getDistinguishedFolderName($folderType)
        );
        if ($distinguishedFolder) {
            $folderCount++;
            $folderInfo = $this->ensureFolderPersisted(
                $origin,
                $folders,
                $distinguishedFolder->FolderId,
                $distinguishedFolder->DisplayName,
                $distinguishedFolder->DisplayName,
                $folderType
            );
            $folderInfo->folderType = $folderType;
            $childFolders = $this->manager->getFolders($distinguishedFolder->FolderId, true);
            foreach ($childFolders as $childFolder) {
                $folderCount++;
                $folderInfo = $this->ensureFolderPersisted(
                    $origin,
                    $folders,
                    $childFolder->FolderId,
                    $this->buildFolderFullName($childFolder, $distinguishedFolder, $childFolders),
                    $childFolder->DisplayName,
                    EmailFolder::OTHER
                );
                $folderInfo->folderType = $folderType;
            }
        }

        return $folderCount;
    }

    /**
     * @param EwsType\FolderType   $folder
     * @param EwsType\FolderType   $distinguishedFolder
     * @param EwsType\FolderType[] $folders
     *
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
            $parentFolder = isset($folders[$folder->ParentFolderId->Id])
                ? $folders[$folder->ParentFolderId->Id]
                : null;
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
     * @param FolderInfo[]         $folders
     * @param EwsType\FolderIdType $id
     * @param string               $fullName
     * @param string               $localName
     * @param string               $type
     *
     * @return FolderInfo
     */
    protected function ensureFolderPersisted(
        EmailOrigin $origin,
        array &$folders,
        EwsType\FolderIdType $id,
        $fullName,
        $localName,
        $type
    ) {
        $folderInfo = isset($folders[$id->Id])
            ? $folders[$id->Id]
            : null;

        if ($folderInfo) {
            $folder = $folderInfo->ewsFolder->getFolder();
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
            if ($folderInfo->ewsFolder->getEwsChangeKey() !== $id->ChangeKey) {
                $this->log->notice(
                    sprintf(
                        'Change folder EWS ChangeKey from "%s" to "%s".',
                        $folderInfo->ewsFolder->getEwsChangeKey(),
                        $id->ChangeKey
                    )
                );
                $folderInfo->ewsFolder->setEwsChangeKey($id->ChangeKey);
            }
            $folderInfo->needSynchronization = true;
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

            $folderInfo = new FolderInfo($ewsFolder, true);
            $folders[$id->Id] = $folderInfo;

            $this->log->notice(sprintf('The "%s" folder was persisted.', $fullName));
        }

        return $folderInfo;
    }

    /**
     * Loads emails from an email server and save them into the database
     *
     * @param FolderInfo  $folderInfo
     * @param SearchQuery $searchQuery
     *
     * @return \DateTime The max sent date
     */
    protected function loadEmails(FolderInfo $folderInfo, SearchQuery $searchQuery)
    {
        $folder = $folderInfo->ewsFolder->getFolder();
        $lastSynchronizedAt = $folder->getSynchronizedAt();

        $this->log->notice(sprintf('Loading emails from "%s" folder ...', $folder->getFullName()));
        $this->log->notice(sprintf('Query: "%s".', $searchQuery->convertToString()));

        $iterator = new EwsEmailIterator($this->manager, $searchQuery, $this->log);

        $needFolderFlush = true;
        $count = 0;
        $batch = [];
        /** @var Email $email */
        foreach ($iterator as $email) {
            if (!$this->isApplicableEmail($email, $folderInfo->folderType)) {
                continue;
            }

            if ($email->getSentAt() > $lastSynchronizedAt) {
                $lastSynchronizedAt = $email->getSentAt();
            }

            $count++;
            $batch[] = $email;
            if ($count === self::DB_BATCH_SIZE) {
                $this->saveEmails($batch, $folderInfo);
                $needFolderFlush = false;
                $count = 0;
                $batch = [];
            }
        }
        if ($count > 0) {
            $this->saveEmails($batch, $folderInfo);
            $needFolderFlush = false;
        }

        if ($needFolderFlush) {
            $this->em->flush();
        }

        return $lastSynchronizedAt;
    }

    /**
     * @param Email  $email
     * @param string $folderType
     *
     * @return bool
     */
    protected function isApplicableEmail(Email $email, $folderType)
    {
        if ($folderType === EmailFolder::SENT) {
            return $this->knownEmailAddressChecker->isAtLeastOneKnownEmailAddress(
                $email->getToRecipients(),
                $email->getCcRecipients(),
                $email->getBccRecipients()
            );
        } else {
            return $this->knownEmailAddressChecker->isAtLeastOneKnownEmailAddress(
                $email->getFrom()
            );
        }
    }

    /**
     * Saves emails into the database
     *
     * @param Email[]    $emails
     * @param FolderInfo $folderInfo
     */
    protected function saveEmails(array $emails, FolderInfo $folderInfo)
    {
        $this->emailEntityBuilder->removeEmails();

        $ewsIds = array_map(
            function ($el) {
                /** @var Email $el */
                return $el->getId()->getId();
            },
            $emails
        );

        $folder = $folderInfo->ewsFolder->getFolder();
        $repo   = $this->em->getRepository('OroProEwsBundle:EwsEmail');

        $result = $repo->createQueryBuilder('e')
            ->select('e.ewsId, se.id')
            ->innerJoin('e.email', 'se')
            ->innerJoin('se.folders', 'sf')
            ->where('sf.id = :folderId AND e.ewsId IN (:ewsIds)')
            ->setParameter('folderId', $folder->getId())
            ->setParameter('ewsIds', $ewsIds)
            ->getQuery()
            ->getResult();

        $existingEwsIds = array_map(
            function ($el) {
                return $el['ewsId'];
            },
            $result
        );

        $existingEwsEmailIds = array_map(
            function ($el) {
                return $el['id'];
            },
            $result
        );

        $newEwsIds = [];
        foreach ($emails as $src) {
            if (in_array($src->getId()->getId(), $existingEwsIds)) {
                $this->log->notice(
                    sprintf(
                        'Skip "%s" (EWS ID: %s) email, because it is already synchronised.',
                        $src->getSubject(),
                        $src->getId()->getId()
                    )
                );
                continue;
            }

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
            $email->addFolder($folder);

            if (!isset($newEwsIds[$src->getMessageId()])) {
                $newEwsIds[$src->getMessageId()] = [];
            }
            $newEwsIds[$src->getMessageId()][] = $src->getId();

            $this->log->notice(sprintf('The "%s" email was persisted.', $src->getSubject()));
        }

        $this->emailEntityBuilder->getBatch()->persist($this->em);
        $this->linkEmailsToEwsEmails($emails, $newEwsIds, $existingEwsEmailIds, $folderInfo);
        $this->em->flush();
    }

    /**
     * @param Email[]|array $emails
     * @param array|ItemId  $newEwsIds
     * @param array         $existingEwsEmailIds
     * @param FolderInfo    $folderInfo
     */
    protected function linkEmailsToEwsEmails(
        array $emails,
        array $newEwsIds,
        array $existingEwsEmailIds,
        FolderInfo $folderInfo
    ) {
        /** @var EmailEntity[] $oEmails */
        $oEmails = $this->getEmailsByMessageId(
            $this->emailEntityBuilder->getBatch()->getEmails()
        );

        foreach ($emails as $emailDTO) {
            if (empty($newEwsIds[$emailDTO->getMessageId()])) {
                // email was skipped
                continue;
            }

            /** @var ItemId[] $newEwsId */
            $newEwsIdArray = $newEwsIds[$emailDTO->getMessageId()];

            /** @var EmailEntity $email */
            $email = $oEmails[$emailDTO->getMessageId()];
            if (in_array($email->getId(), $existingEwsEmailIds)) {
                continue;
            }

            foreach ($newEwsIdArray as $newEwsId) {
                $ewsEmail = new EwsEmail();
                $ewsEmail
                    ->setEwsId($newEwsId->getId())
                    ->setEwsChangeKey($newEwsId->getChangeKey())
                    ->setEmail($email)
                    ->setEwsFolder($folderInfo->ewsFolder);

                $this->em->persist($ewsEmail);
            }
        }
    }

    /**
     * @param EmailEntity[]|array $emails
     *
     * @return array
     */
    protected function getEmailsByMessageId(array $emails)
    {
        $result = [];

        /** @var EmailEntity $email */
        foreach ($emails as $email) {
            $result[$email->getMessageId()] = $email;
        }

        return $result;
    }
}
