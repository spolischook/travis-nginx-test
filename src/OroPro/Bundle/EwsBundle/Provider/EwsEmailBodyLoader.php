<?php

namespace OroPro\Bundle\EwsBundle\Provider;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\EmailBundle\Builder\EmailBodyBuilder;
use Oro\Bundle\EmailBundle\Entity\Email;
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
    public function loadEmailBody(Email $email, EntityManager $em)
    {
        $manager = new EwsEmailManager($this->connector);
        $manager->selectUser($email);

        $repo  = $em->getRepository('OroProEwsBundle:EwsEmail');
        $query = $repo->createQueryBuilder('e')
            ->select('e.ewsId AS ewsId, e.ewsChangeKey AS ewsChangeKey')
            ->where('e.email = ?1')
            ->setParameter(1, $email)
            ->getQuery();

        /** @var EwsEmail $ewsEmail */
        $ewsEmail = $query->getSingleResult();

        /** @var ItemId $id */
        $id = new ItemId(
            $ewsEmail['ewsId'],
            $ewsEmail['ewsChangeKey']
        );

        /** @var \OroPro\Bundle\EwsBundle\Manager\DTO\Email $loadedEmail */
        $loadedEmail = $manager->findEmail($id);

        $builder = new EmailBodyBuilder();

        $emailBody = $manager->getEmailBody($id);
        $builder->setEmailBody(
            $emailBody->getContent(),
            $emailBody->getBodyIsText()
        );

        $emailFileAttachements = $manager->getEmailAttachments($loadedEmail->getAttachmentIds());

        foreach ($emailFileAttachements as $fileAttachment) {
            $builder->addEmailAttachment(
                $fileAttachment->getFileName(),
                $fileAttachment->getContent(),
                $fileAttachment->getContentType(),
                $fileAttachment->getContentTransferEncoding()
            );
        }

        return $builder->getEmailBody();
    }
}
