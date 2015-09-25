<?php

namespace OroCRMPro\Bundle\DemoDataBundle\EventListener;

use Symfony\Component\Console\Event\ConsoleEvent;

use Oro\Bundle\MigrationBundle\Command\LoadDataFixturesCommand;
use Oro\Bundle\SearchBundle\EventListener\DemoDataMigrationListener as BaseDataMigrationListener;

class DemoDataMigrationListener extends BaseDataMigrationListener
{
    const DEMO_FIXTURES_TYPE_MULTI = 'Multi';
    const DEMO_FIXTURES_TYPE_B2C   = 'B2C';
    const DEMO_FIXTURES_TYPE_B2B   = 'B2B';

    /**
     * {@inheritdoc}
     */
    protected function isProcessingRequired(ConsoleEvent $event)
    {
        $processingRequiredDataFixtures = [
            self::DEMO_FIXTURES_TYPE_MULTI,
            self::DEMO_FIXTURES_TYPE_B2C,
            self::DEMO_FIXTURES_TYPE_B2B,
        ];

        return parent::isProcessingRequired($event)
        || ($event->getCommand() instanceof LoadDataFixturesCommand
            && $event->getInput()->hasOption('fixtures-type')
            && in_array($event->getInput()->getOption('fixtures-type'), $processingRequiredDataFixtures, true));
    }
}
