<?php

namespace OroPro\Bundle\EwsBundle\Provider;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;

use Oro\Bundle\EmailBundle\Builder\EmailBodyBuilder;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Provider\EmailBodyLoaderInterface;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroPro\Bundle\EwsBundle\Connector\EwsConnector;
use OroPro\Bundle\EwsBundle\Entity\EwsEmail;
use OroPro\Bundle\EwsBundle\Entity\EwsEmailOrigin;
use OroPro\Bundle\EwsBundle\Ews\EwsType as EwsType;
use OroPro\Bundle\EwsBundle\Manager\DTO\ItemId;
use OroPro\Bundle\EwsBundle\Manager\EwsEmailManager;

class EwsEmailBodyLoader implements EmailBodyLoaderInterface
{
    const ORO_EMAIL_ATTACHMENT_SYNC_ENABLE = 'oro_email.attachment_sync_enable';
    const ORO_EMAIL_ATTACHMENT_SYNC_MAX_SIZE = 'oro_email.attachment_sync_max_size';

    /** @var EwsConnector */
    protected $connector;

    /** @var ConfigManager */
    protected $configManager;

    /**
     * @param EwsConnector $connector
     * @param ConfigManager $configManager
     */
    public function __construct(EwsConnector $connector, ConfigManager $configManager)
    {
        $this->connector = $connector;
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(EmailOrigin $origin)
    {
        return $origin instanceof EwsEmailOrigin;
    }

    /**
     * {@inheritdoc}
     */
    public function loadEmailBody(EmailFolder $folder, Email $email, EntityManager $em)
    {
        $manager = $this->getManager($folder, $email);

        // find ews email by email and ews folder
        $repo    = $em->getRepository('OroProEwsBundle:EwsEmail');
        $query   = $repo->createQueryBuilder('e')
            ->select('e.ewsId AS ewsId, e.ewsChangeKey AS ewsChangeKey')
            ->innerJoin('e.ewsFolder', 'ef')
            ->where('e.email = ?1 AND ef.folder = ?2')
            ->setParameter(1, $email)
            ->setParameter(2, $folder)
            ->getQuery();
        $query->setHydrationMode(Query::HYDRATE_ARRAY);

        try {
            $ewsEmail    = $query->getSingleResult();
            $id          = new ItemId($ewsEmail['ewsId'], $ewsEmail['ewsChangeKey']);
            $loadedEmail = $manager->findEmail($id);
        } catch (\Exception $e) {
            throw new \RuntimeException(
                sprintf('Cannot find a body for "%s" email.', $email->getSubject()),
                $e->getCode(),
                $e
            );
        }

        $builder = new EmailBodyBuilder($this->configManager);

        $builder->setEmailBody(
            $loadedEmail->getBody()->getContent(),
            $loadedEmail->getBody()->getBodyIsText()
        );

        $attachmentIds = $loadedEmail->getAttachmentIds();
        if (!empty($attachmentIds)) {
            $emailFileAttachments = $manager->getEmailAttachments($attachmentIds);
            foreach ($emailFileAttachments as $fileAttachment) {
                $builder->addEmailAttachment(
                    $fileAttachment->getFileName(),
                    $fileAttachment->getContent(),
                    $fileAttachment->getContentType(),
                    $fileAttachment->getContentTransferEncoding(),
                    null,
                    $fileAttachment->getFileSize()
                );
            }
        }

        return $builder->getEmailBody();
    }

    /**
     * @param EmailFolder $folder
     * @param Email       $email
     *
     * @throws \RuntimeException
     * @return EwsEmailManager
     */
    protected function getManager(EmailFolder $folder, Email $email)
    {
        $manager = new EwsEmailManager($this->connector);
        $manager->setAttachmentSyncEnabled(
            $this->configManager->get(self::ORO_EMAIL_ATTACHMENT_SYNC_ENABLE)
        );
        $manager->setAttachmentMaxSize(
            $this->configManager->get(self::ORO_EMAIL_ATTACHMENT_SYNC_MAX_SIZE)
        );

        $origin  = $folder->getOrigin();
        if ($origin instanceof EwsEmailOrigin) {
            $manager->selectUser($origin->getUserEmail());
        } else {
            throw new \RuntimeException(
                sprintf('The origin for "%s" email must be instance of EwsEmailOrigin.', $email->getSubject())
            );
        }

        return $manager;
    }
}
