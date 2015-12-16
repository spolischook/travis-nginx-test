<?php

namespace OroCRM\Bundle\DotmailerBundle\Entity\Repository;

use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use OroCRM\Bundle\DotmailerBundle\Entity\AddressBook;
use OroCRM\Bundle\DotmailerBundle\Entity\AddressBookContactsExport;

class AddressBookContactsExportRepository extends EntityRepository
{
    /**
     * @param Channel $channel
     *
     * @return bool
     */
    public function isExportFinished(Channel $channel)
    {
        $qb = $this->createQueryBuilder('addressBookContactExport');
        $qb->select('addressBookContactExport.id')
            ->innerJoin('addressBookContactExport.status', 'status')
            ->where('addressBookContactExport.channel =:channel')
            ->andWhere('status.id = :status')
            ->setMaxResults(1)
            ->setParameters(
                [
                    'channel' => $channel,
                    'status' => AddressBookContactsExport::STATUS_NOT_FINISHED
                ]
            );

        $result = $qb->getQuery()->getOneOrNullResult();
        return $result === null ? true : false;
    }

    /**
     * @param Channel $channel
     *
     * @return AddressBookContactsExport[]
     */
    public function getNotFinishedExports(Channel $channel)
    {
        $qb = $this->createQueryBuilder('addressBookContactExport');
        $qb->innerJoin('addressBookContactExport.status', 'status')
            ->where('addressBookContactExport.channel =:channel')
            ->andWhere('status.id =:status');

        return $qb->getQuery()
            ->execute(
                [
                    'channel' => $channel,
                    'status' => AddressBookContactsExport::STATUS_NOT_FINISHED
                ]
            );
    }

    /**
     * Get list of exports of address book sorted by date in descending order.
     *
     * @param AddressBook $addressBook
     *
     * @return AddressBookContactsExport[]
     */
    public function getExportsByAddressBook(AddressBook $addressBook)
    {
        $qb = $this->createQueryBuilder('addressBookContactExport');
        $qb->innerJoin('addressBookContactExport.status', 'status')
            ->where('addressBookContactExport.addressBook =:addressBook')
            ->orderBy('addressBookContactExport.updatedAt', 'desc');

        return $qb->getQuery()->execute(['addressBook' => $addressBook]);
    }

    /**
     * Get Dotmailer status object (enum "dm_import_status").
     *
     * @param string $statusCode
     * @return AbstractEnumValue
     * @throws EntityNotFoundException
     */
    public function getStatus($statusCode)
    {
        $statusClassName = ExtendHelper::buildEnumValueClassName('dm_import_status');
        $statusRepository = $this->getEntityManager()->getRepository($statusClassName);

        /** @var AbstractEnumValue|null $result */
        $result = $statusRepository->find($statusCode);

        if (!$result) {
            throw EntityNotFoundException::fromClassNameAndIdentifier(
                $statusClassName,
                $statusCode
            );
        }

        return $result;
    }

    /**
     * @return AbstractEnumValue
     */
    public function getFinishedStatus()
    {
        return $this->getStatus(AddressBookContactsExport::STATUS_FINISH);
    }

    /**
     * @return AbstractEnumValue
     */
    public function getNotFinishedStatus()
    {
        return $this->getStatus(AddressBookContactsExport::STATUS_NOT_FINISHED);
    }

    /**
     * @return AbstractEnumValue
     * @return bool
     */
    public function isFinishedStatus(AbstractEnumValue $status)
    {
        return $status->getId() == AddressBookContactsExport::STATUS_FINISH;
    }

    /**
     * @return AbstractEnumValue
     * @return bool
     */
    public function isNotFinishedStatus(AbstractEnumValue $status)
    {
        return $status->getId() == AddressBookContactsExport::STATUS_NOT_FINISHED;
    }

    /**
     * @return bool
     */
    public function isErrorStatus(AbstractEnumValue $status)
    {
        return $status->getId() !== AddressBookContactsExport::STATUS_FINISH &&
            $status->getId() !== AddressBookContactsExport::STATUS_NOT_FINISHED;
    }
}
