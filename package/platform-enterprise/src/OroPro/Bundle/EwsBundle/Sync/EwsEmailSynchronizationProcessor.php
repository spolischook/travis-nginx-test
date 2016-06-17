<?php

namespace OroPro\Bundle\EwsBundle\Sync;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;

use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Model\FolderType;
use Oro\Bundle\EmailBundle\Builder\EmailEntityBuilder;
use Oro\Bundle\EmailBundle\Entity\Email as EmailEntity;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Sync\AbstractEmailSynchronizationProcessor;
use Oro\Bundle\EmailBundle\Sync\KnownEmailAddressCheckerInterface;
use Oro\Bundle\UserBundle\Entity\User;

use OroPro\Bundle\EwsBundle\Connector\Search\SearchQuery;
use OroPro\Bundle\EwsBundle\Entity\EwsEmail;
use OroPro\Bundle\EwsBundle\Entity\EwsEmailFolder;
use OroPro\Bundle\EwsBundle\Entity\EwsEmailOrigin;
use OroPro\Bundle\EwsBundle\Entity\Repository\EwsEmailFolderRepository;
use OroPro\Bundle\EwsBundle\Entity\Repository\EwsEmailRepository;
use OroPro\Bundle\EwsBundle\Ews\EwsType as EwsType;
use OroPro\Bundle\EwsBundle\Manager\EwsEmailManager;
use OroPro\Bundle\EwsBundle\Manager\DTO\Email;
use OroPro\Bundle\EwsBundle\Manager\DTO\ItemId;
use OroPro\Bundle\EwsBundle\Provider\EwsEmailIterator;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class EwsEmailSynchronizationProcessor extends AbstractEmailSynchronizationProcessor
{
    /** Determines how many emails can be loaded from EWS server at once */
    const READ_BATCH_SIZE = 100;

    /** Determines how often "Processed X emails" hint should be added to a log */
    const READ_HINT_COUNT = 500;

    /** @var EwsEmailManager */
    protected $manager;

    /**
     * Constructor
     *
     * @param EntityManager                     $em
     * @param EmailEntityBuilder                $emailEntityBuilder
     * @param KnownEmailAddressCheckerInterface $knownEmailAddressChecker
     * @param EwsEmailManager                   $manager
     */
    public function __construct(
        EntityManager $em,
        EmailEntityBuilder $emailEntityBuilder,
        KnownEmailAddressCheckerInterface $knownEmailAddressChecker,
        EwsEmailManager $manager
    ) {
        parent::__construct($em, $emailEntityBuilder, $knownEmailAddressChecker);
        $this->manager = $manager;
    }

    /**
     * {@inheritdoc}
     */
    public function process(EmailOrigin $origin, $syncStartTime)
    {
        $this->initEnv($origin);

        /** @var EwsEmailOrigin $origin */
        $this->manager->selectUser($origin->getUserEmail());

        // make sure that the entity builder is empty
        $this->emailEntityBuilder->clear();

        // iterate through all folders and do a synchronization of emails for each one
        $folders = $this->syncFolders($origin);
        foreach ($folders as $folderInfo) {
            $folder = $folderInfo->ewsFolder->getFolder();

            // check if the current folder need to be synchronized
            if (!$folderInfo->needSynchronization) {
                $this->logger->notice(
                    sprintf('Skip "%s" folder, because it is up-to-date.', $folder->getFullName())
                );
                $lastSynchronizedAt = $syncStartTime;
            } else {
                // set current folder
                $this->manager->selectFolder(
                    $folder->getType() === FolderType::OTHER ? $folderInfo->ewsFolder->getEwsId() : $folder->getType()
                );

                // register the current folder in the entity builder
                $this->emailEntityBuilder->setFolder($folder);

                // build a search query
                $sqb = $this->manager->getSearchQueryBuilder();
                if ($origin->getSynchronizedAt() && $folder->getSynchronizedAt()) {
                    if ($folder->getType() === FolderType::SENT) {
                        $sqb->sent($folder->getSynchronizedAt());
                    } else {
                        $sqb->received($folder->getSynchronizedAt());
                    }
                }

                // sync emails using this search query
                $lastSynchronizedAt = $this->syncEmails($folderInfo, $sqb->get());
            }

            // update synchronization date for the current folder
            $folder->setSynchronizedAt($lastSynchronizedAt > $syncStartTime ? $lastSynchronizedAt : $syncStartTime);
            $this->em->flush($folder);
        }
    }

    /**
     * Performs synchronization of folders
     *
     * @param EmailOrigin $origin
     *
     * @return FolderInfo[] The list of folders for which emails need to be synchronized
     */
    protected function syncFolders(EmailOrigin $origin)
    {
        $this->logger->notice('Loading existing folders ...');

        /** @var EwsEmailFolderRepository $repo */
        $repo       = $this->em->getRepository('OroProEwsBundle:EwsEmailFolder');
        $ewsFolders = $repo->getFoldersByOrigin($origin);

        $folders = [];
        foreach ($ewsFolders as $ewsFolder) {
            $needSynchronization = $origin->getSynchronizedAt() && $ewsFolder->getFolder()->getSynchronizedAt()
                ? $ewsFolder->getFolder()->getSynchronizedAt() < $origin->getSynchronizedAt()
                : true;

            $folders[$ewsFolder->getEwsId()] = new FolderInfo($ewsFolder, $needSynchronization);
        }

        $this->logger->notice(sprintf('Loaded %d folder(s).', count($folders)));

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
        $this->logger->notice('Retrieving folders from an email server ...');
        $retrievedFolderCount = $this->ensureDistinguishedFolderInitialized(
            $folders,
            $origin,
            FolderType::SENT
        );
        $retrievedFolderCount += $this->ensureDistinguishedFolderInitialized(
            $folders,
            $origin,
            FolderType::INBOX
        );
        $this->logger->notice(sprintf('Retrieved %d folder(s).', $retrievedFolderCount));

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
                    $folderType === FolderType::SENT ? $folderType : FolderType::OTHER
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
                $this->logger->notice(
                    sprintf(
                        'Change folder full name from "%s" to "%s" for "%s" folder.',
                        $folder->getFullName(),
                        $fullName,
                        $folder->getFullName()
                    )
                );
                $folder->setFullName($fullName);
            }
            if ($folder->getName() !== $localName) {
                $this->logger->notice(
                    sprintf(
                        'Change folder name from "%s" to "%s" for "%s" folder.',
                        $folder->getName(),
                        $localName,
                        $folder->getFullName()
                    )
                );
                $folder->setName($localName);
            }
            if ($folder->getType() !== $type) {
                $this->logger->notice(
                    sprintf(
                        'Change folder type from "%s" to "%s" for "%s" folder.',
                        $folder->getType(),
                        $type,
                        $folder->getFullName()
                    )
                );
                $folder->setType($type);
            }
            if ($folderInfo->ewsFolder->getEwsChangeKey() !== $id->ChangeKey) {
                $this->logger->notice(
                    sprintf(
                        'Change folder EWS ChangeKey from "%s" to "%s" for "%s" folder.',
                        $folderInfo->ewsFolder->getEwsChangeKey(),
                        $id->ChangeKey,
                        $folder->getFullName()
                    )
                );
                $folderInfo->ewsFolder->setEwsChangeKey($id->ChangeKey);
            }
            $folderInfo->needSynchronization = true;
        } else {
            $this->logger->notice(sprintf('Persisting "%s" folder ...', $fullName));

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

            $folderInfo       = new FolderInfo($ewsFolder, true);
            $folders[$id->Id] = $folderInfo;

            $this->logger->notice(sprintf('The "%s" folder was persisted.', $fullName));
        }

        return $folderInfo;
    }

    /**
     * Performs synchronization of emails retrieved by the given search query in the given folder
     *
     * @param FolderInfo  $folderInfo
     * @param SearchQuery $searchQuery
     *
     * @return \DateTime The max sent date
     */
    protected function syncEmails(FolderInfo $folderInfo, SearchQuery $searchQuery)
    {
        $folder             = $folderInfo->ewsFolder->getFolder();
        $folderType         = $folderInfo->folderType;
        $lastSynchronizedAt = $folder->getSynchronizedAt();

        $this->logger->notice(sprintf('Loading emails from "%s" folder ...', $folder->getFullName()));
        $this->logger->notice(sprintf('Query: "%s".', $searchQuery->convertToString()));

        $emails = new EwsEmailIterator($this->manager, $searchQuery);
        $emails->setBatchSize(self::READ_BATCH_SIZE);
        $emails->setBatchCallback(
            function ($batch) {
                $this->registerEmailsInKnownEmailAddressChecker($batch);
            }
        );

        $count     = 0;
        $processed = 0;
        $batch     = [];
        /** @var Email $email */
        foreach ($emails as $email) {
            $processed++;
            if ($processed % self::READ_HINT_COUNT === 0) {
                $this->logger->notice(sprintf('Processed %d emails ...', $processed));
            }

            if (!$this->isApplicableEmail(
                $email,
                $folderType,
                $this->currentUser,
                $this->currentOrganization
            )) {
                continue;
            }

            if ($email->getSentAt() > $lastSynchronizedAt) {
                $lastSynchronizedAt = $email->getSentAt();
            }

            $count++;
            $batch[] = $email;
            if ($count === self::DB_BATCH_SIZE) {
                $this->saveEmails(
                    $batch,
                    $folderInfo
                );
                $count = 0;
                $batch = [];
            }
        }
        if ($count > 0) {
            $this->saveEmails(
                $batch,
                $folderInfo
            );
        }

        return $lastSynchronizedAt;
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

        $folder = $folderInfo->ewsFolder->getFolder();
        $existingEwsIds  = $this->getExistingEwsIds($folder, $emails);

        $messageIds        = $this->getNewMessageIds($emails, $existingEwsIds);
        $existingEwsEmails = $this->getExistingEwsEmails($folder->getOrigin(), $messageIds);

        $existingEmailUsers = $this->getExistingEmailUsers($folder, $messageIds);

        /** @var EwsEmail[] $newEwsEmails */
        $newEwsEmails = [];

        foreach ($emails as $email) {
            if (in_array($email->getId()->getId(), $existingEwsIds)) {
                $this->logger->info(
                    sprintf(
                        'Skip "%s" (EWS ID: %s) email, because it is already synchronised.',
                        $email->getSubject(),
                        $email->getId()->getId()
                    )
                );
                continue;
            }

            /** @var EwsEmail[] $relatedExistingEwsEmails */
            $relatedExistingEwsEmails = array_filter(
                $existingEwsEmails,
                function (EwsEmail $ewsEmail) use ($email) {
                    return $ewsEmail->getEmail()->getMessageId() === $email->getMessageId();
                }
            );

            $existingEwsEmail = $this->findExistingEwsEmail(
                $relatedExistingEwsEmails,
                $folder->getType()
            );
            if ($existingEwsEmail) {
                $this->moveEmailToOtherFolder($existingEwsEmail, $folderInfo->ewsFolder, $email->getId());
            } else {
                try {
                    if (!isset($existingEmailUsers[$email->getMessageId()])) {
                        $emailUser = $this->addEmailUser(
                            $email,
                            $folder,
                            $email->isSeen(),
                            $this->currentUser,
                            $this->currentOrganization
                        );
                    } else {
                        $emailUser = $existingEmailUsers[$email->getMessageId()];
                        $emailUser->addFolder($folder);
                    }

                    $ewsEmail       = $this->createEwsEmail(
                        $email->getId(),
                        $emailUser,
                        $folderInfo->ewsFolder
                    );
                    $newEwsEmails[] = $ewsEmail;
                    $this->em->persist($ewsEmail);
                    $this->logger->notice(
                        sprintf(
                            'The "%s" (EWS ID: %s) email was persisted.',
                            $email->getSubject(),
                            $email->getId()->getId()
                        )
                    );
                } catch (\Exception $e) {
                    $this->logger->warning(
                        sprintf(
                            'Failed to persist "%s" (EWS ID: %s) email. Error: %s',
                            $email->getSubject(),
                            $email->getId()->getId(),
                            $e->getMessage()
                        )
                    );
                }
            }
        }

        $this->emailEntityBuilder->getBatch()->persist($this->em);

        // update references if needed
        $changes = $this->emailEntityBuilder->getBatch()->getChanges();
        foreach ($newEwsEmails as $ewsEmail) {
            foreach ($changes as $change) {
                if ($change['old'] instanceof EmailEntity && $ewsEmail->getEmail() === $change['old']) {
                    $ewsEmail->setEmail($change['new']);
                }
            }
        }

        $this->em->flush();
    }

    /**
     * Tries to find EWS email in the given list of related EWS emails
     * This method returns EwsEmail object only if exactly one email is found
     * and this email is located in the comparable folder {@see isComparableFolders()}
     *
     * @param EwsEmail[] $ewsEmails
     * @param string     $folderType
     *
     * @return EwsEmail|null
     */
    protected function findExistingEwsEmail(array $ewsEmails, $folderType)
    {
        if (empty($ewsEmails)) {
            return null;
        }
        if (count($ewsEmails) === 1) {
            /** @var EwsEmail $ewsEmail */
            $ewsEmail = reset($ewsEmails);
            if (!$this->isComparableFolders($folderType, $ewsEmail->getEwsFolder()->getFolder()->getType())) {
                return null;
            }

            return $ewsEmail;
        }

        /** @var EwsEmail[] $filteredEwsEmails */
        $filteredEwsEmails = array_filter(
            $ewsEmails,
            function (EwsEmail $ewsEmail) use ($folderType) {
                return $this->isComparableFolders($folderType, $ewsEmail->getEwsFolder()->getFolder()->getType());
            }
        );

        return count($filteredEwsEmails) === 1
            ? reset($filteredEwsEmails)
            : null;
    }

    /**
     * Moves an email to another folder
     *
     * @param EwsEmail       $ewsEmail
     * @param EwsEmailFolder $newEwsFolder
     * @param ItemId         $newEwsEmailId
     */
    protected function moveEmailToOtherFolder(EwsEmail $ewsEmail, EwsEmailFolder $newEwsFolder, ItemId $newEwsEmailId)
    {
        $this->logger->notice(
            sprintf(
                'Move "%s" (EWS ID: %s) email from "%s" to "%s". New EWS ID: %s.',
                $ewsEmail->getEmail()->getSubject(),
                $ewsEmail->getEwsId(),
                $ewsEmail->getEwsFolder()->getFolder()->getFullName(),
                $newEwsFolder->getFolder()->getFullName(),
                $newEwsEmailId->getId()
            )
        );

        $emailUser = $ewsEmail->getEmail()->getEmailUserByFolder($ewsEmail->getEwsFolder()->getFolder());
        if ($emailUser && !$emailUser->getFolders()->contains($newEwsFolder->getFolder())) {
            $emailUser->addFolder($newEwsFolder->getFolder());
        }
        $ewsEmail->setEwsFolder($newEwsFolder);
        $ewsEmail->setEwsId($newEwsEmailId->getId());
        $ewsEmail->setEwsChangeKey($newEwsEmailId->getChangeKey());
    }

    /**
     * Gets the list of EWS ids of emails already exist in a database
     *
     * @param EmailFolder $folder
     * @param Email[]     $emails
     *
     * @return string[] array if EWS ids
     */
    protected function getExistingEwsIds(EmailFolder $folder, array $emails)
    {
        if (empty($emails)) {
            return [];
        }

        $ewsIds = array_map(
            function ($el) {
                /** @var Email $el */
                return $el->getId()->getId();
            },
            $emails
        );

        /** @var EwsEmailRepository $repo */
        $repo = $this->em->getRepository('OroProEwsBundle:EwsEmail');

        return $repo->getExistingEwsIds($folder, $ewsIds);
    }

    /**
     * Gets the list of EWS emails by Message-ID
     *
     * @param EmailOrigin $origin
     * @param string[]    $messageIds
     *
     * @return EwsEmail[]
     */
    protected function getExistingEwsEmails(EmailOrigin $origin, array $messageIds)
    {
        if (empty($messageIds)) {
            return [];
        }

        /** @var EwsEmailRepository $repo */
        $repo = $this->em->getRepository('OroProEwsBundle:EwsEmail');

        return $repo->getEmailsByMessageIds($origin, $messageIds);
    }

    /**
     * Gets the list of Message-IDs for emails with the given EWS ids
     *
     * @param Email[] $emails
     * @param array   $existingEwsIds
     *
     * @return string[]
     */
    protected function getNewMessageIds(array $emails, array $existingEwsIds)
    {
        $result = [];
        foreach ($emails as $email) {
            if (!in_array($email->getId()->getId(), $existingEwsIds)) {
                $result[] = $email->getMessageId();
            }

        }

        return $result;
    }

    /**
     * Creates new EwsEmail object
     *
     * @param ItemId         $ewsEmailId
     * @param EmailUser      $emailUser
     * @param EwsEmailFolder $ewsFolder
     *
     * @return EwsEmail
     */
    protected function createEwsEmail(ItemId $ewsEmailId, EmailUser $emailUser, EwsEmailFolder $ewsFolder)
    {
        $ewsEmail = new EwsEmail();
        $ewsEmail
            ->setEwsId($ewsEmailId->getId())
            ->setEwsChangeKey($ewsEmailId->getChangeKey())
            ->setEmail($emailUser->getEmail())
            ->setEwsFolder($ewsFolder);

        return $ewsEmail;
    }
}
