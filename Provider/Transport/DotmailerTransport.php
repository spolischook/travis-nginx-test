<?php

namespace OroCRM\Bundle\DotmailerBundle\Provider\Transport;

use Doctrine\Common\Collections\ArrayCollection;

use DotMailer\Api\DataTypes\ApiCampaignSend;
use DotMailer\Api\DataTypes\ApiContactImport;
use DotMailer\Api\DataTypes\ApiFileMedia;
use DotMailer\Api\DataTypes\ApiResubscribeResult;
use DotMailer\Api\DataTypes\ApiTransactionalDataImport;
use DotMailer\Api\DataTypes\ApiTransactionalDataImportReport;
use DotMailer\Api\DataTypes\ApiTransactionalDataList;
use DotMailer\Api\DataTypes\Int32List;
use DotMailer\Api\Resources\IResources;
use DotMailer\Api\DataTypes\ApiContactResubscription;

use Guzzle\Iterator\AppendIterator;

use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerAwareInterface;

use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\IntegrationBundle\Provider\TransportInterface;

use OroCRM\Bundle\DotmailerBundle\Exception\RequiredOptionException;
use OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator\ActivityContactIterator;
use OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator\AddressBookIterator;
use OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator\CampaignIterator;
use OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator\CampaignSummaryIterator;
use OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator\ExportFaultsReportIterator;
use OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator\UnsubscribedContactIterator;
use OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator\UnsubscribedFromAccountContactIterator;
use OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator\ContactIterator;
use OroCRM\Bundle\DotmailerBundle\Entity\AddressBookContact;

class DotmailerTransport implements TransportInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    const DEFAULT_START_SYNC_DATE = '1971';

    /**
     * @var IResources
     */
    protected $dotmailerResources;

    /**
     * @var DotmailerResourcesFactory
     */
    protected $dotMailerResFactory;

    /**
     * @param DotmailerResourcesFactory $dotMailerResFactory
     */
    public function __construct(DotmailerResourcesFactory $dotMailerResFactory)
    {
        $this->dotMailerResFactory = $dotMailerResFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function init(Transport $transportEntity)
    {
        $settings = $transportEntity->getSettingsBag();
        $username = $settings->get('username');
        if (!$username) {
            throw new RequiredOptionException('username');
        }
        $password = $settings->get('password');
        if (!$password) {
            throw new RequiredOptionException('password');
        }

        $this->dotmailerResources = $this->dotMailerResFactory->createResources($username, $password, $this->logger);
    }

    /**
     * @param array          $addressBooks
     * @param \DateTime|null $dateSince
     *
     * @return ContactIterator
     */
    public function getContacts($addressBooks, $dateSince = null)
    {
        $iterator = new AppendIterator();
        foreach ($addressBooks as $addressBook) {
            $iterator->append(
                new ContactIterator($this->dotmailerResources, $addressBook['originId'], $dateSince)
            );
        }

        return $iterator;
    }

    /**
     * @return \Iterator
     */
    public function getAddressBooks()
    {
        return new AddressBookIterator($this->dotmailerResources);
    }

    /**
     * @param array     $addressBooks
     * @param \DateTime $lastSyncDate
     *
     * @return UnsubscribedContactIterator
     */
    public function getUnsubscribedContacts(array $addressBooks, \DateTime $lastSyncDate = null)
    {
        if (!$lastSyncDate) {
            $lastSyncDate = date_create_from_format('Y', self::DEFAULT_START_SYNC_DATE, new \DateTimeZone('UTC'));
        }

        $iterator = new AppendIterator();
        foreach ($addressBooks as $addressBook) {
            $iterator->append(
                new UnsubscribedContactIterator($this->dotmailerResources, $addressBook['originId'], $lastSyncDate)
            );
        }

        return $iterator;
    }

    /**
     * @param \DateTime $lastSyncDate
     *
     * @return UnsubscribedFromAccountContactIterator
     */
    public function getUnsubscribedFromAccountsContacts(\DateTime $lastSyncDate = null)
    {
        if (!$lastSyncDate) {
            return new \EmptyIterator();
        }

        return new UnsubscribedFromAccountContactIterator($this->dotmailerResources, $lastSyncDate);
    }

    /**
     * @param array $addressBooks
     *
     * @return \Iterator
     */
    public function getCampaigns(array $addressBooks = [])
    {
        $iterator = new AppendIterator();
        foreach ($addressBooks as $addressBook) {
            $iterator->append(
                new CampaignIterator($this->dotmailerResources, $addressBook['originId'])
            );
        }

        return $iterator;
    }

    /**
     * @param array|ArrayCollection $campaignsToSynchronize
     * @param \DateTime             $lastSyncDate = null
     *
     * @return \Iterator
     */
    public function getActivityContacts(array $campaignsToSynchronize = [], \DateTime $lastSyncDate = null)
    {

        $iterator = new AppendIterator();
        foreach ($campaignsToSynchronize as $campaign) {
            $iterator->append(
                new ActivityContactIterator(
                    $this->dotmailerResources,
                    $campaign['originId'],
                    $campaign['isInit'],
                    $lastSyncDate
                )
            );
        }

        return $iterator;
    }

    /**
     * @param array $campaignsToSynchronize
     *
     * @return \Iterator
     */
    public function getCampaignSummary(array $campaignsToSynchronize = [])
    {
        return new CampaignSummaryIterator($this->dotmailerResources, $campaignsToSynchronize);
    }

    /**
     * @param AddressBookContact $abContact
     *
     * @return ApiResubscribeResult
     */
    public function resubscribeAddressBookContact(AddressBookContact $abContact)
    {
        $resubscription = [
            'UnsubscribedContact' => [
                'Email' => $abContact->getContact()->getEmail(),
            ],
            'PreferredLocale' => '',
            'ReturnUrlToUseIfChallenged' => '',
        ];
        $apiContactResubscription = new ApiContactResubscription($resubscription);

        return $this->dotmailerResources->PostAddressBookContactsResubscribe(
            $abContact->getAddressBook()->getOriginId(),
            $apiContactResubscription
        );
    }

    /**
     * @param int[] $removingItemsOriginIds
     * @param int   $addressBookOriginId
     */
    public function removeContactsFromAddressBook(array $removingItemsOriginIds, $addressBookOriginId)
    {
        $contactIdsList = new Int32List($removingItemsOriginIds);
        $this->dotmailerResources->PostAddressBookContactsDelete($addressBookOriginId, $contactIdsList);
    }

    /**
     * @param string $contactsCsv
     * @param int    $addressBookOriginId
     *
     * @return ApiContactImport
     */
    public function exportAddressBookContacts($contactsCsv, $addressBookOriginId)
    {
        $apiFileMedia = new ApiFileMedia(['FileName' => 'contacts.csv', 'Data' => $contactsCsv]);
        return $this->dotmailerResources->PostAddressBookContactsImport($addressBookOriginId, $apiFileMedia);
    }

    /**
     * @param string[] $importIds Array of GUID
     * @param int $addressBookId Address Book Id
     *
     * @return \Iterator
     */
    public function getAddressBookExportReports($importIds, $addressBookId)
    {
        $appendIterator = new AppendIterator();
        foreach ($importIds as $importId) {
            $iterator = new ExportFaultsReportIterator($this->dotmailerResources, $addressBookId, $importId);
            $appendIterator->append($iterator);
        }

        return $appendIterator;
    }

    /**
     * @param string                   $collectionName
     * @param ApiTransactionalDataList $list
     *
     * @return ApiTransactionalDataImport
     */
    public function updateContactsTransactionalData($collectionName, ApiTransactionalDataList $list)
    {
        // workaround till https://github.com/romanpitak/dotMailer-API-v2-PHP-client/issues/7 resolved
        $url = sprintf("contacts/transactional-data/import/%s", $collectionName);

        $reflObject = new \ReflectionObject($this->dotmailerResources);
        $method = $reflObject->getMethod('execute');
        $method->setAccessible(true);

        $result = $method->invoke($this->dotmailerResources, $url, 'POST', $list->toJson());

        return new ApiTransactionalDataImport($result);
        // should be just that
        //return $this->dotmailerResources
        //    ->PostContactsTransactionalDataImport($collectionName, $list);
    }

    /**
     * @param $importId
     *
     * @return ApiTransactionalDataImportReport
     */
    public function getContactDataImportReport($importId)
    {
        return $this->dotmailerResources->GetContactsTransactionalDataImportReport($importId);
    }

    /**
     * Send campaign to contacts specified by contactIds or addressBookIds or all contacts
     * if no parameters (except campaignId) defined
     *
     * @param string $campaignId
     * @param array  $contactIds
     * @param array  $addressBookIds
     *
     * @return ApiCampaignSend
     */
    public function sendCampaign($campaignId, $contactIds = [], array $addressBookIds = [])
    {
        $apiCampaignSend = new ApiCampaignSend(
            [
                'CampaignId'     => $campaignId,
                'ContactIds'     => $contactIds,
                'AddressBookIds' => $addressBookIds,
            ]
        );

        return $this->dotmailerResources->PostCampaignsSend($apiCampaignSend);
    }

    /**
     * @return IResources
     */
    public function getDotmailerResource()
    {
        return $this->dotmailerResources;
    }

    /**
     * @param string $importId
     *
     * @return ApiContactImport
     */
    public function getImportStatus($importId)
    {
        return $this->dotmailerResources->GetContactsImportByImportId($importId);
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return 'orocrm.dotmailer.integration_transport.label';
    }

    /**
     * {@inheritdoc}
     */
    public function getSettingsFormType()
    {
        return 'orocrm_dotmailer_transport_setting_type';
    }

    /**
     * {@inheritdoc}
     */
    public function getSettingsEntityFQCN()
    {
        return 'OroCRM\\Bundle\\DotmailerBundle\\Entity\\DotmailerTransport';
    }
}
