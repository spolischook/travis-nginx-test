<?php

namespace OroCRMPro\Bundle\LDAPBundle\Tests\Functional\EventListener;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\ImportExportBundle\Context\Context;
use Oro\Bundle\ImportExportBundle\Event\StrategyEvent;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;

class ImportValidationSubscriberTest extends WebTestCase
{
    protected $userImportStrategy;

    public function setUp()
    {
        $this->initClient();
        $this->userImportStrategy = $this->getMockBuilder('OroCRMPro\Bundle\LDAPBundle\ImportExport\UserImportStrategy')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testImport2UsersWith2SameUniqueFields()
    {
        $adam = new User();
        $adam
            ->setEmail('adam@example.com')
            ->setUsername('adam');

        $context = new Context([]);
        $event = new StrategyEvent($this->userImportStrategy, $adam, $context);
        $this->getEventDispatcher()->dispatch(StrategyEvent::PROCESS_BEFORE, $event);

        $secondAdam = new User();
        $secondAdam
            ->setEmail('adam@example.com')
            ->setUsername('adam');

        $secondEvent = new StrategyEvent($this->userImportStrategy, $secondAdam, $context);
        $this->getEventDispatcher()->dispatch(StrategyEvent::PROCESS_BEFORE, $secondEvent);

        $this->assertEquals(1, $secondEvent->getContext()->getErrorEntriesCount());
        $this->assertEquals(
            [
                'Error in row #. Entity with following unique fields: ' .
                '"username: adam, email: adam@example.com" was already imported'
            ],
            $secondEvent->getContext()->getErrors()
        );
    }

    public function testImport2UsersWith1SameUniqueField()
    {
        $adam = new User();
        $adam
            ->setEmail('adam@example.com')
            ->setUsername('adam');

        $context = new Context([]);
        $event = new StrategyEvent($this->userImportStrategy, $adam, $context);
        $this->getEventDispatcher()->dispatch(StrategyEvent::PROCESS_BEFORE, $event);

        $secondAdam = new User();
        $secondAdam
            ->setEmail('secondAdam@example.com')
            ->setUsername('adam');

        $secondEvent = new StrategyEvent($this->userImportStrategy, $secondAdam, $context);
        $this->getEventDispatcher()->dispatch(StrategyEvent::PROCESS_BEFORE, $secondEvent);

        $this->assertEquals(1, $secondEvent->getContext()->getErrorEntriesCount());
        $this->assertEquals(
            [
                'Error in row #. Entity with following unique fields: ' .
                '"username: adam" was already imported'
            ],
            $secondEvent->getContext()->getErrors()
        );
    }

    public function testImport2UsersWithDifferentUniqueField()
    {
        $adam = new User();
        $adam
            ->setEmail('adam@example.com')
            ->setUsername('adam');

        $context = new Context([]);
        $event = new StrategyEvent($this->userImportStrategy, $adam, $context);
        $this->getEventDispatcher()->dispatch(StrategyEvent::PROCESS_BEFORE, $event);

        $eva = new User();
        $eva
            ->setEmail('eva@example.com')
            ->setUsername('eva');

        $secondEvent = new StrategyEvent($this->userImportStrategy, $eva, $context);
        $this->getEventDispatcher()->dispatch(StrategyEvent::PROCESS_BEFORE, $secondEvent);

        $this->assertEquals(0, $secondEvent->getContext()->getErrorEntriesCount());
        $this->assertEquals([], $secondEvent->getContext()->getErrors());
    }

    /**
     * @return EventDispatcherInterface
     */
    protected function getEventDispatcher()
    {
        return $this->getContainer()->get('event_dispatcher');
    }
}
