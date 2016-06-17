<?php

namespace OroPro\Bundle\OrganizationBundle\Tests\Unit\Provider;

use Oro\Bundle\EmailBundle\Model\Recipient;
use Oro\Bundle\EmailBundle\Model\RecipientEntity;
use Oro\Bundle\UserBundle\Entity\User;

use OroPro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\Organization;
use OroPro\Bundle\OrganizationBundle\Provider\EmailRecipientsHelper;

class EmailRecipientsHelperTest extends \PHPUnit_Framework_TestCase
{
    protected $aclHelper;
    protected $dqlNameFormatter;
    protected $nameFormatter;
    protected $configManager;
    protected $translator;
    protected $emailOwnerProvider;
    protected $registry;
    protected $addressHelper;
    protected $securityFacade;

    protected $emailRecipientsHelper;

    public function setUp()
    {
        $this->aclHelper = $this->getMockBuilder('Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->dqlNameFormatter = $this->getMockBuilder('Oro\Bundle\LocaleBundle\DQL\DQLNameFormatter')
            ->disableOriginalConstructor()
            ->getMock();

        $this->nameFormatter = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Formatter\NameFormatter')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->translator = $this->getMockBuilder('Symfony\Component\Translation\TranslatorInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->emailOwnerProvider = $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->registry = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->addressHelper = $this->getMockBuilder('Oro\Bundle\EmailBundle\Tools\EmailAddressHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->indexer = $this->getMockBuilder('Oro\Bundle\SearchBundle\Engine\Indexer')
            ->disableOriginalConstructor()
            ->getMock();

        $this->emailRecipientsHelper = new EmailRecipientsHelper(
            $this->aclHelper,
            $this->dqlNameFormatter,
            $this->nameFormatter,
            $this->configManager,
            $this->translator,
            $this->emailOwnerProvider,
            $this->registry,
            $this->addressHelper,
            $this->indexer
        );
        $this->emailRecipientsHelper->setSecurityFacade($this->securityFacade);
    }

    /**
     * @dataProvider createRecipientDataProvider
     */
    public function testCreateRecipientData(Recipient $recipient, Organization $organization, array $expectedResult)
    {
        $this->securityFacade->expects($this->once())
            ->method('getOrganization')
            ->will($this->returnValue($organization));

        $data = $this->emailRecipientsHelper->createRecipientData($recipient);
        $this->assertEquals($expectedResult, $data);
    }

    public function createRecipientDataProvider()
    {
        $recipient = new Recipient(
            'recipient@example.com',
            'Recipient <recipient@example.com>',
            new RecipientEntity(
                'class',
                'id',
                'label',
                'org'
            )
        );

        $globalOrganization = new Organization();
        $globalOrganization->setIsGlobal(true);

        return [
            [
                $recipient,
                new Organization(),
                [
                    'id' => 'Recipient <recipient@example.com>',
                    'text' => 'Recipient <recipient@example.com>',
                    'data' => json_encode([
                        'key' => 'Recipient <recipient@example.com>',
                        'contextText' => 'label',
                        'contextValue' => [
                            'entityClass' => 'class',
                            'entityId' => 'id',
                        ],
                        'organization' => 'org',
                    ]),
                ],
            ],
            [
                $recipient,
                $globalOrganization,
                [
                    'id' => 'Recipient <recipient@example.com>',
                    'text' => 'Recipient <recipient@example.com> (org)',
                    'data' => json_encode([
                        'key' => 'Recipient <recipient@example.com>',
                        'contextText' => 'label',
                        'contextValue' => [
                            'entityClass' => 'class',
                            'entityId' => 'id',
                        ],
                        'organization' => 'org',
                    ]),
                ],
            ],
        ];
    }

    /**
     * @dataProvider isObjectAllowedForOrganizationDataProvider
     */
    public function testIsObjectAllowedForOrganization(
        $object,
        Organization $organization = null,
        $expectedResult = null
    ) {
        $this->securityFacade->expects($this->any())
            ->method('getOrganization')
            ->will($this->returnValue($organization));

        $this->assertEquals(
            $expectedResult,
            $this->emailRecipientsHelper->isObjectAllowedForOrganization($object)
        );
    }

    public function isObjectAllowedForOrganizationDataProvider()
    {
        $organization = new Organization();

        $object = new User();
        $object->setOrganization($organization);

        return [
            [
                new User(),
                null,
                true,
            ],
            [
                new User(),
                null,
                true,
            ],
            [
                $object,
                $organization,
                true
            ],
            [
                $object,
                null,
                false,
            ],
        ];
    }
}
