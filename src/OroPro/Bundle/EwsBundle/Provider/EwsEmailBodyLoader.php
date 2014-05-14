<?php

namespace OroPro\Bundle\EwsBundle\Provider;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;

use Oro\Bundle\EmailBundle\Builder\EmailBodyBuilder;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Provider\EmailBodyLoaderInterface;

use OroPro\Bundle\EwsBundle\Connector\EwsConnector;
use OroPro\Bundle\EwsBundle\Entity\EwsEmail;
use OroPro\Bundle\EwsBundle\Entity\EwsEmailOrigin;
use OroPro\Bundle\EwsBundle\Ews\EwsType as EwsType;
use OroPro\Bundle\EwsBundle\Manager\DTO\ItemId;
use OroPro\Bundle\EwsBundle\Manager\EwsEmailManager;

class EwsEmailBodyLoader implements EmailBodyLoaderInterface
{
    /** @var EwsConnector */
    protected $connector;

    /**
     * @param EwsConnector $connector
     */
    public function __construct(EwsConnector $connector)
    {
        $this->connector = $connector;
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

        // find ews folder based on folder
        $ewsFolder = $em->getRepository('OroProEwsBundle:EwsEmailFolder')
            ->findOneBy(['folder' => $folder]);

        // find ews email by email and ews folder
        $repo    = $em->getRepository('OroProEwsBundle:EwsEmail');
        $query   = $repo->createQueryBuilder('e')
            ->select('e.ewsId AS ewsId, e.ewsChangeKey AS ewsChangeKey')
            ->where('e.email = ?1 AND e.ewsFolder = ?2')
            ->setParameter(1, $email)
            ->setParameter(2, $ewsFolder)
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

        $builder = new EmailBodyBuilder();

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
                    $fileAttachment->getContentTransferEncoding()
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
